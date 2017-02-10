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
 * Internal library of functions for module cobra
 *
 * All the cobra specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package    mod_cobra
 * @copyright  2015 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/lib/cobratextwrapper.php');
require_once(__DIR__ . '/lib/cobraremoteservice.php');
require_once($CFG->libdir . '/formslib.php');

/**
 * Constants definition for CoBRA settings.
 */
// Display mode for concordances.
define('COBRA_EXAMPLES_BILINGUAL', 'bilingual');
define('COBRA_EXAMPLES_MONOLINGUAL', 'monolingual');

// Display mode for translations.
define('COBRA_TRANSLATIONS_ALWAYS', 'always');
define('COBRA_TRANSLATIONS_CONDITIONAL', 'conditional');
define('COBRA_TRANSLATIONS_NEVER', 'never');

// Display mode for descriptions
define('COBRA_ANNOTATIONS_ALWAYS', 'always');
define('COBRA_ANNOTATIONS_CONDITIONAL', 'conditional');
define('COBRA_ANNOTATIONS_NEVER', 'never');

// Web service error types
define('COBRA_ERROR_RETURNTYPE', 'unhandledreturntype');
define('COBRA_ERROR_SERVICE_UNAVAILABLE', 'serviceunavailable');
define('COBRA_ERROR_UNTRUSTED_USER', 'platformnotallowed');
define('COBRA_ERROR_MISSING_PARAM', 'missingparam');
/**
 * Loads the local list of CoBRA texts associated to a text collection (with filter on user profile)
 * @param $collection identifier of the text collection
 * @param $loadmode 'all' for course managers, 'visible' for students
 * @return array containing information on texts to display
 */
function cobra_load_text_list($collection, $loadmode = 'all') {
    global $DB, $course;

    $andclause = '';
    if ('visible' == $loadmode) {
        $andclause = " AND visibility = '1' ";
    }
    $list = $DB->get_records_select('cobra_texts_config', "course='$course->id' AND id_collection=$collection "
        . $andclause, null, 'position');

    if (empty($list)) {
        return false;
    }

    $textlist = array();

    $params = array('collection' => (int)$collection);
    $remotetextobjectlist = cobra_remote_service::call('getTexts', $params);

    foreach ($list as $text) {
        $text->title = '';
        $text->source = '';
        foreach ($remotetextobjectlist as $textojbect) {
            if ($text->id_text == $textojbect->id) {
                $text->title = $textojbect->title;
                $text->source = $textojbect->source;
                $text->level = $textojbect->level;
            }
        }
        $textlist[] = $text;
    }
    return $textlist;
}

/**
 * Changes the visibility status of the given resource (collection or text)
 * @param $resourceid identifier of the resource
 * @param $setvisible boolean 'true' to make the resource visible for students, 'false' to hide it
 * @return boolean true on success, false otherwise
 */
function cobra_change_visibility($resourceid, $resourcetype, $courseid) {
    global $DB;

    $dataobject = new  stdClass();
    $dataobject->course = $courseid;
    if ('text' == $resourcetype) {
        $table = 'cobra_texts_config';
        $columnname = 'id_text';
    } else {
        $table = 'cobra_registered_collections';
        $columnname = 'id_collection';
    }
    $list = $DB->get_record($table, array('course' => $courseid, $columnname => $resourceid));
    if (!empty($list)) {
        // Update record.
        $dataobject->id = $list->id;
        $dataobject->visibility = $list->visibility == 1 ? 0 : 1;
        if (!$DB->update_record($table, $dataobject)) {
            return false;
        }
        return true;
    }
    return false;
}


/**
 * Changes the visibility status of the given resource (collection or text)
 * @param $resourceid identifier of the resource
 * @param $setvisible boolean 'true' to make the resource visible for students, 'false' to hide it
 * @return boolean true on success, false otherwise
 */
