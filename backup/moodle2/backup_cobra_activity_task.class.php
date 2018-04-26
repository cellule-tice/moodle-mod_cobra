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
 * Defines backup_cobra_activity_task class
 *
 * @package    mod_cobra
 * @author     Jean-Roch Meurisse
 * @copyright  2016 - Cellule TICE - Unversite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/cobra/backup/moodle2/backup_cobra_stepslib.php');

/**
 * Provides the steps to perform one complete backup of the Cobra instance
 */
class backup_cobra_activity_task extends backup_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // Cobra only has one structure step.
        $this->add_step(new backup_cobra_activity_structure_step('cobra_structure', 'cobra.xml'));
    }

    /**
     * Encodes URLs to the index.php and view.php scripts
     *
     * @param string $content some HTML text that eventually contains URLs to the activity instance scripts
     * @return string the content with the URLs encoded
     */
    static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, "/");

        // Link to the list of choices.
        $search = "/(".$base."\/mod\/cobra\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@COBRAINDEX*$2@$', $content);

        // Link to choice view by moduleid.
        $search = "/(".$base."\/mod\/cobra\/view.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@COBRAVIEWBYID*$2@$', $content);

        return $content;
    }
}