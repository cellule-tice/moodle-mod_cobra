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


require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once(dirname(__FILE__).'/lib/cobraremoteservice.class.php');
require_once(dirname(__FILE__).'/lib/cobracollectionwrapper.class.php');

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
      redirect('view.php?id='.$cm->id);
}
// Prepare settings menu.
$sectionlist = array('collections' => get_string( 'manage_text_collections', 'cobra' ),
    'corpus' => get_string( 'corpus_selection', 'cobra' ),
    'display' => get_string( 'display_preferences', 'cobra'));
$currentsection = optional_param('section', 'collections', PARAM_ALPHANUM);
if (array_key_exists($currentsection, $sectionlist)) {
    $currentsection = 'collections';
}

// Define acceprted command list.
$acceptedcmdlist = array( 'rqEditLabel', 'exEditLabel', 'exRemove', 'exAdd',
                          'selectionEdit', 'selectionSave', 'saveOrder', 'editOrder',
                          'savePrefs', 'exRefresh');

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

// Add link for Ajax commands.
 $PAGE->requires->jquery();
 $PAGE->requires->js('/mod/cobra/js/cobra.js');
 $PAGE->requires->js_init_call('M.mod_cobra.TextVisibility');
 $PAGE->requires->js_init_call('M.mod_cobra.TextMove');

// Output starts here.
echo $OUTPUT->header();
echo $OUTPUT->heading('Lecture de textes');
echo $OUTPUT->box_start('generalbox collection_content' );

$content = '';
$content .= '<a href="'.$_SERVER['PHP_SELF'].'?id='.$id.'&section=collections">'.
        get_string('manage_text_collections', 'cobra').'</a> &nbsp; ' . "\n"
        . '<a href="'.$_SERVER['PHP_SELF'].'?id='.$id.'&section=corpus">'.
        get_string('corpus_selection', 'cobra') . '</a> &nbsp; ' . "\n"
        . '<a href="'.$_SERVER['PHP_SELF'].'?id='.$id.'&section=display">' .
        get_string('display_preferences', 'cobra') .'</a>';