function cobra_set_visibility($resourceid, $setvisible, $resourcetype, $courseid) {
    global $DB;
    $visibility = $setvisible ? '1' : '0';
    $dataobject = new  stdClass();
    $dataobject->course = $courseid;
    if ('text' == $resourcetype) {
        $list = $DB->get_record_select('cobra_texts_config', "course='$courseid' AND id_text='$resourceid'");
        if (!empty($list)) {
            // Update record.
            $dataobject->id = $list->id;
            $dataobject->visibility = $visibility;
            if (!$DB->update_record('cobra_texts_config', $dataobject)) {
                return false;
            }
            return true;
        }
        return false;
    } else if ('collection' == $resourcetype) {
        $list = $DB->get_record_select('cobra_registered_collections', "course='$courseid' AND id_collection='$resourceid'");
        if (!empty($list)) {
            // Update record.
            $dataobject->id = $list->id;
            $dataobject->visibility = $visibility;
            if (!$DB->update_record('cobra_registered_collections', $dataobject)) {
                return false;
            }
            return true;
        }
        return false;
    }
    return true;
}

/**
 * Changes the position of the given resource (collection or text) in the list
 * @param $resourceid identifier of the resource
 * @param $position the new position to assign to the resource
 * @return boolean true on success, false otherwise
 */
function cobra_set_position($resourceid, $position, $resourcetype, $courseid) {
    global $DB;
    $dataobject = new  stdClass();
    $dataobject->course = $courseid;
    if ('text' == $resourcetype) {
        $list = $DB->get_record_select('cobra_texts_config', "course='$courseid' AND id_text='$resourceid'");
        if (!empty($list)) {
            // Update record.
            $dataobject->id = $list->id;
            $dataobject->position = $position;
            if (!$DB->update_record('cobra_texts_config', $dataobject)) {
                return false;
            }
            return true;
        }
        return false;
    } else if ('collection' == $resourcetype) {
        $list = $DB->get_record_select('cobra_registered_collections', "course='$courseid' AND id_collection='$resourceid'");
        if (!empty($list)) {
            // Update record.
            $dataobject->id = $list->id;
            $dataobject->position = $position;
            if (!$DB->update_record('cobra_registered_collections', $dataobject)) {
                return false;
            }
            return true;
        }
        return false;
    } else if ('corpus' == $resourcetype) {
        $list = $DB->get_record_select('cobra_ordre_concordances', "course='$courseid' AND id_type='$resourceid'");
        if (!empty($list)) {
            // Update record.
            $dataobject->id = $list->id;
            $dataobject->position = $position;
            if (!$DB->update_record('cobra_ordre_concordances', $dataobject)) {
                return false;
            }
            return true;
        }
        return false;
    }
    return true;
}

/**
 * Inserts a row in table 'ordre_concordances' representing a corpus type
 * @param int $typeid : identifier of corpus type
 */
function cobra_insert_corpus_type_display_order($typeid) {
    global $DB, $course;
    $dataobject = new  stdClass();
    $dataobject->course = $course->id;
    $dataobject->id_type = $typeid;
    return  $DB->insert_record('cobra_ordre_concordances', $dataobject);
}

/**
 * Gets the list of unregistered collections available for the language of current course
 * @param array $exclusionlist the list of already registered collections
 * @return array containing the list of these collections
 */
function cobra_get_filtered_collections($language, $exclusionlist = array()) {
    $collections = array();
    $params = array('language' => $language);
    $collectionsobjectlist = cobra_remote_service::call('getFilteredCollections', $params);
    foreach ($collectionsobjectlist as $remotecollection) {
        if (in_array($remotecollection->id, $exclusionlist)) {
            continue;
        }
        $collections[] = array(
            'remoteid' => $remotecollection->id,
            'label' => $remotecollection->label
        );
    }
    return $collections;
}

/**
 * Gives the list of registered collections for current course according to user status
 * --> all registered collections for course manager, visible ones for students
 * @param string $loadmode : 'all' or 'visible'
 * @return array the list of registered (visible) collections
 */
function cobra_get_registered_collections($loadmode = 'all') {
    global $DB,  $course;
    $collectionslist = array();
    $params = null;
    $collectionslist = $DB->get_records_select('cobra_registered_collections', "course='$course->id'", null, 'position');

    return $collectionslist;
}

/**
 * Loads the list of E-Lex texts associated to a text collection from the remote E-Lex repository
 * @param $collection identifier of the remote text collection
 * @return array containing information on texts to display
 */
