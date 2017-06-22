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
require_once(__DIR__ . '/locallib.php');
require_once(__DIR__ . '/lib/glossarylib.php');
require_once(__DIR__ . '/classes/output/myglossary.php');

$id = required_param('id', PARAM_INT);
$cmd = optional_param('cmd', null, PARAM_ALPHA);
$confirm = optional_param('confirm', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$perpage = 25;

list($course, $cm) = get_course_and_cm_from_cmid($id, 'cobra');
$cobra = $DB->get_record('cobra', array('id' => $cm->instance), '*', MUST_EXIST);

// Keep user id and cmid for ajax calls.
global $USER, $OUTPUT;
$cobra->user = $USER->id;
$cobra->cmid = $id;

$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/cobra:view', $context);

// Print the page header.
$PAGE->set_url('/mod/cobra/myglossary.php', array('id' => $cm->id));

$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->navbar->ignore_active();
$PAGE->navbar->add(get_string('mycourses'));
$PAGE->navbar->add($course->shortname, new moodle_url('/course/view.php', array('id' => $course->id)));
$PAGE->navbar->add(get_string('myglossary', 'cobra'), new moodle_url('/mod/cobra/myglossary.php', array('id' => $id)));

$PAGE->requires->css('/mod/cobra/css/cobra.css');

// Add the ajaxcommand for the form.
$PAGE->requires->js_call_amd('mod_cobra/cobra', 'init', array(json_encode($cobra)));
$PAGE->requires->js_call_amd('mod_cobra/cobra', 'global_glossary_actions');

list($totalcount, $data) = cobra_get_student_cached_glossary($cobra->user, $cobra->course, 0, $page, $perpage);

$entries = array();
if (!empty($data)) {

    foreach ($data as $entry) {
        $sourcetexttitle = cobra_get_cached_text_title($entry->id_text);
        $entry->sourcetexttitle = $sourcetexttitle;

        $query = "SELECT GROUP_CONCAT(CAST(id_text AS CHAR)) AS texts
                    FROM {cobra_clic}
                   WHERE user_id = :userid
                         AND id_entite_ling = :lingentity
                         AND course = :course
                   GROUP BY id_entite_ling";
        $result = $DB->get_field_sql($query, array(
                'userid' => $USER->id,
                'lingentity' => $entry->lingentity,
                'course' => $course->id
            )
        );
        $textidlist = explode(',', $result);
        asort($textidlist);

        $texttitles = array();
        foreach ($textidlist as $textid) {
            $texttitles[] = cobra_get_cached_text_title($textid);
        }

        $entry->textcount = count($texttitles);
        $entry->texttitles = implode("\n", $texttitles);
        $entries[] = $entry;
    }
}
if ('export' == $cmd) {
    cobra_export_myglossary($entries);
}

echo $OUTPUT->header();
// Add buttons for export and trash.
$exportbutton = html_writer::link(new moodle_url(
    '/mod/cobra/myglossary.php',
    array(
        'id' => $id,
        'cmd' => 'export',
    )),
    '',
    array(
        'class' => 'glossaryexport',
        'title' => get_string('exportmyglossary', 'cobra')
    ));
$emptybutton = html_writer::link(new moodle_url(
    '/mod/cobra/myglossary.php',
    array(
        'id' => $id,
        'cmd' => 'empty',
    )),
    '',
    array(
        'class' => 'emptyglossary',
        'title' => get_string('emptymyglossary', 'cobra')
    ));

echo $OUTPUT->heading(get_string('myglossary', 'cobra') .
        '&nbsp;&nbsp;&nbsp;' .
        $exportbutton .
        '&nbsp;&nbsp;&nbsp;' .
        $emptybutton);

if (!$cobra->userglossary) {
    redirect(new moodle_url('/mod/cobra/view.php', array('id' => $cm->id)),
            'CoBRA' . ': ' . get_string('myglossaryunavailable', 'cobra', $CFG->cobra_serviceurl), 5);
}
if ($perpage) {
    echo $OUTPUT->paging_bar($totalcount, $page, $perpage, '/mod/cobra/myglossary.php?id='.$cm->id );
}

$table = new html_table();
$table->attributes['class'] = 'admintable generaltable';
$table->id = 'myglossary';
$headercell1 = new html_table_cell(get_string('entry', 'cobra'));
$headercell1->style = 'text-align:left;';
$headercell2 = new html_table_cell(get_string('translation', 'cobra'));
$headercell3 = new html_table_cell(get_string('category'));
$headercell4 = new html_table_cell(get_string('otherforms', 'cobra'));
$headercell5 = new html_table_cell(get_string('sourcetext', 'cobra'));
$headercell6 = new html_table_cell(get_string('clickedin', 'cobra'));
$headercell7 = new html_table_cell('');
$table->head = array(
    $headercell1,
    $headercell2,
    $headercell3,
    $headercell4,
    $headercell5,
    $headercell6,
    $headercell7
);

if ('empty' == $cmd) {
    if (!empty($confirm) && confirm_sesskey()) {
        cobra_empty_glossary($cobra->course, $cobra->user);
    } else {
        $PAGE->navbar->add(get_string('delete'));
        $PAGE->set_title($course->shortname);
        $PAGE->set_heading($course->fullname);

        echo $OUTPUT->confirm(get_string('deletesure', 'mod_cobra'),
            "myglossary.php?cmd=empty&confirm=$course->id&id=$cobra->cmid",
            $CFG->wwwroot.'/mod/cobra/myglossary.php?id='.$cobra->cmid);
        echo $OUTPUT->footer();
        die;
    }
}

if (!empty($data)) {

    foreach ($data as $entry) {
        $removeiconurl = $OUTPUT->image_url('glossaryremove', 'mod_cobra');

        $row = new html_table_row();
        $cell = new html_table_cell();
        $cell->text = $entry->entry;
        $row->cells[] = $cell;

        $cell = new html_table_cell();
        $cell->text = $entry->translations;
        $row->cells[] = $cell;

        $cell = new html_table_cell();
        $cell->text = $entry->category;
        $row->cells[] = $cell;

        $cell = new html_table_cell();
        $cell->text = $entry->extrainfo;
        $row->cells[] = $cell;

        $cell = new html_table_cell();
        $cell->text = $sourcetexttitle;
        $row->cells[] = $cell;

        $cell = new html_table_cell();
        $cell->text = html_writer::tag('span',
            count($texttitles) . '&nbsp;' . get_string('texts', 'cobra'),
            array('title' => implode("\n", $texttitles)));
        $row->cells[] = $cell;

        $cell = new html_table_cell();
        $cell->attributes['class'] = 'glossaryIcon';
        $cellcontent = html_writer::tag('span', $entry->lingentity, array('id' => 'currentLingEntity', 'class' => 'hidden'));
        $cellcontent .= html_writer::img($removeiconurl,
            get_string('myglossaryremove', 'cobra'),
            array('title' => get_string('myglossaryremove', 'cobra'), 'class' => 'glossaryremove inDisplay'));
        $cell->text = $cellcontent;
        $row->cells[] = $cell;

        $table->data[] = $row;
    }

} else {
    $row = new html_table_row();
    $cell = new html_table_cell();
    $cell->colspan = 7;
    $cell->attributes['class'] = 'text-center';
    $cell->text = get_string('emptyglossary', 'cobra');
    $row->cells[] = $cell;
    $table->data[] = $row;
}

$content = html_writer::start_tag('div', array('class' => 'no-overflow'));
$content .= html_writer::table($table);
$content .= html_writer::end_tag('div');

$output = $PAGE->get_renderer('mod_cobra');
$myglossaryview = new myglossary($entries);
echo $output->render($myglossaryview);

// Finish the page.
echo $output->footer();
