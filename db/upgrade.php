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

    if ($oldversion < 2017042800) {

        // Define table cobra to be created.
        $table = new xmldb_table('cobra');
        $fields = array();

        // Adding fields to table cobra.
        new xmldb_field('collection', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, '0', 'course');
        $fields[] = new xmldb_field('collection', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'course');
        $fields[] = new xmldb_field('text', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'collection');
        $fields[] = new xmldb_field('language', XMLDB_TYPE_CHAR, '2', null, XMLDB_NOTNULL, null, 'EN', 'timemodified');
        $fields[] = new xmldb_field('userglossary', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'language');
        $fields[] = new xmldb_field('audioplayer', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'userglossary');
        $fields[] = new xmldb_field('examples', XMLDB_TYPE_CHAR, '12', null, XMLDB_NOTNULL, null, 'bilingual', 'audioplayer');
        $fields[] = new xmldb_field('translations', XMLDB_TYPE_CHAR, '12', null, XMLDB_NOTNULL, null, 'conditional', 'examples');
        $fields[] = new xmldb_field('annotations', XMLDB_TYPE_CHAR, '12', null, null, null, 'conditional', 'translations');
        $fields[] = new xmldb_field('corpusorder', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null, 'annotations' );
        $fields[] = new xmldb_field('isdefaultcorpusorder', XMLDB_TYPE_INTEGER, '1', null,
                XMLDB_NOTNULL, null, '0', 'corpusorder');
        $fields[] = new xmldb_field('isdefaultdisplayprefs', XMLDB_TYPE_INTEGER, '1', null,
                XMLDB_NOTNULL, null, '0', 'isdefaultcorpusorder');

        // Loop add fields to cobra main table.
        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }
        // Cobra savepoint reached.
        upgrade_mod_savepoint(true, 2017042800, 'cobra');
    }

    if ($oldversion < 2017042821) {

        // Define table cobra_clic to be created.
        $table = new xmldb_table('cobra_clic');

        // Adding fields to table cobra_clic.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('course', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('id_entite_ling', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('id_text', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $table->add_field('user_id', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('nbclicsstats', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('nbclicsglossary', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('datecreate', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('datemodif', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('in_glossary', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table cobra_clic.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table cobra_clic.
        $table->add_index('course', XMLDB_INDEX_NOTUNIQUE, array('course'));
        $table->add_index('id_entite_ling', XMLDB_INDEX_NOTUNIQUE, array('id_entite_ling'));
        $table->add_index('id_text', XMLDB_INDEX_NOTUNIQUE, array('id_text'));
        $table->add_index('user_id', XMLDB_INDEX_NOTUNIQUE, array('user_id'));

        // Conditionally launch create table for cobra_clic.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Cobra savepoint reached.
        upgrade_mod_savepoint(true, 2017042821, 'cobra');
    }

    if ($oldversion < 2017042823) {

        // Define table cobra_glossary_cache to be created.
        $table = new xmldb_table('cobra_glossary_cache');

        // Adding fields to table cobra_glossary_cache.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('lingentity', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('entry', XMLDB_TYPE_CHAR, '128', null, XMLDB_NOTNULL, null, null);
        $table->add_field('type', XMLDB_TYPE_CHAR, '12', null, XMLDB_NOTNULL, null, null);
        $table->add_field('translations', XMLDB_TYPE_CHAR, '512', null, XMLDB_NOTNULL, null, null);
        $table->add_field('category', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL, null, null);
        $table->add_field('extrainfo', XMLDB_TYPE_CHAR, '128', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table cobra_glossary_cache.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('secondary', XMLDB_KEY_UNIQUE, array('lingentity'));

        // Conditionally launch create table for cobra_glossary_cache.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table cobra_text_info_cache to be created.
        $table2 = new xmldb_table('cobra_text_info_cache');

        // Adding fields to table cobra_text_info_cache.
        $table2->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table2->add_field('title', XMLDB_TYPE_CHAR, '256', null, XMLDB_NOTNULL, null, null);
        $table2->add_field('collection', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table2->add_field('cecrl', XMLDB_TYPE_CHAR, '3', null, null, null, null);
        $table2->add_field('entities', XMLDB_TYPE_TEXT, null, null, null, null, null);

        // Adding keys to table cobra_text_info_cache.
        $table2->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for cobra_text_info_cache.
        if (!$dbman->table_exists($table2)) {
            $dbman->create_table($table2);
        }

        // Cobra savepoint reached.
        upgrade_mod_savepoint(true, 2017042823, 'cobra');
    }

    if ($oldversion < 2017042829) {

        $fillcachetablestask = new \mod_cobra\task\fill_cache_tables_task();
        \core\task\manager::queue_adhoc_task($fillcachetablestask);
        // Cobra savepoint reached.
        upgrade_mod_savepoint(true, 2017042829, 'cobra');
    }

    return true;
}
