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
 * Defines backup_cobra_activity_structure_step class
 *
 * @package    mod_cobra
 * @author     Jean-Roch Meurisse
 * @copyright  2016 onwards - Cellule TICE - Unversite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the backup steps that will be used by the backup_cobra_activity_task
 *
 * @package    mod_cobra
 * @author     Jean-Roch Meurisse
 * @copyright  2016 onwards - Cellule TICE - Universite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_cobra_activity_structure_step extends backup_activity_structure_step {

    /**
     * Define the complete cobra structure for backup, with file and id annotations
     */
    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $cobra = new backup_nested_element('cobra', array('id'), array(
            'collection', 'text', 'name', 'intro', 'introformat', 'timecreated', 'timemodified',
            'language', 'userglossary', 'audioplayer', 'examples', 'translations', 'annotations',
            'corpusorder', 'isdefaultcorpusorder', 'isdefaultdisplayprefs'));

        $clics = new backup_nested_element('clics');

        $clic = new backup_nested_element('clic', array('id'), array(
            'lingentity', 'textid', 'userid', 'nbclicks',
            'timecreated', 'timemodified', 'inglossary'));

        // Build the tree.
        $cobra->add_child($clics);
        $clics->add_child($clic);

        // Define sources.
        $cobra->set_source_table('cobra', array('id' => backup::VAR_ACTIVITYID));

        // Clicks included only if we are including user info.
        if ($userinfo) {
            $clic->set_source_table('cobra_click', array('cobra' => backup::VAR_PARENTID), 'id ASC');
        }

        // Define id annotations.
        $clic->annotate_ids('user', 'userid');

        // Define file annotations.
        $cobra->annotate_files('mod_cobra', 'intro', null);

        // Return the root element (choice), wrapped into standard activity structure.

        return $this->prepare_activity_structure($cobra);
    }
}
