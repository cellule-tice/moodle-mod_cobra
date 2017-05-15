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
    public static function display_entry_parameters() {
        return new external_function_parameters(
            array(
                'concept_id' => new external_value(PARAM_INT, 'Id of concept to display'),
                'is_expr' => new external_value(PARAM_BOOL, 'Whether entry is expression or lemma'),
                'params' => new external_value(PARAM_RAW, 'Display parameters')
            )
        );
    }

    public static function display_entry_returns() {
        return new external_single_structure(
            array(
                'html' => new external_value(PARAM_RAW, 'Entry details and examples in HTML format'),
                'inglossary' => new external_value(PARAM_BOOL, 'Whether this entry is present in user glossary'),
                'lingentity' => new external_value(PARAM_INT, 'Id of displayed lingentity')
            )
        );
    }

    public static function display_entry($concept, $isexpression, $json) {
        $args = self::validate_parameters(self::display_entry_parameters(), array('concept_id' => $concept, 'is_expr' => $isexpression, 'params' => $json));
        $jsonobj = json_decode($json);

        $html = cobra_remote_service::call('displayEntry', $args, 'json');
        $entrytype = $isexpression ? 'expression' : 'lemma';
        $params = array('conceptId' => $concept, 'entryType' => $entrytype);
        $lingentity = cobra_remote_service::call('getEntityLingIdFromConcept', $params, 'html');
        $lingentity = str_replace("\"", "", $lingentity);
        if ($jsonobj->encodeclic) {
            cobra_clic($jsonobj->text, $lingentity, $jsonobj->course, $jsonobj->user);
        }

        $glossarystatus = cobra_is_in_glossary($lingentity, $jsonobj->course, $jsonobj->user);
        $response = array(
            'html' => utf8_encode($html),
            'inglossary' => $glossarystatus,
            'lingentity' => $lingentity,
        );

        return $response;
    }

    public static function get_full_concordance_parameters() {
        return new external_function_parameters(
            array(
                'id_cc' => new external_value(PARAM_INT, 'Id of concordance to display'),
                'params' => new external_value(PARAM_RAW, 'Display parameters')
            )
        );
    }

    public static function get_full_concordance_returns() {
        return new external_single_structure(
            array(
                'concordance' => new external_value(PARAM_RAW, 'Formatted concordance')
            )
        );
    }

    public static function get_full_concordance($ccid, $json) {
        $args = self::validate_parameters(self::get_full_concordance_parameters(), array('id_cc' => $ccid, 'params' => $json));
        $cc = utf8_encode(cobra_remote_service::call('displayCC', $args, 'html'));
        return array('concordance' => $cc);
    }

    public static function load_glossary_parameters() {
        return new external_function_parameters(
            array(
                //'lingentity' => new external_value(PARAM_INT, 'Id of lingentity'),
                'textid' => new external_value(PARAM_INT, 'Id of current text'),
                'courseid' => new external_value(PARAM_INT, 'Id of current course'),
                'userid' => new external_value(PARAM_INT, 'Id of current user'),
            )
        );
    }

    public static function load_glossary_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'ling_entity' => new external_value(PARAM_INT, 'lingentity id'),
                    'entry' => new external_value(PARAM_RAW, 'word/expression'),
                    'type' => new external_value(PARAM_RAW, 'lemma or expression'),
                    'translations' => new external_value(PARAM_RAW, 'list of translations'),
                    'category' => new external_value(PARAM_RAW, 'category'),
                    'extra_info' => new external_value(PARAM_RAW, 'additional info'),
                    'new' => new external_value(PARAM_BOOL, 'added during this session or not'),
                    'fromThisText' => new external_value(PARAM_BOOL, 'clicked and added in this text')
                )
            )
        );
    }

    public static function load_glossary($textid, $courseid, $userid) {
        $params = self::validate_parameters(self::load_glossary_parameters(), array('textid' => $textid, 'courseid' => $courseid, 'userid' => $userid));
        $data = cobra_get_remote_glossary_info_for_student($textid, $courseid, $userid);
        return $data;
    }



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
                'ling_entity' => new external_value(PARAM_INT, 'lingentity id'),
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
            return cobra_get_remote_glossary_info_for_student($textid, $courseid, $userid, $lingentity);
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