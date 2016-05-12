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
require_once($CFG->libdir . '/medialib.php');
require_once(__DIR__ . '/locallib.php');
require_once(__DIR__ . '/lib/cobraremoteservice.php');
require_once(__DIR__ . '/lib/cobracollectionwrapper.php');
require_once(__DIR__ . '/lib/glossarylib.php');

$id = required_param('id', PARAM_INT);
$textid = required_param('id_text', PARAM_INT);

list($course, $cm) = get_course_and_cm_from_cmid($id, 'cobra');
$cobra = $DB->get_record('cobra', array('id' => $cm->instance), '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/cobra:view', $context);

// Add event management here.

$PAGE->set_url('/mod/cobra/view.php', array('id' => $cm->id));

$PAGE->set_title(format_string($cobra->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->add_body_class('noblocks');
$PAGE->navbar->add(cobra_get_text_title_from_id($textid));

$PAGE->requires->css('/mod/cobra/css/cobra.css');

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
$PAGE->requires->js_init_call('M.mod_cobra.show_full_concordance');
$PAGE->requires->js_init_call('M.mod_cobra.add_to_glossary');
$PAGE->requires->js_init_call('M.mod_cobra.remove_from_glossary');

echo $OUTPUT->header();

$content = '';
// Load content to display.
$text = new cobra_text_wrapper();
$text->set_text_id($textid);
$text->load();

$cobra->ccorder = cobra_get_corpus_type_display_order('stringlist');
$encodeclic = 1;
if (has_capability('mod/cobra:edit', $context) && false) {
    $encodeclic = 0;
}

$content .= '<div id="encode_clic" name="' . $encodeclic . '" class="hidden"></div>';
$content .= '<div id="id_text" class="hidden" name="' . $textid . '">' . $textid . '</div>';
$content .= '<div id="courseLabel" class="hidden" name="' . $course->id . '">&nbsp;</div>';
$content .= '<div id="showglossary" class="hidden">' . $cobra->userglossary . '</div>';
$content .= '<div id="userId" class="hidden" name="' . $USER->id . '">&nbsp;</div>';
$content .= '<div id="courseid" class="hidden" name="' . $course->id .'">' . $course->id . '</div>';

$i = 0;
foreach ($cobra as $key => $info) {
    $content .= '<div id="preferences_' . $i . '_key" class="hidden" name="' . $key . '">' . $key . '</div>';
        $content .= '<div id="preferences_' . $i . '_value" class="hidden" name="' . strtolower($info) . '">' . $info . '</div>';

    $i++;
}


$content .= '<div id="preferencesNb" class="hidden" name="' . $i . '">' . $i . '</div>';

$clearfix = false;

if ($cobra->audioplayer) {
    $audiofileurl = $text->get_audio_file_url();
    if (!empty($audiofileurl)) {
        $clearfix = true;
        $content .= '<div id="audioplayer"> <audio controls="controls">' .
            '<source src="' . $audiofileurl . '" />' .
            '</audio></div>';
    }
}

if ($cobra->nextprevbuttons) {
    $clearfix = true;
    $content .= '<div class="textnavbuttons">';
    $nextid = cobra_get_next_textid($text);
    $previousid = cobra_get_previous_textid($text);
    if ($previousid) {
        $content .= '<a href="' .
                    $_SERVER['PHP_SELF'] .
                    '?id=' . $id .
                    '&id_text=' . $previousid .
                    '#/' . $previousid .
                    '" class="btn btn-default" role="button">' . get_string('previous_text', 'cobra') . '</a>';
    }
    if ($nextid) {
        $content .= '<a href="' .
                    $_SERVER['PHP_SELF'] .
                    '?id=' . $id .
                    '&id_text=' . $nextid .
                    '#/' . $nextid .
                    '" class="btn btn-default" role="button">' . get_string('next_text', 'cobra') . '</a>';
    }
    $content .= '</div>';
}

if ($clearfix) {
    $content .= '<div class="clearfix"></div>';
}
// Add angularjs container.
$content .= '<div ng-app="cobra" id="angContainer" >';
$content .= '<div id="angView" ui-view></div>';
$content .= '</div>';

echo $content;

echo $OUTPUT->footer();

