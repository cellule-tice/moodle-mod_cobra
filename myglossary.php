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
 * Prints a particular instance of cobra
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_cobra
 * @copyright  2015 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Replace cobra with the name of your module and remove this line.

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/locallib.php');
require_once(__DIR__ . '/lib/glossarylib.php');

$id = required_param('id', PARAM_INT);
$cmd = optional_param('cmd', null, PARAM_ALPHA);

list($course, $cm) = get_course_and_cm_from_cmid($id, 'cobra');
$cobra = $DB->get_record('cobra', array('id' => $cm->instance), '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/cobra:view', $context);

// Print the page header.
$PAGE->set_url('/mod/cobra/myglossary.php', array('id' => $cm->id));

$PAGE->set_title(format_string($cobra->name));
$PAGE->navbar->ignore_active();
$PAGE->navbar->add(get_string('mycourses'));
$PAGE->navbar->add($course->shortname, new moodle_url('/course/view.php', array('id' => $course->id)));
$PAGE->navbar->add(get_string('myglossary', 'cobra'));

$PAGE->requires->css('/mod/cobra/css/cobra.css');

// Add the ajaxcommand for the form.
$PAGE->requires->jquery();
$PAGE->requires->js('/mod/cobra/js/cobra.js');
$PAGE->requires->js_init_call('M.mod_cobra.remove_from_global_glossary');

if (!$cobra->userglossary) {
    redirect(new moodle_url('/mod/cobra/view.php', array('id' => $cm->id)), 'CoBRA' . ': ' . get_string('myglossaryunavailable', 'cobra', $CFG->cobra_serverhost), 5);
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

$data = cobra_get_remote_glossary_info_for_student();
$entries = array();
if (!empty($data)) {
    foreach ($data as $entry) {
        $sourcetextid = $DB->get_field('cobra_clic',
                'id_text',
                array(
                    'course' => $course->id,
                    'id_entite_ling' => $entry->ling_entity,
                    'user_id' => $USER->id,
                    'in_glossary' => 1
                )
        );
        $sourcetexttitle = cobra_get_text_title_from_id($sourcetextid);
        $entry->sourcetexttitle = $sourcetexttitle;

        $query = "SELECT GROUP_CONCAT(CAST(id_text AS CHAR)) AS texts
                    FROM {cobra_clic}
                   WHERE user_id = :userid
                         AND id_entite_ling = :lingentity
                         AND course = :course
                   GROUP BY id_entite_ling";
        $result = $DB->get_field_sql($query, array(
                'userid' => $USER->id,
                'lingentity' => $entry->ling_entity,
                'course' => $course->id
            )
        );
        $textidlist = explode(',', $result);
        asort($textidlist);

        $texttitles = array();
        foreach ($textidlist as $textid) {
            $texttitles[] = cobra_get_text_title_from_id($textid);
        }
        $entry->texttitles = $texttitles;
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
        $cell->text = $entry->extra_info;
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
        $cellcontent = html_writer::tag('span', $entry->ling_entity, array('id' => 'currentLingEntity', 'class' => 'hidden'));
        $cellcontent .= html_writer::img($removeiconurl,
                get_string('myglossaryremove', 'cobra'),
                array('title' => get_string('myglossaryremove', 'cobra'), 'class' => 'gGlossaryRemove inDisplay'));
        $cell->text = $cellcontent;
        $row->cells[] = $cell;

        $table->data[] = $row;

        $entries[] = $entry;
    }
    if ('export' == $cmd) {
        cobra_export_myglossary($entries);
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
// Output starts here.
echo $OUTPUT->header();

// Replace the following lines with you own code.
$exportbutton = html_writer::link(new moodle_url(
                    '/mod/cobra/myglossary.php',
                    array(
                        'id' => $id,
                        'cmd' => 'export',
                    )),
            '',
            array(
                'class' => 'glossaryExport',
                'title' => get_string('exportmyglossary', 'cobra')
            ));

echo $OUTPUT->heading(get_string('myglossary', 'cobra') . '&nbsp;&nbsp;&nbsp;' . $exportbutton);

echo $OUTPUT->box_start('generalbox box-content');
echo $content;

echo $OUTPUT->box_end();

// Finish the page.
echo $OUTPUT->footer();
