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


require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/locallib.php');
require_once(__DIR__ . '/lib/cobraremoteservice.php');
require_once(__DIR__ . '/lib/cobracollectionwrapper.php');
require_once(__DIR__ . '/lib/glossarylib.php');

// Init call vars.
$acceptedcmdlist = array();

// Init $call variable if called from angular.
$call = json_decode(file_get_contents('php://input'));

if (!empty($call)) {
    $acceptedcmdlist = array(
        'loadText',
        'loadGlossary',
        'addToGlossary',
        'removeFromGlossary'
    );

    if (empty($call) || !in_array($call->action, $acceptedcmdlist)) {
        echo json_encode($call);
        throw new Exception('Unauthorized access');

    }

    $data = array();
    switch($call->action)
    {
        case 'loadText' :
            $params = array('id_text' => $call->textid);
            try {
                $data = cobra_remote_service::call('getFormattedText', $params, 'json', true);
                echo utf8_encode($data);
                die();
            } catch (Exception $e) {
                $data = $e;
                echo json_encode($data);
                die();
            }
            break;

        case 'loadGlossary' :
            $data = cobra_get_remote_glossary_info_for_student($call->textid, $call->courseid);
            break;

        case 'addToGlossary' :
            $lingentity = $call->lingentity;
            cobra_add_to_glossary($lingentity, $call->textid, $call->courseid);
            break;

        case 'removeFromGlossary' :
            $lingentity = $call->lingentity;
            cobra_remove_from_glossary($lingentity, $call->courseid);
            break;
    }

    echo json_encode($data);
    die();
} else {
    if (!isset($_SERVER['HTTP_REFERER'])) {
        throw new Exception('Unauthorized access');
    }
    $acceptedcmdlist = array(
        'displayEntry',
        'displayCC',
        'changeVisibility',
        'moveDown',
        'moveUp',
        'changeType',
        'removeFromGlossary',
        'loadGlossary',
        'addToGlossary'
    );
    // Init $call variable if called from jQuery.
    $call = optional_param('verb', null, PARAM_ALPHA);
    if (empty($call)) {
        $call = optional_param('call', null, PARAM_ALPHA);
    }
    if (empty($call) || !in_array($call, $acceptedcmdlist)) {
        $response = array(
            'error' => 'Missing or invalid command'
        );
        echo json_encode($response);
        die();
    }

    $id = optional_param('id', 0, PARAM_INT);
    list($course, $cm) = get_course_and_cm_from_cmid($id, 'cobra');
    $position = optional_param('position', 0, PARAM_INT);
    $resource = optional_param('resourceid', null, PARAM_ALPHANUM);
    $resourcetype = optional_param('resourcetype', null, PARAM_ALPHANUM);
    $sibling = optional_param('siblingid', null, PARAM_ALPHANUM);
    $textid = optional_param('textid', 0, PARAM_INT);
    $lingentity = optional_param('lingentity', 0, PARAM_INT);
    $courseid = optional_param('courseid', 0, PARAM_INT);
    //$userid = optional_param('user', 0, PARAM_INT);

    switch ($call) {
        case 'loadGlossary' :
            $data = cobra_get_remote_glossary_info_for_student($textid, $course->id);
            $response = json_encode($data);
            break;
        case 'addToGlossary' :
            //$lingentity = $call->lingentity;
            $response = cobra_add_to_glossary($lingentity, $textid, $courseid);

            break;
        case 'changeVisibility':
            if (cobra_change_visibility($resource, $resourcetype, $course->id)) {
                echo 'true';
                return true;
            }
            $response = 'error';
            break;

        /*case 'moveDown':
            if ($position && cobra_set_position($sibling, $position++, $resourcetype, $course->id)
                && cobra_set_position($resource, $position, $resourcetype, $course->id)) {
                echo 'true';
                return true;
            }
            $response = 'error';
            break;

        case 'moveUp':
            if ($position && cobra_set_position($sibling, $position--, $resourcetype, $course->id)
                && cobra_set_position($resource, $position, $resourcetype, $course->id)) {
                echo 'true';
                return true;
            }
            $response = 'error';
            break;*/

        case 'removeFromGlossary':
            $lingentity = optional_param('lingentity', 0, PARAM_INT);
            if (cobra_remove_from_glossary($lingentity, $course->id)) {
                echo 'true';
                return true;
            }
            $response = 'error';
            break;

        case 'displayEntry':
            $conceptid = optional_param('conceptid', null, PARAM_INT);
            $resourceid = optional_param('resourceid', null, PARAM_INT);
            $isexpression = optional_param('isexpression'  , null, PARAM_BOOL);
            $encodeclic = optional_param('encodeclic', 1, PARAM_ALPHANUM);
            $userid = optional_param('userid', 0, PARAM_INT);
            $pref = isset($_REQUEST['params']) ? $_REQUEST['params'] : null;
            $params = array(
                'concept_id' => $conceptid,
                'resource_id' => $resourceid,
                'is_expr' => $isexpression,
                'params' => $pref
            );

            $html = cobra_remote_service::call('displayEntry', $params, 'json');
            $entrytype = $isexpression ? 'expression' : 'lemma';
            $params = array('conceptId' => $conceptid, 'entryType' => $entrytype);
            $lingentity = cobra_remote_service::call('getEntityLingIdFromConcept', $params, 'html');
            $lingentity = str_replace("\"", "", $lingentity);
            if ($encodeclic) {
                cobra_clic($resourceid, $lingentity, $course->id, $userid);
            }
            $glossarystatus = cobra_is_in_glossary($lingentity, $course->id);
            $response = array(
                'html' => $html,
                'inglossary' => $glossarystatus,
                'lingentity' => $lingentity,
                'userId' => $userid,
            );
            array_walk(
                $response,
                function(&$entry) {
                    $entry = utf8_encode($entry);
                }
            );

            $response = json_encode($response);
            break;

        case 'displayCC':
            $concordanceid = optional_param('concordanceid', null, PARAM_INT);
            $occurrenceid = optional_param('occurrenceid', null, PARAM_INT);
            $color = optional_param('bg_color', null, PARAM_ALPHANUMEXT);
            $pref = isset($_REQUEST['params']) ? $_REQUEST['params'] : null;
            $params = array('id_cc' => $concordanceid, 'id_occ' => $occurrenceid, 'params' => $pref);
            $response = utf8_encode(cobra_remote_service::call('displayCC', $params, 'html'));
            break;

        default:
            break;
    }
    echo $response;
}

