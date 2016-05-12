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
$PAGE->set_url('/mod/cobra/collectionmanagement.php', array('id' => $cm->id));
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

// Define accepted command list.
$acceptedcmdlist = array(
    'rqEditLabel',
    'exEditLabel',
    'exRemove',
    'exAdd',
    'selectionEdit',
    'selectionSave',
    'saveOrder',
    'editOrder',
    'savePrefs',
    'exRefresh',
    'exAddCorpus',
    'exRemoveCorpus'
);

$cmd = optional_param('cmd', '', PARAM_ALPHANUM);
if (!in_array($cmd, $acceptedcmdlist)) {
    $cmd = '';
}

$collectionid = optional_param('collection', 0, PARAM_INT);
$remotecollection = optional_param('remote_collection', null, PARAM_INT);

// Print the page header.

$PAGE->set_url('/mod/cobra/collectionmanagement.php', array('id' => $cm->id));
$PAGE->set_title(format_string($cobra->name));
$PAGE->set_heading(format_string($course->fullname));

// Load CoBRA css file.
$PAGE->requires->css('/mod/cobra/css/cobra.css');

// Add link for Ajax commands.
$PAGE->requires->jquery();
$PAGE->requires->js('/mod/cobra/js/cobra.js');
$PAGE->requires->js_init_call('M.mod_cobra.change_resource_visibility');
$PAGE->requires->js_init_call('M.mod_cobra.move_resource');

// Buffer output
$content = '';
$message = '';

$heading = get_string('modulename', 'cobra') . ' - ' . get_string('managetextcollections', 'cobra');
if ('exEditLabel' == $cmd) {
    $label = optional_param('label', null, PARAM_TEXT);
    if (!empty($label)) {
        $collection = new cobra_collection_wrapper($collectionid);
        $collection->load();
        $collection->set_local_name($label);
        if ($collection->update()) {
            $message .= $OUTPUT->notification(get_string('collectionnamechanged', 'cobra'), 'notifysuccess');
        } else {
            $message .= $OUTPUT->notification(get_string('collectionnamenotchanged', 'cobra'), 'notifiydanger');
        }
    } else {
        $message .= $OUTPUT->notification(get_string('collectionnamecannotbeempty', 'cobra'), 'notifywarning');
        $cmd = 'rqEditLabel';
    }
}
if ('rqEditLabel' == $cmd) {
    $collection = new cobra_collection_wrapper($collectionid);
    $collection->load();
    $url = new moodle_url('/mod/cobra/collectionmanagement.php',
        array(
            'id' => $id,
            'cmd' => 'exEditLabel',
            'collection' => $collection->get_id()
        )
    );
    $thisform = new cobra_edit_collection_label_form($url, array('collectionname' => $collection->get_local_name()));

} else if ('exAdd' == $cmd && !empty($remotecollection)) {
    $collection = new cobra_collection_wrapper();
    $collection->wrapremote($remotecollection);
    $textlist = cobra_load_remote_text_list($collection->get_remote_id());
    $savemode = $collection->save();
    if ('error' == $savemode) {
        $content .= $OUTPUT->notification(get_string('unableregistercollection', 'cobra'));
    } else if ('saved' == $savemode) {
        $position = 1;
        foreach ($textlist as $remotetext) {
            $text = new cobra_text_wrapper();
            $text->set_text_id($remotetext['id']);
            $text->set_collection_id($collection->get_remote_id());
            $text->set_position($position++);
            $text->save();
        }
        $content .= $OUTPUT->box(get_string('textcollectionadded', 'cobra'));
    }
} else if ('exRemove' == $cmd) {
    $collection = new cobra_collection_wrapper($collectionid);
    if (cobra_remove_text_list($collection->get_remote_id())) {
        if (!$collection->remove()) {
            $content .= $OUTPUT->notification(get_string('unableunregistercollection', 'cobra'));
        }
    } else {
        $content .= $OUTPUT->notification(get_string('unableremovetextscollection', 'cobra'));
    }
} else if ('exRefresh' == $cmd) {
    $localcollection = new cobra_collection_wrapper($collectionid);
    $localcollection->load();
    $remotecollection = new cobra_collection_wrapper();
    $remotecollection->wrapremote($localcollection->get_remote_id());
    // Refresh collection name if it has not be changed locally.
    if ($localcollection->get_local_name() == $localcollection->get_remote_name()) {
        $localcollection->set_local_name($remotecollection->get_remote_name());
    }
    $localcollection->set_remote_name($remotecollection->get_remote_name());
    $localcollection->save();
    $remotetextlist = cobra_load_remote_text_list($localcollection->get_remote_id());
    $localtextlist = cobra_load_text_list($localcollection->get_remote_id());
    $localtextidlist = array();
    // Remove legacy texts.
    $legacytextcount = $removedtextcount = 0;
    $messages = array();
    foreach ($localtextlist as $localtext) {
        $localtextidlist[] = $localtext->id_text;
        if (empty($localtext->title)) {
            $legacytextcount++;
            $text = new cobra_text_wrapper();
            $text->set_text_id($localtext->id_text);
            $text->load();
            if ($text->remove()) {
                $removedtextcount++;
            }
        }
    }
    if ($legacytextcount != 0) {
        if ($legacytextcount != $removedtextcount) {
            /*$content .= '' .
                $legacytextcount - $removedtextcount .
                ' ' . get_string('couldnotberemoved', 'cobra') .
                '</br>';*/
            $messages[] = get_string('textnotremoved', 'cobra', $legacytextcount - $removedtextcount);
        } else {
            $messages[] = get_string('textsremoved', 'cobra', $removedtextcount);
        }
    } else {
        $messages[] = get_string('notexttoremove', 'cobra');
    }

    // Add new texts.
    $newtextcount = $addedtextcount = 0;
    foreach ($remotetextlist as $remotetext) {
        if (in_array($remotetext['id'], $localtextidlist)) {
            continue;
        }
        $newtextcount++;
        $text = new cobra_text_wrapper();
        $text->set_text_id($remotetext['id']);
        $text->set_collection_id($localcollection->get_remote_id());
        $text->set_type('Lesson');
        $text->set_position(cobra_text_wrapper::getmaxposition() + 1);
        if ($text->save()) {
            $addedtextcount++;
        }
    }
    if ($newtextcount != 0) {

        if ($newtextcount != $addedtextcount) {
            /*$content .= '' .
                $newtextcount - $addedtextcount .
                ' ' .
                get_string('textcouldnotbeadded', 'cobra') .
                '<br/> ';*/
            $messages[] = get_string('textnotadded', 'cobra', $newtextcount - $addedtextcount);

        } else {
            $messages[] = get_string('textsadded', 'cobra', $newtextcount);
        }
    } else {
        $messages[] = get_string('notexttoadd', 'cobra'). '<br/>';
    }
    if(!empty($messages)) {
        $multinotif = $OUTPUT->notification(implode('<br/>', $messages), 'notifymessage');
    }
}

