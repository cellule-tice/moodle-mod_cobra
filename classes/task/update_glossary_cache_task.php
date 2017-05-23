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
 * @copyright  2016 onwards - Cellule TICE - Unversite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_cobra\task;

require_once($CFG->dirroot . '/mod/cobra/locallib.php');

class update_glossary_cache_task extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('updateglossarycache', 'mod_cobra');
    }

    public function execute() {
        mtrace('Load dirty entries from remote CoBRA server');
        list($new, $updated) = cobra_update_text_info_cache();
        mtrace($new . ' entries inserted');
        mtrace($updated . ' entries updated');
        set_config('lastglossaryupdate', time(), 'mod_cobra');
    }
}