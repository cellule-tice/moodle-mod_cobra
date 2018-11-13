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
    return array('EN' => 'EN', 'NL' => 'NL');
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
    $corpusorder = $DB->get_field('cobra', 'corpusorder', array(
            'course' => $course,
            'language' => $language,
            'isdefaultcorpusorder' => 1
        )
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

/**
 * Prepares and executes a curl request to the central CoBRA system
 *
 * @param string $url target url
 * @return mixed|bool response of the curl request or false on error
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
    $inglossary = $DB->record_exists('cobra_clic', array(
            'course' => $courseid,
            'lingentity' => (int)$lingentity,
            'userid' => $user,
            'inglossary' => 1)
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

    $params = array('language' => $language);
    $data = cobra_remote_service::call('get_collection_list', $params);
    $collectionsarray = array();
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

    $params = array('collection' => (int)$collection);
    $data = cobra_remote_service::call('get_text_list', $params);
    $textsarray = array();
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

    $info = $DB->get_record_select('cobra_clic',
        "course='$courseid' AND userid='$userid' AND textid='$textid' AND lingentity='$lingentityid'");
    if (!$info) {
        // Insert record.
        $dataobject = new stdClass();
        $dataobject->cobra = $cobraid;
        $dataobject->course = $courseid;
        $dataobject->userid = $userid;
        $dataobject->textid = $textid;
        $dataobject->lingentity = $lingentityid;
        $dataobject->nbclicsstats = 1;
        $dataobject->nbclicsglossary = 1;
        $dataobject->timecreated = time();
        $dataobject->timemodified = time();
        $result = $DB->insert_record('cobra_clic', $dataobject);
    } else {
        // Update record.
        $dataobject = new  stdClass();
        $dataobject->id = $info->id;
        $dataobject->nbclicsstats = ($info->nbclicsstats + 1);
        $dataobject->nbclicsglossary = ($info->nbclicsglossary + 1);
        $dataobject->timemodified = time();
        $result = $DB->update_record('cobra_clic', $dataobject);
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
    $entries = cobra_remote_service::call('get_glossary_entries', array('lastupdate' => $timestamp));
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
    return array($new, $updated);
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
    $textlist = cobra_remote_service::call('get_texts_info', array('lastupdate' => $timestamp));
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
    return array($new, $updated);
}

/**
 * Fill in cached text info and glossary entries.
 */
function cobra_fill_cache_tables() {
    if (!empty(get_config('mod_cobra', 'apikey'))) {
        cobra_update_glossary_cache(0);
        cobra_update_text_info_cache(0);
    }
}

/**
 * Loads personal glossary entries for current user in current course for current text or all texts
 *
 * @param int $userid
 * @param int $courseid
 * @param int $textid
 * @param int $page
 * @param int $perpage
 * @param bool $export
 * @param string $initial
 * @return array
 */
function cobra_get_student_glossary($userid = 0, $courseid = 0, $textid = 0, $page = 0,
                                    $perpage = 0, $export = false, $initial = '') {
    global $DB;

    if ($initial !== 'all') {
        $initialfilter = " AND " . $DB->sql_like('entry', ':initial', false);
    } else {
        $initialfilter = '';
    }
    $dataquery = "SELECT DISTINCT(ug.lingentity) AS lingentity, textid, entry, type, translations, category, extrainfo
                    FROM {cobra_clic} ug
                    JOIN {cobra_glossary_cache} gc
                      ON ug.lingentity = gc.lingentity
                   WHERE course = :courseid
                     AND userid = :userid
                     AND inglossary = 1 " . $initialfilter . "
                ORDER BY entry";

    if ($export) {
        $fullglossaryresult = $DB->get_records_sql($dataquery,
            array('courseid' => $courseid, 'userid' => $userid, 'initial' => $initial . '%'));

    } else {
        $fullglossaryresult = $DB->get_records_sql($dataquery,
            array('courseid' => $courseid, 'userid' => $userid, 'initial' => $initial . '%'),
            $page * $perpage, $perpage);
    }

    if (empty($textid)) {
        $result = $DB->get_records_sql($dataquery,
                array('courseid' => $courseid, 'userid' => $userid, 'initial' => $initial . '%'));
        return array(count($result), $fullglossaryresult);
    }

    $fullglossarylist = array_keys($fullglossaryresult);

    $entitiesintext = array();
    $listtoload = array();
    if ($textid) {

        $entitiesintext = json_decode($DB->get_field('cobra_text_info_cache', 'entities', array('id' => $textid)));
        if (empty($entitiesintext)) {
            $entitiesintext = array();
        }
        $textquery = "SELECT DISTINCT(lingentity)
                        FROM {cobra_clic}
                       WHERE course = :courseid
                             AND userid = :userid
                             AND inglossary = 1
                             AND textid = :textid";

        $textresult = $DB->get_records_sql($textquery, array('courseid' => $courseid, 'userid' => $userid, 'textid' => $textid));
        $textglossarylist = array_keys($textresult);
        $listtoload = array_intersect($fullglossarylist, $entitiesintext);
    } else {
        $listtoload = $fullglossarylist;
    }

    if (!count($listtoload)) {
        return array();
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
    return $DB->get_field('cobra_text_info_cache', 'title', array('id' => $textid));
}

/**
 * Get glossary entry with lingentity = $lingentity
 * @param int $lingentity
 * @return mixed
 */
function cobra_get_glossary_entry($lingentity) {
    global $DB;

    $entry = $DB->get_record('cobra_glossary_cache', array('lingentity' => $lingentity));
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
    return $DB->set_field('cobra_clic',
        'inglossary',
        '0',
        array(
            'course' => $course,
            'userid' => $user,
        )
    );
}

/**
 * Checks wether there is at least one instance of cobra resource in current course.
 * @return bool
 */
function cobra_is_used() {
    global $DB, $course;
    $cobra = $DB->get_records('cobra', array('course' => $course->id));
    return (!empty($cobra));
}

/**
 * Returns the list of cobra resources ids for current course.
 * @return array
 */
function cobra_get_text_list() {
    global $DB, $COURSE;
    $cobratexts = $DB->get_records('cobra', array('course' => $COURSE->id), 'id');
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
    return array('lemma', 'expression');
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

    $site = get_site();
    $params = array(
        'caller' => $site->shortname,
        'url' => $CFG->wwwroot,
        'email' => $CFG->supportemail,
        'contact' => '',
        'platformid' => get_config('moodle', 'siteidentifier')
    );

    $data = cobra_remote_service::call('upgrade_credentials', $params);
    return json_decode($data);
}


/**
 * Class cobra_remote_service. This class handle calls to remote CoBRA system
 *
 * @package    mod_cobra
 * @author     Jean-Roch Meurisse
 * @copyright  2016 onwards - Cellule TICE - Universite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cobra_remote_service {

    /**
     * Send request to remote CoBRA system and return response
     *
     * @param string $servicename the request name
     * @param array $params arguments for the call
     * @return mixed
     * @throws cobra_remote_access_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public static function call($servicename, $params = array()) {
        $validreturntypes = array(
            'object',
            'objectList',
            'error'
        );
        $response = new stdClass();
        $site = get_site();
        $params['caller'] = $site->shortname;
        $params['platformid'] = get_config('moodle', 'siteidentifier');
        $params['apikey'] = get_config('mod_cobra', 'apikey');
        $url = get_config('mod_cobra', 'serviceurl');

        $params['from'] = 'moodle';
        if (count($params)) {
            $querystring = http_build_query($params, '', '&');
        }
        $params['verb'] = $servicename;
        $curl = new curl();

        $curl->setHeader(array('Accept: application/json', 'Expect:'));

        $options = array(
            'FRESH_CONNECT' => true,
            'RETURNTRANSFER' => true,
            'FORBID_REUSE' => true,
            'HEADER' => 0,
            'CONNECTTIMEOUT' => 3,
            // Follow redirects with the same type of request when sent 301, or 302 redirects.
            'CURLOPT_POSTREDIR' => 3
        );

        $data = $curl->post($url . '?verb=' . $servicename . '&' . $querystring, json_encode($params), $options);

        if ($data === false) {
            throw new cobra_remote_access_exception('serviceunavailable');
        } else {
            $response = json_decode($data);
        }
        if (!in_array($response->responsetype, $validreturntypes)) {
            print_error('unhandledreturntype', 'cobra', '', $response->responsetype);
        }
        if ('error' == $response->responsetype) {
            if ($response->errortype == COBRA_ERROR_PLATFORM_NOT_ALLOWED) {
                throw new cobra_remote_access_exception('platformnotallowed');
            }
            if ($response->errortype == COBRA_ERROR_MISSING_PARAM) {
                print_error('missingparam', '', '', $response->content);
            }
            if ($response->errortype == COBRA_ERROR_UNHANDLED_CALL) {
                print_error('unhandledcall', '', '', $response->content);
            }
        } else {
            return $response->content;
        }
    }
}

/**
 * Exception handling errors when trying to send requests to the remote CoBRA system (service unavailable or unauthorized access
 *
 * @package    mod_cobra
 * @author     Jean-Roch Meurisse
 * @copyright  2016 onwards - Cellule TICE - Universite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cobra_remote_access_exception extends moodle_exception {

    /**
     * Constructor
     *
     * @param string $debuginfo the debug info
     */
    public function __construct($debuginfo) {
        parent::__construct('platformnotallowed', 'cobra', '', null, $debuginfo);
    }
}