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

$id = required_param('id', PARAM_INT);
$cmd = optional_param('cmd', null, PARAM_ALPHA);

list($course, $cm) = get_course_and_cm_from_cmid($id, 'cobra');
$cobra = $DB->get_record('cobra', array('id' => $cm->instance), '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/cobra:view', $context);

$event = \mod_cobra\event\course_module_viewed::create(array(
    'objectid' => $PAGE->cm->instance,
    'context' => $PAGE->context,
));
$event->add_record_snapshot('course', $PAGE->course);
$event->add_record_snapshot($PAGE->cm->modname, $cobra);
$event->trigger();

// Print the page header.
$PAGE->set_url('/mod/cobra/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($cobra->name));
$PAGE->set_heading(format_string($course->fullname));

// Load CoBRA css file.
$PAGE->requires->css('/mod/cobra/css/cobra.css');
// Load javascript.
$PAGE->requires->jquery();
$PAGE->requires->js('/mod/cobra/js/cobra.js');
$PAGE->requires->js_init_call('M.mod_cobra.change_resource_visibility');
$PAGE->requires->js_init_call('M.mod_cobra.move_resource');

$content = '';
$isallowedtoedit = has_capability('mod/cobra:edit', $context);

// For all chosen collections display text in selected order.
if ($isallowedtoedit) {
    $collectionlist = cobra_get_registered_collections('all');
} else {
    $collectionlist = cobra_get_registered_collections('visible');
}
foreach ($collectionlist as $collection) {
    $content .= html_writer::tag('h3', $collection->local_label);
    $table = new html_table();
    $table->attributes['class'] = 'admintable generaltable textlist';
    $headercell1 = new html_table_cell(get_string('text', 'cobra'));
    $headercell1->style = 'text-align:left;';
    $headercell2 = new html_table_cell(get_string('source', 'cobra'));

    $table->head = array($headercell1, $headercell2);

    if ($isallowedtoedit) {
        // Add teacher only columns
        $headercell3 = new html_table_cell(get_string('move'));
        $headercell4 = new html_table_cell(get_string('visibility', 'cobra'));
        $table->head[] = $headercell3;
        $table->head[] = $headercell4;

        // Load all texts to display for course admin.
        $textlist = cobra_load_text_list($collection->id_collection, 'all');
    } else {
        // Load only visible texts to display for students.
        $textlist = cobra_load_text_list($collection->id_collection, 'visible');
    }

    if (!empty($textlist) && is_array($textlist)) {
        $position = 1;
        foreach ($textlist as $text) {
            // Display title.
            $rowcssclass = $text->visibility ? 'tablerow' : 'tablerow dimmed_text';
            $row = new html_table_row();
            $row->id = $text->id_text . '#textId';
            $row->attributes['class'] = $rowcssclass;
            $row->attributes['name'] = $position++ . '#pos';
            $cell = new html_table_cell();
            $cellcontent = html_writer::tag('i', '', array('class' => 'fa fa-file-text')) .
                           trim(strip_tags($text->title));
            $cellcontent = html_writer::tag('a', $cellcontent, array(
                'href' => 'text.php?' .
                          'id=' . $id .
                          '&id_text=' . $text->id_text . '#/' . $text->id_text
                )
            );
            $cell->text = $cellcontent;
            $row->cells[] = $cell;

            $cell = new html_table_cell();
            $cell->style = 'width:350px';
            $cell->attributes['title'] = $text->source;
            $cell->text = substr($text->source, 0, 30) . '...';
            $row->cells[] = $cell;

            if ($isallowedtoedit) {
                // Change position commands.
                $cell = new html_table_cell();
                $cell->attributes['class'] = 'text-center';
                $upicon = html_writer::tag('i', '', array('class' => 'fa fa-arrow-up'));
                $downicon = html_writer::tag('i', '', array('class' => 'fa fa-arrow-down'));
                $uplink = html_writer::link('#', $upicon . '&nbsp;', array('class' => 'moveUp'));
                $downlink = html_writer::link('#', $downicon, array('class' => 'moveDown'));
                $cell->text = $uplink . $downlink;
                $row->cells[] = $cell;

                // Change visibility commands.
                $cell = new html_table_cell();
                $cell->attributes['class'] = 'text-center';
                $hideicon = html_writer::tag('i', '', array('class' => 'fa fa-eye'));
                $showicon = html_writer::tag('i', '', array('class' => 'fa fa-eye-slash'));
                $hidelinkstyle = $text->visibility ? '' : 'display:none;';
                $showlinkstyle = $text->visibility ? 'display:none;' : '';
                $hidelink = html_writer::link('#', $hideicon, array('class' => 'setVisible', 'style' => $hidelinkstyle));
                $showlink = html_writer::link('#', $showicon, array('class' => 'setInvisible', 'style' => $showlinkstyle));
                $cell->text = $hidelink . $showlink;
                $row->cells[] = $cell;
            }
            $table->data[] = $row;
        }
    } else {
        $row = new html_table_row();
        $cell = new html_table_cell('no text');
        $cell->colspan = $isallowedtoedit ? '4' : '2';
        $cell->attributes['class'] = 'text-center';
        $table->data[]= $row;
    }
    $content .= html_writer::start_tag('div', array('class' => 'no-overflow'));
    $content .= html_writer::table($table);
    $content .= html_writer::end_tag('div');
}

// Output starts here.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('textreading', 'cobra'));
echo $OUTPUT->box_start('generalbox box-content');
echo $content;
echo $OUTPUT->box_end();

// Finish the page.
echo $OUTPUT->footer();
