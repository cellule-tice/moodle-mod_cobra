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
 * @copyright  2015 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Replace cobra with the name of your module and remove this line.

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->libdir . '/medialib.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once(dirname(__FILE__).'/lib/cobraremoteservice.class.php');
require_once(dirname(__FILE__).'/lib/cobracollectionwrapper.class.php');

  $textId = isset( $_REQUEST['id_text'] ) && is_numeric( $_REQUEST['id_text'] ) ? $_REQUEST['id_text'] : null;
    $collectionId = isset( $_REQUEST['id_collection'] ) && is_numeric( $_REQUEST['id_collection'] ) ? $_REQUEST['id_collection'] : null;
    if( is_null( $textId ) )
    {
        header( 'Location: ./index.php' );
        exit();
    }

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

$context = context_module::instance($cm->id);

require_login($course, true, $cm);

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

$PAGE->requires->css('/mod/cobra/css/cobra.css');

// on va ajouter le lien pour pouvoir utiliser les commandes ajax utiles au remplissage d'un questionnaire
 $PAGE->requires->jquery();
 $PAGE->requires->js('/mod/cobra/js/cobra.js');
 $PAGE->requires->js_init_call('M.mod_cobra.init');
 $PAGE->requires->js_init_call('M.mod_cobra.expression_on_click');
 $PAGE->requires->js_init_call('M.mod_cobra.lemma_on_click');
 $PAGE->requires->js_init_call('M.mod_cobra.showFullConcordance');
 $PAGE->requires->js_init_call('M.mod_cobra.showCard');

// Output starts here.
echo $OUTPUT->header();

// Replace the following lines with you own code.
echo $OUTPUT->heading('Lecture de textes');

echo $OUTPUT->box_start('generalbox collection_content' );

$content = '';

   //load content to display
    $collection = new CobraCollectionWrapper( $collectionId );
    $collection->load();
    $text = new CobraTextWrapper();
    $text->setTextId( $textId );
    $text->load();
    $preferences = get_cobra_preferences();
    $ccOrder = getCorpusTypeDisplayOrder();
    $order = implode( ',', $ccOrder );
    $preferences['ccOrder'] = $order;
    
    $encodeClic = 1;
    if (has_capability('mod/cobra:edit', $context))
    {
         $encodeClic = 0;   
    }
   
    $content .= '<div id="encode_clic"  name="'.$encodeClic.'" class="hidden"></div>';
    $content .= '<div id="language" class="hidden" name="' . $collection->getLanguage() . '">&nbsp;</div>';
   
    $content .= '<div id="id_text" class="hidden">' . $textId . '</div>';
    $content .= '<div id="courseLabel" class="hidden" name="' . $course->id . '">&nbsp;</div>';
    $content .= '<div id="userId" class="hidden" name="' . $USER->id . '">&nbsp;</div>';
    $i=0;
    foreach ($preferences as $key=>$info)
    {
         $content .= '<div id="preferences_'.$i. '_key" class="hidden" name="'.$key.'">'.$key.'</div>';
         $content .= '<div id="preferences_'.$i. '_value" class="hidden" name="'.$info.'">'.$info.'</div>';
         $i++;
    }
    $content .= '<div id="preferencesNb" class="hidden" name="'.sizeof($preferences).'">'.sizeof($preferences).'</div>';
    $content .= '<div class="top">';
    
     if( 'SHOW' == $preferences['nextprevbuttons'] )
    {
        $nextId = getNextTextId($text);
        $previousId = getPreviousTextId($text);
        $content .= '<ul class="commandList">';
        if($previousId) $content.= '<li style="padding-right:5px;"><a href="' . $_SERVER['PHP_SELF'] . '?id='.$id. '&id_text=' . $previousId . '&amp;id_collection=' . $collectionId . '#/' . $previousId . '">' . utf8_encode(get_string('previous_text', 'cobra')) . '</a></li>';
        if($nextId) $content .= '<li><a href="' . $_SERVER['PHP_SELF'] . '?id='.$id. '&id_text=' . $nextId . '&amp;id_collection=' . $collectionId . '#/' . $nextId . '">' . get_string('next_text','cobra') . '</a></li>';
        $content .= '</ul>';
    }
    
    $audioFileUrl = $text->getAudioFileUrl();
    if( !empty( $audioFileUrl ) && 'SHOW' == $preferences['player'] )
    {
        $content .= '<object width="300" height="15">
       <param name="src" value="'.$audioFileUrl.'">
       <param name="autoplay" value="false">
       <param name="controller" value="true">
       <embed src="'.$audioFileUrl.'" autostart="false" loop="false" width="300" height="15"
       controller="true"></embed>
       </object>';
    }
       
    
    $content .=  $text->formatHtml()
         . '<div id="card" class="card left">'
         . '</div></div>'
         . '<div class="bottom">'
         . '<div id="details" class="left">' 
         . '</div>'          
         . '<div id="full_concordance" class="right">'
         . '</div>'
         . '</div>'
         ;

echo $content;


echo $OUTPUT->box_end();

// Finish the page.
echo $OUTPUT->footer();