if ('collections' == $currentsection) {
    if ( 'exEditLabel' == $cmd ) {
        $label = optional_param('label', null, PARAM_ALPHANUM);
        if (!empty($label)) {
            $collection = new Cobracollectionwrapper($collectionid);
            $collection->load();
            $collection->set_local_name($label);
            if ($collection->update()) {
                echo $OUTPUT->notification(get_string('Collection_name_changed', 'cobra'));
            } else {
                echo $OUTPUT->notification(get_string('Unable_to_change_collection_name', 'cobra'));
            }
        } else {
            echo $OUTPUT->notification(get_string('Collection_name_cannot_be_empty', 'cobra'));
            $cmd = 'rqEditLabel';
        }
    }
    if ('rqEditLabel' == $cmd) {
        $collection = new Cobracollectionwrapper($collectionid);
        $collection->load();
        $editform = '<strong>' . get_string('edit_collection', 'cobra') . '</strong>' . "\n"
                     .  '<form action="' . $_SERVER['PHP_SELF'] . '?id='.$id .'" method="post">' . "\n"
                     .  '<input type="hidden" name="claroFormId" value="' . uniqid( '' ) . '" />' . "\n"
                     .  '<input type="hidden" name="collection" value="' . $collectionid . '" />'."\n"
                     .  '<input type="hidden" name="cmd" value="exEditLabel" />'."\n"
                     .  '<label for="label">' . get_string( 'name' ) . ' : </label><br />' . "\n"
                     .  '<input type="text" name="label" id="label"'
                     .  ' value="' . $collection->get_local_name() . '" /><br /><br />' . "\n"
                     .  '<input type="submit" value="' . get_string('ok') . '" />&nbsp; '
                     .  '</form>' . "\n"
                     .  "\n";

        echo $OUTPUT->box($editform );
    } else if ('exAdd' == $cmd && !empty($remotecollection)) {
        $collection = new Cobracollectionwrapper();
        $collection->wrapremote($remotecollection);
        $textlist = load_remote_text_list($collection->get_id());
        $savemode = $collection->save();
        if ('error' == $savemode) {
            echo $OUTPUT->notification(get_string('unable_register_collection', 'cobra'));
        } else if ('saved' == $savemode) {
            $position = 1;
            foreach ($textlist as $remotetext) {
                $text = new CobraTextWrapper();
                $text->set_text_id( $remotetext['id'] );
                $text->set_collection_id( $collection->get_id() );
                $text->set_position( $position++ );
                $text->save();
            }
            echo $OUTPUT->box(get_string('text_collection_added', 'cobra'));
        }
    } else if ('exRemove' == $cmd) {
        $collection = new Cobracollectionwrapper($collectionid);
        if (remote_text_list($collectionid)) {
            if (!$collection->remove()) {
                echo $OUTPUT->notification(get_string('unable_unregister_collection', 'cobra'));
            }
        } else {
            echo $OUTPUT->notification(get_string('unable_remove_texts_collection', 'cobra'));
        }
    } else if ('exRefresh' == $cmd) {
        $localcollection = new Cobracollectionwrapper($collectionid);
        $localcollection->load();
        $remotecollection = new Cobracollectionwrapper();
        $remotecollection->wrapremote($collectionid);
        // Refresh collection name if it has not be changed locally.
        if ($localcollection->get_local_name() == $localcollection->get_remote_name()) {
            $localcollection->set_local_name($remotecollection->get_remote_name());
        }
        $localcollection->set_remote_name($remotecollection->get_remote_name());
        $localcollection->save();
        $remotetextlist = load_remote_text_list( $collectionid );
        $localtextlist = load_text_list( $collectionid );
        $localtextidlist = array();
        // Remove legacy texts.
        $legacytextcount = $removedtextcount = 0;
        foreach ($localtextlist as $localtext) {
            $localtextidlist[] = $localtext->id_text;
            if (empty( $localtext->title)) {
                $legacytextcount++;
                $text = new ElexTextWrapper();
                $text->set_text_id( $localtext->id_text );
                $text->load();
                if ($text->remove()) {
                    $removedtextcount++;
                }
            }
        }
        if ($legacytextcount != 0) {
            echo '<br> textt(s) removed';
            if ($legacytextcount != $removedtextcount) {
                echo  '' . $legacytextcount - $removedtextcount . ' ' . get_string( 'could_not_be_removed', 'cobra' ). '</br>';
            }
        } else {
            echo get_string('No_text_to_remove', 'cobra');
        }

        // Add new texts.
        $newtextcount = $addedtextcount = 0;
        foreach ($remotetextlist as $remotetext) {
            if (in_array( $remotetext['id'], $localtextidlist)) {
                continue;
            }
            $newtextcount++;
            $text = new ElexTextWrapper();
            $text->set_text_id( $remotetext['id'] );
            $text->set_collection_id( $collectionid );
            $text->set_type( 'Lesson' );
            $text->set_position(CobraTextWrapper::getmaxposition() + 1);
            if ($text->save()) {
                $addedtextcount++;
            }
        }
        if ($newtextcount != 0) {
            echo '<br> new text(s) added';
            if ($newtextcount != $addedtextcount) {
                echo '' . $newtextcount - $addedtextcount . ' ' . get_string( 'could_not_be_added', 'cobra' ) .'<br/> ';
            }
        } else {
            echo get_string( 'No_text_to_add', 'cobra'  ). '<br/>';
        }
    }
} else if ('corpus' == $currentsection) {
    $prefs = get_cobra_preferences();
    $tabcorpustype = return_valid_list_type_corpus( $prefs['language'] );
    if ('saveOrder' == $cmd) {
        if (!clear_corpus_selection()) {
            echo 'error while saving preferences' . '<br>';
        } else {
            $tabnewordre = array();
            foreach ($tabcorpustype as $corpustypeinfo) {
                $typeid = $corpustypeinfo['id'];
                if (isset( $_REQUEST[$typeid] ) && ('' != $_REQUEST['ordre' . $typeid])) {
                    $tabnewordre[$_REQUEST['ordre' . $typeid]] = $typeid;
                }
            }
            ksort( $tabnewordre );
            foreach ($tabnewordre as $typeid) {
                insert_corpus_type_display_order( $typeid );
            }
            echo 'Concordances Order Saved' . '<br>';
        }
    }
} else if ('display' == $currentsection) {
    if ('savePrefs' == $cmd) {
        $prefs = get_cobra_preferences();
        $prefs['gender'] = isset($_REQUEST['gender']) && is_string($_REQUEST['gender']) ? $_REQUEST['gender'] : $prefs['gender'];
        $prefs['ff'] = isset($_REQUEST['ff']) && is_string( $_REQUEST['ff'] ) ? $_REQUEST['ff'] : $prefs['ff'];
        if (isset($_REQUEST['translations']) && is_string($_REQUEST['translations'])) {
            $prefs['translations'] = $_REQUEST['translations'];
        }
        if (isset($_REQUEST['illustrations']) && is_string($_REQUEST['illustrations'])) {
             $prefs['illustrations'] = $_REQUEST['illustrations'];
        }
        if ( isset($_REQUEST['examples']) && is_string($_REQUEST['examples'])) {
            $prefs['examples'] = $_REQUEST['examples'];
        }
        if (isset($_REQUEST['occurrences']) && is_string($_REQUEST['occurrences'])) {
            $prefs['occurrences'] = $_REQUEST['occurrences'];
        }
        if (isset($_REQUEST['descriptions']) && is_string($_REQUEST['descriptions'])) {
            $prefs['descriptions'] = $_REQUEST['descriptions'];
        }
        $prefs['player'] = isset($_REQUEST['player'] ) && is_string( $_REQUEST['player']) ? $_REQUEST['player'] : $prefs['player'];
        if (isset($_REQUEST['nextprevbuttons']) && is_string($_REQUEST['nextprevbuttons'])) {
            $prefs['nextprevbuttons'] = $_REQUEST['nextprevbuttons'];
        }
        if (!save_cobra_preferences($prefs)) {
                echo ' probleme <br>';
        } else {
            echo  get_string('Display_preferences_updated', 'cobra') . '<br>';
        }
    }
}

