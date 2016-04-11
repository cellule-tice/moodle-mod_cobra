<?php

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir . '/medialib.php');
require_once($CFG->dirroot . '/mod/cobra/lib.php');
require_once($CFG->dirroot . '/mod/cobra/locallib.php');
require_once($CFG->dirroot . '/mod/cobra/lib/cobraremoteservice.class.php');
require_once($CFG->dirroot . '/mod/cobra/lib/cobracollectionwrapper.class.php');
require_once($CFG->dirroot . '/mod/cobra/lib/glossary.lib.php');

$id = required_param('id', PARAM_INT);
$textid = required_param('id_text', PARAM_INT);

list($course, $cm) = get_course_and_cm_from_cmid($id, 'cobra');
$cobra = $DB->get_record('cobra', array('id' => $cm->instance), '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/cobra:view', $context);

// Add event management here

$PAGE->set_url('/mod/cobra/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($cobra->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->add_body_class('noblocks');

$PAGE->requires->css('/mod/cobra/css/cobra.css');

// on va ajouter le lien pour pouvoir utiliser les commandes ajax utiles au remplissage d'un questionnaire
$PAGE->requires->jquery();
$PAGE->requires->js('/mod/cobra/js/cobra.js');
$PAGE->requires->js('/mod/cobra/js/angular.js');
$PAGE->requires->js('/mod/cobra/js/angular-route.js');
$PAGE->requires->js('/mod/cobra/js/ui-router.js');
$PAGE->requires->js('/mod/cobra/js/app.js');
$PAGE->requires->js('/mod/cobra/js/components/controllers.js');
$PAGE->requires->js('/mod/cobra/js/components/services.js');
$PAGE->requires->js('/mod/cobra/js/components/filters.js');
$PAGE->requires->js_init_call('M.mod_cobra.init_no_blocks');
$PAGE->requires->js_init_call('M.mod_cobra.expression_on_click');
$PAGE->requires->js_init_call('M.mod_cobra.lemma_on_click');
$PAGE->requires->js_init_call('M.mod_cobra.showFullConcordance');
$PAGE->requires->js_init_call('M.mod_cobra.showCard');
$PAGE->requires->js_init_call('M.mod_cobra.add_to_glossary');
$PAGE->requires->js_init_call('M.mod_cobra.remove_from_glossary');

echo $OUTPUT->header();

$content = '';
// Load content to display
$text = new CobraTextWrapper();
$text->set_text_id( $textid );
$text->load();
$preferences = get_cobra_preferences();
$ccorder = get_corpus_type_display_order();
$order = implode( ',', $ccorder );
$preferences['ccOrder'] = $order;
$encodeclic = 1;
if (has_capability('mod/cobra:edit', $context)) {
   // $encodeclic = 0;
}

$content .= '<div id="encode_clic" name="' . $encodeclic . '" class="hidden"></div>';
$content .= '<div id="id_text" class="hidden" name="' . $textid . '">' . $textid . '</div>';
$content .= '<div id="courseLabel" class="hidden" name="' . $course->id . '">&nbsp;</div>';
$content .= '<div id="showglossary" class="hidden" name="SHOW">SHOW</div>';
$content .= '<div id="userId" class="hidden" name="' . $USER->id . '">&nbsp;</div>';
$content .= '<div id="courseid" class="hidden" name="' . $course->id .'">' . $course->id . '</div>';
$i=0;
foreach ($preferences as $key => $info) {
    $content .= '<div id="preferences_' . $i . '_key" class="hidden" name="' . $key . '">' . $key . '</div>';
    $content .= '<div id="preferences_' . $i . '_value" class="hidden" name="' . strtolower($info) . '">' . $info . '</div>';
    $i++;
}
$content .= '<div id="preferencesNb" class="hidden" name="' . count($preferences) . '">' . count($preferences) . '</div>';

$clearfix = false;
$audiofileurl = $text->get_audio_file_url();
if ( !empty( $audiofileurl ) && 'SHOW' == $preferences['player'] ) {
    $clearfix = true;
    $content .= '<div id="audioplayer"> <audio controls="controls">'
             . '<source src="' . $audiofileurl . '" />'
             . '</audio></div>';
}

if ('SHOW' == strtoupper($preferences['nextprevbuttons'])) {
    $clearfix = true;
    $content .= '<div class="textnavbuttons">';
    $nextid = get_next_textid($text);
    $previousid = get_previous_textid($text);
    if ($previousid) {
        $content .= '<a href="'
            . $_SERVER['PHP_SELF']
            . '?id=' . $id
            . '&id_text=' . $previousid
            . '#/' . $previousid
            . '" class="btn btn-default" role="button">' . get_string('previous_text', 'cobra') . '</a>';
    }
    if ($nextid) {
        $content .= '<a href="'
            . $_SERVER['PHP_SELF']
            . '?id=' . $id
            . '&id_text=' . $nextid
            . '#/' . $nextid
            . '" class="btn btn-default" role="button">' . get_string('next_text', 'cobra') . '</a>';
    }
    $content .= '</div>';
}

if ($clearfix) {
    $content .= '<div class="clearfix"></div>';
}
// Add angularjs container
$content .= '<div ng-app="cobra" id="angContainer" >';
$content .= '<div id="angView" ui-view></div>';
$content .= '</div>';

echo $content;

echo $OUTPUT->footer();