function cobra_load_remote_text_list($collection) {
    $textlist = array();
    $params = array('collection' => (int)$collection);
    $remotetextobjectlist = cobra_remote_service::call('getTexts', $params);

    foreach ($remotetextobjectlist as $textobject) {
        $text['id'] = $textobject->id;
        $text['title'] = $textobject->title;
        $text['source'] = $textobject->source;
        $textlist[] = $text;
    }
    return $textlist;
}

/**
 * Deletes local data associated to the texts of the collection given in args
 * @param int $collection identifier of the collection
 * @return boolean true on success, false otherwise
 */
function cobra_remove_text_list($collection) {
    global $course, $DB;
    return $DB->delete_records('cobra_texts_config',
            array('course' => $course->id, 'id_collection' => $collection));

}


/**
 * Returns an array containing module preferences for the current course
 * @return array
 */
function cobra_get_preferences() {
    global $DB, $course;
    $params = array();
    $info = $DB->get_record_select('cobra', "course='$course->id'", null, 'language');
    $params['language']  = $info->language;
    // Init.
    $params['nextprevbuttons']  = 'HIDE';
    $params['gender'] = 'HIDE';
    $params['ff'] = 'HIDE';
    $params['player'] = 'HIDE';
    $params['translations'] = 'ALWAYS';
    $params['descriptions'] = 'ALWAYS';
    $params['illustrations'] = 'HIDE';
    $params['examples'] = 'bi-text';
    $params['occurrences'] = 'HIDE';

    $list = $DB->get_records_select('cobra_prefs', "course='$course->id'");
    foreach ($list as $elt) {
        $params[$elt->param] = $elt->value;
    }
    return $params;
}

function cobra_get_legacy_preferences_values($cobra) {
    $preferences = array();
    $preferences['nextprevbuttons'] = $cobra->nextprevbuttons ? 'SHOW' : 'HIDE';
    $preferences['player'] = $cobra->audioplayer ? 'SHOW' : 'HIDE';
    $preferences['examples'] = $cobra->examples == 'bilingual' ? 'bi-text' : 'mono';
    if ($cobra->translations == COBRA_TRANSLATIONS_CONDITIONAL) {
        $preferences['translations'] = 'CONDITIONAL';
    } else if ($cobra->translations == COBRA_TRANSLATIONS_ALWAYS) {
        $preferences['translations'] = 'ALWAYS';
    } else {
        $preferences['translations'] = 'NEVER';
    }
    if ($cobra->annotations == COBRA_ANNOTATIONS_CONDITIONAL) {
        $preferences['descriptions'] = 'CONDITIONAL';
    } else if ($cobra->translations == COBRA_ANNOTATIONS_ALWAYS) {
        $preferences['descriptions'] = 'ALWAYS';
    } else {
        $preferences['descriptions'] = 'NEVER';
    }
    return $preferences;
}

/**
 * Records current course preferences regarding E-lex module
 * @param array $prefs
 * @return boolean true on success, false otherwise
 */
function cobra_save_preferences($prefs) {
    global $DB, $course;
    foreach ($prefs as $key => $value) {
        $dataobject = new stdClass();
        $dataobject->course = $course->id;
        $dataobject->param = $key;
        $dataobject->value = $value;
        $list = $DB->get_record_select('cobra_prefs', "course='$course->id' AND param='$key'");
        if (!empty($list)) {
            // Update record.
            $dataobject->id = $list->id;
            if (!$DB->update_record('cobra_prefs', $dataobject)) {
                return false;
            }
        } else {
            // Insert record.
            if (!$DB->insert_record('cobra_prefs', $dataobject)) {
                return false;
            }
        }
    }
    return true;
}

/**
 * Deletes current preferences linked to corpus selection and display order
 * @return boolean true on success, false otherwise
 */
function cobra_clear_corpus_selection() {
    global $DB, $course;
    return $DB->delete_records('cobra_ordre_concordances', array('course' => $course->id));
}

/**
 * Updates click count table
 * @param int $textid the text within which a word was clicked
 * @param int $lingentityid identifier of the linguistic entity that was clicked
 */
