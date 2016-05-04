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
    'exRefresh'
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
$PAGE->requires->js_init_call('M.mod_cobra.text_visibility');
$PAGE->requires->js_init_call('M.mod_cobra.text_move');

// Buffer output
$content = '';

if ('collections' == $currentsection) {
    //$content .= $OUTPUT->heading(get_string('modulename', 'cobra') . ' - ' . get_string('manage_text_collections', 'cobra'));
    $heading = get_string('modulename', 'cobra') . ' - ' . get_string('manage_text_collections', 'cobra');
    //$content .= $OUTPUT->box_start('generalbox box-content');
    if ('exEditLabel' == $cmd) {
        $label = optional_param('label', null, PARAM_ALPHANUM);
        if (!empty($label)) {
            $collection = new cobra_collection_wrapper($collectionid);
            $collection->load();
            $collection->set_local_name($label);
            if ($collection->update()) {
                $content .= $OUTPUT->notification(get_string('Collection_name_changed', 'cobra'));
            } else {
                $content .= $OUTPUT->notification(get_string('Unable_to_change_collection_name', 'cobra'));
            }
        } else {
            $content .= $OUTPUT->notification(get_string('Collection_name_cannot_be_empty', 'cobra'));
            $cmd = 'rqEditLabel';
        }
    }
    if ('rqEditLabel' == $cmd) {
        $collection = new cobra_collection_wrapper($collectionid);
        $collection->load();
        $editform = '<strong>' . get_string('edit_collection', 'cobra') . '</strong>' .
                    '<form action="' . $_SERVER['PHP_SELF'] . '?id='.$id .'" method="post">' .
                    '<input type="hidden" name="claroFormId" value="' . uniqid('') . '" />' .
                    '<input type="hidden" name="collection" value="' . $collectionid . '" />' .
                    '<input type="hidden" name="cmd" value="exEditLabel" />' .
                    '<label for="label">' . get_string('name') . ' : </label><br />' .
                    '<input type="text" name="label" id="label"' .
                    ' value="' . $collection->get_local_name() . '" /><br /><br />' .
                    '<input type="submit" value="' . get_string('ok') . '" />&nbsp; ' .
                    '</form>';

        $content .= $OUTPUT->box($editform);
    } else if ('exAdd' == $cmd && !empty($remotecollection)) {
        $collection = new cobra_collection_wrapper();
        $collection->wrapremote($remotecollection);
        $textlist = cobra_load_remote_text_list($collection->get_id());
        $savemode = $collection->save();
        if ('error' == $savemode) {
            $content .= $OUTPUT->notification(get_string('unable_register_collection', 'cobra'));
        } else if ('saved' == $savemode) {
            $position = 1;
            foreach ($textlist as $remotetext) {
                $text = new cobra_text_wrapper();
                $text->set_text_id($remotetext['id']);
                $text->set_collection_id($collection->get_id());
                $text->set_position($position++);
                $text->save();
            }
            $content .= $OUTPUT->box(get_string('text_collection_added', 'cobra'));
        }
    } else if ('exRemove' == $cmd) {
        $collection = new cobra_collection_wrapper($collectionid);
        if (cobra_remove_text_list($collectionid)) {
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
        $remotecollection->wrapremote($collectionid);
        // Refresh collection name if it has not be changed locally.
        if ($localcollection->get_local_name() == $localcollection->get_remote_name()) {
            $localcollection->set_local_name($remotecollection->get_remote_name());
        }
        $localcollection->set_remote_name($remotecollection->get_remote_name());
        $localcollection->save();
        $remotetextlist = cobra_load_remote_text_list($collectionid);
        $localtextlist = cobra_load_text_list($collectionid);
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
            $text->set_collection_id($collectionid);
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
                $typeid = $corpustypeinfo['id'];
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
}

if ('collections' == $currentsection) {
    $content .= '<h3>' . get_string('mycollections', 'cobra') . '</h3>';
    $content .= '<table class="table table-condensed table-hover table-striped collectionlist">' .
                '<thead>' .
                '<tr>' .
                '<th style="text-align:left;">' . get_string('collection_name', 'cobra') . '</th>' .
                '<th>' . get_string('edit') . '</th>' .
                '<th>' . get_string('refresh') . '</th>' .
                '<th>' . get_string('remove') . '</th>' .
                '<th>' . get_string('move') . '</th>' .
                '<th>' . get_string('visibility', 'cobra') . '</th>' .
                '</tr>' .
                '</thead>' .
                '<tbody>';

    $idlist = array();
    $registeredcollectionslist = cobra_get_registered_collections('all');

    $position = 1;
    foreach ($registeredcollectionslist as $collection) {
        $rowcssclass = $collection['visibility'] ? 'tablerow' : 'tablerow dimmed_text';
        $content .= '<tr id="' . $collection['id_collection']  .
                    '#collectionId" class="' . $rowcssclass . '" name="' . $position++ . '#pos">' .
                    '<td><i class="fa fa-list"></i> ' . $collection['local_label'] . '</td>' .
                    '<td align="center">' .
                    '<a href="'.$_SERVER['PHP_SELF'].'?id=' . $id .
                    '&cmd=rqEditLabel&amp;collection='.$collection['id_collection'].'">'.
                    '<i class="fa fa-edit"></i>' .
                    '</a></td>' .
                    '<td align="center">' .
                    '<a href="'.$_SERVER['PHP_SELF'].'?id=' . $id .
                    '&cmd=exRefresh&amp;collection='.$collection['id_collection'].'">'.
                    '<i class="fa fa-refresh"></i>' .
                    '</a></td>' .
                    '<td align="center">' .
                    '<a href="'.$_SERVER['PHP_SELF'] . '?id=' . $id .
                    '&cmd=exRemove&amp;collection='.$collection['id_collection'].'">'.
                    '<i class="fa fa-remove"></i>' .
                    '</a></td>' .
                    // Change position commands.
                    '<td align="center">' .
                    '<a href="#" class="moveUp">' .  '<i class="fa fa-arrow-up"></i>' . '</a>' .
                    '&nbsp;' .
                    '<a href="#" class="moveDown">' .  '<i class="fa fa-arrow-down"></i>' . '</a>' .
                    '</td>' .
                    // Visibility commands.
                    '<td align="center">' .
                    '<a href="#" class="setInvisible" '. (!$collection['visibility'] ? 'style="display:none"' : '').'>' .
                    '<i class="fa fa-eye"></i>' . '</a>' .
                    '<a href="#" class="setVisible" ' . ($collection['visibility'] ? 'style="display:none"' : '') . '>' .
                    '<i class="fa fa-eye-slash"></i>' . '</a>' .
                    '</td>' .
                    '</tr>';
        $idlist[] = $collection['id_collection'];
    }
    if (!count($registeredcollectionslist)) {
        $content .= '<tr><td colspan="6" align="center"> / </td> </tr>';
    }
    $content .= '</tbody>' .
                '</table>';

    $content .= '<h3>' . get_string('collections_available', 'cobra') . '</h3>';
    $content .= '<table class="table table-condensed table-hover table-striped">' .
                '<thead>' .
                '<tr>' .
                '<th>' . get_string('collection_name', 'cobra') . '</th>' .
                '<th style="text-align:center;">' . get_string('add') . '</th>' .
                '</tr>' .
                '</thead>' .
                '<tbody>';
    $availablecollectionslist = cobra_get_filtered_collections($cobra->language, $idlist);
    foreach ($availablecollectionslist as $collection) {
        $content .= '<tr>' .
                    '<td><i class="fa fa-list"></i> ' . $collection['label'] . '</td>' .
                    '<td align="center">' .
                    '<a href="' . $_SERVER['PHP_SELF'] . '?id='.$id.'&cmd=exAdd&amp;remote_collection=' . $collection['id'] . '">' .
                    '<i class="fa fa-plus"></i>' .
                    '</a></td>' .
                    '</tr>';
    }
    $content .= '</tbody>' .
                '</table>';
} else if ('corpus' == $currentsection) {
    $ordretypelist = cobra_get_corpus_type_display_order();
    $list = '';
    foreach ($tabcorpustype as $corpustypeinfo) {
        $typeid = (int)$corpustypeinfo['id'];
        $couleur = cobra_find_background($typeid);
        $corpustypename = $corpustypeinfo['name'];

        if (cobra_corpus_type_exists($typeid, $cobra->language)) {
            $typeselected = '';
            if (in_array($typeid, $ordretypelist)) {
                $typeselected = ' checked="checked"';
            }
            $list .= '<tr><td>';
            $list .= '<input type="checkbox" name="' . $typeid . '" value="true"' . $typeselected . '></td>' .
                     '<td class="' . $couleur . '"> ' . $corpustypename . '</td>' .
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
    $content .= $form;
}

echo $OUTPUT->header();
echo $OUTPUT->heading($heading);
echo $OUTPUT->box_start('generalbox box-content');
echo $content;

echo $OUTPUT->box_end();

// Finish the page.
echo $OUTPUT->footer();
