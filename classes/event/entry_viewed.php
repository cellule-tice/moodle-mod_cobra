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
 * Entry viewed event class.
 *
 * @package    mod_cobra
 * @author     Jean-Roch Meurisse
 * @copyright  2016 onwards - Cellule TICE - Universite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_cobra\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Class entry_viewed
 *
 * @package    mod_cobra
 * @author     Jean-Roch Meurisse
 * @copyright  2016 onwards - Cellule TICE - Universite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class entry_viewed extends \core\event\base {
    /**
     * Initialize the event
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'cobra_click';
    }

    /**
     * Get the event name.
     *
     * @return string
     * @throws \coding_exception
     */
    public static function get_name() {
        return get_string('entry_viewed', 'mod_cobra');
    }

    /**
     * Get a description of the event.
     * @return string
     */
    public function get_description() {
        return "The user with id {$this->userid} clicked entry with id {$this->other['lingentity']}";
    }

    /**
     * Get the url of the related cobra activity.
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/cobra/view.php', array('id' => $this->contextinstanceid));
    }
}