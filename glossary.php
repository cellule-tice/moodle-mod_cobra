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

use mod_cobra\cobra_edit_glossary_form;
use mod_cobra\output\glossary_action_menu;
use mod_cobra\local\teacher_glossary_helper;
use mod_cobra\local\helper;
use core\url;

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/cobra/locallib.php');
require_once($CFG->dirroot . '/lib/dataformatlib.php');
require_once($CFG->libdir . '/csvlib.class.php');

// Course id.
$id = optional_param('id', 0, PARAM_INT);

if ($id) {
    $course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);
    if (!helper::is_cobra_used()) {
        // Redirect to course page.
        redirect('../course/view.php?id=' . $id);
    }
}

// Security check.
require_login($course, true);

$context = context_course::instance($course->id, MUST_EXIST);

if (!has_capability('mod/cobra:addinstance', $context)) {
    redirect('../course/view.php?id=' . $id);
}

/*
 * Init request vars.
 */
$cmd = optional_param('cmd', '', PARAM_ALPHANUM);
$acceptedcmdlist = ['rqexport', 'exexport', 'rqcompare', 'excompare'];
if (!in_array($cmd, $acceptedcmdlist)) {
    $cmd = 'rqexport';
}

$out = '';

$textlist = helper::get_text_list();

if ($cmd == 'exexport') {
    $glossary = [];
    foreach ($textlist as $text) {
        $mustexport = optional_param('text_' . $text->id, 0, PARAM_INT);
        if ($mustexport) {
            $glossary = array_merge($glossary, teacher_glossary_helper::get_exportable_glossary_entries($text->text));
        }
    }

    $downloadentries = new ArrayObject($glossary);
    $downloadfields = [
        'entry' => get_string('entry', 'cobra'),
        'translations' => get_string('translations', 'cobra'),
        'category' => get_string('category', 'cobra'),
        'extrainfo' => get_string('otherforms', 'cobra'),
        'sourcetexttitle' => get_string('sourcetext', 'cobra'),
    ];
    $iterator = $downloadentries->getIterator();
    \core\dataformat::download_data($course->shortname . '-' . get_string('myglossary', 'cobra'),
        'excel',
        $downloadfields,
        $iterator
    );
    die();
}

// Print the page header.
$PAGE->set_url('/mod/cobra/glossary.php', ['id' => $context->id]);
$PAGE->set_title(format_string(get_string('pluginname', 'mod_cobra')));
$PAGE->set_heading(format_string($course->fullname));

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

// Print tertiary navigation.
$renderer = $PAGE->get_renderer('mod_cobra');

// Render the selection action.
$glossaryactionmenu = new glossary_action_menu(new url('/mod/cobra/glossary.php', ['id' => $course->id]));
echo $renderer->render($glossaryactionmenu);

echo $OUTPUT->box_start();

if ($cmd == 'rqexport') {
    $url = new url('/mod/cobra/glossary.php',
        [
            'id' => $id,
            'cmd' => 'exexport',
        ]
    );
    $thisform = new cobra_edit_glossary_form ($url, ['textlist' => $textlist, 'compare' => false]);
} else if ($cmd == 'rqcompare') {
    $url = new url('/mod/cobra/glossary.php',
        [
            'id' => $id,
            'cmd' => 'excompare',
        ]
    );
    $thisform = new cobra_edit_glossary_form ($url, ['textlist' => $textlist, 'compare' => true]);
} else if ( $cmd == 'excompare') {
    helper::increase_script_time();
    $glossary = [];

    $textlist = array_values($textlist);
    $language = $textlist[0]->language;
    $glossary = [];
    foreach ($textlist as $text) {
        $mustexport = optional_param('text_' . $text->id, 0, PARAM_INT);
        if ($mustexport) {
            $glossary = array_merge($glossary, teacher_glossary_helper::get_glossary_entries($text->text));
        }
    }

    list($lemmaentities, $expentities) = teacher_glossary_helper::explode_glossary_into_lemmas_and_expression( $glossary );

    $mytext = optional_param('mytext', '', PARAM_RAW);

    $newwords = '';
    $otherwords = '';
    $words = teacher_glossary_helper::get_list_of_words_in_text ($mytext, $language);
    $newwords = [];

    foreach ($words as $word) {

        $listflexions = teacher_glossary_helper::word_exists_as_flexion ($word, $language);
        $found = false;
        if (count( $listflexions) != 0) {
            $found = false;
            $listpossibleentities = teacher_glossary_helper::get_entity_list_from_ff($listflexions );

            foreach ($listpossibleentities as $entityid) {
                if (array_key_exists($entityid, $lemmaentities)) {
                    $found = true;

                    $info = $lemmaentities[$entityid]['entry'] . ' ('.$lemmaentities[$entityid]['category'].') - '
                        . $lemmaentities[$entityid]['traduction'];
                    $otherwords .= '<li> ' . get_string('possibletranslations', 'cobra') . ' : '. $word . ' : '
                        . mb_convert_encoding($info, 'UTF-8', 'ISO-8859-1') . '</li>';
                }
            }
        }
        if (!$found) {
            $newwords[] = $word;
            $mytext = teacher_glossary_helper::mark_unknown_word($word, $mytext);
        }
    }

    $out .= \core\output\html_writer::tag('div', get_string('text', 'mod_cobra') . ' : ' . $mytext);
    $out .= \core\output\html_writer::tag('span', '&nbsp; ');
    $out .= '<ul>';
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