function cobra_clic($textid, $lingentityid, $DB, $courseid, $userid) {
    $info = $DB->get_record_select('cobra_clic',
            "course='$courseid' AND user_id='$userid' AND id_text='$textid' AND id_entite_ling='$lingentityid'");
    if (!$info) {
        // Insert record.
        $dataobject = new  stdClass();
        $dataobject->course = $courseid;
        $dataobject->user_id = $userid;
        $dataobject->id_text = $textid;
        $dataobject->id_entite_ling = $lingentityid;
        $dataobject->nbclicsstats = 1;
        $dataobject->nbclicsglossary = 1;
        $dataobject->datecreate = time();
        $dataobject->datemodif = time();
        return  $DB->insert_record('cobra_clic', $dataobject);
    } else {
        // Update record.
        $dataobject = new  stdClass();
        $dataobject->id = $info->id;
        $dataobject->nbclicsstats = ($info->nbclicsstats + 1);
        $dataobject->nbclicsglossary = ($info->nbclicsglossary + 1);
        $dataobject->datemodif = time();
        return  $DB->update_record('cobra_clic', $dataobject);
    }
}

/**
 * Collects the set of synonyms for the concept given in args and produces a string representation of it
 * Handled with remote call
 * @param int $conceptid identifier of the concept
 * @param string $entryType the type of lexicon entry ('lemma' or 'expression')
 */
function cobra_get_translations($conceptid, $entrytype) {
    $params = array('id_concept' => (int)$conceptid, 'entry_type' => $entrytype);
    $translations = cobra_remote_service::call('getTranslations', $params);
    return $translations;
}

/**
 * Collects information associated to the given linguistic entity
 * Handled with remote call
 * @param int $lingentityid identifier of the linguistic entity
 * @return array containing information on this linguistic entity
 */
function cobra_get_concept_info_from_ling_entity($lingentityid) {
    $params = array('ling_entity_id' => (int)$lingentityid);
   // print_object($params);die();
    $conceptinfo = cobra_remote_service::call('getConceptInfoFromLingEntity', $params);

    return array($conceptinfo->id_concept, $conceptinfo->construction , $conceptinfo->entry_type,
        $conceptinfo->entry_category, $conceptinfo->entry, $conceptinfo->entry_id);
}

/**
 * Gives the list of corpus types for the language given in args
 * Handled with remote call
 * @param string $langue
 * @return array $listofcorpustype
 */
function cobra_get_valid_list_type_corpus($language) {
    $params = array('language' => $language);
    $remotelistofcorpustype = cobra_remote_service::call('getValidListTypeCorpus', $params);
    $listofcorpustype = array();
    foreach ($remotelistofcorpustype as $corpusobject) {
        $corpus['id'] = $corpusobject->id;
        $corpus['name'] = $corpusobject->name;
        $corpusobject->colorclass = cobra_remote_service::call('findBackGround', array('typeId' => $corpusobject->id));
        $listofcorpustype[$corpusobject->id] = $corpusobject;
    }
    return $listofcorpustype;
}

/**
 * Gets the list and order of corpus types that must be taken into account when displaying concordances
 * @return array of corpus types sorted according to the recorded order of display
 */
function cobra_get_corpus_type_display_order($returntype = 'object') {
    global $DB, $course;
    $list = $DB->get_records('cobra_ordre_concordances', array('course' => $course->id), 'position');
    if ('object' == $returntype) {
        return $list;
    } else if ('stringlist') {
        $typelist = array();
        $i = 0;
        foreach ($list as $info) {
            $i++;
            $typelist[$i] = $info->id_type;
        }
        return implode(',', $typelist);
    }
}

function cobra_add_corpus_to_selection($corpustypeid) {
    global $DB, $COURSE;
    if($DB->record_exists('cobra_ordre_concordances', array('course' => $COURSE->id, 'id_type' => $corpustypeid)))   {
        return false;
    }
    $records = $DB->get_records_select('cobra_ordre_concordances', "course='$COURSE->id'", null, 'position DESC' );
    $dataobject = new stdClass();
    $dataobject->course = $COURSE->id;
    $dataobject->id_type = $corpustypeid;
    if (!empty($records)) {
        foreach ($records as $record) {
            $dataobject->position = $record->position + 1;
            break;
        }
    } else {
        $dataobject->position = 1;
    }
    return $DB->insert_record('cobra_ordre_concordances', $dataobject, true);
}

function cobra_remove_corpus_from_selection($selectionid) {
    global $DB;
    return $DB->delete_records('cobra_ordre_concordances', array('id' => $selectionid));
}

