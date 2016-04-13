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

try {
    require(__DIR__ . '/../../config.php');
    require_once(__DIR__ . '/locallib.php');
    require_once(__DIR__ . '/lib/glossarylib.php');

    // Init request vars.
    $acceptedcmdlist = array(
        'displayEntry',
        'displayCC',
        'displayCard'
    );
    // Security checks.
    if (!isset($_SERVER['HTTP_REFERER'])) {
        throw new Exception('Unauthorized access');
    }
    if (isset($_REQUEST['verb']) && in_array($_REQUEST['verb'], $acceptedcmdlist)) {
        $call = $_REQUEST['verb'];
    } else {
        throw new Exception('Missing or invalid command');
    }

    // Force headers.
    header('Content-Type: text/html; charset=iso-8859-1'); // Charset.
    header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1.
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past.

    if ('displayEntry' == $call) {
        $conceptid = optional_param('concept_id', null, PARAM_INT);
        $resourceid = optional_param('resource_id', null, PARAM_INT);
        $isexpr = optional_param('is_expr'  , null, PARAM_BOOL);
        $encodeclic = optional_param('encodeClic', 1, PARAM_ALPHANUM);
        $courseid = optional_param('courseId', 0, PARAM_INT);
        $userid = optional_param('userId', 0, PARAM_INT);
        $pref = isset($_REQUEST['params']) ? $_REQUEST['params'] : null;
        $params = array(
            'concept_id' => $conceptid,
            'resource_id' => $resourceid,
            'is_expr' => $isexpr,
            'params' => $pref
        );

        $html = cobra_remote_service::call('displayEntry', $params, 'json');
        $entrytype = $isexpr ? 'expression' : 'lemma';
        $params = array('conceptId' => $conceptid, 'entryType' => $entrytype);
        $lingentity = cobra_remote_service::call('getEntityLingIdFromConcept', $params, 'html');
        $lingentity = str_replace("\"", "", $lingentity);
        if ($encodeclic) {
            clic($resourceid, $lingentity, $DB, $courseid, $userid);
        }
        $glossarystatus = is_in_glossary($lingentity, $courseid);
        $response = array(
            'html' => $html,
            'inglossary' => $glossarystatus,
            'lingentity' => $lingentity,
            'userId' => $userid
        );
        array_walk(
            $response,
            function(&$entry) {
                $entry = utf8_encode($entry);
            }
        );
        echo json_encode($response);
    }

    if ('displayCC' == $call) {
        $id = optional_param('id_cc', null, PARAM_INT);
        $occid = optional_param('id_occ', null, PARAM_INT);
        $color = optional_param('bg_color', null, PARAM_ALPHANUMEXT);
        $pref = isset($_REQUEST['params']) ? $_REQUEST['params'] : null;
        $params = array('id_cc' => $id, 'id_occ' => $occid, 'params' => $pref);
        $html = cobra_remote_service::call('displayCC', $params, 'html');
        echo $html;
    }

    if ('displayCard' == $call) {
        $entryid = optional_param('entry_id', null, PARAM_INT);
        $isexpr = optional_param('is_expr'  , false, PARAM_BOOL);
        $construction = optional_param('currentConstruction', null, PARAM_ALPHANUM);
        $prefs = isset($_REQUEST['params']) ? $_REQUEST['params'] : null;
        $params = array(
            'entry_id' => $entryid,
            'is_expr' => $isexpr,
            'currentConstruction' => $construction,
            'params' => $prefs
        );
        $html = cobra_remote_service::call('displayCard', $params, 'html');
        echo $html;
    }
} catch (Exception $e) {
    die($e->getMessage());
}