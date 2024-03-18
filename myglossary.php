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
 * Cobra myglossary viewer.
 *
 * @package    mod_cobra
 * @author     Jean-Roch Meurisse
 * @copyright  2016 onwards - Cellule TICE - Universite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/cobra/locallib.php');
require_once($CFG->dirroot . '/lib/dataformatlib.php');

use mod_cobra\output\myglossary;
use mod_cobra\local\student_glossary_helper;

$id = required_param('id', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_INT);
$initial = optional_param('initial', 'all', PARAM_ALPHA);
$export = optional_param('download', '', PARAM_TEXT);
$format = optional_param('exportformat', null, PARAM_ALPHA);
$empty = optional_param('empty', null, PARAM_TEXT);

$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);

// Keep user id and cmid for ajax calls.
global $USER, $OUTPUT;

$context = context_course::instance($course->id);

require_login($course);
require_capability('mod/cobra:view', $context);

// Print the page header.
$PAGE->set_url('/mod/cobra/myglossary.php', ['id' => $course->id]);

$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->navbar->ignore_active();
$PAGE->navbar->add(get_string('mycourses'));
$PAGE->navbar->add($course->shortname, new moodle_url('/course/view.php', ['id' => $course->id]));
$PAGE->navbar->add(get_string('myglossary', 'cobra'), new moodle_url('/mod/cobra/myglossary.php', ['id' => $id]));

// Add the ajaxcommand for the form.
$params = new stdClass();
$params->course = $course->id;
$params->user = $USER->id;
$PAGE->requires->js_call_amd('mod_cobra/cobra', 'initData', [json_encode($params)]);
$PAGE->requires->js_call_amd('mod_cobra/cobra', 'myGlossaryActions');

// Handle empty action.
if ($empty) {
    if (!empty($confirm) && confirm_sesskey()) {
        student_glossary_helper::empty_glossary($course->id, $USER->id);
    } else {
        $PAGE->navbar->add(get_string('delete'));
        $PAGE->set_title($course->shortname);
        $PAGE->set_heading($course->fullname);
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('myglossary', 'cobra'));
        echo $OUTPUT->confirm(get_string('deletesure', 'mod_cobra'),
            "myglossary.php?empty=true&confirm=1&id=$course->id",
            $CFG->wwwroot.'/mod/cobra/myglossary.php?id='.$course->id);
        echo $OUTPUT->footer();
        die;
    }
}

list($totalcount, $data) = student_glossary_helper::get_student_glossary($USER->id, $course->id, 0, $initial);

$entries = [];
if (!empty($data)) {

    foreach ($data as $entry) {
        $sourcetexttitle = student_glossary_helper::get_cached_text_title($entry->textid);
        $entry->sourcetexttitle = $sourcetexttitle;

        $where = 'userid = :userid AND lingentity = :lingentity AND course = :course';
        $sqlparams = [
            'userid' => $USER->id,
            'lingentity' => $entry->lingentity,
            'course' => $course->id,
        ];

        $textidlist = $DB->get_fieldset_select('cobra_click', 'textid', $where, $sqlparams);

        asort($textidlist);

        $texttitles = [];
        foreach ($textidlist as $textid) {
            $texttitles[] = student_glossary_helper::get_cached_text_title($textid);
        }

        $entry->textcount = count($texttitles);
        $entry->texttitles = implode("\n", $texttitles);

        if ($export) {
            unset($entry->lingentity);
            unset($entry->textid);
            unset($entry->type);
            unset($entry->textcount);
            unset($entry->texttitles);
        }
        $entries[] = $entry;
    }
}

if ($export) {
    $downloadentries = new ArrayObject($entries);
    $downloadfields = [
        'entry' => get_string('entry', 'cobra'),
        'translations' => get_string('translations', 'cobra'),
        'category' => get_string('category', 'cobra'),
        'extrainfo' => get_string('otherforms', 'cobra'),
        'sourcetexttitle' => get_string('sourcetext', 'cobra'),
    ];
    $iterator = $downloadentries->getIterator();
    \core\dataformat::download_data($course->shortname . '-' . get_string('myglossary', 'cobra'),
        $format,
        $downloadfields,
        $iterator
    );
    die();
}

echo $OUTPUT->header();
// Add buttons for export and trash.

echo $OUTPUT->heading(
        get_string('myglossary', 'cobra') .
        ' (' . $totalcount . ' ' .
        strtolower(get_string('entries', 'cobra')) .
        ')'
);

$baseurl = new moodle_url('/mod/cobra/myglossary.php', ['id' => $course->id]);
$records = [];
$entities = [];

$initialsbar = $OUTPUT->initials_bar($initial, 'initials', '', 'initial', $baseurl);

$output = $PAGE->get_renderer('mod_cobra');
$myglossaryview = new myglossary($entries, $course->id, $initialsbar, $initial);
echo $output->render($myglossaryview);

// Finish the page.
echo $output->footer();
