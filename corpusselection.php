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
 * Controller file for corpus selection functionality
 *
 * @package    mod_cobra
 * @copyright  2016 - Cellule TICE - Unversite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/locallib.php');
require_once(__DIR__ . '/lib/cobraremoteservice.php');
require_once(__DIR__ . '/lib/cobracollectionwrapper.php');

$id = required_param('id', PARAM_INT);

list($course, $cm) = get_course_and_cm_from_cmid($id, 'cobra');
$cobra = $DB->get_record('cobra', array('id' => $cm->instance), '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/cobra:edit', $context);

// Print the page header.
$PAGE->set_url('/mod/cobra/corpus_selection.php', array('id' => $cm->id));
$PAGE->set_title(format_string($cobra->name));
$PAGE->set_heading(format_string($course->fullname));

// Load CoBRA css file.
$PAGE->requires->css('/mod/cobra/css/cobra.css');

// Add link for Ajax commands.
$PAGE->requires->jquery();
$PAGE->requires->js('/mod/cobra/js/cobra.js');
$PAGE->requires->js_init_call('M.mod_cobra.move_resource');

// Buffer output
$content = '';
$message = '';

$acceptedcmdlist = array(
    'exRemove',
    'exAdd',
);

$cmd = optional_param('cmd', '', PARAM_ALPHANUM);
if (!in_array($cmd, $acceptedcmdlist)) {
    $cmd = '';
}

$heading = get_string('modulename', 'cobra') . ' - ' . get_string('corpusselection', 'cobra');

if ('exAddCorpus' == $cmd) {
    $corpustypeid = required_param('corpus', PARAM_INT);
    if(cobra_add_corpus_to_selection($corpustypeid)) {
        $content .= $OUTPUT->notification(get_string('corpusadded', 'cobra'), 'notifysuccess');
    } else {
        $content .= $OUTPUT->notification(get_string('corpusnotadded', 'cobra'), 'notifyerror');
    }
} else if ('exRemoveCorpus' == $cmd) {
    $corpustypeid = required_param('corpus', PARAM_INT);
    if (cobra_remove_corpus_from_selection($corpustypeid)) {
        $content .= $OUTPUT->notification(get_string('corpusremovedfromselection', 'cobra'), 'notifysuccess');
    } else {
        $content .= $OUTPUT->notification(get_string('corpusnotremoved', 'cobra'), 'notifyerror');
    }
}

$tabcorpustype = cobra_get_valid_list_type_corpus($cobra->language);

$content .= html_writer::tag('h3', get_string('currentcorpuselection', 'cobra'));
$table = new html_table();
$table->attributes['class'] = 'admintable generaltable corpuslist';
$headercell1 = new html_table_cell(get_string('corpustype', 'cobra'));
$headercell1->style = 'text-align:left;';
$headercell2 = new html_table_cell(get_string('remove'));
$headercell2->attributes['class'] = 'text-center';
$headercell3 = new html_table_cell(get_string('move'));
$headercell3->attributes['class'] = 'text-center';
$table->head = array($headercell1, $headercell2, $headercell3);

$tabcorpustype = cobra_get_valid_list_type_corpus($cobra->language);
$ordretypelist = cobra_get_corpus_type_display_order();
$selected = array();
$position = 1;
foreach ($ordretypelist as $corpusorder) {
    if (cobra_corpus_type_exists($corpusorder->id_type, $cobra->language)) {
        $selected[] = $corpusorder->id_type;
        $row = new html_table_row();
        $row->id = $corpusorder->id_type . '#corpustypeid';
        $row->attributes['name'] = $position++ . '#pos';
        $row->attributes['class'] = 'tablerow';
        $cell = new html_table_cell();
        $cell->text = $tabcorpustype[$corpusorder->id_type]->name;
        $cell->attributes['class'] = 'text-left ' . $tabcorpustype[$corpusorder->id_type]->colorclass;
        $row->cells[] = $cell;
        $table->data[] = $row;

        $cell = new html_table_cell();
        $cellcontent = html_writer::tag('i', '', array('class' => 'fa fa-remove'));
        $cellcontent = html_writer::tag('a', $cellcontent, array(
            'href' => new moodle_url('/mod/cobra/corpusselection.php',
                array(
                    'id' => $id,
                    'cmd' => 'exRemoveCorpus',
                    'section' => 'corpus',
                    'corpus' => $corpusorder->id
                )
            )
        ));
        $cell->text = $cellcontent;
        $cell->attributes['class'] = 'text-center';
        $row->cells[] = $cell;

        $cell = new html_table_cell();
        $cellcontent = html_writer::tag('i', '', array('class' => 'fa fa-arrow-up'));
        $cellcontent = html_writer::tag('a', $cellcontent, array('href' => '#', 'class' => 'moveUp'));
        $extracontent = html_writer::tag('i', '', array('class' => 'fa fa-arrow-down'));
        $extracontent = html_writer::tag('a', $extracontent, array('href' => '#', 'class' => 'moveDown'));
        $cell->text = $cellcontent . $extracontent;
        $cell->attributes['class'] = 'text-center';
        $row->cells[] = $cell;
    }
}

$content .= html_writer::start_tag('div', array('class'=>'no-overflow'));
$content .= html_writer::table($table);
$content .= html_writer::end_tag('div');

$content .= html_writer::tag('h3', get_string('corpusnotselected', 'cobra'));
$table = new html_table();
$table->attributes['class'] = 'admintable generaltable';
$headercell1 = new html_table_cell('type corpus');
$headercell1->style = 'text-align:left;';
$headercell2 = new html_table_cell(get_string('add'));
$headercell2->attributes['class'] = 'text-center';
$table->head = array($headercell1, $headercell2);

foreach ($tabcorpustype as $corpustype) {
    if (cobra_corpus_type_exists($corpustype->id, $cobra->language) && !in_array($corpustype->id, $selected)) {
        $row = new html_table_row();
        $cell = new html_table_cell();
        $cell->text = $corpustype->name;
        $cell->attributes['class'] = 'text-left ' . $corpustype->colorclass;
        $row->cells[] = $cell;

        $cell = new html_table_cell();
        $cellcontent = html_writer::tag('i', '', array('class' => 'fa fa-plus'));
        $cellcontent = html_writer::tag('a', $cellcontent, array(
            'href' => new moodle_url('/mod/cobra/corpusselection.php',
                array(
                    'id' => $id,
                    'cmd' => 'exAddCorpus',
                    'section' => 'corpus',
                    'corpus' => $corpustype->id
                )
            )
        ));
        $cell->text = $cellcontent;
        $cell->attributes['class'] = 'text-center';
        $row->cells[] = $cell;
        $table->data[] = $row;
    }
}

$content .= html_writer::start_tag('div', array('class' => 'no-overflow'));
$content .= html_writer::table($table);
$content .= html_writer::end_tag('div');

echo $OUTPUT->header();
echo $OUTPUT->heading($heading);
echo $OUTPUT->box_start('generalbox box-content');
if(!empty($message)) echo $message;
echo $content;

echo $OUTPUT->box_end();

// Finish the page.
echo $OUTPUT->footer();