/**
 * Gets the css class associated to the corpus type given in args
 * Handled with remote call
 * @param int $typeid
 * @return string css class
 */
function cobra_find_background($typeid) {
    $params = array('typeId' => $typeid);
    $backgroundclass = cobra_remote_service::call('findBackGround', $params);
    return $backgroundclass;
}

/**
 * Checks existence of a given corpus type for a given language
 * @param int $typeid
 * @param string $language
 * @return boolean true if the given corpus type exists for that language, false otherwise
 */
function cobra_corpus_type_exists($typeid, $language) {
    $params = array('language' => $language, 'typeId' => $typeid);
    $ok = cobra_remote_service::call('corpusTypeExists', $params);
    return $ok;
}

/**
 * Gives the list of valid entry types in the lexicon (currently 'lemma' and 'expression')
 * @return array containing the list
 */
function cobra_get_valid_entry_types() {
    return array('lemma', 'expression');
}

/**
 * Tries various methods to handle http request
 * @param string $url url address of the http request
 * @throws Exception
 * @return the response obtained on success, false otherwise
 */
function cobra_http_request($url) {
    if (ini_get('allow_url_fopen')) {
        if (false === $response = @file_get_contents($url)) {
            return false;
        } else {
            return $response;
        }
    } else if (function_exists('curl_init')) {
        if (!$response = cobra_curl_request($url)) {
            return false;
        } else {
            return $response;
        }
    } else {
        throw new Exception('Your PHP install does not support url access.');
    }
}

/**
 * Handles curl request
 * @param string $url url address of the request
 * @return the response obtained on success, false otherwise
 */
function cobra_curl_request($url) {
    $handle = curl_init();

    $options = array(
        CURLOPT_URL => $url,
        CURLOPT_HEADER => false,
        CURLOPT_RETURNTRANSFER => true
    );
    curl_setopt_array($handle, $options);

    if (!$content = curl_exec($handle)) {
        return false;
    }
    curl_close($handle);
    return $content;
}

function cobra_get_distinct_access_for_text($textid) {
    global $DB, $course;
    $userlist = array();
    $list = $DB->get_records_select('cobra_clic',
            "course='$course->id' AND id_text='$textid' GROUP BY user_id");
    foreach ($list as $info) {
        $userlist[] = $info->user_id;
    }
    if (cobra_has_anonymous_clic()) {
        $userlist[] = 0;
    }
    return $userlist;
}

function cobra_has_anonymous_clic() {
    global $DB, $course;
    $list = $DB->get_records_select('cobra_clic', "course='$course->id' AND user_id='0'");
    if (!empty($list)) {
        return true;
    }
    return false;
}

function cobra_get_nb_clics_for_text($textid) {
    global $DB, $course;
    $list = $DB->get_records_select('cobra_clic', "course='$course->id' AND id_text='$textid'");
    $nb = 0;
    foreach ($list as $info) {
        $nb += $info->nbclicsstats;
    }
    return $nb;
}

function cobra_get_nb_texts_for_user($userid) {
    global $DB, $course;
    $list = $DB->get_recordset_select('cobra_clic',
            "course='$course->id' AND user_id='$userid'", null, '', 'DISTINCT id_text');
    return count($list);
}

function cobra_get_nb_clic_for_user($userid) {
    global $DB, $course;
    $list = $DB->get_records_select('cobra_clic', "course='$course->id' AND user_id='$userid'");
    $nb = 0;
    foreach ($list as $info) {
        $nb += $info->nbclicsstats;
    }
    return $nb;
}

function cobra_get_user_list_for_clic() {
    global $DB, $course;
    $list = $DB->get_records_select('cobra_clic', "course='$course->id'", null, '', 'DISTINCT user_id');
    $userlist = array();
    foreach ($list as $info) {
        $user = $DB->get_record_select('user', "id='$info->user_id'");
        $userlist[$user->lastname . '_' . $user->firstname] = array(
            'userId' => $info->user_id,
            'lastName' => $user->lastname,
            'firstName' => $user->firstname
        );
    }
    ksort($userlist);
    return $userlist;
}

function cobra_get_nb_tags_in_text($textid) {
    $text = new cobra_text_wrapper();
    $text->set_text_id($textid);
    $text->load();
    $html = $text->format_html();
    $nb = substr_count($html, '</span>');
    return $nb;
}

