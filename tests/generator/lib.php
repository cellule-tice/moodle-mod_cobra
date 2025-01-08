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

/**
 * Defines class mod_cobra_generator.
 *
 * @package    mod_cobra
 * @author     Jean-Roch Meurisse
 * @copyright  2016 onwards - Cellule TICE - Universite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_cobra_generator extends testing_module_generator {

    /**
     * Create a cobra instance for testing purpose
     *
     * @param stdClass|null $record
     * @param array|null $options
     * @return stdClass
     * @throws coding_exception
     * @throws dml_exception
     */
    public function create_instance($record = null, ?array $options = null) {
        global $CFG;
        require_once($CFG->dirroot .'/mod/cobra/locallib.php');

        $record = (object)(array)$record;
        $config = get_config('mod_cobra');

        // Add default values for cobra.
        $defaultsettings = [
            'collection' => 21,
            'text' => 2139,
            'name' => get_string('cobraname', 'cobra'),
            'language' => 'EN',
            'userglossary' => $config->userglossary,
            'audioplayer' => $config->audioplayer,
            'examples' => $config->examples,
            'translations' => $config->translations,
            'annotations' => $config->annotations,
            'corpusorder' => $config->defaultcorpusorderen,
            'isdefaultcorpusorder' => 1,
            'isdefaultdisplayprefs' => 1,
        ];
        foreach ($defaultsettings as $name => $value) {
            if (!isset($record->{$name})) {
                $record->{$name} = $value;
            }
        }

        return parent::create_instance($record, (array)$options);
    }

    /**
     * Add glossary and text data for testing purpose
     * @return void
     */
    public function init_local_data() {
        global $DB;
        $record1 = new stdClass();
        $record1->lingentity = 36638;
        $record1->entry = 'language';
        $record1->type = 'lemma';
        $record1->translations = 'langue';
        $record1->category = 'n';
        $record1->extrainfo = 'languages';
        $DB->insert_record('cobra_glossary_cache', $record1);

        $record2 = new stdClass();
        $record2->lingentity = 147302;
        $record2->entry = 'as far as is concerned';
        $record2->type = 'expression';
        $record2->translations = 'en ce qui concerne';
        $record2->category = 'conj';
        $record2->extrainfo = '';
        $DB->insert_record('cobra_glossary_cache', $record2);

        $record3 = new stdClass();
        $record3->lingentity = 36515;
        $record3->entry = 'start';
        $record3->type = 'lemma';
        $record3->translations = 'commencer, débuter, démarrer, lancer';
        $record3->category = 'v';
        $record3->extrainfo = 'started, started';
        $DB->insert_record('cobra_glossary_cache', $record3);

        $record4 = new stdClass();
        $record4->id = 2139;
        $record4->title = 'Best to learn a new language before age of 10';
        $record4->collection = 21;
        $DB->insert_record_raw('cobra_text_info_cache', $record4, false, false, true);
    }

    /**
     * Add user data for testing purposes.
     *
     * @param int $userid the userid to insert data for
     * @param stdClass $cobraobject the related cobra instance object
     * @return void
     */
    public function init_user_data($userid, $cobraobject) {
        global $DB;
        $record1 = new stdClass();
        $record1->cobra = $cobraobject->id;
        $record1->course = $cobraobject->course;
        $record1->userid = $userid;
        $record1->textid = $cobraobject->text;
        $record1->lingentity = 36638;
        $record1->nbclicks = 1;
        $record1->timecreated = time();
        $record1->timemodified = time();
        $record1->inglossary = 0;
        $DB->insert_record('cobra_click', $record1);

        $record2 = new stdClass();
        $record2->cobra = $cobraobject->id;
        $record2->course = $cobraobject->course;
        $record2->userid = $userid;
        $record2->textid = $cobraobject->text;
        $record2->lingentity = 147302;
        $record2->nbclicks = 1;
        $record2->timecreated = time();
        $record2->timemodified = time();
        $record2->inglossary = 1;
        $DB->insert_record('cobra_click', $record2);

        $record3 = new stdClass();
        $record3->cobra = $cobraobject->id;
        $record3->course = $cobraobject->course;
        $record3->userid = $userid;
        $record3->textid = $cobraobject->text;
        $record3->lingentity = 36515;
        $record3->nbclicks = 1;
        $record3->timecreated = time();
        $record3->timemodified = time();
        $record3->inglossary = 1;
        $DB->insert_record('cobra_click', $record3);
    }
}
