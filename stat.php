<?php

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
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);

$context = context_module::instance($cm->id);

if (!has_capability('mod/cobra:edit', $context))
{
      redirect('view.php?id='.$cm->id); ;   
}


$view = ( isset( $_REQUEST['view'] ) && is_numeric( $_REQUEST['view'] ) ) ? $_REQUEST['view'] : null;
$cmd = (isset ( $_REQUEST['cmd'])) ? $_REQUEST['cmd'] : null;

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

echo $OUTPUT->header();

// Replace the following lines with you own code.
echo $OUTPUT->heading('Lecture de textes');

echo $OUTPUT->box_start('Statistics' );

$out = '';

if (!is_null($cmd))
{
    if ($cmd == 'cleanStats')
    {
          $out .= get_string('Clean_Clic_Stats', 'cobra');
         $out .= '<span class="warning"> ' . get_string('Delete_is_definitive._No_way_to_rollback', 'cobra') . '</span>';
         $thisForm = new mod_cleanStats_form($_SERVER['PHP_SELF'].'?id=' .$id . '&cmd=exDelete');
            $out = $thisForm->display();    
            
    // end else if $delete
    }
    else if ($cmd == 'exDelete')
    {
        // scope
        $acceptedScopeList = array( 'ALL', 'BEFORE' );

        if( isset($_REQUEST['scope']) && in_array($_REQUEST['scope'], $acceptedScopeList) )
        {
            $scope = $_REQUEST['scope'];
        }
        else
        {
            $scope = null;
        }
        // date
        if ( isset($_REQUEST['beforeDate'])
            && is_array($_REQUEST['beforeDate'])
            && array_key_exists('day',$_REQUEST['beforeDate'])
            && array_key_exists('month',$_REQUEST['beforeDate'])
            && array_key_exists('year',$_REQUEST['beforeDate'])
            && (bool) checkdate( $_REQUEST['beforeDate']['month'], $_REQUEST['beforeDate']['day'], $_REQUEST['beforeDate']['year'] ))
        {
            $beforeDate = mktime(0,0,0, $_REQUEST['beforeDate']['month'], $_REQUEST['beforeDate']['day'], $_REQUEST['beforeDate']['year'] );
            $myDate = $_REQUEST['beforeDate']['day'] . '/'.  $_REQUEST['beforeDate']['month'] . '/'. $_REQUEST['beforeDate']['year'];
        }
        else
        {
            $beforeDate = null;
        }

        if ('BEFORE' == $scope)
        {
            $beforeDate = null;
            if (isset($_REQUEST['before_date']))
            {
                 $beforeDate = mktime(0,0,0, $_REQUEST['before_date']['month'], $_REQUEST['before_date']['day'], $_REQUEST['before_date']['year'] );
            }
            if( !is_null($beforeDate) )
            {
                // execute delete before date
                if (cleanStatsBeforeDate ($course->id, $beforeDate))
                {
                    $myDate = $_REQUEST['before_date']['day'] . '/'.  $_REQUEST['before_date']['month'] . '/'. $_REQUEST['before_date']['year'];
                    $out .= '<span class="pre"> Clic stats deleted before ' . $myDate . '</span>';
                }
            }
            else
            {
                $out .= '<span class="pre"> date non valide</span>';
            }
        }
        elseif ('ALL' == $scope)
        {
            // delete all stats
            if (cleanAllStats($course->id))
            {
                 $out .= '<span class="pre"> All click stats deleted</span>';
            }
        }
    } // exDelete
}
else
{

$out .= '<ul>';
$out .= '<li><a href="' . $_SERVER['PHP_SELF'] . '?id='.$id.'&view=1">' . get_string( 'Display_entries_clicked_at_least_20_times' , 'cobra') . '</a></li>';
$out .= '<li><a href="' . $_SERVER['PHP_SELF'] . '?id='.$id.'&view=2">' . get_string( 'Display_the_10_most_frequently_clicked_entries_per_text', 'cobra' ) . '</a></li>';
$out .= '<li><a href="' . $_SERVER['PHP_SELF'] . '?id='.$id.'&view=3">' . get_string( 'Display_the_most_frequently_analysed_texts', 'cobra' ) . '</a></li>';
$out .= '<li><a href="' . $_SERVER['PHP_SELF'] . '?id='.$id.'&view=4">' . get_string( 'Display_statistics_by_text' , 'cobra') . '</a></li>';
$out .= '<li><a href="' . $_SERVER['PHP_SELF'] . '?id='.$id.'&view=5">' . get_string( 'Display_statistics_by_user', 'cobra' ) . '</a></li>';
$out .= '<li><a href="' . $_SERVER['PHP_SELF'] . '?id='.$id.'&cmd=cleanStats">' . get_string('Clean_Clic_Stats', 'cobra') .'</a></li>';
$out .= '</ul>';
}

