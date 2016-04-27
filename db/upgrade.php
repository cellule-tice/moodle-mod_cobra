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
 * This file keeps track of upgrades to the cobra module
 *
 * Sometimes, changes between versions involve alterations to database
 * structures and other major things that may break installations. The upgrade
 * function in this file will attempt to perform all the necessary actions to
 * upgrade your older installation to the current version. If there's something
 * it cannot do itself, it will tell you what you need to do.  The commands in
 * here will all be database-neutral, using the functions defined in DLL libraries.
 *
 * @package    mod_cobra
 * @copyright  2016 - Cellule TICE - Unversite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute cobra upgrade from the given old version
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_cobra_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.
    if ($oldversion < 2016042000) {

        // Define field intro to be added to cobra.
        $table = new xmldb_table('cobra');
        $field = new xmldb_field('intro', XMLDB_TYPE_TEXT, null, null, null, null, null, 'name');

        // Conditionally launch add field intro.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field introformat to be added to cobra.
        $field = new xmldb_field('introformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'intro');

        // Conditionally launch add field introformat.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field nextprevbuttons to be added to cobra.
        $field = new xmldb_field('nextprevbuttons', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'language');

        // Conditionally launch add field nextprevbuttons.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field userglossary to be added to cobra.
        $field = new xmldb_field('userglossary', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'nextprevbuttons');

        // Conditionally launch add field userglossary.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field audioplayer to be added to cobra.
        $field = new xmldb_field('audioplayer', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'userglossary');

        // Conditionally launch add field audioplayer.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field examples to be added to cobra.
        $field = new xmldb_field('examples', XMLDB_TYPE_CHAR, '12', null, XMLDB_NOTNULL, null, 'bilingual', 'audioplayer');

        // Conditionally launch add field examples.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field translations to be added to cobra.
        $field = new xmldb_field('translations', XMLDB_TYPE_CHAR, '12', null, XMLDB_NOTNULL, null, 'conditional', 'examples');

        // Conditionally launch add field translations.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field descriptions to be added to cobra.
        $field = new xmldb_field('annotations', XMLDB_TYPE_CHAR, '12', null, XMLDB_NOTNULL, null, 'conditional', 'translations');

        // Conditionally launch add field descriptions.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field in_glossary to be added to cobra_clic.
        $table = new xmldb_table('cobra_clic');
        $field = new xmldb_field('in_glossary', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'datemodif');

        // Conditionally launch add field in_glossary.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Cobra savepoint reached.
        upgrade_mod_savepoint(true, 2016042000, 'cobra');
    }

    if ($oldversion < 2016042700) {

        // Rename field nbclicsstats on table cobra_clic to NEWNAMEGOESHERE.
        $table = new xmldb_table('cobra_clic');
        $field = new xmldb_field('nb_clics', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, '0', 'user_id');

        // Launch rename field nbclicsstats.
        $dbman->rename_field($table, $field, 'nbclicsstats');

        // Define field nbclicsglossary to be added to cobra_clic.
        $field = new xmldb_field('nbclicsglossary', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, '0', 'nbclicsstats');

        // Conditionally launch add field nbclicsglossary.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $updatedata = "UPDATE {cobra_clic}
                          SET nbclicsglossary = nbclicsstats";
        $DB->execute($updatedata);

        // Cobra savepoint reached.
        upgrade_mod_savepoint(true, 2016042700, 'cobra');
    }
    return true;
}
