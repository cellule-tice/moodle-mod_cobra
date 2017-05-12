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
 * @package    mod_cobra
 * @author     Jean-Roch Meurisse
 * @author     Laurence Dumortier
 * @copyright  2016 onwards - Cellule TICE - Unversite de Namur
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

    public static function remove_from_glossary($lingentity, $courseid, $userid) {
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
            )
        );
    }
}