function cobra_increase_script_time($time = 0) {
    set_time_limit($time);
}

function cobra_clean_all_stats($courseid) {
    global $DB;
    $sql = "UPDATE {cobra_clic}
               SET nbclicsstats = 0";
    return $DB->execute($sql);
}

function cobra_clean_stats_before_date($courseid, $mydate) {
    global $DB;
    $sql = "UPDATE {cobra_clic}
               SET nbclicsstats = 0
             WHERE datemodif < :limit";
    return $DB->execute($sql, array('limit' => $mydate));
}

function cobra_get_next_textid($text) {
    global $DB, $course;
    $textcollectionid = $text->get_collection_id();
    $textposition = $text->get_position();
    $list = $DB->get_records_select('cobra_texts_config',
            "course='$course->id' AND id_collection='$textcollectionid' AND position > '$textposition'",
            array(), 'position ASC', 'id_text', 0, 1);
    if (empty($list)) {
        return false;
    }
    $keys = array_keys($list);
    return $list[$keys[0]]->id_text;
}

function cobra_get_previous_textid($text) {
    global $DB, $course;
    $textcollectionid = $text->get_collection_id();
    $textposition = $text->get_position();
    $list = $DB->get_records_select('cobra_texts_config',
            "course='$course->id' AND id_collection='$textcollectionid' AND position < '$textposition'", array(), 'position ASC',
            'id_text', 0, 1);
    if (empty($list)) {
        return false;
    }
    $keys = array_keys($list);
    return $list[$keys[0]]->id_text;
}

function cobra_get_clicked_texts_frequency($courseid) {
    global $DB;
    $nbcliclist = array();
    $params = array('GROUP BY id_text', 'HAVING nb >=5');
    $list = $DB->get_records_select('cobra_clic',
            "course='$courseid'", $params, 'nb DESC, id_text', 'id_text, SUM(nbclicsstats) AS nb');
    foreach ($list as $info) {
        $textid = $info->id_text;
        $nbcliclist[$textid] = $info->nb;
    }
    arsort($nbcliclist);
    return $nbcliclist;
}

function cobra_get_clicked_entries($courseid, $nb = 20) {
    global $DB;
    $params = array();
    $nbcliclist = array();
    $list = $DB->get_records_select('cobra_clic', "course='$courseid' GROUP BY id_entite_ling HAVING nb >=' $nb' ",
            $params, 'id_entite_ling ASC LIMIT 100', 'id_entite_ling, SUM(nbclicsstats) AS nb');
    foreach ($list as $info) {
        if ($info->id_entite_ling > 0)
        {
            $nbtotalclics = $info->nb;
            $nbcliclist[$info->id_entite_ling] = $nbtotalclics;
        }
    }
    return $nbcliclist;
}

function cobra_get_text_title_from_id($textid) {
    $texttitle = cobra_remote_service::call('getTextTitle', array('id_text' => $textid));
    return strip_tags($texttitle);
}

function cobra_get_foreign_languages() {
    return array('EN' => 'EN', 'NL' => 'NL');
}

class cobra_clean_statistics_form extends moodleform {
    /**
     * Define this form - called by the parent constructor
     */
    public function definition() {
        $mform = $this->_form;
        $options = array('' => '', 'ALL' => get_string('all'), 'BEFORE' => get_string('before', 'cobra'));
        $mform->addElement('select', 'scope', get_string('delete'), $options);
        $mform->addElement('date_selector', 'before_date', get_string('before', 'cobra'));
        $this->add_action_buttons(true, get_string('OK', 'cobra'));
    }
}

class cobra_edit_collection_label_form extends moodleform {
    public function definition()
    {
        $mform = $this->_form;
        $mform->addElement('header', 'title', get_string('editcollection', 'cobra'));
        $mform->addElement('text', 'label', get_string('collectionname', 'cobra'));
        $mform->setType('label', PARAM_TEXT);
        $mform->setDefault('label', $this->_customdata['collectionname']);
        $mform->addRule('label', get_string('collectionnamecannotbeempty', 'cobra'), 'required', null, 'client');
        $this->add_action_buttons(true, get_string('OK', 'cobra'));
    }
}
