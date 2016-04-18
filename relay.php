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
require_once(__DIR__ . '/lib/glossarylib.php');

// Init request vars.
$acceptedcmdlist = array(
    'displayEntry',
    'displayCC'
);
// Security checks.
if (!isset($_SERVER['HTTP_REFERER'])) {
    throw new Exception('Unauthorized access');
}
$call = optional_param('verb', null, PARAM_ALPHA);
if (empty($call) || !in_array($call, $acceptedcmdlist)) {
    //throw new Exception('Missing or invalid command');
    $response = array(
        'error' => 'Missing or invalid command'
    );
    $response = json_encode($response);
}

// Force headers.
header('Content-Type: text/html; charset=utf-8'); // Charset.
header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1.
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past.

if ('displayEntry' == $call) {
    $conceptid = optional_param('conceptid', null, PARAM_INT);
    $resourceid = optional_param('resourceid', null, PARAM_INT);
    $isexpression = optional_param('isexpression'  , null, PARAM_BOOL);
    $encodeclic = optional_param('encodeclic', 1, PARAM_ALPHANUM);
    $courseid = optional_param('courseid', 0, PARAM_INT);
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
        cobra_clic($resourceid, $lingentity, $DB, $courseid, $userid);
    }
    $glossarystatus = cobra_is_in_glossary($lingentity, $courseid);
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
}

if ('displayCC' == $call) {
    $concordanceid = optional_param('concordanceid', null, PARAM_INT);
    $occurrenceid = optional_param('occurrenceid', null, PARAM_INT);
    $color = optional_param('bg_color', null, PARAM_ALPHANUMEXT);
    $pref = isset($_REQUEST['params']) ? $_REQUEST['params'] : null;
    $params = array('id_cc' => $concordanceid, 'id_occ' => $occurrenceid, 'params' => $pref);
    $response = utf8_encode(cobra_remote_service::call('displayCC', $params, 'html'));
}

echo $response;