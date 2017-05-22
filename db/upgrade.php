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
 * @author     Jean-Roch Meurisse
 * @author     Laurence Dumortier
 * @copyright  2016 onwards - Cellule TICE - Universite de Namur
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

    if ($oldversion < 2016051000) {

        // Define field position to be added to cobra_ordre_concordances.
        $table = new xmldb_table('cobra_ordre_concordances');
        $field = new xmldb_field('position', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'id_type');

        // Conditionally launch add field position.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $updatedata = "TRUNCATE {cobra_ordre_concordances}";
        $DB->execute($updatedata);
        // Cobra savepoint reached.
        upgrade_mod_savepoint(true, 2016051000, 'cobra');
    }

    if ($oldversion < 2016051800) {

        // Define field id_text to be dropped from cobra_texts_config.
        $table = new xmldb_table('cobra_texts_config');
        $field = new xmldb_field('text_type');

        // Conditionally launch drop field id_text.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Cobra savepoint reached.
        upgrade_mod_savepoint(true, 2016051800, 'cobra');
    }

    if ($oldversion < 2017050801) {

        // Define field collection to be added to cobra.
        $table = new xmldb_table('cobra');
        $field = new xmldb_field('collection', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, '0', 'course');

        // Conditionally launch add field collection.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field text to be added to cobra.
        $field = new xmldb_field('text', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, '0', 'collection');

        // Conditionally launch add field text.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('isdefaultdisplayprefs', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'annotations');

        // Conditionally launch add field isdefaultcorpusorder.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field corpusorder to be added to cobra.
        $field = new xmldb_field('corpusorder', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null, 'annotations');

        // Conditionally launch add field corpusorder.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('isdefaultcorpusorder', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'corpusorder');

        // Conditionally launch add field isdefaultcorpusorder.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // Cobra savepoint reached.
        upgrade_mod_savepoint(true, 2017050801, 'cobra');
    }

    if ($oldversion < 2017050807) {

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

        // Cobra savepoint reached.
        upgrade_mod_savepoint(true, 2017050807, 'cobra');
    }

    if ($oldversion < 2017050809) {

        set_config('lastglossaryupdate', 0, 'mod_cobra');
        // Cobra savepoint reached.
        upgrade_mod_savepoint(true, 2017050809, 'cobra');
    }

    if ($oldversion < 2017050811) {

        // Define table cobra_text_info_cache to be created.
        $table = new xmldb_table('cobra_text_info_cache');

        // Adding fields to table cobra_text_info_cache.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('title', XMLDB_TYPE_CHAR, '256', null, XMLDB_NOTNULL, null, null);
        $table->add_field('collection', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('cecrl', XMLDB_TYPE_CHAR, '3', null, null, null, null);

        // Adding keys to table cobra_text_info_cache.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for cobra_text_info_cache.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        set_config('lasttextinfoupdate', 0, 'mod_cobra');

        // Cobra savepoint reached.
        upgrade_mod_savepoint(true, 2017050811, 'cobra');
    }

    if ($oldversion < 2017050812) {

        // Define field entities to be added to cobra_text_info_cache.
        $table = new xmldb_table('cobra_text_info_cache');
        $field = new xmldb_field('entities', XMLDB_TYPE_TEXT, null, null, null, null, null, 'cecrl');

        // Conditionally launch add field entities.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Cobra savepoint reached.
        upgrade_mod_savepoint(true, 2017050812, 'cobra');
    }

    return true;
}
