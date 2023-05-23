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
 * Plugin upgrade steps are defined here.
 *
 * @package     mod_cobra
 * @category    upgrade
 * @copyright   2016 onwards - Cellule TICE - University of Namur
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/cobra/locallib.php');

/**
 * Execute mod_cobra upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_cobra_upgrade($oldversion) {

    global $DB;
    // Loads ddl manager and xmldb classes.
    $dbman = $DB->get_manager();

    if ($oldversion < 2018111603) {
        // Refactor click table.
        $table = new xmldb_table('cobra_clic');

        // Rename field nbclicsstats on table cobra_click to nbclicks.
        $field = new xmldb_field('nbclicsstats', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, '0', 'userid');

        // Launch rename field nbclicks.
        $dbman->rename_field($table, $field, 'nbclicks');

        $field = new xmldb_field('nbclicsglossary');

        // Conditionally launch drop field nbclicsglossary.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Finally rename table cobra_clic to cobra_click.
        $dbman->rename_table($table, 'cobra_click');

        upgrade_mod_savepoint(true, 2018111603, 'cobra');
    }

    return true;
}
