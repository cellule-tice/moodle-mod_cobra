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
 * @copyright  2016 - Cellule TICE - Unversite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/locallib.php');
require_once(__DIR__ . '/lib/glossarylib.php');
require_once(__DIR__ . '/lib/cobraremoteservice.php');
require_once(__DIR__ . '/lib/cobracollectionwrapper.php');
require_once($CFG->libdir . '/csvlib.class.php');



$id = optional_param('id', 0, PARAM_INT); // Course_module ID, or

if ($id) {
    $course     = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
    if (!cobra_is_used()) {
       // redirect vers la page de cours
        redirect('../course/view.php?id='.id);
    }
}

// Security check.
require_login($course, true);

$context = context_course::instance($course->id, MUST_EXIST);

if (!has_capability('mod/cobra:edit', $context)) {
    redirect('../course/view.php?id='.id);
}

/*
 * Init request vars.
 */


$cmd = optional_param('cmd', '', PARAM_ALPHANUM);
$acceptedcmdlist = array('rqexport', 'exexport', 'rqcompare', 'excompare');
if (!in_array($cmd, $acceptedcmdlist)) {
    $cmd = 'rqexport';
}

$out = '';

$textlist = get_cobra_text_list();

if ($cmd == 'exexport') {
    $glossary = array();
    foreach ($textlist as $text) {
        if ($_REQUEST['text_'.$text->id]) {
            $textid = $text->text;
            $glossary2 = cobra_get_glossary_for_text ( $textid );
            
            if (array_key_exists( $textid, $glossary2 )) {
                $glossary2 = cobra_get_glossary_entry_of_text( $glossary2[$text->text], $text, $text->id );
                $glossary = array_merge( $glossary, $glossary2 );
            }
        }
    } 
    cobra_export_glossary($glossary);    
}


// Print the page header.
$PAGE->set_url('/mod/cobra/glossary.php', array('id' => $context->id));
$PAGE->set_title(format_string($cobra->name));
$PAGE->set_heading(format_string($course->fullname));

$PAGE->requires->css('/mod/cobra/css/cobra.css');

echo $OUTPUT->header();

switch ($cmd) {
    case 'rqexport' : $heading = get_string('modulename', 'cobra') . ' - ' . get_string('exportglossary', 'cobra');;
        break;
    case 'rqcompare' : $heading = get_string('modulename', 'cobra') . ' - ' . get_string('comparetextwithglossary', 'cobra');
        break;
    case 'excompare' : $heading = get_string('modulename', 'cobra') . ' - ' . get_string('comparetextwithglossary', 'cobra');
        break;
}
echo $OUTPUT->heading($heading);

echo $OUTPUT->box_start();

if ($cmd == 'rqexport') {
    $url = new moodle_url('/mod/cobra/glossary.php',
        array(
            'id' => $id,
            'cmd' => 'exexport'
        )
    );
    $thisform = new cobra_edit_glossary_form ($url, array('textlist' => $textlist, 'compare' => false));
} else if ($cmd == 'rqcompare') {
    $url = new moodle_url('/mod/cobra/glossary.php',
        array(
            'id' => $id,
            'cmd' => 'excompare'
        )
    );
    $thisform = new cobra_edit_glossary_form ($url, array('textlist' => $textlist, 'compare' => true));
} else if ( $cmd == 'excompare') {
    cobra_increase_script_time();
    $glossary = array();   
    $language = '';
    foreach ($textlist as $text) {
        if ($_REQUEST['text_'.$text->id]) {
            $textid = $text->text;
            if ($language == '') {
                $language = $text->language;
            }
            $glossary2 = cobra_get_glossary_for_text ( $textid );
            if (array_key_exists( $textid, $glossary2 )) {
                $glossary2 = cobra_get_glossary_entry_of_text( $glossary2[$text->text], $text, $text->id );
                $glossary = array_merge( $glossary, $glossary2 );
            }
        }
    }

    list( $lemmaglossary, $expglossary ) = cobra_explode_glossary_into_lemmas_and_expression( $glossary );
    $glossarylemmaid = cobra_explode_array_on_key ( $lemmaglossary, 'id' );
    $mytext = isset($_REQUEST['mytext']) ? $_REQUEST['mytext'] : '';
    $newwords = '';
    $otherwords = '';
    $words = cobra_get_list_of_words_in_text ( $mytext, $language );
    
    $newwords = array();

    foreach ($words as $word) {
        $listflexions = cobra_word_exists_as_flexion ( $word, $language );
        if (count( $listflexions) != 0) {
            $trouve = false;
            $listpossiblelemmas = cobra_get_lemmacat_list_from_ff( $word, $language );
            foreach ($listpossiblelemmas as $lemmaid) {
                if (array_key_exists($lemmaid, $glossarylemmaid)) {
                    $trouve = true;
                    $info = $glossarylemmaid[$lemmaid]['entry'] . ' ('.$glossarylemmaid[$lemmaid]['category'].') - '
                        . $glossarylemmaid[$lemmaid]['traduction'];
                    $otherwords .= '<li> ' . get_string('possibletranslations', 'cobra') . ' : '. $word . ' : '
                        . utf8_encode($info) . '</li>';
                }
            }
            if (!$trouve) {
                $newwords[] = $word;
                //$newwords .= '<li> ' . get_string('newwords', 'cobra') . ' : '. $word . '</li>';
                $mytext = str_replace(' ' .$word. ' ', ' <span style="color:red">'. $word. '</span> ', $mytext);
                $mytext = str_replace(' ' .$word. ',', ' <span style="color:red">'. $word. '</span>,', $mytext);
                $mytext = str_replace(' ' .$word. '.', ' <span style="color:red">'. $word. '</span>.', $mytext);
            }
        } else {
             $newwords[] = $word;
            //$newwords .= '<li> ' . get_string('newwords', 'cobra')  . ' : '. $word . '</li>';
             $mytext = str_replace(' ' .$word.' ', ' <span style="color:red">'. $word. '</span> ', $mytext);
             $mytext = str_replace(' ' .$word. ',', ' <span style="color:red">'. $word. '</span>,', $mytext);
             $mytext = str_replace(' ' .$word. '.', ' <span style="color:red">'. $word. '</span>.', $mytext);
        }
    }
    
    $out .= html_writer::tag('div', get_string('text', 'mod_cobra') . ' : ' . $mytext);
    $out .= html_writer::tag('span', '&nbsp; ');
    
    $out .= '<ul>';
    //$out .= $newwords;
    $out .= '<li>' . get_string('newwords', 'cobra')  . ' : ' . implode(', ', $newwords) . '</li>'; 
    $out .= $otherwords;
    $out .= '</ul>';
}

if (!empty($thisform)) {
    echo $thisform->display();
}

echo $out;

echo $OUTPUT->box_end();

// Finish the page.
echo $OUTPUT->footer();
