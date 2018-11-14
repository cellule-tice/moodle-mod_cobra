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
 * Prints an instance of mod_cobra.
 *
 * @package     mod_cobra
 * @copyright   2016 onwards - Cellule TICE - University of Namur
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/locallib.php');
require_once(__DIR__ . '/classes/output/cobratext.php');

// Course_module ID.
$id = required_param('id', PARAM_INT);
list($course, $cm) = get_course_and_cm_from_cmid($id, 'cobra');
$cobra = $DB->get_record('cobra', array('id' => $cm->instance), '*', MUST_EXIST);

$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/cobra:view', $context);

// Add event management here.
$event = \mod_cobra\event\course_module_viewed::create(array(
    'objectid' => $cobra->id,
    'context' => $context,
));
$event->add_record_snapshot('course', $PAGE->course);
$event->add_record_snapshot($cm->modname, $cobra);
$event->trigger();

// Completion.
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

// Keep userid and cmid for ajax calls.
global $USER;
$cobra->user = $USER->id;
$cobra->cmid = $id;
$cobra->encodeclic = user_has_role_assignment($USER->id, 5, $context->id);

$PAGE->set_url('/mod/cobra/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($cobra->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->force_settings_menu();
$PAGE->add_body_class('noblocks');
$PAGE->set_context($context);

// Add css and js requires.
//$PAGE->requires->css('/mod/cobra/css/cobra.css');

$PAGE->requires->js_call_amd('mod_cobra/cobra', 'initData', array(json_encode($cobra)));
$PAGE->requires->js_call_amd('mod_cobra/cobra', 'entryOnClick');
$PAGE->requires->js_call_amd('mod_cobra/cobra', 'concordanceOnClick');
$PAGE->requires->js_call_amd('mod_cobra/cobra', 'textGlossaryActions');

$output = $PAGE->get_renderer('mod_cobra');

$cobratext = new cobratext($cobra);
$content = $output->render($cobratext);

echo $output->header();
echo $output->heading(get_string('textreading', 'cobra'));
echo $content;

$PAGE->requires->js_call_amd('mod_cobra/cobra', 'initUi');
echo $output->footer();