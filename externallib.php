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
 * External cobra API
 *
 * @package    mod_cobra
 * @author     Jean-Roch Meurisse
 * @author     Laurence Dumortier
 * @copyright  2016 onwards - Cellule TICE - Unversite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once(__DIR__ . '/locallib.php');

/**
 * Cobra external functions.
 *
 * @package    mod_cobra
 * @author     Jean-Roch Meurisse
 * @copyright  2016 onwards - Cellule TICE - Universite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_cobra_external extends external_api {

    /**
     * Describes the parameters for get_entry.
     * @return external_function_parameters
     */
    public static function get_entry_parameters() {
        return new external_function_parameters(
            array(
                'concept_id' => new external_value(PARAM_INT, 'Id of concept to display'),
                'is_expr' => new external_value(PARAM_BOOL, 'Whether entry is expression or lemma'),
                'params' => new external_value(PARAM_RAW, 'Display parameters')
            )
        );
    }

    /**
     * Describes the get_entry return value
     * @return external_single_structure
     */
    public static function get_entry_returns() {
        return new external_single_structure(
            array(
                'data' => new external_single_structure(
                    array(
                        'entry' => new external_value(PARAM_RAW, 'Non inflected entry'),
                        'category' => new external_value(PARAM_RAW, 'Entry syntactic nature'),
                        'article' => new external_value(PARAM_RAW, 'Article for Dutch nouns', VALUE_OPTIONAL),
                        'abbreviations' => new external_value(PARAM_RAW, 'Entry abbreviation', VALUE_OPTIONAL),
                        'forms' => new external_multiple_structure(
                            new external_single_structure(
                                array(
                                    'type' => new external_value(PARAM_RAW, 'Inflected form(s) type'),
                                    'form' => new external_value(PARAM_RAW, 'Inflected form(s)'),
                                    'first' => new external_value(PARAM_RAW, 'Flag for display', VALUE_OPTIONAL),
                                    'last' => new external_value(PARAM_RAW, 'Flag for display', VALUE_OPTIONAL)
                                )
                            ), '', VALUE_OPTIONAL
                        ),
                        'translations' => new external_value(PARAM_RAW, 'Translations of entry', VALUE_OPTIONAL),
                        'annotation' => new external_value(PARAM_RAW, 'Annotation in source language', VALUE_OPTIONAL),
                        'trannotation' => new external_value(PARAM_RAW, 'Annotation in French', VALUE_OPTIONAL),
                        'definition' => new external_value(PARAM_RAW, 'Definition in source language', VALUE_OPTIONAL),
                        'trdefinition' => new external_value(PARAM_RAW, 'Definition in French', VALUE_OPTIONAL),
                        'concordances' => new external_multiple_structure(
                            new external_single_structure(
                                array(
                                    'source' => new external_value(PARAM_RAW, 'Concordance in source language'),
                                    'target' => new external_value(PARAM_RAW, 'Concordance in French'),
                                    'type' => new external_value(PARAM_RAW, 'Corpus type'),
                                    'first' => new external_value(PARAM_BOOL,
                                            'Is this the first concordance in list?', VALUE_OPTIONAL)
                                )
                            ), '', VALUE_OPTIONAL
                        )
                    )
                ),
                'technicalinfo' => new external_single_structure(
                    array(
                        'concept' => new external_value(PARAM_INT, 'Identifier of linked concept'),
                        'entity' => new external_value(PARAM_INT, 'Identifier of linked linguistic entity'),
                        'inglossary' => new external_value(PARAM_BOOL, 'Is this entry into student\'s personal glossary'),
                        'concordancescount' => new external_value(PARAM_INT, 'Number of concordances to display', VALUE_OPTIONAL),
                        'hasannotations' => new external_value(PARAM_BOOL, 'Is there any annotations', VALUE_OPTIONAL)
                    )
                ),
                'warnings' => new external_warnings()
            )
        );
    }

    /**
     * Returns details about entry with concept id $concept
     * @param int $concept
     * @param bool $isexpression
     * @param string $json
     * @return array
     * @throws cobra_remote_access_exception
     * @throws invalid_parameter_exception
     */
    public static function get_entry($concept, $isexpression, $json) {
        $args = self::validate_parameters(self::get_entry_parameters(),
                array(
                    'concept_id' => $concept,
                    'is_expr' => $isexpression,
                    'params' => $json
                )
        );

        $data = cobra_remote_service::call('get_entry', $args);
        $dataobj = json_decode($data);
        $jsonobj = json_decode($json);

        if ($jsonobj->encodeclic) {
            cobra_record_clic($jsonobj->text, $dataobj->technicalinfo->entity,
                    $jsonobj->course, $jsonobj->user, $jsonobj->id);
        }

        $dataobj->technicalinfo->inglossary = cobra_is_in_glossary($dataobj->data->entity,
                $jsonobj->course, $jsonobj->user);

        $response = array(
            'data' => $dataobj->data,
            'technicalinfo' => $dataobj->technicalinfo
        );

        return $response;
    }

    /**
     * Describes the parameters for get_full_concordance
     * @return external_function_parameters
     */
    public static function get_full_concordance_parameters() {
        return new external_function_parameters(
            array(
                'id_concordance' => new external_value(PARAM_INT, 'Id of concordance to display')
            )
        );
    }

    /**
     * Describes the get_full_concordance return value
     * @return external_single_structure
     */
    public static function get_full_concordance_returns() {
        return new external_single_structure(
            array(
                'source' => new external_value(PARAM_RAW, 'Concordance in source language'),
                'target' => new external_value(PARAM_RAW, 'Concordance in French'),
                'reference' => new external_value(PARAM_RAW, 'Corpus'),
                'type' => new external_value(PARAM_RAW, 'Corpus type'),
                'warnings' => new external_warnings()
            )
        );
    }

    /**
     * Returns full text of concordance with id = $ccid
     * @param int $ccid
     * @return mixed
     * @throws cobra_remote_access_exception
     * @throws invalid_parameter_exception
     */
    public static function get_full_concordance($ccid) {
        $args = self::validate_parameters(self::get_full_concordance_parameters(),
                array(
                    'id_concordance' => $ccid
                )
        );
        $cc = cobra_remote_service::call('get_full_concordance', $args);

        return json_decode($cc);
    }

    /**
     * Describes the parameters for load_glossary
     * @return external_function_parameters
     */
    public static function load_glossary_parameters() {
        return new external_function_parameters(
            array(
                'textid' => new external_value(PARAM_INT, 'Id of current text'),
                'courseid' => new external_value(PARAM_INT, 'Id of current course'),
                'userid' => new external_value(PARAM_INT, 'Id of current user'),
            )
        );
    }

    /**
     * Describes the load_glossary return value
     * @return external_multiple_structure
     */
    public static function load_glossary_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'lingentity' => new external_value(PARAM_INT, 'lingentity id'),
                    'entry' => new external_value(PARAM_RAW, 'word/expression'),
                    'type' => new external_value(PARAM_RAW, 'lemma or expression'),
                    'translations' => new external_value(PARAM_RAW, 'list of translations'),
                    'category' => new external_value(PARAM_RAW, 'category'),
                    'extrainfo' => new external_value(PARAM_RAW, 'additional info'),
                    'new' => new external_value(PARAM_BOOL, 'added during this session or not', false),
                    'fromThisText' => new external_value(PARAM_BOOL, 'clicked and added in this text', false)
                )
            )
        );
    }

    /**
     * Loads glossary entries for user $userid in course $courseid for text $textid or all texts if $textid = 0
     * @param int $textid
     * @param int $courseid
     * @param int $userid
     * @return array
     * @throws invalid_parameter_exception
     */
    public static function load_glossary($textid, $courseid, $userid) {
        $params = self::validate_parameters(self::load_glossary_parameters(),
                array(
                    'textid' => $textid,
                    'courseid' => $courseid,
                    'userid' => $userid
                )
        );

        $data = cobra_get_student_glossary($params['userid'], $params['courseid'], $params['textid']);
        return $data;
    }


    /**
     * Describes the parameters for add_to_glossary
     * @return external_function_parameters
     */
    public static function add_to_glossary_parameters() {
        return new external_function_parameters(
            array(
                'lingentity' => new external_value(PARAM_INT, 'Id of lingentity'),
                'textid' => new external_value(PARAM_INT, 'Id of current text'),
                'course' => new external_value(PARAM_INT, 'Id of current course'),
                'userid' => new external_value(PARAM_INT, 'Id of current user')
            )
        );
    }

    /**
     * Describes the add_to_glossary return value
     * @return external_single_structure
     */
    public static function add_to_glossary_returns() {

        return new external_single_structure(
            array(
                'lingentity' => new external_value(PARAM_INT, 'lingentity id'),
                'entry' => new external_value(PARAM_RAW, 'word/expression'),
                'type' => new external_value(PARAM_RAW, 'lemma or expression'),
                'translations' => new external_value(PARAM_RAW, 'list of translations'),
                'category' => new external_value(PARAM_RAW, 'category'),
                'extrainfo' => new external_value(PARAM_RAW, 'additional info'),
                'new' => new external_value(PARAM_BOOL, 'added during this session or not'),
                'fromThisText' => new external_value(PARAM_BOOL, 'clicked and added in this text')
            )
        );
    }

    /**
     * Adds $lingentity to $userid personal glossary for $courseid and $textid
     * @param int $lingentity
     * @param int $textid
     * @param int $courseid
     * @param int $userid
     * @return mixed
     * @throws invalid_parameter_exception
     */
    public static function add_to_glossary($lingentity, $textid, $courseid, $userid) {

        global $DB;

        $params = self::validate_parameters(self::add_to_glossary_parameters(),
                array(
                    'lingentity' => $lingentity,
                    'textid' => $textid,
                    'course' => $courseid,
                    'userid' => $userid
                )
        );
        $result = (int)$DB->set_field('cobra_clic',
            'inglossary',
            '1',
            $params
        );

        if ($result) {
            $entry = cobra_get_glossary_entry($lingentity);
            $entry->new = true;
            $entry->fromThisText = true;
            return $entry;
        } else {
            throw Exception('error');
        }
    }

    /**
     * Describes the parameters for remove_from_glossary
     * @return external_function_parameters
     */
    public static function remove_from_glossary_parameters() {
        return new external_function_parameters(
            array(
                'lingentity' => new external_value(PARAM_INT, 'Id of lingentity'),
                'course' => new external_value(PARAM_INT, 'Id of current course'),
                'userid' => new external_value(PARAM_INT, 'Id of current user'),
            )
        );
    }

    /**
     * Removes $lingentity from $userid personal glossary for $courseid
     * @param int $lingentity
     * @param int $courseid
     * @param int $userid
     * @return array|bool
     * @throws invalid_parameter_exception
     */
    public static function remove_from_glossary($lingentity, $courseid, $userid) {
        global $DB;

        $params = self::validate_parameters(self::remove_from_glossary_parameters(),
                array(
                    'lingentity' => $lingentity,
                    'course' => $courseid,
                    'userid' => $userid
                )
        );
        $result = (int)$DB->set_field('cobra_clic',
            'inglossary',
            '0',
            $params
        );
        if ($result) {
            return array('lingentity' => $lingentity);
        }
        return false;
    }

    /**
     * Describes the remove_from_glossary return value
     * @return external_single_structure
     */
    public static function remove_from_glossary_returns() {

        return new external_single_structure(
            array(
                'lingentity' => new external_value(PARAM_INT, 'lingentity id'),
            )
        );
    }

    /**
     * Describes the parameters for get_text
     * @return external_function_parameters
     */
    public static function get_text_parameters() {
        return new external_function_parameters(
            array(
                'id_text' => new external_value(PARAM_RAW, 'Text remote identifier'),
            )
        );
    }

    /**
     * Loads tagged text $idtext from cobra remote server.
     * @param int $idtext
     * @return mixed
     * @throws cobra_remote_access_exception
     * @throws invalid_parameter_exception
     */
    public static function get_text($idtext) {
        $params = self::validate_parameters(self::get_text_parameters(),
            array(
                'id_text' => $idtext
            )
        );

        $data = cobra_remote_service::call('get_text', $params);
        $text = json_decode($data);

        return $text;
    }

    /**
     * Describes the get_text return value
     * @return external_single_structure
     */
    public static function get_text_returns() {
        return new external_single_structure(
            array(
                'title' => new external_value(PARAM_RAW, 'Text title in CoBRA format'),
                'source' => new external_value(PARAM_RAW, 'Source of this text (website, editor, ...)', VALUE_OPTIONAL),
                'audiofile' => new external_value(PARAM_RAW, 'Url of audio version of the text', VALUE_OPTIONAL),
                'textpart' => new external_multiple_structure(
            new external_single_structure(
                array(
                    'issubtitle' => new external_value(PARAM_BOOL, 'Is this text part a subtitle?', VALUE_OPTIONAL),
                    'isparagraph' => new external_value(PARAM_BOOL, 'Is this text part a paragraph?', VALUE_OPTIONAL),
                    'content' => new external_value(PARAM_RAW, 'Content in CoBRA format')
                        )
                    )
                ),
                'warnings' => new external_warnings()
            )
        );
    }

    /**
     * Describes the parameters for get_text_list
     * @return external_function_parameters
     */
    public static function get_text_list_parameters() {
        return new external_function_parameters(
            array(
                'collection' => new external_value(PARAM_RAW, 'Collection remote identifier'),
            )
        );
    }

    /**
     * Gets the list of texts for collection with id = $collection
     * @param int $collection
     * @return mixed
     * @throws cobra_remote_access_exception
     * @throws invalid_parameter_exception
     */
    public static function get_text_list($collection) {
        $params = self::validate_parameters(self::get_text_list_parameters(),
            array(
                'collection' => $collection
            )
        );

        $data = cobra_remote_service::call('get_text_list', $params);
        return $data;
    }

    /**
     * Describes the get_text_list return value
     * @return external_single_structure
     */
    public static function get_text_list_returns() {
        return new external_single_structure(
            array(
                'texts' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'Text remote identifier'),
                            'title' => new external_value(PARAM_RAW, 'Collection name'),
                        )
                    )
                ),
                'warnings' => new external_warnings()
            )
        );
    }

    /**
     * Describes the parameters for get_collection_list
     * @return external_function_parameters
     */
    public static function get_collection_list_parameters() {
        return new external_function_parameters(
            array(
                'language' => new external_value(PARAM_RAW, 'Current language, either "EN" or "NL"'),
            )
        );
    }

    /**
     * Gets the list of available collections for language $language.
     * @param string $language
     * @return mixed
     * @throws cobra_remote_access_exception
     * @throws invalid_parameter_exception
     */
    public static function get_collection_list($language) {
        $params = self::validate_parameters(self::get_collection_list_parameters(),
            array(
                'language' => $language
            )
        );

        $data = cobra_remote_service::call('get_collection_list', $params);
        return $data;
    }

    /**
     * Describes the get_collection_list return value
     * @return external_single_structure
     */
    public static function get_collection_list_returns() {
        return new external_single_structure(
            array(
                'collections' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'Collection remote identifier'),
                            'name' => new external_value(PARAM_RAW, 'Collection name'),
                        )
                    )
                ),
                'warnings' => new external_warnings()
            )
        );
    }

    /**
     * Describes the parameters for get_demo_api_key
     * @return external_function_parameters
     */
    public static function get_demo_api_key_parameters() {
        return new external_function_parameters(
            array(
            )
        );
    }

    /**
     * Gets a demo api key for testing purpose.
     * @return stdClass
     * @throws cobra_remote_access_exception
     * @throws invalid_parameter_exception
     */
    public static function get_demo_api_key() {
        global $CFG;
        $site = get_site();
        $email = get_config('moodle', 'supportemail');
        $params = array(
            'caller' => $site->shortname,
            'email' => $email,
            'url' => $CFG->wwwroot,
            'platformid' => get_config('moodle', 'siteidentifier')
        );
        $data = cobra_remote_service::call('get_demo_api_key', $params);
        return json_decode($data);
    }

    /**
     * Describes the get_demo_api_key return value
     * @return external_single_structure
     */
    public static function get_demo_api_key_returns() {
        return new external_single_structure(
            array(
                'apikey' => new external_value(PARAM_RAW, 'Demo API key'),
                'warnings' => new external_warnings()
            )
        );
    }
}