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
 * Library of interface functions and constants for module cobra
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 *
 * All the cobra specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package    mod_cobra
 * @copyright  2015 Laurence Dumortier UNamur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class cobra_remote_service {
    public static function call($servicename, $params = array(), $returntype = 'json') {
        global $CFG;
        try {
            $validreturntypes = array('html', 'object', 'objectList', 'string', 'integer', 'boolean', 'error');
            $site = get_site();
            $url = $_SERVER['SERVER_NAME'] = $CFG->cobra_serverhost;
            // Localhost config url   $url = 'http://localhost/cobra/services/service_handler.php'; .
            $params['caller'] = $site->shortname;
            if (count($params)) {
                $querystring = http_build_query($params, '', '&');
            }
            if (!$response = cobra_http_request($url . '?verb=' . $servicename . '&' . $querystring)) {
                throw new Exception('Unable to access required URL' . $url);
            }
            $response = json_decode($response);

            if (!in_array($response->responseType, $validreturntypes)) {
                throw new Exception(get_string('Unhandled return type') . '&nbsp;:&nbsp;' . $response->responseType);
            }
            if ('error' == $response->responseType) {
                throw new Exception(get_string(utf8_decode($response->content)));
            } else if ('html' == $response->responseType) {
                return utf8_decode($response->content);
            } else {
                return $response->content;
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
}