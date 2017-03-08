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

defined('MOODLE_INTERNAL') || die();

function cobra_export_glossary($glossary) {
    global $CFG;
    require_once($CFG->libdir . '/csvlib.class.php');

    $filename = clean_filename(get_string('glossary', 'cobra'));

    $csvexport = new csv_export_writer('semicolon');
    $csvexport->set_filename($filename);
    $records = array();
    // Get the title of the columns.
    $records[0] = array(get_string('entry', 'cobra'), get_string('category'),
        get_string('info', 'cobra'), get_string('translation', 'cobra'), get_string('text', 'cobra'));
    list($lemmaglossary, $expglossary) = cobra_explode_glossary_into_lemmas_and_expression($glossary);
    $entry = array();
    $category = array();
    $num = array();
    $traduction = array();
    foreach ($lemmaglossary as $key => $row) {
        $entry[$key] = $row['entry'];
        $category[$key] = $row['category'];
        $num[$key] = $row['num'];
        $traduction[$key] = $row['traduction'];
    }
    array_multisort($entry, SORT_ASC, $category, SORT_ASC, $num, SORT_NUMERIC, $traduction, SORT_ASC, $lemmaglossary);

    // Get all records for lemmas.
    foreach ($lemmaglossary as $key => $entry) {
        $newrecord = array($entry["entry"], $entry["category"] , $entry["ss_cat"], $entry["traduction"]);
        if (!in_array($newrecord, $records)) {
            $records[] = array(
                $entry['entry'],
                $entry['category'],
                $entry['ss_cat'],
                $entry['traduction'],
                $entry['title']
            );
        }
    }
    $entry = array();
    $category = array();
    $num = array();
    $traduction = array();
    foreach ($expglossary as $key => $row) {
        $entry[$key] = $row['entry'];
        $category[$key] = $row['category'];
        $num[$key] = $row['num'];
        $traduction[$key] = $row['traduction'];
    }
    array_multisort($entry, SORT_ASC, $category, SORT_ASC, $num, SORT_NUMERIC, $traduction, SORT_ASC, $expglossary);

    // Get all records for expressions.
    foreach ($expglossary as $key => $entry) {
        $newrecord = array(
            $entry['entry'],
            $entry['category'],
            $entry['ss_cat'],
            $entry['traduction']
        );
        if (!in_array($newrecord, $records)) {
            $records[] = array(
                $entry['entry'],
                $entry['category'],
                '',
                $entry['traduction'],
                $entry['title']);
        }
    }
    foreach ($records as $record) {
        $csvexport->add_data($record);
    }
    // Export in csv format.
    $csvexport->download_file();
    die;
}

/**
 * @param $textid integer
 * @return $glossary array
 */

function cobra_get_glossary_for_text($textid) {
    $glossary = array();
    cobra_increase_script_time();
    $oldconceptlist = array();
    for ($i = 1; $i <= 2; $i++) {
        switch ($i) {
            case '1' : $entrytype = 'expression';
                break;
            case '2' : $entrytype = 'lemma';
                break;
        }
        // Get first lemmas and then expressions.
        $conceptidlist = cobra_list_concepts_in_text($textid, $entrytype);
        foreach ($conceptidlist as $conceptid) {
            if (!in_array($conceptid, $oldconceptlist)) {
                $params = array('conceptId' => $conceptid, 'entryType' => $entrytype);
                $entitylingid = cobra_remote_service::call('getEntityLingIdFromConcept', $params);   
                // Permet d'éviter d'avoir un message d'erreur de cobra
                if ($entitylingid != '-1') {
                    $glossary[$textid][] = $entitylingid;
                    $oldconceptlist[] = $conceptid;
                } 
            }
        }
    }
    return $glossary;
}

/*
 * @param $textid integer
 * @param $entrytype : lemma or expression
 * @return $glossary array
 * Get the concepts list for a given text for the given entrytype
 */
function cobra_list_concepts_in_text($textid, $entrytype) {
    $conceptidlist = array();
    if (!in_array($entrytype, cobra_get_valid_entry_types())) {
        return false;
    }
    if ($textid) {
        // Get concept list for one text.
        $text = new cobra_text_wrapper();

        $text->set_text_id($textid);
        $text->load_remote_data();
        $titre = $text->get_title();
        $content = $text->get_content();
        $conceptidlist = cobra_get_concept_list_from_para ($titre, $entrytype);
        foreach ($content as $i => $element) {
            $conceptidlist = cobra_get_concept_list_from_para($element['content'], $entrytype, $conceptidlist);
        }
    } else {
        // Get concept list for all texts of registrered collections.
        $collectionlist = cobra_get_registered_collections();
        foreach ($collectionlist as $collection) {
            $textlist = cobra_load_text_list($collection->id_collection, 'visible');
            foreach ($textlist as $text) {
                $conceptids = cobra_list_concepts_in_text($text->id_text, $entrytype);
                foreach ($conceptids as $conceptid) {
                    if (!in_array($conceptid, $conceptidlist)) {
                        $conceptidlist[] = $conceptid;
                    }
                }
            }
        }
    }
    return $conceptidlist;
}

