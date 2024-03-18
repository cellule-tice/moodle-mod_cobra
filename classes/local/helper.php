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
 * Main helper class for cobra activity
 *
 * @package    mod_cobra
 * @copyright  2024 - Cellule TICE - Universite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_cobra\local;

use mod_cobra\cobra_remote_service;

/**
 * Helper class for cobra activity
 */
class helper {
    /**
     * Gives the list of available languages for CoBRA tool.
     *
     * @return array
     */
    public static function get_available_languages() {
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
    public static function get_default_corpus_order($course, $language) {
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

    /**
     * Loads the list of collections for a given language
     *
     * @param string $language chosen language, either 'EN' or 'NL'
     * @return array containing information on collections for given language
     */
    public static function get_collections_options_list($language) {

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
    public static function get_texts_options_list($collection) {

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
    public static function record_clic($textid, $lingentityid, $courseid, $userid, $cobraid) {
        global $DB;

        $info = $DB->get_record_select('cobra_click',
            "course='$courseid' AND userid='$userid' AND textid='$textid' AND lingentity='$lingentityid'");
        if (!$info) {
            // Insert record.
            $dataobject = new \stdClass();
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
            $dataobject = new  \stdClass();
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
    public static function update_glossary_cache($timestamp) {
        global $DB;
        $entries = cobra_remote_service::call('get_glossary_entries', ['lastupdate' => $timestamp]);
        $new = 0;
        $updated = 0;
        foreach ($entries as $entry) {
            try {
                $DB->insert_record_raw('cobra_glossary_cache', $entry, false, false, true);
                $new++;
            } catch (\dml_write_exception $ex) {
                if (0 === strpos($ex->debuginfo, 'Duplicate entry')) {
                    try {
                        $DB->update_record('cobra_glossary_cache', $entry);
                        $updated++;
                    } catch (\Exception $ex) {
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
    public static function update_text_info_cache($timestamp) {
        global $DB;
        $textlist = cobra_remote_service::call('get_texts_info', ['lastupdate' => $timestamp]);
        $new = 0;
        $updated = 0;
        foreach ($textlist as $text) {
            try {
                $DB->insert_record_raw('cobra_text_info_cache', $text, false, false, true);
                $new++;
            } catch (\dml_write_exception $ex) {
                if (0 === strpos($ex->debuginfo, 'Duplicate entry')) {
                    try {
                        $DB->update_record('cobra_text_info_cache', $text);
                        $updated++;
                    } catch (\Exception $ex) {
                        return $ex;
                    }
                }
            }
        }
        return [$new, $updated];
    }

    /**
     * Fill in cached text info and glossary entries.
     */
    public static function fill_cache_tables() {
        if (!empty(get_config('mod_cobra', 'apikey'))) {
            self::update_glossary_cache(0);
            self::update_text_info_cache(0);
        }
    }

    /**
     * Checks wether there is at least one instance of cobra resource in current course.
     * @return bool
     */
    public static function is_cobra_used() {
        global $DB, $course;
        $cobra = $DB->get_records('cobra', ['course' => $course->id]);
        return (!empty($cobra));
    }

    /**
     * Returns the list of cobra resources ids for current course.
     * @return array
     */
    public static function get_text_list() {
        global $DB, $COURSE;
        $cobratexts = $DB->get_records('cobra', ['course' => $COURSE->id], 'id');
        return $cobratexts;
    }

    /**
     * Increase execution time limit.
     * @param int $time
     */
    public static function increase_script_time($time = 0) {
        set_time_limit($time);
    }

    /**
     * Request an api key from CoBRA central repository.
     *
     * @return stdClass
     * @throws cobra_remote_access_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public static function get_apikey() {
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
}
