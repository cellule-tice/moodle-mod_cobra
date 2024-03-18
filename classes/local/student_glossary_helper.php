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
 * Helper class for lomonitoring activity
 *
 * All the lectopt specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package    mod_cobra
 * @copyright  2024 - Cellule TICE - Universite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_cobra\local;

use mod_cobra\cobra_remote_service;

/**
 * Glossary helper class for cobra activity
 */
class student_glossary_helper {
    /**
     * States whether a dictionary entry is in the student's personal glossary
     *
     * @param int $lingentity the entry to search for
     * @param int $courseid the current course identifier
     * @param int $userid the current user identifier
     * @return bool
     */
    public static function is_in_user_glossary($lingentity, $courseid, $userid = 0) {
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
     * Loads personal glossary entries for current user in current course for current text or all texts
     *
     * @param int $userid
     * @param int $courseid
     * @param int $textid
     * @param string $initial
     * @return array
     */
    public static function get_student_glossary($userid = 0, $courseid = 0, $textid = 0, $initial = '') {
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
    public static function get_cached_text_title($textid) {
        global $DB;
        return $DB->get_field('cobra_text_info_cache', 'title', ['id' => $textid]);
    }

    /**
     * Get glossary entry with lingentity = $lingentity
     * @param int $lingentity
     * @return mixed
     */
    public static function get_cached_glossary_entry($lingentity) {
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
    public static function empty_glossary($course, $user) {
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
}