if( !is_null( $view ) )
{
    switch ($view)
    {
    case '1' :
        $out .= '<h3><small>' . get_string( 'Display_entries_clicked_at_least_20_times', 'cobra' ) . '</small></h3>';
        $out .= '<table class="claroTable emphaseLine">'
             .  '<thead>'
             .  '<tr class="headerX">'
            // .  '<th> Graphique ... </th>'
             .  '<th> Nombre total de clics </th>'
             .  '<th>' . get_string( 'Lemma', 'cobra' ) . '</th>'
             .  '<th>' . get_string( 'Translation', 'cobra' ) . '</th>'
             .  '<th>' . get_string( 'Category', 'cobra' ) . '</th>'
             .  '</tr>'
             .  '</thead>';

        $list = getClickedEntries ($course->id, 20);
        foreach ($list as $id_entite_ling=>$nb)
        {     
            list( $conceptId, $construction, $entryType, $category ) = get_concept_info_from_ling_entity( $id_entite_ling );           
            $out .= '<tr>'
                 .  '<td>' . $nb . '</td>'
                 .  '<td>' . $construction . '</td>'
                 .  '<td>' . get_translations( $conceptId, $entryType ) . '</td>'
                 .  '<td>' . $category . '</td>'
                 .  '</tr>';
        }
        $out .= '</table>';
        break;

    case '2' :
        $out .= '<h3><small>' . get_string( 'Display_the_10_most_frequently_clicked_entries_per_text', 'cobra' ) . '</small></h3>';
        $collectionList = get_registered_collections( 'all' );
        foreach( $collectionList as $collection )
        {
            $textList = load_text_list( $collection['id_collection'], 'all' );
            //var_dump($textList);

            $out .= '<table class="claroTable">'
                 .  '<thead>'
                 .  '<tr class="superHeader"><th colspan="5">' . get_string( 'Collection', 'cobra' ) . '&nbsp;:&nbsp;' . $collection['local_label'] . '</th></tr>'
                 .  '<tr class="headerX">'
                 .  '<th> Texte </th>'
                 .  '<th> Nombre de clics </th>'
                 .  '<th>' . get_string( 'Lemma', 'cobra' ) . '</th>'
                 .  '<th>' . get_string( 'Translation', 'cobra' ) . '</th>'
                 .  '<th>' . get_string( 'Category', 'cobra' ) . '</th>'
                 .  '</tr>'
                 .  '</thead>';
            
 
             foreach ($textList as $textInfo)                 
             {
                $textId = $textInfo->id_text;
                $textTitle = $textInfo->title;
                $clicList = $DB->get_records_select('cobra_clic', "course='$course->id' AND id_text='$textId' AND nb_clics >= 10", array(), 'nb_clics DESC LIMIT 10', 'id_entite_ling, nb_clics');

                $nb_mot = 0;
                foreach ($clicList as $info2)
                { 
                    $id_entite_ling = $info2->id_entite_ling;
                    $nb_clics = $info2->nb_clics;
                    $nb_mot++;
                    $out .= '<tr><td>';
                    if( $nb_mot == 1 )
                    {
                        $out .=  $textTitle;
                    }
                    $out .= '&nbsp; </td>';
     
                    list( $conceptId, $construction, $entryType, $category ) = get_concept_info_from_ling_entity( $id_entite_ling );
                    
                    $out .= '<td>' . $nb_clics . '</td>'
                         .  '<td>' . $construction . '</td>'
                         .  '<td>' . get_translations( $conceptId, $entryType ) . '</td>'
                         .  '<td>' . $category . '</td>'
                         .  '</tr>';
                }
            }
            $out .= '</table>';
        }
        break;

    case '3' :
        $out .= '<h3><small>' . get_string( 'Display_the_most_frequently_analysed_texts', 'cobra' ) . '</small></h3>';
        $collectionList = get_registered_collections( 'all' );
        foreach( $collectionList as $collection )
        {
            $textList = load_text_list( $collection['id_collection'], 'all' );
            $textInfo = array();
            foreach( $textList as $text )
            {
                $textInfo[$text->id_text] = $text->title;
            }

            $out .= '<table class="claroTable emphaseLine">'
                 .  '<thead>'
                 .  '<tr class="superHeader"><th colspan="2">' . get_string( 'Collection', 'cobra' ) . '&nbsp;:&nbsp;' . $collection['local_label'] . '</th></tr>'
                 .  '<tr class="headerX">'
                 .  '<th> Nombre total de clics </th>'
                 .  '<th> Texte </th>'
                 .  '</tr>'
                 .  '</thead>';
            $tab_nb_clics = getClickedTextsFrequency($course->id);            
            foreach( $tab_nb_clics as $textId => $nb_total_clics )
            {
                if( isset( $textInfo[$textId] ) )
                {
                    $out .= '<tr>'
                         .  '<td>' . $nb_total_clics . '</td>'
                         .  '<td>' . $textInfo[$textId] . '</td>'
                         .  '</tr>';
                }
            }
            $out .= '</table>';
        }
        break;
       case '4' :
        $collectionList = get_registered_collections( 'all' );
        foreach( $collectionList as $collection )
        {
             $out .= '<table class="claroTable emphaseLine textList" width="100%" border="0" cellspacing="2" style="margin-bottom:20px;">' . "\n"
             .  '<thead>' . "\n"
             .  '<tr class="superHeader" align="center" valign="top"><th colspan="4">' . $collection['local_label'] . '</th></tr>' . "\n"
             .  '<tr class="headerX" align="center" valign="top">' . "\n"
             .  '<th>' . get_string( 'Text','cobra' ) . '</th>' . "\n"
             .  '<th>' . get_string( 'Nb_of_clickable_words','cobra' ) . '</th>' . "\n"
             .  '<th>' . get_string( 'Different_users','cobra' ) . '</th>' . "\n"
             .  '<th>' . get_string( 'Total_clic', 'cobra' ) . '</th>' . "\n";
            $textList = load_text_list( $collection['id_collection'], 'all' );
            foreach( $textList as $text )
            {              
                $out .= '<tr> <td>' . $text->title. '</td>' . "\n"
                        . '<td>' . getNbTagsInText ($text->id_text) . '</td>' . "\n"
                        . '<td> ' . sizeof(getDistinctAccessForText($text->id_text)).' </td>' . "\n"
                        . '<td> ' . getNbClicsForText($text->id_text). '</td> </tr>' . "\n";
            }
            $out .= '</table>';
        }
        break;
       case '5' :
     //   $userList = getUserList ();
        $userClicList = getUserListForClic ();
        if (!empty($userClicList))
        {
             $out .= '<table class="claroTable emphaseLine textList" width="100%" border="0" cellspacing="2" style="margin-bottom:20px;">' . "\n"
             .  '<thead>' . "\n"
             .  '<tr class="headerX" align="center" valign="top">' . "\n"
             .  '<th>' . get_string( 'User','cobra' ) . '</th>' . "\n"
             .  '<th>' . get_string( 'Nb_Texts', 'cobra' ) . '</th>' . "\n"
             .  '<th>' . get_string( 'Total_clic', 'cobra' ) . '</th>' . "\n";

            foreach ($userClicList as $userInfo)
            {
               /* if (in_array($userId,$userClicList ))
                {*/
                    $out .= '<tr> <td> '. $userInfo['lastName'] . ' ' . $userInfo['firstName'] . '</td>' . "\n"
                        . '<td> ' . getNbTextsForUser($userInfo['userId']) . '</td>' . "\n"
                        . '<td> ' . getNbClicForUser ($userInfo['userId']) . '</td></tr>'  . "\n";
               // }
            }
            if (hasAnonymousClic())
            {
                $out .= '<tr> <td> '. get_string('Anonymous','cobra') . '</td>' . "\n"
                        . '<td> ' . getNbTextsForUser('0') . '</td>' . "\n"
                        . '<td> ' . getNbClicForUser ('0') . '</td></tr>'  . "\n";
            }
            $out .= '</table>';
        }
       break;
    }
}


echo $out;
//echo format_text($out);


echo $OUTPUT->box_end();

// Finish the page.
echo $OUTPUT->footer();