if ('collections' == $currentsection) {
    $content .= '<h3 style="margin-left:24px;">' . 'Collections currently linked to your course' . '</h3>';
    $content .= '<blockquote>' . "\n"
        . '<table id="collectionList" class="claroTable emphaseLine" style="width:60%">' . "\n"
        . '<thead>' . "\n"
        . '<tr class="headerX">' . "\n"
        .'<th> &nbsp; </th>' . "\n"
        . '<th>' . get_string( 'collection_name', 'cobra' ) . '</th>' . "\n"
        . '<th>' . get_string( 'edit' ) . '</th>' . "\n"
        . '<th>' . get_string( 'refresh' ) . '</th>' . "\n"
        . '<th>' . get_string( 'remove' ) . '</th>' . "\n"
        . '<th>' . get_string( 'move' ) . '</th>' . "\n"
        . '<th>' . get_string( 'visibility', 'cobra' ) . '</th>' . "\n"
        . '</tr>' . "\n"
        . '</thead>' . "\n"
        . '<tbody>' . "\n";

    $idlist = array();
    $registeredcollectionslist = get_registered_collections('all');

    $position = 1;
    foreach ($registeredcollectionslist as $collection) {
        $content .= '<tr id="' . $collection['id_collection']  . '#collectionId" class="row" name="' . $position++ . '#pos">' . "\n"
            . '<td>' . $collection['local_label'] . '</td>' . "\n"
            . '<td align="center">' . "\n"
            . '<a href="'.$_SERVER['PHP_SELF'].'?id='.$id.'&cmd=rqEditLabel&amp;collection='.$collection['id_collection'].'">'. "\n"
            . $OUTPUT->pix_icon('t/editstring', get_string('edit')) . "\n"
            . '</a></td>' . "\n"
            . '<td align="center">' . "\n"
            . '<a href="'.$_SERVER['PHP_SELF'].'?id='.$id.'&cmd=exRefresh&amp;collection='.$collection['id_collection'].'">'."\n"
            .  $OUTPUT->pix_icon('t/reload', get_string('reload')). "\n"
            . '</a></td>' . "\n"
            . '<td align="center">' . "\n"
            . '<a href="'.$_SERVER['PHP_SELF'] . '?id='.$id.'&cmd=exRemove&amp;collection='.$collection['id_collection'].'">'."\n"
            .   $OUTPUT->pix_icon('t/delete', get_string('delete')). "\n"
            . '</a></td>' . "\n"
            // Change position commands.
            . '<td align="center">' . "\n"
            . '<a href="#" class="moveUp">' .   $OUTPUT->pix_icon('t/up', get_string('moveup')). '</a>'
            . '&nbsp;'
            . '<a href="#" class="moveDown">' .   $OUTPUT->pix_icon('t/down', get_string('movedown')) . '</a>'
            . '</td>' . "\n"
            // Visibility commands.
            . '<td align="center">' . "\n"
            . '<a href="#" class="setInvisible" '. (!$collection['visibility'] ? 'style="display:none"' : '').'>'
            . $OUTPUT->pix_icon('t/hide', get_string('hide')) . '</a>'
            . '<a href="#" class="setVisible" '.( $collection['visibility'] ? 'style="display:none"' : '').'>'
            . $OUTPUT->pix_icon('t/show', get_string('show')) . '</a>'
            . '</td>' . "\n"
            . '</tr>' . "\n";
        $idlist[] = $collection['id_collection'];
    }
    if (!count($registeredcollectionslist)) {
        $content .= '<tr><td colspan="6" align="center"> / </td> </tr>';
    }
    $content .= '</tbody>' . "\n"
            . '</table>' . "\n"
            . '</blockquote>' . "\n";

    $content .= '<h3 style="margin-left:24px;">' . get_string( 'collections_available', 'cobra' ) . '</h3>';
    $content .= '<blockquote>' . "\n"
        . '<table class="claroTable emphaseLine" style="width:60%">' . "\n"
        . '<thead>' . "\n"
        . '<tr class="headerX">' . "\n"
        . '<th>' . get_string('collection_name', 'cobra') . '</th>' . "\n"
        . '<th>' . get_string('add') . '</th>' . "\n"
        . '</tr>' . "\n"
        . '</thead>' . "\n"
        . '<tbody>' . "\n";
    $availablecollectionslist = get_filtered_cobra_collections( $cobra->language, $idlist);
    foreach ($availablecollectionslist as $collection) {
        $content .= '<tr>' . "\n"
            . '<td>' . $collection['label'] . '</td>' . "\n"
            . '<td align="center">' . "\n"
            . '<a href="' . $_SERVER['PHP_SELF'] . '?id='.$id.'&cmd=exAdd&amp;remote_collection=' . $collection['id'] . '">' . "\n"
            . $OUTPUT->pix_icon('t/check', get_string('add_collection', 'cobra')) . "\n"
            . '</a></td>' . "\n"
            . '</tr>' . "\n";
    }
    $content .= '</tbody>' . "\n"
        . '</table>' . "\n"
        . '</blockquote>' . "\n";
} else if ('corpus' == $currentsection) {
    $ordretypelist = get_corpus_type_display_order();
    $list = '';
    foreach ($tabcorpustype as $corpustypeinfo) {
        $typeid = (int)$corpustypeinfo['id'];
        $couleur = find_background( $typeid );
        $corpustypename = $corpustypeinfo['name'];

        if (corpus_type_exists( $typeid, $prefs['language'])) {
            $typeselected = '';
            if (in_array( $typeid, $ordretypelist)) {
                $typeselected = ' checked="checked"';
            }
            $list .= '<tr><td>';
            $list .= '<input type="checkbox" name="' . $typeid . '" value="true"' . $typeselected . '></td>'
                   . '<td class="' . $couleur . '"> ' . $corpustypename . '</td>'
                   . '<td> <select name="ordre'. $typeid . '">' . "\n"
                   . '<option value="" ' . ( '' == $typeselected ? ' selected="selected"' : '' )
                   . '>&nbsp; </option>';

            for ($i = 1; $i <= count( $tabcorpustype ); $i++) {
                $list .= '<option value="' . $i . '"';
                if (in_array($i, array_keys( $ordretypelist, $typeid))) {
                    $list .= ' selected="selected" ';
                }
                $list .= '>' . $i . '</option>';
            }
            $list .= '</select>' . "\n" . '</td></tr>';
        }
    }
    $form = '<div class="">' . "\n"
          . '<form action="' . $_SERVER['PHP_SELF'] . '?id='.$id.'&section=corpus&amp;cmd=saveOrder" method="post"> ' . "\n"
          . '<table> <thead> <tr> <th> &nbsp; </th><th> Type de corpus </th> <th> Ordre </th> </tr> </thead>'
          . $list
          . '<tr><td colspan="3" style="text-align:center;">' . "\n"
          . '<input value="' . get_string ('Ok', 'cobra') . '" type="submit" name="submit"/>&nbsp;' . "\n"
          . '</td></tr>' . "\n"
          . '</table>' . "\n"
          . '</form>' . "\n"
          . '</div>' . "\n";
    $content .= $form;
} else if ('display' == $currentsection) {
    if (!isset($prefs)) {
        $prefs = get_cobra_preferences();
    }
    $checkedstring = ' checked="checked"';
    $content .= '<form method="post" action="' . $_SERVER['PHP_SELF'] . '?id='.$id.'&section=display">' . "\n"
        .  '<input type="hidden" name="cmd" value="savePrefs" />' . "\n"
        .  '<table border="0" cellpadding="5" width="100%">' . "\n"
        .  '<tr style="vertical-align:top;">' . "\n"
        .  '<td style="text-align: right" width="25%">' . get_string( 'Previous_and_Next_buttons', 'cobra' ) . '&nbsp;:</td>' . "\n"
        .  '<td nowrap="nowrap" width="25%">' . "\n"
        .  '<input id="nextPrevYes" type="radio" name="nextprevbuttons" value="SHOW"'
        . ( 'SHOW' == $prefs['nextprevbuttons'] ? $checkedstring : '' ) . '/>' . "\n"
        .  '<label for="nextPrevYes">' . get_string( 'yes' ) . '</label><br/>' . "\n"
        .  '<input id="nextPrevNo" type="radio" name="nextprevbuttons" value="HIDE"'
        . ( 'HIDE' == $prefs['nextprevbuttons'] ? $checkedstring : '' ) . '/>' . "\n"
        .  '<label for="nextPrevNo">' . get_string( 'no' ) . '</label>' . "\n"
        .  '</td>'
        .  '<td width="50%"><em><small>' . get_string( 'Display_Prev_and_Next_buttons', 'cobra' ) . '</small></em></td>' . "\n"
        .  '</tr>' . "\n"
        .  '<tr style="vertical-align:top;">' . "\n"
        .  '<td style="text-align: right" width="25%">' . get_string ('MP3_player', 'cobra' ) . '&nbsp;:</td>' . "\n"
        .  '<td nowrap="nowrap" width="25%">' . "\n"
        .  '<input id="playerYes" type="radio" name="player" value="SHOW"' . ( 'SHOW' == $prefs['player'] ? $checkedstring : '' )
        . '/>' . "\n"
        .  '<label for="playerYes">' . get_string( 'yes' ) . '</label><br/>' . "\n"
        .  '<input id="playerNo" type="radio" name="player" value="HIDE"' . ( 'HIDE' == $prefs['player'] ? $checkedstring : '' )
        . '/>' . "\n"
        .  '<label for="playerNo">' . get_string( 'no' ) . '</label>' . "\n"
        .  '</td>'
        .  '<td width="50%"><em><small>' . get_string ('Show_MP3_player', 'cobra' ) . '</small></em></td>' . "\n"
        .  '</tr>' . "\n"
        .  '<tr style="vertical-align:top;">' . "\n"
        .  '<td style="text-align: right" width="25%">' . get_string( 'Display_gender', 'cobra' ) . '&nbsp;:</td>' . "\n"
        .  '<td nowrap="nowrap" width="25%">' . "\n"
        .  '<input id="genderYes" type="radio" name="gender" value="SHOW"' . ( 'SHOW' == $prefs['gender'] ? $checkedstring : '' )
        . '/>' . "\n"
        .  '<label for="genderYes">' . get_string ('yes' ) . '</label><br/>' . "\n"
        .  '<input id="genderNo" type="radio" name="gender" value="HIDE"' . ( 'HIDE' == $prefs['gender'] ? $checkedstring : '' )
        . '/>' . "\n"
        .  '<label for="genderNo">' . get_string( 'no' ) . '</label>' . "\n"
        .  '</td>'
        .  '<td width="50%"><em><small>' . get_string( 'Only_for_Dutch_courses', 'cobra' ) . '</small></em></td>' . "\n"
        .  '</tr>' . "\n"
        .  '<tr style="vertical-align:top;">' . "\n"
        .  '<td style="text-align: right" width="25%">' . get_string( 'All_inflected_forms', 'cobra' ) . '&nbsp;:</td>' . "\n"
        .  '<td nowrap="nowrap" width="25%">' . "\n"
        .  '<input id="ffYes" type="radio" name="ff" value="SHOW"' . ( 'SHOW' == $prefs['ff'] ? $checkedstring : '' ) . '/>' . "\n"
        .  '<label for="ffYes">' . get_string( 'yes' ) . '</label><br/>' . "\n"
        .  '<input id="ffNo" type="radio" name="ff" value="HIDE"' . ( 'HIDE' == $prefs['ff'] ? $checkedstring : '' ) . '/>' . "\n"
        .  '<label for="ffNo">' . get_string( 'no' ) . '</label>' . "\n"
        .  '</td>'
        .  '<td width="50%"><em><small>' . get_string( 'Choose_yes_to_display_all_inflected_forms_in_lexical_card', 'cobra' )
        . '</small></em></td>' . "\n"
        .  '</tr>' . "\n";

        $content .= '<tr style="vertical-align:top;">' . "\n"
             .  '<td style="text-align: right" width="25%">' . get_string( 'Show_translations', 'cobra' ) . '&nbsp;:</td>' . "\n"
             .  '<td nowrap="nowrap" width="25%">' . "\n"
             .  '<input id="translationsYes" type="radio" name="translations" value="ALWAYS"'
             . ( 'ALWAYS' == $prefs['translations'] ? $checkedstring : '' ) . '/>' . "\n"
             .  '<label for="translationsYes">' . get_string( 'Always', 'cobra' ) . '</label><br/>' . "\n"
             .  '<input id="translationsNo" type="radio" name="translations" value="NEVER"'
             . ( 'NEVER' == $prefs['translations'] ? $checkedstring : '' ) . '/>' . "\n"
             .  '<label for="translationsNo">' . get_string( 'Never', 'cobra' ) . '</label><br/>' . "\n"
             .  '<input id="translationsCond" type="radio" name="translations" value="CONDITIONAL"'
             . ( 'CONDITIONAL' == $prefs['translations'] ? $checkedstring : '' ) . '/>' . "\n"
             .  '<label for="translationsCond">' . get_string( 'When_no_concordances', 'cobra' ) . '</label>' . "\n"
             .  '</td>'
             .  '<td width="50%"><em><small>' . get_string( 'Display_preference_for_translations', 'cobra' ) . '</small></em></td>'
             . "\n" .  '</tr>' . "\n";

        $content .= '<tr style="vertical-align:top;">' . "\n"
             .  '<td style="text-align: right" width="25%">' . get_string( 'Show_annotations', 'cobra' ) . '&nbsp;:</td>' . "\n"
             .  '<td nowrap="nowrap" width="25%">' . "\n"
             .  '<input id="descriptionsYes" type="radio" name="descriptions" value="ALWAYS"'
             . ( 'ALWAYS' == $prefs['descriptions'] ? $checkedstring : '' ) . '/>' . "\n"
             .  '<label for="descriptionsYes">' . get_string( 'Always', 'cobra' ) . '</label><br/>' . "\n"
             .  '<input id="descriptionsNo" type="radio" name="descriptions" value="NEVER"'
             . ( 'NEVER' == $prefs['descriptions'] ? $checkedstring : '' ) . '/>' . "\n"
             .  '<label for="descriptionsNo">' . get_string( 'Never', 'cobra' ) . '</label><br/>' . "\n"
             .  '<input id="descriptionsCond" type="radio" name="descriptions" value="CONDITIONAL"'
             . ( 'CONDITIONAL' == $prefs['descriptions'] ? $checkedstring : '' ) . '/>' . "\n"
             .  '<label for="descriptionsCond">' . get_string( 'When_no_concordances', 'cobra' ) . '</label>' . "\n"
             .  '</td>'
             .  '<td width="50%"><em><small>' . get_string( 'Display_preference_for_definitions-annotations', 'cobra' )
             . '</small></em></td>' . "\n"
             .  '</tr>' . "\n";

        $content .= '<tr style="vertical-align:top;">' . "\n"
             .  '<td style="text-align: right" width="25%">' . get_string( 'Show_illustrations', 'cobra' ) . '&nbsp;:</td>' . "\n"
             .  '<td nowrap="nowrap" width="25%">' . "\n"
             .  '<input id="illustrationsYes" type="radio" name="illustrations" value="SHOW"'
             . ( 'SHOW' == $prefs['illustrations'] ? $checkedstring : '' ) . '/>' . "\n"
             .  '<label for="illustrationsYes">' . get_string( 'yes' ) . '</label><br/>' . "\n"
             .  '<input id="illustrationsNo" type="radio" name="illustrations" value="HIDE"'
             . ( 'HIDE' == $prefs['illustrations'] ? $checkedstring : '' ) . '/>' . "\n"
             .  '<label for="illustrationsNo">' . get_string( 'no' ) . '</label>' . "\n"
             .  '</td> <td width="50%"><em><small>'
             . get_string( 'Choose_yes_to_display_possible_illustrations_associated_to_the_entry', 'cobra' )
             . '</small></em></td>' . "\n"
             .  '</tr>' . "\n";
        $content .= '<tr style="vertical-align:top;">' . "\n"
             .  '<td style="text-align: right" width="25%">' . get_string( 'Display_mode_for_examples', 'cobra' )
             . '&nbsp;:</td>' . "\n"
             .  '<td nowrap="nowrap" width="25%">' . "\n"
             .  '<input id="examplesYes" type="radio" name="examples" value="bi-text"'
             . ( 'bi-text' == $prefs['examples'] ? $checkedstring : '' ) . '/>' . "\n"
             .  '<label for="examplesYes">' . get_string( 'Bilingual', 'cobra' ) . '</label><br/>' . "\n"
             .  '<input id="examplesNo" type="radio" name="examples" value="mono"'
             . ( 'mono' == $prefs['examples'] ? $checkedstring : '' ) . '/>' . "\n"
             .  '<label for="examplesNo">' . get_string( 'Monolingual', 'cobra' ) . '</label>' . "\n"
              .  '</td>'
              .  '<td width="50%">&nbsp;</td>' . "\n"
              .  '</tr>' . "\n";

         $content .= '<tr style="vertical-align:top;">' . "\n"
             .  '<td style="text-align: right" width="25%">' . get_string( 'Show_occurrences', 'cobra' ) . '&nbsp;:</td>' . "\n"
             .  '<td nowrap="nowrap" width="25%">' . "\n"
             .  '<input id="occurrencesYes" type="radio" name="occurrences" value="SHOW"'
             . ( 'SHOW' == $prefs['occurrences'] ? $checkedstring : '' ) . '/>' . "\n"
             .  '<label for="occurrencesYes">' . get_string( 'yes' ) . '</label><br/>' . "\n"
             .  '<input id="occurrencesNo" type="radio" name="occurrences" value="HIDE"'
             . ( 'HIDE' == $prefs['occurrences'] ? $checkedstring : '' ) . '/>' . "\n"
             .  '<label for="occurrencesNo">' . get_string( 'no' ) . '</label>' . "\n"
             .  '</td> <td width="50%"><em><small>'
             . get_string( 'Choose_yes_to_display_possible_contexts_when_there_are_no_concordances', 'cobra' )
             . '</small></em></td>' . "\n"
             .  '</tr>' . "\n"
             .  '<tr>' . "\n"
             .  '<td style="text-align: right">' . get_string( 'Save_changes', 'cobra' ) . '&nbsp;:</td>' . "\n"
             .  '<td colspan="2"><input id="submit" type="submit" value="Ok" />&nbsp;'
             . '<a href="index.php"><input type="reset" value="Annuler" /></a>'
             . "\n"
             .  '</td>'
             .  '</tr>'
             .  '</table>' . "\n"
             .  '</form>' . "\n";
}

echo $content;

echo $OUTPUT->box_end();

// Finish the page.
echo $OUTPUT->footer();
