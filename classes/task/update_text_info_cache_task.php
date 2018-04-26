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
 * Scheduled task to update text info cache table.
 *
 * @package    mod_cobra
 * @author     Jean-Roch Meurisse
 * @copyright  2016 - Cellule TICE - Unversite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_cobra\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/cobra/locallib.php');

/**
 * Class update_text_info_cache_task
 */
class update_text_info_cache_task extends \core\task\scheduled_task {

    /**
     * Get task name.
     *
     * @return string
     * @throws \coding_exception
     */
    public function get_name() {
        return get_string('updatetextinfocache', 'mod_cobra');
    }

    /**
     * Execute task.
     *
     * @throws \dml_exception
     */
    public function execute() {
        mtrace('Update basic information for texts (title, cecrl)');
        list($new, $updated) = cobra_update_text_info_cache(get_config('mod_cobra', 'lasttextinfoupdate'));
        mtrace($new . ' entries inserted');
        mtrace($updated . ' entries updated');
        set_config('lasttextinfoupdate', time(), 'mod_cobra');
    }
}