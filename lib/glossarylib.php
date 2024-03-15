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
 * CoBRA functions dedicated to glossary management.
 *
 * @package    mod_cobra
 * @author     Jean-Roch Meurisse
 * @author     Laurence Dumortier
 * @copyright  2016 onwards - Cellule TICE - Unversite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_cobra\cobra_remote_service;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Get all entries of a text in exportable format.
 *
 * @param int $textid id of current text.
 * @return array of entries to export.
 */
function cobra_get_exportable_glossary_entries($textid) {
    global $DB;
    $entries = [];
    $texttitle = $DB->get_field('cobra_text_info_cache', 'title', ['id' => $textid]);
    $entitylist = json_decode($DB->get_field('cobra_text_info_cache', 'entities', ['id' => $textid]));

    $dataquery = "SELECT DISTINCT(lingentity) AS lingentity, entry, translations, category, extrainfo
                    FROM {cobra_glossary_cache}
                   WHERE lingentity IN (" . implode(',', $entitylist) . ")
                ORDER BY entry";

    $data = $DB->get_records_sql($dataquery, []);
    foreach ($data as $entry) {
        $entry->sourcetexttitle = $texttitle;
        unset($entry->lingentity);
        $entries[] = $entry;
    }
    return $entries;
}

/**
 * Get all entries of a text.
 *
 * @param int $textid id of current text
 * @return array of entries for current text.
 */
function cobra_get_glossary_entries($textid) {
    global $DB;
    $entitylist = json_decode($DB->get_field('cobra_text_info_cache', 'entities', ['id' => $textid]));

    $dataquery = "SELECT id, entry, type, translations, category, extrainfo
                    FROM {cobra_glossary_cache}
                   WHERE lingentity IN (" . implode(',', $entitylist) . ")
                ORDER BY entry";

    $entries = $DB->get_records_sql($dataquery, []);

    return $entries;
}

/**
 * Get flexion id for current word and language if any.
 *
 * @param string $word
 * @param string $language (either 'EN' or 'NL')
 * @return array
 * @throws cobra_remote_access_exception
 */
function cobra_word_exists_as_flexion($word, $language) {
    $params = ['word' => $word, 'language' => $language];

    $list = cobra_remote_service::call('wordExistsAsFlexion', $params);
    return $list;
}

/**
 * Get entity list for given flexions.
 *
 * @param array $flexionids
 * @return array
 * @throws cobra_remote_access_exception
 */
function cobra_get_entity_list_from_ff($flexionids) {
    $params = ['flexionids' => implode(',', $flexionids)];
    $list = cobra_remote_service::call('getEntityListFromFlexion', $params);
    $entitylist = [];
    foreach ($list as $listobject) {
        $entitylist[] = $listobject;
    }
    return $entitylist;
}

/**
 * Tokenize input text.
 *
 * @param string $mytext
 * @param language $language
 * @return array
 * @throws cobra_remote_access_exception
 */
function cobra_get_list_of_words_in_text($mytext, $language) {
    cobra_increase_script_time();
    $paragraphs = explode ("\n", $mytext);
    $words = [];
    foreach ($paragraphs as $para) {
        $params = ['text' => $para, 'language' => $language];
        $wordlist = cobra_remote_service::call('getListOfWordsInText', $params);
        foreach ($wordlist as $word) {
            if (!in_array($word->value, $words)) {
                $words[] = $word->value;
            }
        }
    }
    return $words;
}

/**
 * Separate lemmas and expressions into 2 distinct arrays.
 *
 * @param array $glossary
 * @return array
 */
function cobra_explode_glossary_into_lemmas_and_expression($glossary) {
    $lemmalist = [];
    $explist = [];

    foreach ($glossary as $element) {
        if ($element->type == 'lemma') {
            $lemmalist[$element->id] = [
                'id' => $element->id,
                'entry' => $element->entry,
                'category' => $element->category,
                'ss_cat' => $element->extrainfo,
                'traduction' => /*utf8_decode*/($element->translations),
            ];
        } else if ($element->type == 'expression') {
            $explist[$element->id] = [
                'id' => $element->id,
                'entry' => $element->entry,
                'category' => $element->category,
                'ss_cat' => $element->extrainfo,
                'traduction' => /*utf8_decode*/($element->translations),
            ];
        }
    }
    return [$lemmalist, $explist];
}


/**
 * Marks the unknown words in the searched text.
 *
 * @param string $word the current word to mark
 * @param string $text the text within which the word is searched for to be marked
 * @return string
 */
function cobra_mark_unknown_word($word, $text) {
    $text = str_replace(' ' . $word, ' <span style="color:red">'. $word . '</span>', $text);
    $text = str_replace($word. ' ', '<span style="color:red">'. $word . '</span> ', $text);
    $text = str_replace(' ' . $word . ' ', ' <span style="color:red">' . $word . '</span> ', $text);
    $text = str_replace(' ' . $word . ',', ' <span style="color:red">' . $word . '</span>,', $text);
    $text = str_replace(' ' . $word . '.', ' <span style="color:red">' . $word . '</span>.', $text);
    $text = str_replace(' ' . $word . ':', ' <span style="color:red">' . $word . '</span>:', $text);
    $text = str_replace(' ' . $word . ';', ' <span style="color:red">' . $word . '</span>;', $text);
    $text = str_replace('(' . $word . ')', '(<span style="color:red">' . $word . '</span>)', $text);
    $text = str_replace(' ' . $word . ')', ' <span style="color:red">' . $word . '</span>)', $text);
    $text = str_replace('(' . $word . ' ', '(<span style="color:red">' . $word . '</span> ', $text);
    return $text;
}
