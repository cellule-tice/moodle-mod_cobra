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
 * Code to be executed after the plugin's database scheme has been installed is defined here.
 *
 * @package     mod_cobra
 * @category    upgrade
 * @copyright   2016 onwards - Cellule TICE - University of Namur
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use mod_cobra\local\helper;

require_once($CFG->dirroot . '/mod/cobra/locallib.php');

/**
 * Custom code to be run on installing the plugin.
 */
function xmldb_cobra_install() {

    set_config('serviceurl', 'https://webapps.unamur.be/elv/nederlex/services/api.php', 'mod_cobra');

    $key = helper::get_apikey();
    if (!empty($key->apikey)) {
        set_config('apikey', $key->apikey, 'mod_cobra');
        helper::update_glossary_cache(0);
        helper::update_text_info_cache(0);
        set_config('lastglossaryupdate', time(), 'mod_cobra');
        set_config('lasttextinfoupdate', time(), 'mod_cobra');
    }
    return true;
}
