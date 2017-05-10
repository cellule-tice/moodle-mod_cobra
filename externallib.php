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
 * @package    
 * @author     Jean-Roch Meurisse
 * @copyright  2016 - Cellule TICE - Unversite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(__DIR__ . '/lib/cobraremoteservice.php');
require_once(__DIR__ . '/locallib.php');
require_once(__DIR__ . '/lib/glossarylib.php');
//print_object(__DIR__);
class mod_cobra_external extends external_api
{
    public static function add_to_glossary_parameters() {
        return new external_function_parameters(
            array(
                'lingentity' => new external_value(PARAM_INT, 'Id of lingentity'),
                'textid' => new external_value(PARAM_INT, 'Id of current text'),
                'courseid' => new external_value(PARAM_INT, 'Id of current course'),
                'userid' => new external_value(PARAM_INT, 'Id of current user'),
            )
        );
    }

    public static function add_to_glossary_returns() {

        return new external_single_structure(
            array(
                'lingentity' => new external_value(PARAM_INT, 'lingentity id'),
                'entry' => new external_value(PARAM_RAW, 'word/expression'),
                'type' => new external_value(PARAM_RAW, 'lemma or expression'),
                'translations' => new external_value(PARAM_RAW, 'list of translations'),
                'category' => new external_value(PARAM_RAW, 'category'),
                'extra_info' => new external_value(PARAM_RAW, 'additional info'),
                'new' => new external_value(PARAM_BOOL, 'added during this session or not'),
                'fromThisText' => new external_value(PARAM_BOOL, 'clicked and added in this text')
            )
        );
    }

    public static function add_to_glossary($lingentity, $textid, $courseid, $userid) {

        global $DB;

        $params = self::validate_parameters(self::add_to_glossary_parameters(), array('lingentity' => $lingentity, 'textid' => $textid, 'courseid' => $courseid, 'userid' => $userid));
        $result = (int)$DB->set_field('cobra_clic',
            'in_glossary',
            '1',
            array(
                'course' => $courseid,
                'user_id' => $userid,
                'id_text' => $textid,
                'id_entite_ling' => $lingentity
            )
        );

        if ($result) {
            return cobra_get_remote_glossary_info_for_student($textid, $courseid, $lingentity);
            //return $test[0];
        } else {
            throw Exception('error');
        }
    }

    public static function remove_from_glossary_parameters() {
        return new external_function_parameters(
            array(
                'lingentity' => new external_value(PARAM_INT, 'Id of lingentity'),
                'courseid' => new external_value(PARAM_INT, 'Id of current course'),
                'userid' => new external_value(PARAM_INT, 'Id of current user'),
            )
        );
    }

    public static function remove_from_glossary($lingentity, $courseid, $userid)
    {
        global $DB;

        $params = self::validate_parameters(self::remove_from_glossary_parameters(), array('lingentity' => $lingentity, 'courseid' => $courseid, 'userid' => $userid));
        $result = (int)$DB->set_field('cobra_clic',
            'in_glossary',
            '0',
            array(
                'course' => $courseid,
                'user_id' => $userid,
                'id_entite_ling' => $lingentity
            )
        );
        if ($result) {
            return array('lingentity' => $lingentity);
        }
        return false;
    }

    public static function remove_from_glossary_returns() {

        return new external_single_structure(
            array(
                'lingentity' => new external_value(PARAM_INT, 'lingentity id'),
                /*'entry' => new external_value(PARAM_RAW, 'word/expression'),
                'type' => new external_value(PARAM_RAW, 'lemma or expression'),
                'translations' => new external_value(PARAM_RAW, 'list of translations'),
                'category' => new external_value(PARAM_RAW, 'category'),
                'extra_info' => new external_value(PARAM_RAW, 'additional info'),
                'new' => new external_value(PARAM_BOOL, 'added during this session or not'),
                'fromThisText' => new external_value(PARAM_INT, 'clicked and added in this text')*/
            )
        );
    }

    /*public static function get_remote_glossary_info_for_student($textid = 0, $courseid = 0, $lingentity = 0) {

        global $DB, $COURSE, $USER;
        if ($lingentity) {

            $params = array('entity_list' => $lingentity);
            $glossaryentries = cobra_remote_service::call('getGlossaryInfoForStudent', $params);
            $singleentry = $glossaryentries[0];
            $singleentry->new = true;
            $singleentry->fromThisText = true;
            $singleentry->lingentity = $singleentry->ling_entity;
            return $singleentry;
        }
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
    }*/
}