/**
 * @param  $para : string
 * @param tentrytype : lemma or expression
 * @return tabidconcept : array
 */
function  cobra_get_concept_list_from_para($para, $entrytype, $conceptlist = array()) {
    $sep1 = '<span class="'.$entrytype.'" name="';
    $sep2 = '>';
    $para = ' ' . $para . ' ';
    while ($pos = strpos($para, $sep1)) {
        $posfin = strpos($para, $sep2, $pos);
        $conceptid = substr($para, $pos + strlen($sep1), $posfin - $pos - strlen($sep1) - 1);
        if (!in_array($conceptid, $conceptlist)) {
            $conceptlist[] = (int)$conceptid;
        }
        $para = substr($para, $posfin + 1);
    }
    return $conceptlist;
}


/**
 * @param $glossary : array
 * @param text : class Text
 * @return @glossary : array
 */
function cobra_get_glossary_entry_of_text($glossary, $text, $num) {
    $textid = $text->id_text;
    $tempglossary = array();
    foreach ($glossary as $key => $entitelingid) {
        $params = array('id_entite_ling' => $entitelingid);
        $tempglossary[$key] = cobra_remote_service::call('getGlossaryInfoForEntityLing', $params);
    }
    $glossary = array();
    foreach ($tempglossary as $key => $glossaryentry) {
        $glossary[$key]['id'] = $glossaryentry->id;
        $glossary[$key]['type'] = $glossaryentry->type;
        $glossary[$key]['entry'] = utf8_decode($glossaryentry->entry);
        $glossary[$key]['category'] = $glossaryentry->category;
        $glossary[$key]['ss_cat'] = utf8_decode($glossaryentry->ss_cat);
        $glossary[$key]['traduction'] = utf8_decode($glossaryentry->traduction);
        $glossary[$key]['num'] = $num;
        $glossary[$key]['title'] = strip_tags($text->title);
    }
    return $glossary;
}


function cobra_array_sort_func($a, $b=null) {
    static $keys;
    if ($b === null) {
        return $keys = $a;
    }
    foreach ($keys as $k) {
        if (@$k[0] == '!') {
            $k = substr($k, 1);
            if (@$a[$k] !== @$b[$k]) {
                return strcmp(strtolower(@$b[$k]), strtolower(@$a[$k]));
            }
        } else if (@$a[$k] !== @$b[$k]) {
            return strcmp(strtolower(@$a[$k]), strtolower(@$b[$k]));
        }
    }
    return 0;
}

function cobra_array_sort($array) {
    $keys = func_get_args();
    array_shift($keys);
    cobra_array_sort_func($keys);
    usort($array, "cobra_array_sort_func");
    return $array;
}

function cobra_word_exists_as_flexion($word, $language) {
    $params = array('word' => $word, 'language' => $language);
    $list = cobra_remote_service::call('wordExistsAsFlexion', $params);
    return $list;
}

function cobra_get_lemmacat_list_from_ff($word, $language) {
    $params = array('word' => $word, 'language' => $language);
    $list = cobra_remote_service::call('getLemmaCatListFromFlexion', $params);
    $lemmalist = array();
    foreach ($list as $listobject) {
        $lemmalist[] = $listobject->value;
    }
    return $lemmalist;
}

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

function cobra_explode_array_on_key($array, $key) {
    $tab = array();
    foreach ($array as $key2 => $arrayvalue) {
        $tab[$arrayvalue[$key]] = $arrayvalue;
    }
    return $tab;
}

function cobra_explode_glossary_into_lemmas_and_expression($glossary) {
    $lemmalist = array();
    $explist = array();
    foreach ($glossary as $key => $element) {
        if ($element['type'] == 'lemma') {
            $lemmalist[] = array(
                'id' => $element['id'],
                'entry' => $element['entry'],
                'category' => $element['category'],
                'ss_cat' => $element['ss_cat'],
                'traduction' => $element['traduction'],
                'title' => $element['title'],
                'num' => $element['num']
            );
        } else if ($element['type'] == 'expression') {
            $explist[] = array('id' => $element['id'],
                'entry' => $element['entry'],
                'category' => $element['category'],
                'ss_cat' => $element['ss_cat'],
                'traduction' => $element['traduction'],
                'title' => $element['title'],
                'num' => $element['num']);
        }
    }
    return array($lemmalist, $explist);
}

// Functions dedicated to student personal glossary.
function cobra_is_in_glossary($lingentity, $courseid) {
    global $DB, $USER;
    return (int)$DB->record_exists('cobra_clic', array(
                'course' => $courseid,
                'id_entite_ling' => (int)$lingentity,
                'user_id' => $USER->id,
                'in_glossary' => 1)
            );
}

