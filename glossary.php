<?php

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once(dirname(__FILE__).'/lib/cobra.lib.php');
require_once(dirname(__FILE__).'/lib/glossary.lib.php');
require_once(dirname(__FILE__).'/lib/cobraremoteservice.class.php');
require_once(dirname(__FILE__).'/lib/cobracollectionwrapper.class.php');
require_once($CFG->libdir . '/csvlib.class.php');

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

// Security check
require_login($course, true, $cm);

$context = context_module::instance($cm->id);

if (!has_capability('mod/cobra:edit', $context))
{
      redirect('view.php?id='.$cm->id); ;   
}

/*
 * init request vars
 */

$acceptedCmdList = array(  'rqExport', 'exExport', 'rqCompare', 'exCompare' );

$cmd = isset( $_REQUEST['cmd'] ) && in_array( $_REQUEST['cmd'], $acceptedCmdList ) ? $_REQUEST['cmd'] : null;

 $collectionList = get_registered_collections('visible');

 if ( $cmd == 'exExport' )
{
    $glossary = array();  
    foreach( $collectionList as $collection )
    {
        $textList = load_text_list( $collection['id_collection'], 'visible' );
    
        foreach ( $textList as $num=>$text )
        {         
            if (array_key_exists($text->id,$_REQUEST))
            {
                $textId = $text->id_text;
                $glossary2 = get_glossary_for_text ( $textId );
                if (array_key_exists( $text->id_text, $glossary2 ))
                {
                    $glossary2 = getGlossaryEntryOfText( $glossary2[$text->id_text], $text, $num );
                    $glossary = array_merge( $glossary, $glossary2 );
                }
            }
        }
    }   
    export_glossary ($glossary);
   //$display = exportCsvGlossaryEntries( $glossary );

}
/*
 * Ouput
 */

// Print the page header.

