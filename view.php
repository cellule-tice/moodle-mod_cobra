<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Cobra text viewer.
 *
 * @package    mod_cobra
 * @author     Jean-Roch Meurisse
 * @author     Laurence Dumortier
 * @copyright  2016 onwards - Cellule TICE - Universite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/locallib.php');
require_once(__DIR__ . '/lib/cobraremoteservice.php');
require_once(__DIR__ . '/lib/cobracollectionwrapper.php');
require_once(__DIR__ . '/lib/glossarylib.php');
require_once(__DIR__ . '/classes/output/cobratext.php');

$id = required_param('id', PARAM_INT);
list($course, $cm) = get_course_and_cm_from_cmid($id, 'cobra');
$cobra = $DB->get_record('cobra', array('id' => $cm->instance), '*', MUST_EXIST);

$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/cobra:view', $context);

// Backwards compatibility with cobrapi.
$cobra->ccorder = $cobra->corpusorder;

// Keep user id and cmid for ajax calls.
global $USER;
$cobra->user = $USER->id;
$cobra->cmid = $id;

// Unset intro for ajax params.
$cobra->intro = null;

$cobra->encodeclic = 1;
if (has_capability('mod/cobra:edit', $context) && false) {
    $cobra->encodeclic = 0;
}

// Add event management here.
$event = \mod_cobra\event\course_module_viewed::create(array(
    'objectid' => $PAGE->cm->instance,
    'context' => $PAGE->context,
));
$event->add_record_snapshot('course', $PAGE->course);
$event->trigger();

// Completion.
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$PAGE->set_url('/mod/cobra/view.php', array('id' => $cm->id));

$PAGE->set_title(format_string($cobra->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->force_settings_menu();
$PAGE->add_body_class('noblocks');

$PAGE->requires->css('/mod/cobra/css/cobra.css');

$PAGE->requires->jquery();

$PAGE->requires->js_call_amd('mod_cobra/cobra', 'init', array(json_encode($cobra)));
$PAGE->requires->js_call_amd('mod_cobra/cobra', 'entry_on_click');
$PAGE->requires->js_call_amd('mod_cobra/cobra', 'concordance_on_click');

if ((int)$cobra->userglossary) {
    $PAGE->requires->js_call_amd('mod_cobra/cobra', 'text_glossary_actions');
}

$content = '';
// Load content to display. Still needed?
$text = new cobra_text_wrapper($cm);
$text->set_text_id($cobra->text);
$text->load_remote_data();

$content .= html_writer::div('', 'hidden', array('id' => 'courseLabel', 'name' => $course->id));
$content .= html_writer::div($cobra->userglossary, 'hidden', array('id' => 'showglossary'));
$content .= html_writer::div('', 'hidden', array('id' => 'userId', 'name' => $USER->id));
$content .= html_writer::div($course->id, 'hidden', array('id' => 'courseid', 'name' => $course->id));
$content .= html_writer::div($cm->id, 'hidden', array('id' => 'cmid', 'name' => $cm->id));

$content .= '<div id="preferencesNb" class="hidden" name="' . $i . '">' . $i . '</div>';
$content .= html_writer::div($i, 'hidden', array('id' => 'prefrencesNb', 'name' => $i));

$clearfix = false;

if ($clearfix) {
    $content .= html_writer::div('', 'clearfix');
}

$output = $PAGE->get_renderer('mod_cobra');

echo $output->header();

echo $output->heading(get_string('textreading', 'cobra')/* . ' : ' . $cobra->name*/);

if (empty($cobra->ccorder)) {
    echo $output->notification(get_string('nocollectionsmessage', 'cobra'), 'info');
}

$cobratext = new cobratext($cobra);
echo $output->render($cobratext);

echo $content;

echo $output->footer();
