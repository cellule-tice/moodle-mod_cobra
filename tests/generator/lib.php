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
 * mod_url data generator.
 *
 * @package    mod_url
 * @category   test
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * mod_cobra data generator class.
 *
 * @package    mod_cobra
 * @category   test
 * @author     Jean-Roch Meurisse
 * @copyright  2016 onwards - Cellule TICE - Universite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_cobra_generator extends testing_module_generator {

    public function create_instance($record = null, array $options = null) {
        global $CFG;
        require_once($CFG->dirroot .'/mod/cobra/locallib.php');

        // Add default values for cobra.
        $record = (array)$record + array(
            'collection' => 1,
            'text' => 1,
            'name' => 'Lecture de textes',
            'language' => 'EN',
            'userglossary' => 1,
            'audioplayer' => 1,
            'nextprevbuttons' => 0,
            'examples' => COBRA_EXAMPLES_BILINGUAL,
            'translations' => COBRA_TRANSLATIONS_CONDITIONAL,
            'annotations' => COBRA_ANNOTATIONS_CONDITIONAL,
            'corpusorder' => '1,11,21',
            'isdefaultcorpusorder' => 1,
            'isdefaultprefs' => 1

        );

        return parent::create_instance($record, (array)$options);
    }
}
