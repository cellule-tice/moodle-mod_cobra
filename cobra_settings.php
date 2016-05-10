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


require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/locallib.php');
require_once(__DIR__ . '/lib/cobraremoteservice.php');
require_once(__DIR__ . '/lib/cobracollectionwrapper.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // ... cobra instance ID - it should be named as the first character of the module.

if ($id) {
    $cm         = get_coursemodule_from_id('cobra', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $cobra  = $DB->get_record('cobra', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($n) {
    $cobra  = $DB->get_record('cobra', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cobra->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('cobra', $cobra->id, $course->id, false, MUST_EXIST);
} else {
    print_error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/cobra:edit',$context);
if (!has_capability('mod/cobra:edit', $context)) {
      redirect('view.php?id=' . $cm->id);
}
// Prepare settings menu.
$sectionlist = array(
    'collections' => get_string('manage_text_collections', 'cobra'),
    'corpus' => get_string('corpus_selection', 'cobra'),
    'display' => get_string('display_preferences', 'cobra')
);
$currentsection = optional_param('section', 'collections', PARAM_ALPHANUM);

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

// Add link for Ajax commands.
$PAGE->requires->jquery();
$PAGE->requires->js('/mod/cobra/js/cobra.js');
$PAGE->requires->js_init_call('M.mod_cobra.move_resource');

// Buffer output
$content = '';
$message = '';

if ('collections' == $currentsection) {
        $heading = get_string('modulename', 'cobra') . ' - ' . get_string('manage_text_collections', 'cobra');
    if ('exEditLabel' == $cmd) {
        $label = optional_param('label', null, PARAM_TEXT);
        if (!empty($label)) {
            $collection = new cobra_collection_wrapper($collectionid);
            $collection->load();
            $collection->set_local_name($label);
            if ($collection->update()) {
                $message .= $OUTPUT->notification(get_string('Collection_name_changed', 'cobra'), 'notifysuccess');
            } else {
                $message .= $OUTPUT->notification(get_string('Unable_to_change_collection_name', 'cobra'), 'notifiydanger');
            }
        } else {
            $message .= $OUTPUT->notification(get_string('Collection_name_cannot_be_empty', 'cobra'), 'notifywarning');
            $cmd = 'rqEditLabel';
        }
    }
    if ('rqEditLabel' == $cmd) {
        $collection = new cobra_collection_wrapper($collectionid);
        $collection->load();
        $url = new moodle_url('/mod/cobra/cobra_settings.php',
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
            $content .= $OUTPUT->notification(get_string('unable_register_collection', 'cobra'));
        } else if ('saved' == $savemode) {
            $position = 1;
            foreach ($textlist as $remotetext) {
                $text = new cobra_text_wrapper();
                $text->set_text_id($remotetext['id']);
                $text->set_collection_id($collection->get_remote_id());
                $text->set_position($position++);
                $text->save();
            }
            $content .= $OUTPUT->box(get_string('text_collection_added', 'cobra'));
        }
    } else if ('exRemove' == $cmd) {
        $collection = new cobra_collection_wrapper($collectionid);
        if (cobra_remove_text_list($collection->get_remote_id())) {
            if (!$collection->remove()) {
                $content .= $OUTPUT->notification(get_string('unable_unregister_collection', 'cobra'));
            }
        } else {
            $content .= $OUTPUT->notification(get_string('unable_remove_texts_collection', 'cobra'));
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
            $content .= '<br> textt(s) removed';
            if ($legacytextcount != $removedtextcount) {
                $content .= '' .
                    $legacytextcount - $removedtextcount .
                    ' ' . get_string('could_not_be_removed', 'cobra') .
                    '</br>';
            }
        } else {
            $content .= get_string('No_text_to_remove', 'cobra');
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
            $content .= '<br> new text(s) added';
            if ($newtextcount != $addedtextcount) {
                $content .= '' .
                    $newtextcount - $addedtextcount .
                    ' ' .
                    get_string('could_not_be_added', 'cobra') .
                    '<br/> ';
            }
        } else {
            $content .= get_string('No_text_to_add', 'cobra'). '<br/>';
        }
    }
} else if ('corpus' == $currentsection) {
    $heading = (get_string('modulename', 'cobra') . ' - ' . get_string('corpus_selection', 'cobra'));
    $prefs = cobra_get_preferences();
    $tabcorpustype = cobra_get_valid_list_type_corpus($cobra->language);
    if ('saveOrder' == $cmd) {
        if (!cobra_clear_corpus_selection()) {
            $content .= 'error while saving preferences' . '<br/>';
        } else {
            $tabnewordre = array();
            foreach ($tabcorpustype as $corpustypeinfo) {
                $typeid = $corpustypeinfo->id;
                if (isset($_REQUEST[$typeid]) && ('' != $_REQUEST['ordre' . $typeid])) {
                    $tabnewordre[$_REQUEST['ordre' . $typeid]] = $typeid;
                }
            }
            ksort($tabnewordre);
            foreach ($tabnewordre as $typeid) {
                cobra_insert_corpus_type_display_order($typeid);
            }
            $content .= 'Concordances Order Saved' . '<br/>';
        }
    }
    if ('exAddCorpus' == $cmd) {
        $corpustypeid = required_param('corpus', PARAM_INT);
        if(cobra_add_corpus_to_selection($corpustypeid)) {
            $content .= $OUTPUT->notification('corpus added', 'notifysuccess');
        } else {
            $content .= $OUTPUT->notification('unable to add corpus type to selection');
        }
    } else if ('exRemoveCorpus' == $cmd) {
        $corpustypeid = required_param('corpus', PARAM_INT);
        if (cobra_remove_corpus_from_selection($corpustypeid)) {
            $content .= $OUTPUT->notification('corpus_removed_from_selection', 'notifysuccess');
        } else {
            $content .= $OUTPUT->notification('unable_to_remove_corpus_from_selection', 'notifyerror');
        }
    }
}

if ('collections' == $currentsection) {

    $content .= html_writer::tag('h3', get_string('mycollections', 'cobra'));
    $table = new html_table();
    $table->attributes['class'] = 'admintable generaltable collectionlist';
    $headercell1 = new html_table_cell(get_string('collection_name', 'cobra'));
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
                'href' => new moodle_url('/mod/cobra/cobra_settings.php',
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
            'href' => new moodle_url('/mod/cobra/cobra_settings.php',
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
            'href' => new moodle_url('/mod/cobra/cobra_settings.php',
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

    $content .= html_writer::start_tag('div', array('class'=>'no-overflow'));
    $content .= html_writer::table($table);
    $content .= html_writer::end_tag('div');

    $content .= html_writer::tag('h3', get_string('collections_available', 'cobra'));
    $table = new html_table();
    $table->attributes['class'] = 'admintable generaltable';
    $headercell1 = new html_table_cell(get_string('collection_name', 'cobra'));
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
            'href' => new moodle_url('/mod/cobra/cobra_settings.php',
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

} else if ('corpus' == $currentsection) {
    $content .= html_writer::tag('h3', 'todo string: ma liste de corpus');
    $table = new html_table();
    $table->attributes['class'] = 'admintable generaltable corpuslist';
    $headercell1 = new html_table_cell('Type de corpus');
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
                'href' => new moodle_url('/mod/cobra/cobra_settings.php',
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

    $content .= html_writer::tag('h3', 'corpus non sélectionnés');
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
                'href' => new moodle_url('/mod/cobra/cobra_settings.php',
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


    $content .= html_writer::start_tag('div', array('class'=>'no-overflow'));
    $content .= html_writer::table($table);
    $content .= html_writer::end_tag('div');
    /*$tabcorpustype = cobra_get_valid_list_type_corpus($cobra->language);
    $ordretypelist = cobra_get_corpus_type_display_order();
    print_object($ordretypelist);
    $list = '';
    foreach ($tabcorpustype as $corpustypeinfo) {
        $typeid = (int)$corpustypeinfo->id;
        $couleur = cobra_find_background($typeid);
        $corpustypename = $corpustypeinfo->name;

        if (cobra_corpus_type_exists($typeid, $cobra->language)) {
            $typeselected = '';
            if (in_array($typeid, $ordretypelist)) {
                $typeselected = ' checked="checked"';
            }
            $list .= '<tr><td>';
            $list .= '<input type="checkbox" name="' . $typeid . '" value="true"' . $typeselected . '></td>' .
                     '<td class="' . $corpustypeinfo->colorclass . '"> ' . $corpustypename . '</td>' .
                     '<td> <select name="ordre'. $typeid . '">' .
                     '<option value="" ' . ('' == $typeselected ? ' selected="selected"' : '') .
                     '>&nbsp; </option>';

            for ($i = 1; $i <= count($tabcorpustype); $i++) {
                $list .= '<option value="' . $i . '"';
                if (in_array($i, array_keys($ordretypelist, $typeid))) {
                    $list .= ' selected="selected" ';
                }
                $list .= '>' . $i . '</option>';
            }
            $list .= '</select></td></tr>';
        }
    }
    $form = '<div class="">' .
            '<form action="' . $_SERVER['PHP_SELF'] . '?id='.$id.'&section=corpus&amp;cmd=saveOrder" method="post"> ' .
            '<table class="table table-condensed table-hover table-striped">' .
            '<thead><tr>' .
            '<th>&nbsp;</th>' .
            '<th>Type de corpus</th>' .
            '<th>Ordre</th>' .
            '</tr> </thead>' .
            $list .
            '<tr><td colspan="3" style="text-align:center;">' .
            '<input value="' . get_string ('Ok', 'cobra') . '" type="submit" name="submit"/>&nbsp;' .
            '</td></tr>' .
            '</table>' .
            '</form>' .
            '</div>';
    $content .= $form;*/
}

echo $OUTPUT->header();
echo $OUTPUT->heading($heading);
echo $OUTPUT->box_start('generalbox box-content');
if(!empty($message)) echo $message;
if(!empty($thisform)) echo $thisform->display();
echo $content;

echo $OUTPUT->box_end();

// Finish the page.
echo $OUTPUT->footer();
