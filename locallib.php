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
 * @author     Jean-Roch Meurisse
 * @author     Laurence Dumortier
 * @copyright  2016 onwards - Cellule TICE - Universite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_cobra\cobra_remote_service;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/lib.php');
require_once($CFG->dirroot . '/lib/filelib.php');
require_once($CFG->dirroot . '/user/lib.php');

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

// Display mode for descriptions.
define('COBRA_ANNOTATIONS_ALWAYS', 'always');
define('COBRA_ANNOTATIONS_CONDITIONAL', 'conditional');
define('COBRA_ANNOTATIONS_NEVER', 'never');

// Web service error types.
define('COBRA_ERROR_RETURNTYPE', 'unhandledreturntype');
define('COBRA_ERROR_SERVICE_UNAVAILABLE', 'serviceunavailable');
define('COBRA_ERROR_PLATFORM_NOT_ALLOWED', 'platformnotallowed');
define('COBRA_ERROR_MISSING_PARAM', 'missingparam');
define('COBRA_ERROR_UNHANDLED_CALL', 'unhandledcall');

/**
 * Gives the list of available languages for CoBRA tool.
 *
 * @return array
 */
function cobra_get_available_languages() {
    return ['EN' => 'EN', 'NL' => 'NL'];
}


/**
 * Gives the default corpus order for this course if any, platform default otherwise
 *
 * @param int $course the current course id
 * @param string $language language to get corpus order for
 * @return string
 * @throws dml_exception
 */
function cobra_get_default_corpus_order($course, $language) {
    global $DB;
    if (is_array($language)) {
        $language = $language[0];
    }
    $corpusorder = $DB->get_field('cobra', 'corpusorder', [
            'course' => $course,
            'language' => $language,
            'isdefaultcorpusorder' => 1,
        ]
    );
    if (empty($corpusorder)) {
        if ($language == 'EN') {
            $corpusorder = get_config('mod_cobra', 'defaultcorpusorderen');
        } else if ($language == 'NL') {
            $corpusorder = get_config('mod_cobra', 'defaultcorpusordernl');
        } else {
            $corpusorder = '';
        }
    }
    return $corpusorder;
}

// Functions dedicated to student personal glossary.

/**
 * States whether a dictionary entry is in the student's personal glossary
 *
 * @param int $lingentity the entry to search for
 * @param int $courseid the current course identifier
 * @param int $userid the current user identifier
 * @return bool
 */
function cobra_is_in_glossary($lingentity, $courseid, $userid = 0) {
    global $DB, $USER;
    if (!empty($userid)) {
        $user = $userid;
    } else {
        $user = $USER->id;
    }
    $inglossary = $DB->record_exists('cobra_click', [
            'course' => $courseid,
            'lingentity' => (int)$lingentity,
            'userid' => $user,
            'inglossary' => 1,
        ]
    );
    return $inglossary === true;
}

/**
 * Loads the list of collections for a given language
 *
 * @param string $language chosen language, either 'EN' or 'NL'
 * @return array containing information on collections for given language
 */
function cobra_get_collections_options_list($language) {

    $params = ['language' => $language];
    $data = cobra_remote_service::call('get_collection_list', $params);
    $collectionsarray = [];
    foreach ($data->collections as $collection) {
        $collectionsarray[$collection->id] = $collection->name;
    }
    return $collectionsarray;
}

/**
 * Loads the list of CoBRA texts associated to a text collection from the remote CoBRA repository
 *
 * @param int $collection identifier of the remote text collection
 * @return array containing information on texts
 */
function cobra_get_texts_options_list($collection) {

    $params = ['collection' => (int)$collection];
    $data = cobra_remote_service::call('get_text_list', $params);
    $textsarray = [];
    foreach ($data->texts as $text) {
        $textsarray[$text->id] = $text->title;
    }
    return $textsarray;
}


/**
 * Updates click count table
 * @param int $textid the text within which a word was clicked
 * @param int $lingentityid identifier of the linguistic entity that was clicked
 * @param int $courseid current course id
 * @param int $userid current user id
 * @param int $cobraid module instance id
 * @return bool|int
 * @throws coding_exception
 * @throws dml_missing_record_exception
 */