function cobra_add_to_glossary($lingentity, $textid, $courseid) {
    global $DB, $USER;
    return (int)$DB->set_field('cobra_clic',
            'in_glossary',
            '1',
            array(
                'course' => $courseid,
                'user_id' => $USER->id,
                'id_text' => $textid,
                'id_entite_ling' => $lingentity
            )
        );
}

function cobra_remove_from_glossary($lingentity, $courseid) {
    global $DB, $COURSE, $USER;
    if (empty($courseid)) {
        $courseid = $COURSE->id;
    }
    $DB->set_field('cobra_clic',
        'in_glossary',
        '0',
        array(
            'course' => $courseid,
            'user_id' => $USER->id,
            'id_entite_ling' => $lingentity
        )
    );
    return $courseid;
}

function cobra_get_remote_glossary_info_for_student($textid = 0, $courseid = 0) {
    global $DB, $COURSE, $USER;
    if (!$courseid) {
        $courseid = $COURSE->id;
    }
    $fullquery = "SELECT DISTINCT(id_entite_ling) AS id_entite_ling
                    FROM {cobra_clic}
                   WHERE course = :courseid
                         AND user_id = :userid
                         AND in_glossary = 1";
    $fullglossaryresult = $DB->get_records_sql($fullquery, array('courseid' => $courseid, 'userid' => $USER->id));

    $fullglossarylist = array_keys($fullglossaryresult);

    $entitiesintext = array();
    $listtoload = array();
    if ($textid) {
        $entitiesintext = cobra_remote_service::call('getEntitiesListForText', array('textId' => $textid));
        $textquery = "SELECT DISTINCT(id_entite_ling) AS id_entite_ling
                        FROM {cobra_clic}
                       WHERE course = :courseid
                             AND user_id = :userid
                             AND in_glossary = 1
                             AND id_text = :textid";

        $textresult = $DB->get_records_sql($textquery, array('courseid' => $courseid, 'userid' => $USER->id, 'textid' => $textid));
        $textglossarylist = array_keys($textresult);
        $listtoload = array_intersect($fullglossarylist, $entitiesintext);
    } else {
        $listtoload = $fullglossarylist;
    }

    if (!count($listtoload)) {
        return array();
    }
    $chunks = array_chunk($listtoload, 200, true);
    $flatlisttoload = '';
    $glossaryentries = array();
    foreach ($chunks as $chunk) {
        $flatlisttoload = implode(',', $chunk);
        $params = array('entity_list' => $flatlisttoload);
        $glossaryentries = array_merge($glossaryentries, cobra_remote_service::call('getGlossaryInfoForStudent', $params));
    }

    if ($textid) {
        foreach ($glossaryentries as &$entry) {
            if (in_array($entry->ling_entity, $textglossarylist)) {
                $entry->fromThisText = true;
            }
        }
    }
    return $glossaryentries;
}

function cobra_export_myglossary($data) {
    global $CFG;
    require_once($CFG->libdir . '/csvlib.class.php');

    $filename = clean_filename(get_string('glossary', 'cobra'));

    $csvexport = new csv_export_writer('semicolon');
    $csvexport->set_filename($filename);
    $records = array();
    $records[0] = array(get_string('entry', 'cobra'), get_string('category'),
        'Autres formes', get_string('translation', 'cobra'), 'Texte source', utf8_encode('Cliqué dans ...'));
    $csvexport->add_data($records[0]);
    foreach ($data as $entry) {
        $record = array(
            $entry->entry,
            $entry->category,
            $entry->extra_info,
            utf8_decode($entry->translations),
            $entry->sourcetexttitle,
            '' . count($entry->texttitles) . ' texte(s)');

        $csvexport->add_data($record);

        // Export in csv format.
    }
    $csvexport->download_file();
    die;
}

class cobra_edit_glossary_form extends moodleform {
    public function definition()
    {
        $mform = $this->_form;
        $this->add_checkbox_controller(1, null, null, 1); // 1st argument is group name, 2nd is link text, 3rd is attributes and 4th is original value
        $collectionlist =  $this->_customdata['collectionlist'];
        $compare = $this->_customdata['compare'];

        foreach ($collectionlist as $collection) {
            $textlist = cobra_load_text_list( $collection->id_collection, 'visible' );
            foreach ($textlist as $text) {
                
                $mform->addElement('advcheckbox', 'text_'.$text->id, '',  htmlspecialchars( strip_tags( $text->title)), array('group'=>1));
                $mform->setDefault('text_'.$text->id, 1);

            }
        }
        if ($compare) {
            $mform->addElement('textarea', 'mytext', get_string('mytext', 'cobra'), array('rows' => 10));
        }
        $this->add_action_buttons(true, get_string('OK', 'cobra'));
    }
}
