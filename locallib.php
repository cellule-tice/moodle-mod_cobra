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

require_once(dirname( __FILE__ ) . '/lib/cobratextwrapper.class.php');
require_once(dirname( __FILE__ ) . '/lib/cobraremoteservice.class.php');
require_once($CFG->libdir.'/formslib.php');

/**
 * Loads the local list of E-Lex texts associated to a text collection (with filter on user profile)
 * @param $collection identifier of the text collection
 * @param $loadmode 'all' for course managers, 'visible' for students
 * @return array containing information on texts to display
 */
function load_text_list( $collection, $loadmode = 'all' ) {
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

    $params = array( 'collection' => (int)$collection );
    $remotetextobjectlist = CobraRemoteService::call( 'loadTexts', $params );

    foreach ($list as $text) {
        $text->title = '';
        $text->source = '';
        foreach ($remotetextobjectlist as $textojbect) {
            if ($text->id_text == $textojbect->id) {
                $text->title = $textojbect->title;
                $text->source = $textojbect->source;
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
function set_visibility($resourceid, $setvisible, $resourcetype, $courseid) {
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
            if (!$DB->update_record('cobra_texts_config', $dataobject) ) {
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
            if (!$DB->update_record('cobra_registered_collections', $dataobject) ) {
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
function set_position( $resourceid, $position, $resourcetype,  $courseid ) {
    global $DB;
    $dataobject = new  stdClass();
    $dataobject->course = $courseid;
    if ('text' == $resourcetype) {
        $list = $DB->get_record_select('cobra_texts_config', "course='$courseid' AND id_text='$resourceid'");
        if (!empty($list)) {
            // Update record.
            $dataobject->id = $list->id;
            $dataobject->position = $position;
            if (!$DB->update_record('cobra_texts_config', $dataobject) ) {
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
            if (!$DB->update_record('cobra_registered_collections', $dataobject) ) {
                return false;
            }
            return true;
        }
        return false;
    }
    return true;;
}

/**
 * Inserts a row in table 'ordre_concordances' representing a corpus type
 * @param int $typeid : identifier of corpus type
 */
function insert_corpus_type_display_order($typeid) {
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
function get_filtered_cobra_collections( $language, $exclusionlist = array() ) {
    $collections = array();
    $params = array( 'language' => $language);
    $collectionsobjectlist = CobraRemoteService::call( 'loadFilteredCollections', $params );
    foreach ($collectionsobjectlist as $remotecollection) {
        if (in_array( $remotecollection->id, $exclusionlist)) {
            continue;
        }
        $collections[] = array( 'id' => utf8_decode( $remotecollection->id ),
                                'label' => $remotecollection->label );
    }
    return $collections;
}

/**
 * Gives the list of registered collections for current course according to user status
 * --> all registered collections for course manager, visible ones for students
 * @param string $loadmode : 'all' or 'visible'
 * @return array the list of registered (visible) collections
 */
function get_registered_collections( $loadmode = 'all' ) {
    global $DB,  $course;
    $collectionslist = array();
    $params = null;
    $list = $DB->get_records_select('cobra_registered_collections', "course='$course->id'");
    foreach ($list as $collectioninfo) {
        $collectionslist[$collectioninfo->id_collection] = array('id_collection' => $collectioninfo->id_collection,
           'label' => $collectioninfo->label,
           'local_label' => $collectioninfo->local_label, 'visibility' => $collectioninfo->visibility);
    }
    return $collectionslist;
}

/**
 * Loads the list of E-Lex texts associated to a text collection from the remote E-Lex repository
 * @param $collection identifier of the text collection
 * @return array containing information on texts to display
 */
function load_remote_text_list( $collection ) {
    $textlist = array();
    $params = array( 'collection' => (int)$collection );
    $remotetextobjectlist = CobraRemoteService::call( 'loadTexts', $params );

    foreach ($remotetextobjectlist as $textobject) {
        $text['id'] = utf8_decode( $textobject->id );
        $text['title'] = utf8_decode( $textobject->title );
        $text['source'] = utf8_decode( $textobject->source );
        $textlist[] = $text;
    }
    return $textlist;
}

/**
 * Deletes local data associated to the texts of the collection given in args
 * @param int $collection identifier of the collection
 * @return boolean true on success, false otherwise
 */
function remote_text_list( $collection ) {
    global $course, $DB;
    return $DB->delete_records('cobra_texts_config',
            array('course' => $course->id, 'id_collection' => $collection));

}


/**
 * Returns an array containing module preferences for the current course
 * @return array
 */
function get_cobra_preferences() {
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

/**
 * Records current course preferences regarding E-lex module
 * @param array $prefs
 * @return boolean true on success, false otherwise
 */
function save_cobra_preferences( $prefs ) {
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
            if (!$DB->update_record('cobra_prefs', $dataobject) ) {
                return false;
            }
        } else {
            // Insert record.
            if (!$DB->insert_record('cobra_prefs', $dataobject) ) {
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
function clear_corpus_selection() {
    global $DB, $course;
    return $DB->delete_records('cobra_ordre_concordances', array('course' => $course->id));
}

/**
 * Updates click count table
 * @param int $textid the text within which a word was clicked
 * @param int $lingentityid identifier of the linguistic entity that was clicked
 */
function clic( $textid, $lingentityid, $DB, $courseid, $userid ) {
    $info = $DB->get_record_select('cobra_clic',
            "course='$courseid' AND user_id='$userid' AND id_text='$textid' AND id_entite_ling='$lingentityid'");
    if (!$info) {
        // Insert record.
        $dataobject = new  stdClass();
        $dataobject->course = $courseid;
        $dataobject->user_id = $userid;
        $dataobject->id_text = $textid;
        $dataobject->id_entite_ling = $lingentityid;
        $dataobject->nb_clics = 1;
        $dataobject->datecreate = date("Y-m-d H:i:s");
        $dataobject->datemodif = date("Y-m-d H:i:s");
        return  $DB->insert_record('cobra_clic', $dataobject);
    } else {
        // Update record.
        $dataobject = new  stdClass();
        $dataobject->id = $info->id;
        $dataobject->nb_clics = ($info->nb_clics + 1);
        return  $DB->update_record('cobra_clic', $dataobject);
    }
}

/**
 * Collects the set of synonyms for the concept given in args and produces a string representation of it
 * Handled with remote call
 * @param int $conceptid identifier of the concept
 * @param string $entryType the type of lexicon entry ('lemma' or 'expression')
 */
function get_translations( $conceptid, $entrytype ) {
    $params = array( 'id_concept' => (int)$conceptid, 'entry_type' => $entrytype );
    $translations = CobraRemoteService::call( 'get_translations', $params );
    return $translations;
}

/**
 * Collects information associated to the given linguistic entity
 * Handled with remote call
 * @param int $lingentityid identifier of the linguistic entity
 * @return array containing information on this linguistic entity
 */
function get_concept_info_from_ling_entity( $lingentityid ) {
    $params = array( 'ling_entity_id' => (int)$lingentityid );
    $conceptinfo = CobraRemoteService::call( 'get_concept_info_from_ling_entity', $params );
    return array( $conceptinfo->id_concept, $conceptinfo->construction , $conceptinfo->entry_type,
        $conceptinfo->entry_category, $conceptinfo->entry, $conceptinfo->entry_id );
}

/**
 * Gives the list of corpus types for the language given in args
 * Handled with remote call
 * @param string $langue
 * @return array $listofcorpustype
 */
function return_valid_list_type_corpus ( $language ) {
    $params = array( 'language' => $language );
    $remotelistofcorpustype = CobraRemoteService::call( 'returnValidListTypeCorpus'/*'return_valid_list_type_corpus'*/, $params );
    $listofcorpustype = array();
    foreach ($remotelistofcorpustype as $corpusobject) {
        $corpus['id'] = $corpusobject->id;
        $corpus['name'] = $corpusobject->name;
        $listofcorpustype[] = $corpus;
    }
    return $listofcorpustype;
}

/**
 * Gets the list and order of corpus types that must be taken into account when displaying concordances
 * @return array of corpus types sorted according to the recorded order of display
 */
function get_corpus_type_display_order() {
    global $DB, $course;
    $list = $DB->get_records_select('cobra_ordre_concordances', "course='$course->id'", null, 'id');
    $typelist = array();
    $i = 0;
    foreach ($list as $info) {
        $i++;
        $typelist[$i] = $info->id_type;
    }
    return $typelist;
}

/**
 * Collects information associated to the given corpus
 * Handled with remote call
 * @param int $lingentityid identifier of the corpus
 * @return array containing information about the corpus
 */
function get_corpus_info( $corpusid ) {
    $params = array( 'id_corpus' => $corpusid );
    $corpusinfo = CobraRemoteService::call( 'getCorpusInfo', $params );
    if (is_array($corpusinfo)) {
        return array( $corpusinfo->id_groupe, $corpusinfo->nom_corpus,
            utf8_decode( $corpusinfo->reference ), $corpusinfo->langue, $corpusinfo->id_type);
    }
    return array();
}

/**
 * Gets the css class associated to the corpus type given in args
 * Handled with remote call
 * @param int $typeid
 * @return string css class
 */
function find_background( $typeid ) {
    $params = array( 'typeId' => $typeid );
    $backgroundclass = CobraRemoteService::call( 'findBackGround', $params );
    return $backgroundclass;
}

/**
 * Checks existence of a given corpus type for a given language
 * @param int $typeid
 * @param string $language
 * @return boolean true if the given corpus type exists for that language, false otherwise
 */
function corpus_type_exists( $typeid, $language ) {
    $params = array( 'language' => $language, 'typeId' => $typeid );
    $ok = CobraRemoteService::call( 'corpusTypeExists', $params );
    return $ok;
}

/**
 * Gives the list of valid entry types in the lexicon (currently 'lemma' and 'expression')
 * @return array containing the list
 */
function get_valid_entry_types() {
    return array( 'lemma', 'expression');
}

/**
 * Tries various methods to handle http request
 * @param string $url url address of the http request
 * @throws Exception
 * @return the response obtained on success, false otherwise
 */
function cobra_http_request( $url ) {
    if ( ini_get( 'allow_url_fopen' ) ) {
        if ( false === $response = @file_get_contents( $url ) ) {
            return false;
        } else {
            return $response;
        }
    } else if ( function_exists('curl_init') ) {
        if ( !$response = cobra_curl_request( $url ) ) {
            return false;
        } else {
            return $response;
        }
    } else {
        throw new Exception( "Your PHP install does not support url access." );
    }
}

/**
 * Handles curl request
 * @param string $url url address of the request
 * @return the response obtained on success, false otherwise
 */
function cobra_curl_request( $url ) {
    $handle = curl_init();

    $options = array( CURLOPT_URL => $url,
                      CURLOPT_HEADER => false,
                      CURLOPT_RETURNTRANSFER => true
                    );
    curl_setopt_array( $handle, $options );

    if ( !$content = curl_exec( $handle ) ) {
        return false;
    }
    curl_close( $handle );
    return $content;
}

/**
 * Changes the type of the text with id given in args
 * Possible modes are 'Lesson', 'Reading' and 'Exercise'
 * @param int $textid
 * @return boolean true on success, false otherwise
 */
function change_text_type( $textid, $courseid ) {
    global $DB;
    $list = $DB->get_record_select('cobra_texts_config', "course='$courseid' AND id_text='$textid'");
    if (!empty($list)) {
        $texttype = $list->text_type;
        $newtype = get_next_type( $texttype );
        $dataobject = new stdClass();
        $dataobject->id = $list->id;
        $dataobject->text_type = $newtype;
        if (!$DB->update_record('cobra_texts_config', $dataobject) ) {
            return false;
        }
        return true;
    }
    return false;
}

/**
 * Gives the type of the text with id given in args
 * @param int $textid
 * @return string the type of the text
 */
function get_text_type( $textid, $courseid ) {
    global $DB;
    $list = $DB->get_record_select('cobra_texts_config', "course='$courseid' AND id_text='$textid'");
    if (!empty($list)) {
        return $list->text_type;
    }
    return false;
}

/**
 * Gives the "next" type of text according to a definite order : Lesson -> Reading -> Exercise
 * @param string $textType the current text type
 * @return string the "next" text type according to the current one
 */
function get_next_type( $texttype ) {
    switch( $texttype )
    {
        case 'Lesson' : $newtype = 'Reading';
            break;
        case 'Reading' : $newtype = 'Exercise';
            break;
        case 'Exercise' : $newtype = 'Lesson';
            break;
        default : $newtype = 'Lesson';
    }
    return $newtype;
}

function get_distinct_access_for_text($textid) {
    global $DB, $course;
    $userlist = array();
    $list = $DB->get_records_select('cobra_clic',
            "course='$course->id' AND id_text='$textid' GROUP BY user_id");
    foreach ($list as $info) {
        $userlist[] = $info->user_id;
    }
    if (has_anonymous_clic()) {
        $userlist[] = 0;
    }
    return $userlist;
}

function has_anonymous_clic () {
    global $DB, $course;
    $list = $DB->get_records_select('cobra_clic', "course='$course->id' AND user_id='0'");
    if (!empty($list)) {
        return true;
    }
    return false;
}

function get_nb_clics_for_text ($textid) {
    global $DB, $course;
    $list = $DB->get_records_select('cobra_clic', "course='$course->id' AND id_text='$textid'");
    $nb = 0;
    foreach ($list as $info) {
        $nb += $info->nb_clics;
    }
    return $nb;
}

function get_nb_texts_for_user ($userid) {
    global $DB, $course;
    $list = $DB->get_recordset_select('cobra_clic',
            "course='$course->id' AND user_id='$userid'", null, '', 'DISTINCT id_text');
    return count($list);
}

function get_nb_clic_for_user ($userid) {
    global $DB, $course;
    $list = $DB->get_records_select('cobra_clic', "course='$course->id' AND user_id='$userid'");
    $nb = 0;
    foreach ($list as $info) {
        $nb += $info->nb_clics;
    }
    return $nb;
}

function get_user_list_for_clic () {
    global $DB, $course;
    $list = $DB->get_records_select('cobra_clic', "course='$course->id'", null, '', 'DISTINCT user_id');
    $userlist = array();
    foreach ($list as $info) {
        $user = $DB->get_record_select('user', "id='$info->user_id'");
        $userlist[] = array('userId' => $info->user_id, 'lastName' => $user->lastname,
            'firstName' => $user->firstname);
    }
    return $userlist;
}

function get_nb_tags_in_text ($textid) {
    $text = new CobraTextWrapper();
    $text->set_text_id( $textid );
    $text->load();
    $html = $text->format_html();
    $nb = substr_count($html, '</span>');
    return $nb;
}

function increase_script_time( $time = 0) {
    set_time_limit( $time );
}

function clean_all_stats( $courseid) {
    global $DB;
    return $DB->delete_records('cobra_clic', array('course' => $courseid));
}

function clean_stats_before_date ($courseid, $mydate) {
    global $DB;
    $datemodif = ' < FROM_UNIXTIME('. $mydate.')';
    return $DB->delete_records('cobra_clic', array('course' => $courseid, 'datemodif' => $datemodif));
}

function get_next_textid($text) {
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

function get_previous_textid($text) {
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

function get_clicked_texts_frequency ( $courseid ) {
    global $DB;
    $nbcliclist = array();
    $params = array('GROUP BY id_text', 'HAVING nb >=5');
    $list = $DB->get_records_select('cobra_clic',
            "course='$courseid'", $params, 'nb DESC, id_text', 'id_text, SUM(nb_clics) AS nb');
    foreach ($list as $info) {
        $textid = $info->id_text;
        $nbcliclist[$textid] = $info->nb;
    }
    arsort( $nbcliclist );
    return $nbcliclist;
}

function get_clicked_entries ($courseid, $nb = 20) {
    global $DB;
    $params = array( );
    $nbcliclist = array();
    $list = $DB->get_records_select('cobra_clic', "course='$courseid' GROUP BY id_entite_ling HAVING nb >=' $nb' ",
            $params, 'id_entite_ling ASC LIMIT 100', 'id_entite_ling, SUM(nb_clics) AS nb');
    foreach ($list as $info) {
        $nbtotalclics = $info->nb;
        $nbcliclist[$info->id_entite_ling] = $nbtotalclics;
    }
    return $nbcliclist;
}

class mod_cleanStats_form extends moodleform {
    /**
     * Define this form - called by the parent constructor
     */
    public function definition() {
        $mform = $this->_form;
        $options = array('' => '', 'ALL' => get_string('All', 'cobra'), 'BEFORE' => get_string('Before', 'cobra'));
        $mform->addElement('select', 'scope', get_string('Delete', 'cobra'), $options);
        $mform->addElement('date_selector', 'before_date', get_string('Before', 'cobra'));
        $this->add_action_buttons(true, get_string('OK', 'cobra'));
    }
}

function get_foreign_languages() {
     return array('EN' => 'EN', 'NL' => 'NL');
}