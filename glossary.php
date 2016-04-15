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
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/locallib.php');
require_once(__DIR__ . '/lib/glossarylib.php');
require_once(__DIR__ . '/lib/cobraremoteservice.php');
require_once(__DIR__ . '/lib/cobracollectionwrapper.php');
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

// Security check.
require_login($course, true, $cm);

$context = context_module::instance($cm->id);

if (!has_capability('mod/cobra:edit', $context)) {
      redirect('view.php?id='.$cm->id);
}

/*
 * Init request vars.
 */

$acceptedcmdlist = array(  'rqExport', 'exExport', 'rqCompare', 'exCompare' );

$cmd = isset( $_REQUEST['cmd'] ) && in_array( $_REQUEST['cmd'], $acceptedcmdlist ) ? $_REQUEST['cmd'] : null;

$collectionlist = cobra_get_registered_collections('visible');

if ($cmd == 'exExport') {
    $glossary = array();
    foreach ($collectionlist as $collection) {
        $textlist = cobra_load_text_list( $collection['id_collection'], 'visible' );
        foreach ($textlist as $num => $text) {
            if (array_key_exists($text->id, $_REQUEST)) {
                $textid = $text->id_text;
                $glossary2 = cobra_get_glossary_for_text ( $textid );
                if (array_key_exists( $text->id_text, $glossary2 )) {
                    $glossary2 = cobra_get_glossary_entry_of_text( $glossary2[$text->id_text], $text, $num );
                    $glossary = array_merge( $glossary, $glossary2 );
                }
            }
        }
    }
    cobra_export_glossary($glossary);
}

// Print the page header.

$PAGE->set_url('/mod/cobra/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($cobra->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->requires->css('/mod/cobra/css/cobra.css');

 $PAGE->requires->jquery();
 $PAGE->requires->js('/mod/cobra/js/cobra.js');
 $PAGE->requires->js_init_call('M.mod_cobra.SelectAll');

echo $OUTPUT->header();

// Replace the following lines with you own code.
echo $OUTPUT->heading(get_string('textreading', 'cobra'));

echo $OUTPUT->box_start('Glossaire');

$prefs = cobra_get_preferences();
$language = $prefs['language'];

$display = '';
$out = '';

$out .= '<a href="?cmd=rqExport&id='.$id. '">'. get_string('ExportGlossary', 'cobra') . '</a> &nbsp; '. "\n"
               .'<a href="?cmd=rqCompare&id='.$id. '">'. get_string('Compare_text_with_glossary', 'cobra') . '</a> &nbsp; '. "\n";

if ($cmd == 'rqExport') {
    // Show checkbox foreach text of this course.
    $display = '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">' . "\n";
    $display .= '<table>';
    $display .= '<tr> <td><input type="checkbox" class="selectall" id="selectall"  >'
            .get_string('checkall_uncheckall', 'cobra') .'</td></tr>';
    foreach ($collectionlist as $collection) {
        $textlist = cobra_load_text_list( $collection['id_collection'], 'visible' );
        foreach ($textlist as $text) {
            // Display Title.
            $display  .= '<tr><td style="min-width:33%;">' . "\n"
            .    '<input class="checkbox" type="checkbox" value="true" id="textId' . $text->id . '" name="' . $text->id . '" />'
            .    htmlspecialchars( strip_tags( $text->title) )
            .    '</td>' . "\n"
            .    '</tr>';
        }
    }
    $display .= '<tr><td align="center"><input type="submit" value="' . get_string( 'ok' ) . '" />&nbsp; </td></tr>';
    $display  .= '</table>';
    $display .= '<input type="hidden" name="cmd" value="exExport" >';
    $display .= '<input type="hidden" name="id" value="'.$id. '" >';
    $display .= '</form>';
    $out .= $display;
} else if ($cmd == 'rqCompare') {
    $display = '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">' . "\n";
    $display .= '<input type="hidden" name="cmd" value="exCompare" >';
    $display .= '<input type="hidden" name="id" value="'.$id. '" >';
    $display .= '<table>';
    $display .= '<tr> <td colspan="2"><input type="checkbox" class="selectall" id="selectall"  >'
        . get_string('checkall_uncheckall', 'cobra') .'</td></tr>';

    foreach ($collectionlist as $collection) {
        $textlist = cobra_load_text_list( $collection['id_collection'], 'visible' );
        foreach ($textlist as $text) {
            // Display checkbox foreach text.
            $display  .= '<tr><td style="min-width:33%;">' . "\n"
            .    '<input type="checkbox" value="true" name="' . $text->id . '" id="textId' . $text->id . '"/>'
            .    htmlspecialchars( strip_tags( $text->title ) )
            .    '</td>' . "\n"
            .    '</tr>';
        }
    }

    $display .= get_string('Text', 'cobra');
    $display .= '<tr><td><textarea name="myText" id="myText" cols="80" rows="20" style="border-width:1px;vertical-align:middle;">'
            . '</textarea></td></tr>'
            . '<tr><td align="center"><input value="' . get_string ( 'ok' )
            . '" type="submit" name="submit" />&nbsp;</td></tr>' . "\n" . '</table> </form>' . "\n";
    $out .= $display;
} else if ( $cmd == 'exCompare') {
    cobra_increase_script_time();
    $glossary = array();
    foreach ($collectionlist as $collection) {
        $textlist = cobra_load_text_list( $collection['id_collection'], 'visible' );
        foreach ($textlist as $num => $text) {
            if (array_key_exists($text->id, $_REQUEST)) {
                $textid = $text->id;
                $glossary2 = cobra_get_glossary_for_text ( $textid );
                if (array_key_exists( $textid, $glossary2 )) {
                    $glossary2 = cobra_get_glossary_entry_of_text( $glossary2[$text->id], $text, $num );
                    $glossary = array_merge( $glossary, $glossary2 );
                }
            }
        }
    }

    list( $lemmaglossary, $expglossary ) = cobra_explode_glossary_into_lemmas_and_expression( $glossary );
    $glossarylemmaid = cobra_explode_array_on_key ( $lemmaglossary, 'id' );
    $mytext = $_REQUEST['myText'];
    $newwords = '';
    $otherwords = '';
    $words = cobra_get_list_of_words_in_text ( $mytext, $language );

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
                    $otherwords .= '<li> ' . get_string('possible_translation', 'cobra') . ' : '. $word . ' : '
                            . utf8_encode($info) . '</li>';
                }
            }
            if (!$trouve) {
                $newwords .= '<li> ' . get_string('new_word', 'cobra') . ' : '. $word . '</li>';
            }
        } else {
            $newwords .= '<li> ' . get_string('new_word', 'cobra')  . ' : '. $word . '</li>';
        }
    }
    $out .= '<ul>';
    $out .= $newwords;
    $out .= $otherwords;
    $out .= '</ul>';
}

echo $out;

echo $OUTPUT->box_end();

// Finish the page.
echo $OUTPUT->footer();
