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
 * @copyright  2015 Laurence Dumortier
 * @license    
 */


require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once(dirname(__FILE__).'/lib/cobra.lib.php');
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

if (!has_capability('mod/cobra:edit', $context))
{
      redirect('view.php?id='.$cm->id); ;   
}
    //Prepare settings menu
    $sectionList = array( 'collections' => get_string( 'manage_text_collections','cobra' ), 'corpus' => get_string( 'corpus_selection','cobra' ), 'display' => get_string( 'display_preferences','cobra'));
    $currentSection = isset( $_REQUEST['section'] ) && array_key_exists( $_REQUEST['section'], $sectionList ) ? $_REQUEST['section'] : 'collections';
    
    //init request vars
    $acceptedCmdList = array( 'rqEditLabel', 'exEditLabel', 'exRemove', 'exAdd',
                              'selectionEdit', 'selectionSave', 'saveOrder', 'editOrder', 
                              'savePrefs', 'exRefresh' );
                          
    $cmd = isset( $_REQUEST['cmd'] ) && in_array( $_REQUEST['cmd'], $acceptedCmdList ) ? $_REQUEST['cmd'] : '';
    
    $collectionId = isset( $_REQUEST['collection'] ) && is_numeric( $_REQUEST['collection'] ) ? $_REQUEST['collection'] : 0;
    $remoteCollection = isset( $_REQUEST['remote_collection'] ) && is_numeric( $_REQUEST['remote_collection'] ) ? $_REQUEST['remote_collection'] : null;

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

/*
 * Other things you may want to set - remove if not needed.
 * $PAGE->set_cacheable(false);
 * $PAGE->set_focuscontrol('some-html-id');
 * $PAGE->add_body_class('cobra-'.$somevar);
 */

// on va ajouter le lien pour pouvoir utiliser les commandes ajax utiles au remplissage d'un questionnaire
 $PAGE->requires->jquery();
 $PAGE->requires->js('/mod/cobra/js/cobra.js');
 $PAGE->requires->js_init_call('M.mod_cobra.TextVisibility');
  $PAGE->requires->js_init_call('M.mod_cobra.TextMove');

// Output starts here.
echo $OUTPUT->header();

// Replace the following lines with you own code.
echo $OUTPUT->heading('Lecture de textes');

echo $OUTPUT->box_start('generalbox collection_content' );

$content = '';

