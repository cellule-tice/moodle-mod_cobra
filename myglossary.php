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

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/mod/cobra/lib.php');
require_once($CFG->dirroot . '/mod/cobra/locallib.php');
require_once($CFG->dirroot . '/mod/cobra/lib/glossary.lib.php');

$id = required_param('id', PARAM_INT);

list($course, $cm) = get_course_and_cm_from_cmid($id, 'cobra');
$cobra = $DB->get_record('cobra', array('id' => $cm->instance), '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/cobra:view', $context);

// Print the page header.
$PAGE->set_url('/mod/cobra/myglossary.php', array('id' => $cm->id));
$PAGE->set_title(format_string($cobra->name));
//$PAGE->set_heading(format_string($course->fullname));
$PAGE->add_body_class('noblocks');

$PAGE->requires->css('/mod/cobra/css/cobra.css');

// Add the ajaxcommand for the form.
$PAGE->requires->jquery();
$PAGE->requires->js('/mod/cobra/js/jquery.qtip.js');
$PAGE->requires->js('/mod/cobra/js/cobra.js');
$PAGE->requires->js_init_call('M.mod_cobra.remove_from_global_glossary');
//$PAGE->requires->js_init_call('M.mod_cobra.add_qtip_text_list');

// Output starts here.
echo $OUTPUT->header();

// Replace the following lines with you own code.
$exportbutton = '<a href="' . $_SERVER['PHP_SELF'] .
                '?id=' . $id .
                '&cmd=export" ' .
                //'class="btn btn-default" ' .
                'class="glossaryExport" ' .
                'title="Exporter mon glossaire">   ' .
                //'<i class="fa fa-download"></i>' .
                '</a>';
echo $OUTPUT->heading('Mon glossaire' . '&nbsp;&nbsp;&nbsp;' . $exportbutton);

echo $OUTPUT->box_start('generalbox box-content' );

$content = '';
$content .= '<div id="courseid" class="hidden" name="' . $course->id .'">' . $course->id . '</div>';

$preferences = get_cobra_preferences();
if ('HIDE' == $preferences['show_glossary']) {
    //$content .= '<a href="myglossary.php">' . get_string('myglossary', 'cobra') . '</a>';
    die('not allowed');
}

/*$content .= '<a href="' . $_SERVER['PHP_SELF'] .
            '?id=' . $id .
            '&cmd=export' .
            '" class="btn btn-default" role="button">Exporter mon glossaire</a>';*/

$content .= '<table class="table table-condensed table-hover table-striped" id="myglossary">' .
            '<thead>' .
            '<tr>' .
            '<th>Entrée</th>' .
            '<th>Traduction</th>' .
            '<th>Catégorie</th>' .
            '<th>Autres formes</th>' .
            '<th>Texte source</th>' .
            '<th>Cliqué dans ...</th>' .
            '<th>&nbsp;</th>' .
            '</tr>' .
            '</thead>';

$data = get_remote_glossary_info_for_student();
$entries = array();
if (!empty($data)) {
    foreach ($data as $entry) {
        $sourcetextid = $DB->get_field('cobra_clic', 'id_text', array('course' => $course->id, 'id_entite_ling' => $entry->ling_entity, 'user_id' => $USER->id, 'in_glossary' => 1));
        $sourcetexttitle = cobra_get_text_title_from_id($sourcetextid);
        $entry->sourcetexttitle = $sourcetexttitle;

        $query = "SELECT GROUP_CONCAT(CAST(id_text AS CHAR)) AS texts
                            FROM {cobra_clic}
                           WHERE user_id = :userid
        AND id_entite_ling = :lingentity
        AND course = :course
                        GROUP BY id_entite_ling";
        $result = $DB->get_field_sql($query, array('userid' => $USER->id, 'lingentity' => $entry->ling_entity, 'course' => $course->id));
        $textidlist = explode(',', $result);
        asort($textidlist);

        $texttitles = array();
        foreach ($textidlist as $textid) {
            $texttitles[] = cobra_get_text_title_from_id($textid);
        }
        $entry->texttitles = $texttitles;
        $content .= '<tr>'
            . '<td style="vertical-align: top;">' . $entry->entry . '</td>'
            . '<td style="vertical-align: top;">' . $entry->translations . '</td>'
            . '<td style="vertical-align: top;">' . $entry->category . '</td>'
            . '<td style="vertical-align: top;">' . $entry->extra_info . '</td>'
            . '<td style="vertical-align: top;">' . $sourcetexttitle . '</td>'
            . '<td style="vertical-align:top;"><span title="' . implode("\n", $texttitles) . '">' . count($texttitles) . ' texte(s)' . '</span></td>'
            . '<td style="vertical-align: top;" class="glossaryIcon"><span id="currentLingEntity" class="hidden">' . $entry->ling_entity . '</span><img height="20px" class="gGlossaryRemove inDisplay" src="img/glossary_remove.png" title="Supprimer de mon glossaire"/></td>'
            //   .  '<td style="vertical-align: top;" title="' . implode("\n", $textTitles) . '">' . sizeof($textTitles) . ' texte(s)' . '</td>'
            . '</tr>';
        $entries[] = $entry;
    }
} else {
    $content .= '<tr>'
        . '<td colspan="7" style="text-align:center;">Glossaire vide</td>'
        . '</tr>';
}

$content .= '</table>';

echo $content;


echo $OUTPUT->box_end();

// Finish the page.
echo $OUTPUT->footer();