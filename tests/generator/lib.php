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
 * mod_cobra data generator class.
 *
 * @package    mod_cobra
 * @category   test
 * @author     Jean-Roch Meurisse
 * @copyright  2016 onwards - Cellule TICE - Universite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Defines class mod_cobra_generator.
 */
class mod_cobra_generator extends testing_module_generator {

    /**
     * Create a cobra instance for testing purpose
     *
     * @param stdClass $record
     * @param array $options
     * @return stdClass
     * @throws coding_exception
     * @throws dml_exception
     */
    public function create_instance($record = null, array $options = null) {
        global $CFG;
        require_once($CFG->dirroot .'/mod/cobra/locallib.php');

        $record = (object)(array)$record;
        $config = get_config('mod_cobra');

        // Add default values for cobra.
        $defaultsettings = array(
            'collection' => 1,
            'text' => 1,
            'name' => get_string('cobraname', 'cobra'),
            'language' => 'EN',
            'userglossary' => $config->userglossary,
            'audioplayer' => $config->audioplayer,
            'examples' => $config->examples,
            'translations' => $config->translations,
            'annotations' => $config->annotations,
            'corpusorder' => $config->defaultcorpusorderen,
            'isdefaultcorpusorder' => 1,
            'isdefaultdisplayprefs' => 1
        );
        foreach ($defaultsettings as $name => $value) {
            if (!isset($record->{$name})) {
                $record->{$name} = $value;
            }
        }

        return parent::create_instance($record, (array)$options);
    }

    /**
     * Add data for testing purpose
     *
     * @param stdClass $cobraobject
     * @return void
     */
    public function init_local_data($cobraobject) {
        global $DB;
        $record1 = new stdClass();
        $record1->lingentity = 34347;
        $record1->entry = 'friend';
        $record1->type = 'lemma';
        $record1->translations = 'ami';
        $record1->category = 'n';
        $record1->extrainfo = 'friends';
        $DB->insert_record('cobra_glossary_cache', $record1);

        $record2 = new stdClass();
        $record2->course = $cobraobject->course;
        $record2->user_id = $cobraobject->user;
        $record2->id_text = $cobraobject->text;
        $record2->id_entite_ling = 34347;
        $record2->nbclicsstats = 1;
        $record2->nbclicsglossary = 1;
        $record2->datecreate = time();
        $record2->datemodif = time();
        $record2->in_glossary = 0;
        $result = $DB->insert_record('cobra_clic', $record2);

        $record3 = new stdClass();
        $record3->lingentity = 27305;
        $record3->entry = 'help';
        $record3->type = 'lemma';
        $record3->translations = 'aider';
        $record3->category = 'v';
        $record3->extrainfo = 'helped, helped';
        $DB->insert_record('cobra_glossary_cache', $record3);

        $record4 = new stdClass();
        $record4->course = $cobraobject->course;
        $record4->user_id = $cobraobject->user;
        $record4->id_text = $cobraobject->text;
        $record4->id_entite_ling = 27305;
        $record4->nbclicsstats = 1;
        $record4->nbclicsglossary = 1;
        $record4->datecreate = time();
        $record4->datemodif = time();
        $record4->in_glossary = 1;
        $result = $DB->insert_record('cobra_clic', $record4);

        $record5 = new stdClass();
        $record5->lingentity = 40738;
        $record5->entry = 'long';
        $record5->type = 'lemma';
        $record5->translations = 'depuis longtemps, longtemps';
        $record5->category = 'adv';
        $record5->extrainfo = 'longer, longest';
        $DB->insert_record('cobra_glossary_cache', $record5);

        $record6 = new stdClass();
        $record6->course = $cobraobject->course;
        $record6->user_id = $cobraobject->user;
        $record6->id_text = 2;
        $record6->id_entite_ling = 40738;
        $record6->nbclicsstats = 1;
        $record6->nbclicsglossary = 1;
        $record6->datecreate = time();
        $record6->datemodif = time();
        $record6->in_glossary = 1;
        $result = $DB->insert_record('cobra_clic', $record6);
    }
}