function cobra_record_clic($textid, $lingentityid, $courseid, $userid, $cobraid) {
    global $DB;

    $info = $DB->get_record_select('cobra_click',
        "course='$courseid' AND userid='$userid' AND textid='$textid' AND lingentity='$lingentityid'");
    if (!$info) {
        // Insert record.
        $dataobject = new stdClass();
        $dataobject->cobra = $cobraid;
        $dataobject->course = $courseid;
        $dataobject->userid = $userid;
        $dataobject->textid = $textid;
        $dataobject->lingentity = $lingentityid;
        $dataobject->nbclicks = 1;
        $dataobject->timecreated = time();
        $dataobject->timemodified = time();
        $result = $DB->insert_record('cobra_click', $dataobject);
    } else {
        // Update record.
        $dataobject = new  stdClass();
        $dataobject->id = $info->id;
        $dataobject->nbclicks = ($info->nbclicks + 1);
        $dataobject->timemodified = time();
        $result = $DB->update_record('cobra_click', $dataobject);
    }

    return $result;
}

/**
 * Update local glossary view with new/updated entries collected from remote server
 *
 * @param int $timestamp
 * @return array|dml_write_exception|Exception
 * @throws cobra_remote_access_exception
 */
function cobra_update_glossary_cache($timestamp) {
    global $DB;
    $entries = cobra_remote_service::call('get_glossary_entries', ['lastupdate' => $timestamp]);
    $new = 0;
    $updated = 0;
    foreach ($entries as $entry) {
        try {
            $DB->insert_record_raw('cobra_glossary_cache', $entry, false, false, true);
            $new++;
        } catch (dml_write_exception $ex) {
            if (0 === strpos($ex->debuginfo, 'Duplicate entry')) {
                try {
                    $DB->update_record('cobra_glossary_cache', $entry);
                    $updated++;
                } catch (Exception $ex) {
                    return $ex;
                }
            }
        }
    }
    return [$new, $updated];
}

/**
 * Update local text info new/updated texts collected from remote server
 *
 * @param int $timestamp
 * @return array|dml_write_exception|Exception
 * @throws cobra_remote_access_exception
 */
function cobra_update_text_info_cache($timestamp) {
    global $DB;
    $textlist = cobra_remote_service::call('get_texts_info', ['lastupdate' => $timestamp]);
    $new = 0;
    $updated = 0;
    foreach ($textlist as $text) {
        try {
            $DB->insert_record_raw('cobra_text_info_cache', $text, false, false, true);
            $new++;
        } catch (dml_write_exception $ex) {
            if (0 === strpos($ex->debuginfo, 'Duplicate entry')) {
                try {
                    $DB->update_record('cobra_text_info_cache', $text);
                    $updated++;
                } catch (Exception $ex) {
                    return $ex;
                }
            }
        }
    }
    return [$new, $updated];
}

/**
 * Loads personal glossary entries for current user in current course for current text or all texts
 *
 * @param int $userid
 * @param int $courseid
 * @param int $textid
 * @param string $initial
 * @return array
 */
