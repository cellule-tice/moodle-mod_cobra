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
 * Ad-hoc task to fill cobra cache tables.
 *
 * @package    mod_cobra
 * @author     Jean-Roch Meurisse
 * @copyright  2016 - Cellule TICE - Unversite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_cobra\task;

use mod_cobra\local\helper;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/cobra/locallib.php');

/**
 * Class fill_cache_tables_task
 *
 * @package    mod_cobra
 * @author     Jean-Roch Meurisse
 * @copyright  2016 onwards - Cellule TICE - Universite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class fill_cache_tables_task extends \core\task\adhoc_task {

    /**
     * Execute ad-hoc task.
     * @throws \dml_exception
     */
    public function execute() {
        helper::update_glossary_cache((int)get_config('mod_cobra', 'lastglossaryupdate'));
        helper::update_text_info_cache((int)get_config('mod_cobra', 'lasttextinfoupdate'));
        set_config('lastglossaryupdate', time(), 'mod_cobra');
        set_config('lasttextinfoupdate', time(), 'mod_cobra');
    }
}
