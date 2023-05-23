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
 * Cobra remote service class definition.
 *
 * @package    mod_cobra
 * @author     Jean-Roch Meurisse
 * @copyright  2016 - Cellule TICE - Unversite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_cobra;

use curl;

/**
 * Class cobra_remote_service. This class handle calls to remote CoBRA system
 *
 * @package    mod_cobra
 * @author     Jean-Roch Meurisse
 * @copyright  2016 onwards - Cellule TICE - Universite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cobra_remote_service {

    /**
     * Send request to remote CoBRA system and return response
     *
     * @param string $servicename the request name
     * @param array $params arguments for the call
     * @return mixed
     * @throws cobra_remote_access_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public static function call($servicename, $params = array()) {
        $validreturntypes = array(
            'object',
            'objectList',
            'error'
        );
        $response = new \stdClass();
        $site = get_site();
        $params['caller'] = $site->shortname;
        $params['platformid'] = get_config('moodle', 'siteidentifier');
        $params['apikey'] = get_config('mod_cobra', 'apikey');
        $url = get_config('mod_cobra', 'serviceurl');

        $params['from'] = 'moodle';
        if (count($params)) {
            $querystring = http_build_query($params, '', '&');
        }
        $params['verb'] = $servicename;
        $curl = new curl();

        $curl->setHeader(array('Accept: application/json', 'Expect:'));

        $options = array(
            'FRESH_CONNECT' => true,
            'RETURNTRANSFER' => true,
            'FORBID_REUSE' => true,
            'HEADER' => 0,
            'CONNECTTIMEOUT' => 3,
            // Follow redirects with the same type of request when sent 301, or 302 redirects.
            'CURLOPT_POSTREDIR' => 3
        );

        $data = $curl->post($url . '?verb=' . $servicename . '&' . $querystring, json_encode($params), $options);

        if ($data === false) {

            throw new cobra_remote_access_exception('serviceunavailable');
        } else {
            $response = json_decode($data);
        }
        if (!in_array($response->responsetype, $validreturntypes)) {
            throw new \moodle_exception('unhandledreturntype', 'cobra', '', null, $response->responsetype);
        }
        if ('error' == $response->responsetype) {
            if ($response->errortype == COBRA_ERROR_PLATFORM_NOT_ALLOWED) {
                throw new cobra_remote_access_exception('platformnotallowed');
            }
            if ($response->errortype == COBRA_ERROR_MISSING_PARAM) {
                throw new \moodle_exception('missingparam', 'cobra', '', null);
            }
            if ($response->errortype == COBRA_ERROR_UNHANDLED_CALL) {
                throw new \moodle_exception('unhandledcall', 'cobra', '', null);
            }
        } else {
            return $response->content;
        }
    }
}