function cobra_get_student_glossary($userid = 0, $courseid = 0, $textid = 0, $initial = '') {
    global $DB;

    if ($initial !== 'all') {
        $initialfilter = " AND " . $DB->sql_like('entry', ':initial', false);
    } else {
        $initialfilter = '';
    }

    $dataquery = "SELECT DISTINCT(ug.lingentity) AS lingentity, textid, entry, type, translations, category, extrainfo
                    FROM {cobra_click} ug
                    JOIN {cobra_glossary_cache} gc
                      ON ug.lingentity = gc.lingentity
                   WHERE course = :courseid
                     AND userid = :userid
                     AND inglossary = 1 " . $initialfilter . "
                ORDER BY entry";

    $fullglossaryresult = $DB->get_records_sql($dataquery,
            ['courseid' => $courseid, 'userid' => $userid, 'initial' => $initial . '%']);

    if (empty($textid)) {
        $result = $DB->get_records_sql($dataquery,
                ['courseid' => $courseid, 'userid' => $userid, 'initial' => $initial . '%']);
        return [count($result), $fullglossaryresult];
    }

    $fullglossarylist = array_keys($fullglossaryresult);

    $entitiesintext = [];
    $listtoload = [];
    if ($textid) {

        $entitiesintext = json_decode($DB->get_field('cobra_text_info_cache', 'entities', ['id' => $textid]));
        if (empty($entitiesintext)) {
            $entitiesintext = [];
        }
        $textquery = "SELECT DISTINCT(lingentity)
                        FROM {cobra_click}
                       WHERE course = :courseid
                         AND userid = :userid
                         AND inglossary = 1
                         AND textid = :textid";

        $textresult = $DB->get_records_sql($textquery, ['courseid' => $courseid, 'userid' => $userid, 'textid' => $textid]);
        $textglossarylist = array_keys($textresult);
        $listtoload = array_intersect($fullglossarylist, $entitiesintext);
    } else {
        $listtoload = $fullglossarylist;
    }

    if (!count($listtoload)) {
        return [];
    }

    $query = "SELECT id, lingentity, entry, type, translations, category, extrainfo
                FROM {cobra_glossary_cache}
               WHERE lingentity IN (" . implode(',', $listtoload) . ")
               ORDER BY entry";

    $glossaryentries = $DB->get_records_sql($query);
    $glossaryentries = array_values($glossaryentries);

    if ($textid) {
        foreach ($glossaryentries as &$entry) {
            if (in_array($entry->lingentity, $textglossarylist)) {
                $entry->fromThisText = true;
            }
        }
    }
    return $glossaryentries;
}

/**
 * Gets the text title from local text info
 * @param int $textid
 * @return mixed
 */
function cobra_get_cached_text_title($textid) {
    global $DB;
    return $DB->get_field('cobra_text_info_cache', 'title', ['id' => $textid]);
}

/**
 * Get glossary entry with lingentity = $lingentity
 * @param int $lingentity
 * @return mixed
 */
function cobra_get_glossary_entry($lingentity) {
    global $DB;

    $entry = $DB->get_record('cobra_glossary_cache', ['lingentity' => $lingentity]);
    return $entry;
}

/**
 * Trash current user personal glossary for course $course
 * @param int $course
 * @param int $user
 * @return bool
 */
function cobra_empty_glossary($course, $user) {
    global $DB;
    return $DB->set_field('cobra_click',
        'inglossary',
        '0',
        [
            'course' => $course,
            'userid' => $user,
        ]
    );
}

/**
 * Checks wether there is at least one instance of cobra resource in current course.
 * @return bool
 */
function cobra_is_used() {
    global $DB, $course;
    $cobra = $DB->get_records('cobra', ['course' => $course->id]);
    return (!empty($cobra));
}

/**
 * Returns the list of cobra resources ids for current course.
 * @return array
 */
function cobra_get_text_list() {
    global $DB, $COURSE;
    $cobratexts = $DB->get_records('cobra', ['course' => $COURSE->id], 'id');
    return $cobratexts;
}


/**
 * Increase execution time limit.
 * @param int $time
 */
function cobra_increase_script_time($time = 0) {
    set_time_limit($time);
}

/**
 * Gives the list of valid entry types in the lexicon (currently 'lemma' and 'expression')
 * @return array containing the list
 */
function cobra_get_valid_entry_types() {
    return ['lemma', 'expression'];
}

/**
 * Request an api key from CoBRA central repository.
 *
 * @return stdClass
 * @throws cobra_remote_access_exception
 * @throws dml_exception
 * @throws moodle_exception
 */
function cobra_get_apikey() {
    global $CFG;
    if (!isset($CFG->supportemail)) {
        $supportemail = 'noreply@mymoodle.com';
    } else {
        $supportemail = $CFG->supportemail;
    }
    $site = get_site();
    $params = [
        'caller' => $site->shortname,
        'url' => $CFG->wwwroot,
        'email' => $supportemail,
        'contact' => '',
        'platformid' => get_config('moodle', 'siteidentifier'),
    ];

    $data = cobra_remote_service::call('upgrade_credentials', $params);
    return json_decode($data);
}
