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
 * Prints a particular instance of cobra
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_cobra
 * @copyright  2015 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/medialib.php');
require_once(__DIR__ . '/locallib.php');
require_once(__DIR__ . '/lib/cobraremoteservice.php');
require_once(__DIR__ . '/lib/cobracollectionwrapper.php');
require_once(__DIR__ . '/lib/glossarylib.php');

$call = json_decode(file_get_contents('php://input'));
$data = array();
switch($call->action)
{
    case 'loadText' :
        $params = array('id_text' => $call->textId);
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
        $lingentity = $call->lingEntity;
        cobra_add_to_glossary($lingentity, $call->textid, $call->courseid);
        break;

    case 'removeFromGlossary' :
        $lingentity = $call->lingEntity;
        cobra_remove_from_glossary($lingentity, $call->courseid);
        break;
}

echo json_encode($data);
die();