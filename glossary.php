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

$acceptedCmdList = array(  'rqExportPartial', 'rqExportFull', 'exExport', 'rqCompare', 'exCompare' );


$cmd = isset( $_REQUEST['cmd'] ) && in_array( $_REQUEST['cmd'], $acceptedCmdList ) ? $_REQUEST['cmd'] : null;

/*
 * Ouput
 */

// Print the page header.

$PAGE->set_url('/mod/cobra/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($cobra->name));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();

// Replace the following lines with you own code.
echo $OUTPUT->heading('Lecture de textes');

echo $OUTPUT->box_start('Glossaire' );

require_once dirname( __FILE__ ) . '/lib/cobra.lib.php';

$prefs = getCobraPreferences();
$language = $prefs['language'];

$htmlHeaders = "\n"
    .    '<script type="text/javascript" language="JavaScript">
function checkAll(pForm)
 {
   for (i=0, n=pForm.elements.length; i<n; i++)
   {
        var objName = pForm.elements[i].name;
        var objType = pForm.elements[i].type;
        if (objType = "checkbox")
        {
            box = eval(pForm.elements[i]);
              if (box.checked == false) box.checked = true;
        }
      }
 }

  function unCheckAll(pForm)
  {
      for (i=0, n=pForm.elements.length; i<n; i++)
      {
        var objName = pForm.elements[i].name;
        var objType = pForm.elements[i].type;
        if (objType = "checkbox")
        {
              box = eval(pForm.elements[i]);
              if (box.checked == true) box.checked = false;
        }
      }
 }

var checkflag = "false";

function check(pForm)
{
    if (checkflag == "false")
    {
        for (i=0, n=pForm.elements.length; i<n; i++)
        {
              var objName = pForm.elements[i].name;
              var objType = pForm.elements[i].type;
              if (objType = "checkbox")
              {
                   box = eval(pForm.elements[i]);
                if (box.checked == false) box.checked = true;
              }
          }
        checkflag = "true";
        return "Toutes les decocher";
    }
    else
    {
        for (i=0, n=pForm.elements.length; i<n; i++)
        {
              var objName = pForm.elements[i].name;
              var objType = pForm.elements[i].type;
              if (objType = "checkbox")
              {
                   box = eval(pForm.elements[i]);
                if (box.checked == true) box.checked = false;
              }
        }
        checkflag = "false";
        return "Cocher toutes les cases";
    }
}
</script>' . "\n\n";

    $display = '';
    $out = '';
    $collectionList = getRegisteredCollections('visible');

if (( $cmd != 'rqExportPartial' ) && ( $cmd != 'rqCompare' ))
{
   if (has_capability('mod/cobra:edit', $context))
    {
       
        
       $out .= '<a href="?cmd=rqExportFull?&id='.$id. '">'. get_string('ExportFullGlossary', 'cobra') . '</a> &nbsp; '. "\n"
               . '<a href="?cmd=rqExportPartial&id='.$id. '">'. get_string('PartialGlossary', 'cobra') . '</a> &nbsp; '. "\n"
               .'<a href="?cmd=rqCompare&id='.$id. '">'. get_string('Compare_text_with_glossary', 'cobra') . '</a> &nbsp; '. "\n";
    }

}

if ( $cmd == 'rqExportFull' )
{
    buildGlossary( );
    $glossary = array();
    foreach( $collectionList as $collection )
    {
        $textList = loadTextList( $collection['id_collection'], 'visible' );
        foreach ( $textList as $num=>$text )
        {
            $glossary2 = getGlossaryTableEntries( $text['id_text'] );
            if( array_key_exists( $text['id_text'], $glossary2 ) )
            {
                $glossary2 = getGlossaryEntryOfText( $glossary2[$text['id_text']], $text, $num );
                $glossary = array_merge( $glossary, $glossary2 );
            }
        }
    }
    $dialogBox->info( 'Glossary exported' );
    $display = exportCsvGlossaryEntries( $glossary );
}
else if ( $cmd  == 'rqExportPartial' )
{
    // afficher une checkbox pour chacun des textes de ce cours
    $display = '<table>';
    $position = 1;
    foreach( $collectionList as $collection )
    {
        $textList = loadTextList( $collection['id_collection'], 'visible' );

        foreach( $textList as $text )
        {
            // title
            $display  .= '<tr class="row" ><td style="min-width:33%;">' . "\n"
            .    '<input type="checkbox" value="true" id="textId' . $text->id . '" name="' . $text->id . '" />'
            .    htmlspecialchars( strip_tags( $text->title) )
            .    '</td>' . "\n"
            .    '</tr>';
        }
    }
    $display  .= '</table>';
    $out .= $display;
    /*$dialogBox->form('<form action="glossary.php?cmd=exExport" method="post"> ' . "\n"
        . '<input type=button value="Cocher toutes les cases" onClick="this.value=check(this.form)" />' . "\n"
        .  $display . "\n"
        . '<input value="' . get_lang ( 'Ok' ) . '" type="submit" name="submit" /> &nbsp;' . "\n"
                . claro_html_button (  'index.php' , get_lang ( 'Cancel' ) )
                . '</form>' . "\n"
                );*/

}
else if ( $cmd == 'exExport' )
{
    $glossary = array();
    foreach( $collectionList as $collection )
    {
        $textList = loadTextList( $collection['id_collection'], 'visible' );
        foreach ( $textList as $num=>$text )
        {
            if ( isset( $_REQUEST[$text['id_text']] ) )
            {
                $textId = $text['id_text'];
                $glossary2 = getGlossaryForText ( $textId );
                if (array_key_exists( $text['id_text'], $glossary2 ))
                {
                    $glossary2 = getGlossaryEntryOfText( $glossary2[$text['id_text']], $text, $num );
                    $glossary = array_merge( $glossary, $glossary2 );
                }
            }
        }
    }
    $dialogBox-> info ('Glossary exported');
    $display = exportCsvGlossaryEntries( $glossary );

}
else if ( $cmd == 'rqCompare')
{
    $display = '<table>';
    $position = 1;
    // afficher une checkbox pour chacun des textes de ce cours
    foreach( $collectionList as $collection )
    {
        $textList = loadTextList( $collection['id_collection'], 'visible' );

        foreach( $textList as $text )
        {
            // title
            $display  .= '<tr class="row"><td style="min-width:33%;">' . "\n"
            .    '<input type="checkbox" value="true" name="' . $text->id . '" id="textId' . $text->id . '"/>'
            .    htmlspecialchars( strip_tags( $text->title ) )
            .    '</td>' . "\n"
            .    '</tr>';
        }
    }
    $display  .= '</table>';
    // ajouter un champ textarea pour y mettre le texte de comparaison
  /*  $myText = new TextArea('myText');
    $myText->setOptionList(array("cols"=>"80","rows"=>"20","style"=>"border-width:1px;vertical-align:middle;"));
    $display .= 'Text to compare : ';
    $display .= $myText->render(); */
    $display .= 'Text to compare : ';
    $display .= '<textarea name="myText" id="myText" cols="80" rows="20" style="border-width:1px;vertical-align:middle;"></textarea>';
    $dialogBox->form('<form action="glossary.php?cmd=exCompare" method="post"> ' . "\n"
        . '<input type=button value="Cocher toutes les cases" onClick="this.value=check(this.form)" />' . "\n"
        .  $display . "\n"
        . '<br />' . "\n"
        . '<input value="' . get_lang ( 'Ok' ) . '" type="submit" name="submit" />&nbsp;' . "\n"
                . claro_html_button (  'index.php' , get_lang ( 'Cancel' ) )
                . '</form>' . "\n"
                );
}
else if ( $cmd == 'exCompare')
{
	increaseScriptTime();
    $glossary = array();
    foreach( $collectionList as $collection )
    {
        $textList = loadTextList( $collection['id_collection'], 'visible' );
        foreach ( $textList as $num=>$text )
        {
            if ( isset( $_REQUEST[$text['id_text']] ) )
            {
                $textId = $text['id_text'];
                $glossary2 = getGlossaryForText ( $textId );
                if (array_key_exists( $text['id_text'], $glossary2 ))
                {
                    $glossary2 = getGlossaryEntryOfText( $glossary2[$text['id_text']], $text, $num );
                    $glossary = array_merge( $glossary, $glossary2 );
                }
            }
        }
    }

    list( $lemmaGlossary, $expGlossary ) = explodeGlossaryIntoLemmasAndExpression( $glossary );
    $glossaryLemmaId = explodeArrayOnKey ( $lemmaGlossary, 'id' );
    $myText = $_REQUEST['myText'];
    $newWords = '';
    $otherWords = '';
    $words =  returnListOfWordsInText ( $myText, $language );

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
                    $otherWords .= '<li> ' . get_lang('Possible Translation') . ' : '. $word . ' : '. $info . '</li>';
                }
            }
            if (!$trouve)
            {
                $newWords .= '<li> ' . get_lang('New Word') . ' : '. $word . '</li>';
            }
        }
        else
        {
            $newWords .= '<li> ' . get_lang('New Word') . ' : '. $word . '</li>';
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