$content .= '<a href="'.$_SERVER['PHP_SELF'].'?id='.$id.'&section=collections">'.get_string('manage_text_collections', 'cobra').'</a> &nbsp; ' . "\n"
        . '<a href="'.$_SERVER['PHP_SELF'].'?id='.$id.'&section=corpus">'.get_string('corpus_selection','cobra') . '</a> &nbsp; ' . "\n"
        . '<a href="'.$_SERVER['PHP_SELF'].'?id='.$id.'&section=display">' . get_string('display_preferences', 'cobra') .'</a>';
 if( 'collections' == $currentSection )
 {
     if ( 'exEditLabel' == $cmd )
        {
            $label = isset( $_REQUEST['label'] ) && is_string( $_REQUEST['label'] ) ? $_REQUEST['label'] : null;
               
            if( !empty( $label ) )
            {
                $collection = new ElexCollectionWrapper( $collectionId );
                $collection->load();
                $collection->setLocalName( $label );
                if ( $collection->update() )
                {
                    $dialogBox->success( get_string( 'Collection name changed' ) );
                }
                else
                {
                    $dialogBox->error( get_string( 'Unable to change collection name' ) );
                }
            }
            else
            {
                $dialogBox->error( get_string( 'Collection name cannot be empty' ) );
                $cmd = 'rqEditLabel';
            }
        }
       if ( 'rqEditLabel' == $cmd )
        {
            $collection = new CobraCollectionWrapper( $collectionId );
            $collection->load();
            $editForm = '<strong>' . get_string('edit_collection','cobra') . '</strong>' . "\n"
                     .  '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">' . "\n"
                     .  '<input type="hidden" name="claroFormId" value="' . uniqid( '' ) . '" />' . "\n"
                     .  '<input type="hidden" name="collection" value="' . $collectionId . '" />'."\n"
                     .  '<input type="hidden" name="cmd" value="exEditLabel" />'."\n"
                     .  '<label for="label">' . get_string( 'name' ) . ' : </label><br />' . "\n"
                     .  '<input type="text" name="label" id="label"'
                     .  ' value="' . $collection->getLocalName() . '" /><br /><br />' . "\n"
                     .  '<input type="submit" value="' . get_string( 'ok' ) . '" />&nbsp; '
                     //.  claro_html_button( $_SERVER['PHP_SELF'], get_string( 'cancel' ) )
                     .  '</form>' . "\n"
                     .  "\n";
        
                echo $OUTPUT->box($editForm );
        }
       elseif( 'exAdd' == $cmd && !empty( $remoteCollection ) )
      {
            $collection = new CobraCollectionWrapper();
            $collection->wrapRemote( $remoteCollection );
            $textList = load_remote_text_list( $collection->getId() );
            $position = 1;
            $saveMode = $collection->save();
            if( 'error' == $saveMode )
            {
                echo $OUTPUT->box( get_string( 'unable_register_collection','cobra' ) );
            }
            elseif( 'saved' == $saveMode )
            {
                foreach( $textList as $remoteText )
                {
                    $text = new CobraTextWrapper();
                    $text->setTextId( $remoteText['id'] );
                    $text->setCollectionId( $collection->getId() );
                    $text->set_position( $position++ );
                    $text->save();
                }
                echo $OUTPUT->box(get_string('text_collection_added','cobra'));
            }   
        }
        elseif( 'exRemove' == $cmd )
        {
            $collection = new CobraCollectionWrapper( $collectionId );
            if( remote_text_list( $collectionId ) )
            {
                if( !$collection->remove() )
                {
                    echo $OUTPUT->box(get_string('unable_unregister_collection', 'cobra' ) );
                }
            }
            else
            {
                echo $OUTPUT->box(get_string('unable_remove_texts_collection', 'cobra' ) );
            }
        }
         elseif( 'exRefresh' == $cmd )
        {
            $localCollection = new CobraCollectionWrapper( $collectionId );
            $localCollection->load();
            $remoteCollection = new CobraCollectionWrapper();
            $remoteCollection->wrapRemote( $collectionId );
            //refresh collection name if it has not be changed locally
            if( $localCollection->getLocalName() == $localCollection->getRemoteName() )
            {
                $localCollection->setLocalName( $remoteCollection->getRemoteName() );
            }
            $localCollection->setRemoteName( $remoteCollection->getRemoteName() );
            $localCollection->save();
            $remoteTextList = load_remote_text_list( $collectionId );
            $localTextList = load_text_list( $collectionId );
            $localTextIdList = array();
            //remove legacy texts
            $legacyTextCount = $removedTextCount = 0;
            foreach( $localTextList as $localText )
            {
                $localTextIdList[] = $localText->id_text;
                if( empty( $localText->title ) )                    
                {
                    $legacyTextCount++;
                    $text = new ElexTextWrapper();
                    $text->setTextId( $localText->id_text );
                    $text->load();
                    if( $text->remove() ) $removedTextCount++;
                }
            }
            if( $legacyTextCount != 0 )
            {
                $dialogBox->success( $removedTextCount . ' ' . get_string( 'legacy text(s) removed' ) ) . '</br>';
                if( $legacyTextCount != $removedTextCount )
                {
                    echo  '' . $legacyTextCount - $removedTextCount . ' ' . get_string( 'could_not_be_removed', 'cobra' ). '</br>' ;
                }
            }
            else 
            {
                echo get_string( 'No_text_to_remove', 'cobra' ) ;
            }
                
            //add new texts
            $newTextCount = $addedTextCount = 0;
            foreach( $remoteTextList as $remoteText )
            {
                if( in_array( $remoteText['id'], $localTextIdList ) ) continue;
                $newTextCount++;
                $text = new ElexTextWrapper();
                $text->setTextId( $remoteText['id'] );
                $text->setCollectionId( $collectionId );
                $text->setType( 'Lesson' );
                $text->set_position( ElexTextWrapper::getMaxPosition() +1 );
                if( $text->save() ) $addedTextCount++;
            }
            if( $newTextCount != 0 )
            {
                $dialogBox->success( $addedTextCount . ' ' . get_string( 'new text(s) added' ) );
                if( $newTextCount != $addedTextCount )
                {
                    echo '' . $newTextCount - $addedTextCount . ' ' . get_string( 'could_not_be_added', 'cobra' ) .'<br/> ';
                }
            }
            else 
            {
                echo get_string( 'No_text_to_add', 'cobra'  ). '<br/>';
            }
        }
 }
    elseif( 'corpus' == $currentSection )
    {
        $prefs = get_cobra_preferences();
        $tabCorpusType = returnValidListTypeCorpus( $prefs['language'] );
        if( 'saveOrder' == $cmd )
        {
            if( !clear_corpus_selection() ) 
            {
                echo 'error while saving preferences' . '<br>' ;
            }
            else 
            {
                $tab_new_ordre = array();
                foreach( $tabCorpusType as $corpusTypeInfo ) 
                {
                    $typeId = $corpusTypeInfo['id'];
                    if( isset( $_REQUEST[$typeId] ) && ( '' != $_REQUEST['ordre' . $typeId] ) )
                    {
                        $tab_new_ordre[$_REQUEST['ordre' . $typeId]] = $typeId;
                    }
                }
                ksort( $tab_new_ordre );
                foreach( $tab_new_ordre as $typeId )
                {
                    insert_corpus_type_display_order( $typeId );
                }
                 echo 'Concordances Order Saved' . '<br>';
            }
        }
    }
    elseif( 'display' == $currentSection )
    {
        if( 'savePrefs' == $cmd )
        {        
            $prefs = get_cobra_preferences();
            $prefs['gender'] = isset( $_REQUEST['gender'] ) && is_string( $_REQUEST['gender'] ) ? $_REQUEST['gender'] : $prefs['gender'];
            $prefs['ff'] = isset( $_REQUEST['ff'] ) && is_string( $_REQUEST['ff'] ) ? $_REQUEST['ff'] : $prefs['ff'];
            $prefs['translations'] = isset( $_REQUEST['translations'] ) && is_string( $_REQUEST['translations'] ) ? $_REQUEST['translations'] : $prefs['translations'];
            $prefs['illustrations'] = isset( $_REQUEST['illustrations'] ) && is_string( $_REQUEST['illustrations'] ) ? $_REQUEST['illustrations'] : $prefs['illustrations'];
            $prefs['examples'] = isset( $_REQUEST['examples'] ) && is_string( $_REQUEST['examples'] ) ? $_REQUEST['examples'] : $prefs['examples'];
            $prefs['occurrences'] = isset( $_REQUEST['occurrences'] ) && is_string( $_REQUEST['occurrences'] ) ? $_REQUEST['occurrences'] : $prefs['occurrences'];
            $prefs['descriptions'] = isset( $_REQUEST['descriptions'] ) && is_string( $_REQUEST['descriptions'] ) ? $_REQUEST['descriptions'] : $prefs['descriptions'];
            $prefs['player'] = isset( $_REQUEST['player'] ) && is_string( $_REQUEST['player'] ) ? $_REQUEST['player'] : $prefs['player'];
            $prefs['nextprevbuttons'] = isset( $_REQUEST['nextprevbuttons'] ) && is_string( $_REQUEST['nextprevbuttons'] ) ? $_REQUEST['nextprevbuttons'] : $prefs['nextprevbuttons'];
            
            if( !save_Cobra_preferences( $prefs ) )
            {
                echo ' probleme <br >' ;
            }
            else
            {
                echo  get_string( 'Display_preferences_updated' , 'cobra' ) . '<br>';
            }
        }
    }

    
    
     if( 'collections' == $currentSection )
 {
          $content .= '<h3 style="margin-left:24px;">' . 'Collections currently linked to your course' . '</h3>';
        $content .= '<blockquote>' . "\n"
            . '<table id="collectionList" class="claroTable emphaseLine" style="width:60%">' . "\n"
            . '<thead>' . "\n"
            . '<tr class="headerX">' . "\n"
                .'<th> &nbsp; </th>' . "\n" 
            . '<th>' . get_string( 'collection_name','cobra' ) . '</th>' . "\n"
            . '<th>' . get_string( 'edit' ) . '</th>' . "\n"
            . '<th>' . get_string( 'refresh' ) . '</th>' . "\n"
            . '<th>' . get_string( 'remove' ) . '</th>' . "\n"
            . '<th>' . get_string( 'move' ) . '</th>' . "\n"
            . '<th>' . get_string( 'visibility', 'cobra' ) . '</th>' . "\n"
            . '</tr>' . "\n"
            . '</thead>' . "\n"
            . '<tbody>' . "\n";
            
        $idList = array();     
        $registeredCollectionsList = get_registered_collections( 'all' );
        $idList = array();     
        //echo $OUTPUT->pix_icon('t/down', get_string('down')), array('title' => get_string('down'));
       
        $position = 1;
        foreach( $registeredCollectionsList as $collection )
        {  
            $content .= '<tr id="' . $collection['id_collection']  . '#collectionId" class="row" name="' . $position++ . '#pos">' . "\n"
                . '<td>' . $collection['local_label'] . '</td>' . "\n"
                . '<td align="center">' . "\n"
                . '<a href="' . $_SERVER['PHP_SELF'] . '?id='.$id.'&cmd=rqEditLabel&amp;collection=' . $collection['id_collection'] . '">' . "\n"
                . $OUTPUT->pix_icon('t/editstring',get_string('edit')) . "\n"
                . '</a></td>' . "\n"
                . '<td align="center">' . "\n"
                . '<a href="' . $_SERVER['PHP_SELF'] . '?id='.$id.'&cmd=exRefresh&amp;collection=' . $collection['id_collection'] . '">' . "\n"
                .  $OUTPUT->pix_icon('t/reload',get_string('reload')). "\n"
                . '</a></td>' . "\n"
                . '<td align="center">' . "\n"
                . '<a href="' . $_SERVER['PHP_SELF'] . '?id='.$id.'&cmd=exRemove&amp;collection=' . $collection['id_collection'] . '">' . "\n"
                .   $OUTPUT->pix_icon('t/delete',get_string('delete')). "\n"
                . '</a></td>' . "\n"
                //change position commands
                . '<td align="center">' . "\n"
                . '<a href="#" class="moveUp">' .   $OUTPUT->pix_icon('t/up',get_string('moveup')). '</a>'
                . '&nbsp;'
                . '<a href="#" class="moveDown">' .   $OUTPUT->pix_icon('t/down',get_string('movedown')) . '</a>'
                . '</td>' . "\n"
                //visibility commands
                . '<td align="center">' . "\n"
                . '<a href="#" class="setInvisible" '.( !$collection['visibility']  ? 'style="display:none"':'').'>' .   $OUTPUT->pix_icon('t/hide',get_string('hide')) . '</a>'
                . '<a href="#" class="setVisible" '.( $collection['visibility'] ? 'style="display:none"':'').'>' .  $OUTPUT->pix_icon('t/show',get_string('show')) . '</a>'
                . '</td>' . "\n"
                . '</tr>' . "\n";
            $idList[] = $collection['id_collection'];
        }
        if (!sizeof($registeredCollectionsList))
        {
            $content .= '<tr><td colspan="6" align="center"> / </td> </tr>';
        }
        $content .= '</tbody>' . "\n"
            . '</table>' . "\n"
            . '</blockquote>' . "\n";
        
        $content .= '<h3 style="margin-left:24px;">' . get_string( 'collections_available','cobra' ) . '</h3>';
        $content .= '<blockquote>' . "\n"
            . '<table class="claroTable emphaseLine" style="width:60%">' . "\n"
            . '<thead>' . "\n"
            . '<tr class="headerX">' . "\n"
            . '<th>' . get_string( 'collection_name','cobra' ) . '</th>' . "\n"
            . '<th>' . get_string( 'add' ) . '</th>' . "\n"
            . '</tr>' . "\n"
            . '</thead>' . "\n"
            . '<tbody>' . "\n";
        $availableCollectionsList = get_filtered_cobra_collections( $cobra->language, $idList );
        foreach( $availableCollectionsList as $collection )
        {
            $content .= '<tr>' . "\n"
                . '<td>' . $collection['label'] . '</td>' . "\n"
                . '<td align="center">' . "\n"
                . '<a href="' . $_SERVER['PHP_SELF'] . '?id='.$id.'&cmd=exAdd&amp;remote_collection=' . $collection['id'] . '">' . "\n"
                . $OUTPUT->pix_icon('t/check',get_string('add_collection','cobra')) . "\n"
                . '</a></td>' . "\n"
                . '</tr>' . "\n";
        }
        $content .= '</tbody>' . "\n"
            . '</table>' . "\n"
            . '</blockquote>' . "\n";
     }
     elseif( 'corpus' == $currentSection )
    {
        $ordreTypeList = getCorpusTypeDisplayOrder();
        $list = '';          
        foreach( $tabCorpusType as $corpusTypeInfo ) 
        {
            $typeId = (int)$corpusTypeInfo['id'];
            $couleur = findBackGround( $typeId );
            $corpusTypeName = $corpusTypeInfo['name'];
                   
            if( corpus_type_exists( $typeId, $prefs['language'] ) )
            {
                $type_selected = '';
                if( in_array( $typeId, $ordreTypeList ) )
                {
                    $type_selected = ' checked="checked"';
                }
                $keys = array_keys( $ordreTypeList , $typeId );
                $list .= '<tr><td>';
                $list .=  '<input type="checkbox" name="' . $typeId . '" value="true"' . $type_selected . '></td>'
                       .  '<td class="' . $couleur . '"> ' . $corpusTypeName . '</td>'
                       .  '<td> <select name="ordre'. $typeId . '">' . "\n"
                       .  '<option value="" ' . ( '' == $type_selected ? ' selected="selected"' : '' )
                       .  '>&nbsp; </option>';       
    
                for( $i=1; $i <= sizeof( $tabCorpusType ); $i++ )
                {
                    $list .= '<option value="' . $i . '"' ;
                    if( in_array( $i, array_keys( $ordreTypeList, $typeId ) ) )
                    { 
                        $list .= ' selected="selected" ';                        
                    }
                    $list .= '>' . $i . '</option>';                                                           
                }   
                $list .= '</select>' . "\n" . '</td></tr>';
            }
            
        }
    
        $form = '<div class="claroDialogBox">' . "\n"
              . '<form action="' . $_SERVER['PHP_SELF'] . '?id='.$id.'&section=corpus&amp;cmd=saveOrder" method="post"> ' . "\n" 
              . '<table> <thead> <tr> <th> &nbsp; </th><th> Type de corpus </th> <th> Ordre </th> </tr> </thead>'
              . $list
              . '<tr><td colspan="3" style="text-align:center;">' . "\n"
              . '<input value="' . get_string ( 'Ok', 'cobra' ) . '" type="submit" name="submit"/>&nbsp;' . "\n"
              //. claro_html_button ( 'index.php' , get_string ( 'Cancel' ) )
              . '</td></tr>' . "\n"
              . '</table>' . "\n"
              . '</form>' . "\n"
              . '</div>' . "\n";
        $content .= $form;
    }
    elseif( 'display' == $currentSection )
    {
        if( !isset( $prefs ) ) $prefs = get_cobra_preferences();

        $checkedString = ' checked="checked"';
        $content .= '<form method="post" action="' . $_SERVER['PHP_SELF'] . '?id='.$id.'&section=display">' . "\n"
             .  '<input type="hidden" name="cmd" value="savePrefs" />' . "\n"    
             .  '<table border="0" cellpadding="5" width="100%">' . "\n"
            .  '<tr style="vertical-align:top;">' . "\n"
            .  '<td style="text-align: right" width="25%">' . get_string( 'Previous_and_Next_buttons','cobra' ) . '&nbsp;:</td>' . "\n"
            .  '<td nowrap="nowrap" width="25%">' . "\n"
            .  '<input id="nextPrevYes" type="radio" name="nextprevbuttons" value="SHOW"' . ( 'SHOW' == $prefs['nextprevbuttons'] ? $checkedString : '' ) . '/>' . "\n"
            .  '<label for="nextPrevYes">' . get_string( 'yes' ) . '</label><br/>' . "\n"
            .  '<input id="nextPrevNo" type="radio" name="nextprevbuttons" value="HIDE"' . ( 'HIDE' == $prefs['nextprevbuttons'] ? $checkedString : '' ) . '/>' . "\n"
            .  '<label for="nextPrevNo">' . get_string( 'no' ) . '</label>' . "\n"
            .  '</td>'
            .  '<td width="50%"><em><small>' . get_string( 'Display_Prev_and_Next_buttons', 'cobra' ) . '</small></em></td>' . "\n"
            .  '</tr>' . "\n"
             .  '<tr style="vertical-align:top;">' . "\n"
             .  '<td style="text-align: right" width="25%">' . get_string ('MP3_player', 'cobra' ) . '&nbsp;:</td>' . "\n"
             .  '<td nowrap="nowrap" width="25%">' . "\n"
             .  '<input id="playerYes" type="radio" name="player" value="SHOW"' . ( 'SHOW' == $prefs['player'] ? $checkedString : '' ) . '/>' . "\n"
             .  '<label for="playerYes">' . get_string( 'yes' ) . '</label><br/>' . "\n"
             .  '<input id="playerNo" type="radio" name="player" value="HIDE"' . ( 'HIDE' == $prefs['player'] ? $checkedString : '' ) . '/>' . "\n"
             .  '<label for="playerNo">' . get_string( 'no' ) . '</label>' . "\n"
             .  '</td>'
             .  '<td width="50%"><em><small>' . get_string ('Show_MP3_player', 'cobra' ) . '</small></em></td>' . "\n"
             .  '</tr>' . "\n"
             .  '<tr style="vertical-align:top;">' . "\n"
             .  '<td style="text-align: right" width="25%">' . get_string( 'Display_gender', 'cobra' ) . '&nbsp;:</td>' . "\n"
             .  '<td nowrap="nowrap" width="25%">' . "\n"
             .  '<input id="genderYes" type="radio" name="gender" value="SHOW"' . ( 'SHOW' == $prefs['gender'] ? $checkedString : '' ) . '/>' . "\n"
             .  '<label for="genderYes">' . get_string ('yes' ) . '</label><br/>' . "\n"
             .  '<input id="genderNo" type="radio" name="gender" value="HIDE"' . ( 'HIDE' == $prefs['gender'] ? $checkedString : '' ) . '/>' . "\n"
             .  '<label for="genderNo">' . get_string( 'no' ) . '</label>' . "\n"
             .  '</td>'
             .  '<td width="50%"><em><small>' . get_string( 'Only_for_Dutch_courses', 'cobra' ) . '</small></em></td>' . "\n"
             .  '</tr>' . "\n"
             .  '<tr style="vertical-align:top;">' . "\n"
             .  '<td style="text-align: right" width="25%">' . get_string( 'All_inflected_forms','cobra' ) . '&nbsp;:</td>' . "\n"
             .  '<td nowrap="nowrap" width="25%">' . "\n"
             .  '<input id="ffYes" type="radio" name="ff" value="SHOW"' . ( 'SHOW' == $prefs['ff'] ? $checkedString : '' ) . '/>' . "\n"
             .  '<label for="ffYes">' . get_string( 'yes' ) . '</label><br/>' . "\n"
             .  '<input id="ffNo" type="radio" name="ff" value="HIDE"' . ( 'HIDE' == $prefs['ff'] ? $checkedString : '' ) . '/>' . "\n"
             .  '<label for="ffNo">' . get_string( 'no' ) . '</label>' . "\n"
             .  '</td>'
             .  '<td width="50%"><em><small>' . get_string( 'Choose_yes_to_display_all_inflected_forms_in_lexical_card', 'cobra' ) . '</small></em></td>' . "\n"
             .  '</tr>' . "\n";
        
        $content .= '<tr style="vertical-align:top;">' . "\n"
             .  '<td style="text-align: right" width="25%">' . get_string( 'Show_translations', 'cobra' ) . '&nbsp;:</td>' . "\n"
             .  '<td nowrap="nowrap" width="25%">' . "\n"
             .  '<input id="translationsYes" type="radio" name="translations" value="ALWAYS"' . ( 'ALWAYS' == $prefs['translations'] ? $checkedString : '' ) . '/>' . "\n"
             .  '<label for="translationsYes">' . get_string( 'Always', 'cobra' ) . '</label><br/>' . "\n"
             .  '<input id="translationsNo" type="radio" name="translations" value="NEVER"' . ( 'NEVER' == $prefs['translations'] ? $checkedString : '' ) . '/>' . "\n"
             .  '<label for="translationsNo">' . get_string( 'Never', 'cobra' ) . '</label><br/>' . "\n"
             .  '<input id="translationsCond" type="radio" name="translations" value="CONDITIONAL"' . ( 'CONDITIONAL' == $prefs['translations'] ? $checkedString : '' ) . '/>' . "\n"
             .  '<label for="translationsCond">' . get_string( 'When_no_concordances', 'cobra' ) . '</label>' . "\n"
             .  '</td>'
             .  '<td width="50%"><em><small>' . get_string( 'Display_preference_for_translations', 'cobra' ) . '</small></em></td>' . "\n"
             .  '</tr>' . "\n";
        
        $content .= '<tr style="vertical-align:top;">' . "\n"
             .  '<td style="text-align: right" width="25%">' . get_string( 'Show_annotations', 'cobra' ) . '&nbsp;:</td>' . "\n"
             .  '<td nowrap="nowrap" width="25%">' . "\n"
             .  '<input id="descriptionsYes" type="radio" name="descriptions" value="ALWAYS"' . ( 'ALWAYS' == $prefs['descriptions'] ? $checkedString : '' ) . '/>' . "\n"
             .  '<label for="descriptionsYes">' . get_string( 'Always', 'cobra' ) . '</label><br/>' . "\n"
             .  '<input id="descriptionsNo" type="radio" name="descriptions" value="NEVER"' . ( 'NEVER' == $prefs['descriptions'] ? $checkedString : '' ) . '/>' . "\n"
             .  '<label for="descriptionsNo">' . get_string( 'Never', 'cobra' ) . '</label><br/>' . "\n"
             .  '<input id="descriptionsCond" type="radio" name="descriptions" value="CONDITIONAL"' . ( 'CONDITIONAL' == $prefs['descriptions'] ? $checkedString : '' ) . '/>' . "\n"
             .  '<label for="descriptionsCond">' . get_string( 'When_no_concordances', 'cobra' ) . '</label>' . "\n"
             .  '</td>'
             .  '<td width="50%"><em><small>' . get_string( 'Display_preference_for_definitions-annotations', 'cobra' ) . '</small></em></td>' . "\n"
             .  '</tr>' . "\n";

       /* if( get_conf( 'allowIllustrations', false ) )
        {*/
            $content .= '<tr style="vertical-align:top;">' . "\n"
                 .  '<td style="text-align: right" width="25%">' . get_string( 'Show_illustrations','cobra' ) . '&nbsp;:</td>' . "\n"
                 .  '<td nowrap="nowrap" width="25%">' . "\n"
                 .  '<input id="illustrationsYes" type="radio" name="illustrations" value="SHOW"' . ( 'SHOW' == $prefs['illustrations'] ? $checkedString : '' ) . '/>' . "\n"
                 .  '<label for="illustrationsYes">' . get_string( 'yes' ) . '</label><br/>' . "\n"
                 .  '<input id="illustrationsNo" type="radio" name="illustrations" value="HIDE"' . ( 'HIDE' == $prefs['illustrations'] ? $checkedString : '' ) . '/>' . "\n"
                 .  '<label for="illustrationsNo">' . get_string( 'no' ) . '</label>' . "\n"
                 .  '</td>'
                 .  '<td width="50%"><em><small>' . get_string( 'Choose_yes_to_display_possible_illustrations_associated_to_the_entry','cobra' ) . '</small></em></td>' . "\n"
                 .  '</tr>' . "\n";
     //   }
        $content .= '<tr style="vertical-align:top;">' . "\n"
             .  '<td style="text-align: right" width="25%">' . get_string( 'Display_mode_for_examples', 'cobra' ) . '&nbsp;:</td>' . "\n"
             .  '<td nowrap="nowrap" width="25%">' . "\n"
             .  '<input id="examplesYes" type="radio" name="examples" value="bi-text"' . ( 'bi-text' == $prefs['examples'] ? $checkedString : '' ) . '/>' . "\n"
             .  '<label for="examplesYes">' . get_string( 'Bilingual', 'cobra' ) . '</label><br/>' . "\n"
             .  '<input id="examplesNo" type="radio" name="examples" value="mono"' . ( 'mono' == $prefs['examples'] ? $checkedString : '' ) . '/>' . "\n"
              .  '<label for="examplesNo">' . get_string( 'Monolingual','cobra' ) . '</label>' . "\n"
              .  '</td>'
              .  '<td width="50%">&nbsp;</td>' . "\n"
              .  '</tr>' . "\n";
             
         $content .= '<tr style="vertical-align:top;">' . "\n"
             .  '<td style="text-align: right" width="25%">' . get_string( 'Show_occurrences', 'cobra' ) . '&nbsp;:</td>' . "\n"
             .  '<td nowrap="nowrap" width="25%">' . "\n"
             .  '<input id="occurrencesYes" type="radio" name="occurrences" value="SHOW"' . ( 'SHOW' == $prefs['occurrences'] ? $checkedString : '' ) . '/>' . "\n"
             .  '<label for="occurrencesYes">' . get_string( 'yes' ) . '</label><br/>' . "\n"
             .  '<input id="occurrencesNo" type="radio" name="occurrences" value="HIDE"' . ( 'HIDE' == $prefs['occurrences'] ? $checkedString : '' ) . '/>' . "\n"
              .  '<label for="occurrencesNo">' . get_string( 'no' ) . '</label>' . "\n"
              .  '</td>'
               .  '<td width="50%"><em><small>' . get_string( 'Choose_yes_to_display_possible_contexts_when_there_are_no_concordances', 'cobra' ) . '</small></em></td>' . "\n"
              .  '</tr>' . "\n"
             .  '<tr>' . "\n"
             .  '<td style="text-align: right">' . get_string( 'Save_changes', 'cobra' ) . '&nbsp;:</td>' . "\n"
             .  '<td colspan="2"><input id="submit" type="submit" value="Ok" />&nbsp;<a href="index.php"><input type="reset" value="Annuler" /></a>' . "\n"
             .  '</td>'
             .  '</tr>'       
             .  '</table>' . "\n"
             .  '</form>' . "\n";
    }

echo $content;

echo $OUTPUT->box_end();

// Finish the page.
echo $OUTPUT->footer();