$PAGE->set_url('/mod/cobra/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($cobra->name));
$PAGE->set_heading(format_string($course->fullname));

 $PAGE->requires->jquery();
 $PAGE->requires->js('/mod/cobra/js/cobra.js');
 $PAGE->requires->js_init_call('M.mod_cobra.SelectAll');

echo $OUTPUT->header();

// Replace the following lines with you own code.
echo $OUTPUT->heading('Lecture de textes');

echo $OUTPUT->box_start('Glossaire' );

require_once dirname( __FILE__ ) . '/lib/cobra.lib.php';

$prefs = get_cobra_preferences();
$language = $prefs['language'];

$display = '';
$out = '';
 

if (( $cmd != 'rqExport' ) && ( $cmd != 'rqCompare' ))
{
   if (has_capability('mod/cobra:edit', $context))
    {
               
       $out .= '<a href="?cmd=rqExport&id='.$id. '">'. get_string('ExportGlossary', 'cobra') . '</a> &nbsp; '. "\n"
               .'<a href="?cmd=rqCompare&id='.$id. '">'. get_string('Compare_text_with_glossary', 'cobra') . '</a> &nbsp; '. "\n";
    }
}

 if ( $cmd  == 'rqExport' )
{
    // afficher une checkbox pour chacun des textes de ce cours
     $display = '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">' . "\n";
    $display .= '<table>';
    $display .= '<tr> <td colspan="2"><input type="checkbox" class="selectall" id="selectall"  >'.get_string('checkall_uncheckall', 'cobra') .'</td></tr>';
    foreach( $collectionList as $collection )
    {
        $textList = load_text_list( $collection['id_collection'], 'visible' );

        foreach( $textList as $text )
        {
            // title
            $display  .= '<tr class="row" ><td style="min-width:33%;">' . "\n"
            .    '<input class="checkbox" type="checkbox" value="true" id="textId' . $text->id . '" name="' . $text->id . '" />'
            .    htmlspecialchars( strip_tags( $text->title) )
            .    '</td>' . "\n"
            .    '</tr>';
        }
    }
    $display .= '<tr><td colspan="2" align="center"><input type="submit" value="' . get_string( 'ok' ) . '" />&nbsp; </td></tr>';    
    $display  .= '</table>';
    $display .= '<input type="hidden" name="cmd" value="exExport" >';
     $display .= '<input type="hidden" name="id" value="'.$id. '" >';
    $display .= '</form>';
    $out .= $display;
    /*$dialogBox->form('<form action="glossary.php?cmd=exExport" method="post"> ' . "\n"
        . '<input type=button value="Cocher toutes les cases" onClick="this.value=check(this.form)" />' . "\n"
        .  $display . "\n"
        . '<input value="' . get_lang ( 'Ok' ) . '" type="submit" name="submit" /> &nbsp;' . "\n"
                . claro_html_button (  'index.php' , get_lang ( 'Cancel' ) )
                . '</form>' . "\n"
                );*/

}
else if ( $cmd == 'rqCompare')
{
    foreach( $collectionList as $collection )
    {
        $textList = load_text_list( $collection['id_collection'], 'visible' );
        $display = '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">' . "\n";
        $display .= '<input type="hidden" name="cmd" value="exCompare" >';
        $display .= '<input type="hidden" name="id" value="'.$id. '" >';
        $display .= '<table>';
        $display .= '<tr> <td colspan="2"><input type="checkbox" class="selectall" id="selectall"  >'.get_string('checkall_uncheckall', 'cobra') .'</td></tr>';
   
        foreach( $textList as $text )
        {
            // title
                // afficher une checkbox pour chacun des textes de ce cours
            $display  .= '<tr class="row"><td style="min-width:33%;">' . "\n"
            .    '<input type="checkbox" value="true" name="' . $text->id . '" id="textId' . $text->id . '"/>'
            .    htmlspecialchars( strip_tags( $text->title ) )
            .    '</td>' . "\n"
            .    '</tr>';
        }
    }
    $display  .= '</table>';
    
   $display .= get_string('Text','cobra');
    $display .= '<textarea name="myText" id="myText" cols="80" rows="20" style="border-width:1px;vertical-align:middle;"></textarea>'
        . '<input value="' . get_string ( 'ok' ) . '" type="submit" name="submit" />&nbsp;' . "\n"
               
                . '</form>' . "\n";
    $out .= $display;
}
else if ( $cmd == 'exCompare')
{
	increaseScriptTime();
    $glossary = array();
    foreach( $collectionList as $collection )
    {
        $textList = load_text_list( $collection['id_collection'], 'visible' );
        foreach ( $textList as $num=>$text )
        {
            if (array_key_exists($text->id,$_REQUEST))
            {
                $textId = $text->id;
                $glossary2 = get_glossary_for_text ( $textId );
                if (array_key_exists( $textId, $glossary2 ))
                {
                    $glossary2 = getGlossaryEntryOfText( $glossary2[$text->id], $text, $num );
                    $glossary = array_merge( $glossary, $glossary2 );
                }
            }
        }
    }

    list( $lemmaGlossary, $expGlossary ) = explode_glossary_into_lemmas_and_expression( $glossary );
    $glossaryLemmaId = explodeArrayOnKey ( $lemmaGlossary, 'id' );
    $myText = $_REQUEST['myText'];
    $newWords = '';
    $otherWords = '';
    $words =  return_list_of_words_in_text ( $myText, $language );

    foreach ( $words as $word )
    {
        $listFlexions = wordExistsAsFlexion ( $word, $language );
        if (sizeof( $listFlexions ) != 0)
        {
            $trouve = false;
            $listPossibleLemmas = get_lemmaCatList_from_ff( $word, $language );
            foreach ( $listPossibleLemmas as $lemmaId )
            {
                if (array_key_exists( $lemmaId, $glossaryLemmaId ))
                {
                    $trouve = true;
                    $info = $glossaryLemmaId[$lemmaId]['entry'] . ' ('.$glossaryLemmaId[$lemmaId]['category'].') - ' . $glossaryLemmaId[$lemmaId]['traduction'];
                    $otherWords .= '<li> ' . get_string('possible_translation','cobra') . ' : '. $word . ' : '. $info . '</li>';
                }
            }
            if (!$trouve)
            {
                $newWords .= '<li> ' . get_string('new_word','cobra') . ' : '. $word . '</li>';
            }
        }
        else
        {
            $newWords .= '<li> ' . get_string('new_word','cobra')  . ' : '. $word . '</li>';
        }
    }
    $out .= '<ul>';
    $out .= $newWords;
    $out .= $otherWords;
    $out .= '</ul>';
}

echo $out;


echo $OUTPUT->box_end();

// Finish the page.
echo $OUTPUT->footer();
