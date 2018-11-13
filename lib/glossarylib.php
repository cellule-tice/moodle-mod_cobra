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
 * External cobra API
 *
 * @package    mod_cobra
 * @author     Jean-Roch Meurisse
 * @author     Laurence Dumortier
 * @copyright  2016 onwards - Cellule TICE - Unversite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
    $entries = array();
    $texttitle = $DB->get_field('cobra_text_info_cache', 'title', array('id' => $textid));
    $entitylist = json_decode($DB->get_field('cobra_text_info_cache', 'entities', array('id' => $textid)));

    $dataquery = "SELECT DISTINCT(lingentity) AS lingentity, entry, translations, category, extrainfo
                    FROM {cobra_glossary_cache}
                   WHERE lingentity IN (" . implode(',', $entitylist) . ")
                ORDER BY entry";

    $data = $DB->get_records_sql($dataquery, array());
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
    $entitylist = json_decode($DB->get_field('cobra_text_info_cache', 'entities', array('id' => $textid)));

    $dataquery = "SELECT id, entry, type, translations, category, extrainfo
                    FROM {cobra_glossary_cache}
                   WHERE lingentity IN (" . implode(',', $entitylist) . ")
                ORDER BY entry";

    $entries = $DB->get_records_sql($dataquery, array());

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
    $params = array('word' => $word, 'language' => $language);

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
    $params = array('flexionids' => implode(',', $flexionids));
    $list = cobra_remote_service::call('getEntityListFromFlexion', $params);
    $entitylist = array();
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
    $words = array();
    foreach ($paragraphs as $para) {
        $params = array('text' => $para, 'language' => $language);
        $wordlist = cobra_remote_service::call('getListOfWordsInText', $params);
        foreach ($wordlist as $word) {
            if (!in_array(utf8_decode($word->value), $words)) {
                $words[] = utf8_decode($word->value);
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
    $lemmalist = array();
    $explist = array();

    foreach ($glossary as $element) {
        if ($element->type == 'lemma') {
            $lemmalist[$element->id] = array(
                'id' => $element->id,
                'entry' => $element->entry,
                'category' => $element->category,
                'ss_cat' => $element->extrainfo,
                'traduction' => utf8_decode($element->translations)
            );
        } else if ($element->type == 'expression') {
            $explist[$element->id] = array(
                'id' => $element->id,
                'entry' => $element->entry,
                'category' => $element->category,
                'ss_cat' => $element->extrainfo,
                'traduction' => utf8_decode($element->translations)
            );
        }
    }
    return array($lemmalist, $explist);
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

/**
 * Class cobra_edit_glossary_form
 *
 * @package    mod_cobra
 * @author     Laurence Dumortier
 * @copyright  2016 onwards - Cellule TICE - Universite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cobra_edit_glossary_form extends moodleform {
    /**
     * Form definition.
     *
     * @throws coding_exception
     */
    public function definition() {
        $mform = $this->_form;
        // 1st argument is group name, 2nd is link text, 3rd is attributes and 4th is original value.
        $this->add_checkbox_controller(1, null, null, 1);
        $textlist = $this->_customdata['textlist'];
        $compare = $this->_customdata['compare'];
        foreach ($textlist as $text) {
            $mform->addElement('advcheckbox', 'text_' . $text->id, '',
                    htmlspecialchars(strip_tags($text->name)), array('group' => 1));
            $mform->setDefault('text_' . $text->id, 1);

        }
        if ($compare) {
            $mform->addElement('textarea', 'mytext', get_string('mytext', 'cobra'), array('rows' => 30, 'cols' => 80));
        }
        $this->add_action_buttons(true, get_string('OK', 'cobra'));
    }
}