$content .= html_writer::tag('h3', get_string('mycollections', 'cobra'));
$table = new html_table();
$table->attributes['class'] = 'admintable generaltable collectionlist';
$headercell1 = new html_table_cell(get_string('collectionname', 'cobra'));
$headercell1->style = 'text-align:left;';
$headercell2 = new html_table_cell(get_string('edit'));
$headercell2->attributes['class'] = 'text-center';
$headercell3 = new html_table_cell(get_string('refresh'));
$headercell3->attributes['class'] = 'text-center';
$headercell4 = new html_table_cell(get_string('remove'));
$headercell4->attributes['class'] = 'text-center';
$headercell5 = new html_table_cell(get_string('move'));
$headercell5->attributes['class'] = 'text-center';
$headercell6 = new html_table_cell(get_string('visibility', 'cobra'));
$headercell6->attributes['class'] = 'text-center';
$table->head = array($headercell1, $headercell2, $headercell3, $headercell4, $headercell5, $headercell6);

$idlist = array();
$registeredcollectionslist = cobra_get_registered_collections('all');

$position = 1;
foreach ($registeredcollectionslist as $collection) {

    $rowcssclass = $collection->visibility ? 'tablerow' : 'tablerow dimmed_text';
    $row = new html_table_row();
    $row->id = $collection->id_collection . '#collectionId';
    $row->attributes['name'] = $position++ . '#pos';
    $row->attributes['class'] = $rowcssclass;
    $cell = new html_table_cell();
    //$cell->text = '<i class="fa fa-list"></i> ' . $collection['local_label'];
    $cell->text = html_writer::tag('i', '', array('class' => 'fa fa-list')) . $collection->local_label;
    $cell->attributes['class'] = 'text-left';
    $row->cells[] = $cell;

    $cell = new html_table_cell();
    $cellcontent = html_writer::tag('i', '', array('class' => 'fa fa-edit'));
    $cellcontent = html_writer::tag('a', $cellcontent, array(
        'href' => new moodle_url('/mod/cobra/collectionmanagement.php',
            array(
                'id' => $id,
                'cmd' => 'rqEditLabel',
                'collection' => $collection->id
            )
        )
    ));
    $cell->text = $cellcontent;
    $cell->attributes['class'] = 'text-center';
    $row->cells[] = $cell;

    $cell = new html_table_cell();
    $cellcontent = html_writer::tag('i', '', array('class' => 'fa fa-refresh'));
    $cellcontent = html_writer::tag('a', $cellcontent, array(
        'href' => new moodle_url('/mod/cobra/collectionmanagement.php',
            array(
                'id' => $id,
                'cmd' => 'exRefresh',
                'collection' => $collection->id
            )
        )
    ));
    $cell->text = $cellcontent;
    $cell->attributes['class'] = 'text-center';
    $row->cells[] = $cell;

    $cell = new html_table_cell();
    $cellcontent = html_writer::tag('i', '', array('class' => 'fa fa-remove'));
    $cellcontent = html_writer::tag('a', $cellcontent, array(
        'href' => new moodle_url('/mod/cobra/collectionmanagement.php',
            array(
                'id' => $id,
                'cmd' => 'exRemove',
                'collection' => $collection->id
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

    $cell = new html_table_cell();

    $cellcontent = html_writer::tag('i', '', array('class' => 'fa fa-eye'));
    $params = array('href' => '#', 'class' => 'setInvisible');
    if (!$collection->visibility) {
        $params['style'] = 'display:none';
    }
    $cellcontent = html_writer::tag('a', $cellcontent, $params);
    $extracontent = html_writer::tag('i', '', array('class' => 'fa fa-eye-slash'));
    $params = array('href' => '#', 'class' => 'setVisible');
    if ($collection->visibility) {
        $params['style'] = 'display:none';
    }
    $extracontent = html_writer::tag('a', $extracontent, $params);
    $cell->text = $cellcontent . $extracontent;
    $cell->attributes['class'] = 'text-center';
    $row->cells[] = $cell;

    $table->data[] = $row;

    $idlist[] = $collection->id_collection;
}
if (!count($registeredcollectionslist)) {
    $row = new html_table_row();
    $cell = new html_table_cell('no collection registered');
    $cell->colspan = '6';
    $cell->style = 'text-align:center;';
    $row->cells[] = $cell;
    $table->data[] = $row;
}

$content .= html_writer::start_tag('div', array('class' => 'no-overflow'));
$content .= html_writer::table($table);
$content .= html_writer::end_tag('div');

$content .= html_writer::tag('h3', get_string('collectionsavailable', 'cobra'));
$table = new html_table();
$table->attributes['class'] = 'admintable generaltable';
$headercell1 = new html_table_cell(get_string('collectionname', 'cobra'));
$headercell1->style = 'text-align:left;';
$headercell2 = new html_table_cell(get_string('add'));
$headercell2->attributes['class'] = 'text-center';
$table->head = array($headercell1, $headercell2);

$availablecollectionslist = cobra_get_filtered_collections($cobra->language, $idlist);
foreach ($availablecollectionslist as $collection) {
    $row = new html_table_row();
    $cell = new html_table_cell();
    $cell->text = html_writer::tag('i', '', array('class' => 'fa fa-list')) . $collection['label'];
    $cell->attributes['class'] = 'text-left';
    $row->cells[] = $cell;

    $cell = new html_table_cell();
    $cellcontent = html_writer::tag('i', '', array('class' => 'fa fa-plus'));
    $cellcontent = html_writer::tag('a', $cellcontent, array(
        'href' => new moodle_url('/mod/cobra/collectionmanagement.php',
            array(
                'id' => $id,
                'cmd' => 'exAdd',
                'remote_collection' => $collection['remoteid']
            )
        )
    ));
    $cell->text = $cellcontent;
    $cell->attributes['class'] = 'text-center';
    $row->cells[] = $cell;
    $table->data[] = $row;
}
$content .= html_writer::start_tag('div', array('class'=>'no-overflow'));
$content .= html_writer::table($table);
$content .= html_writer::end_tag('div');

echo $OUTPUT->header();
echo $OUTPUT->heading($heading);
echo $OUTPUT->box_start('generalbox box-content');
if(!empty($message)) echo $message;
if(!empty($multinotif)) echo $multinotif;
if(!empty($thisform)) echo $thisform->display();
echo $content;

echo $OUTPUT->box_end();

// Finish the page.
echo $OUTPUT->footer();
