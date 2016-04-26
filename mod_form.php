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
 * The main cobra configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_cobra
 * @copyright  2016 - Cellule TICE - Unversite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once(__DIR__ . '/locallib.php');

/**
 * Module instance settings form
 *
 * @package    mod_cobra
 * @copyright  2015 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_cobra_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {

        global $DB, $CFG;

        //$courseid = optional_param('course', 0, PARAM_INT);
        $mode = optional_param('add', null, PARAM_ALPHA);
        if (empty($mode)) {
            $mode = 'edit';
        } else {
            $mode = 'add';
            $courseid = required_param('course', PARAM_INT);
        }

        if ('add' == $mode && $DB->record_exists('cobra', array('course' => $courseid))) {
       // if ($this->context->contextlevel == CONTEXT_MODULE)
            $url = new moodle_url('/course/view.php', array('id' => $courseid));
            redirect($url, 'Une seule instance de CoBRA est autorisée pour un cours', 3);
        } else {
            $mform = $this->_form;

            // Adding the "general" fieldset, where all the common settings are showed.
            $mform->addElement('header', 'general', get_string('general', 'form'));

            // Adding a hidden field to prevent prohibited changes in edit mode (language).
            $mform->addElement('hidden', 'mode', $mode);
            $mform->setType('mode', PARAM_ALPHA);

            // Adding the standard "name" field.
            $mform->addElement('text', 'name', get_string('cobraname', 'cobra'), array('size' => '64'));
            if (!empty($CFG->formatstringstriptags)) {
                $mform->setType('name', PARAM_TEXT);
            } else {
                $mform->setType('name', PARAM_CLEANHTML);
            }
            $mform->addRule('name', null, 'required', null, 'client');
            $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
            $mform->addHelpButton('name', 'cobraname', 'cobra');
            $mform->setDefault('name', get_string('textreading', 'cobra'));

            // Introduction.
            $this->standard_intro_elements();

            $options = cobra_get_foreign_languages();
            $mform->addElement('select', 'language', get_string('language', 'cobra'), $options);
            $mform->disabledIf('language', 'mode', 'edit');
            $mform->addHelpButton('language', 'language', 'cobra');

            // Add display preferences elements
            $mform->addElement('header', 'displaysettings', get_string('displaysettings', 'cobra'));

            // Add checkbox for next and previous buttons
            $mform->addElement('advcheckbox',
                    'nextprevbuttons',
                    get_string('nextprevbuttons', 'cobra'),
                    null,
                    null,
                    array(0, 1)
                );
            $mform->addHelpButton('nextprevbuttons', 'nextprevbuttons', 'cobra');

            // Add checkbox for next and previous buttons
            $mform->addElement('advcheckbox',
                    'userglossary',
                    get_string('userglossary', 'cobra'),
                    null,
                    null,
                    array(0, 1)
                );
            $mform->addHelpButton('userglossary', 'userglossary', 'cobra');

            // Add checkbox for next and previous buttons
            $mform->addElement('advcheckbox',
                    'audioplayer',
                    get_string('audioplayer', 'cobra'),
                    null,
                    null,
                    array(0, 1)
                );
            $mform->addHelpButton('audioplayer', 'audioplayer', 'cobra');

            // Add select box for examples display mode.
            $options = array(
                COBRA_EXAMPLES_BILINGUAL => get_string('bilingual', 'cobra'),
                COBRA_EXAMPLES_MONOLINGUAL => get_string('monolingual', 'cobra')
            );
            $mform->addElement('select', 'examples', get_string('examplesdisplaymode', 'cobra'), $options);
            $mform->addHelpButton('examples', 'examplesdisplaymode', 'cobra');
            $mform->setDefault('examples', COBRA_EXAMPLES_BILINGUAL);

            // Add select box for translations display mode.
            $options = array(
                COBRA_TRANSLATIONS_ALWAYS => get_string('always', 'cobra'),
                COBRA_TRANSLATIONS_CONDITIONAL => get_string('conditional', 'cobra'),
                COBRA_TRANSLATIONS_NEVER => get_string('never', 'cobra')
            );
            $mform->addElement('select', 'translations', get_string('translationsdisplaymode', 'cobra'), $options);
            $mform->setDefault('translations', COBRA_TRANSLATIONS_CONDITIONAL);

            // Add select box for descriptions display mode.
            $options = array(
                COBRA_TRANSLATIONS_ALWAYS => get_string('always', 'cobra'),
                COBRA_TRANSLATIONS_CONDITIONAL => get_string('conditional', 'cobra'),
                COBRA_TRANSLATIONS_NEVER => get_string('never', 'cobra')
            );
            $mform->addElement('select', 'annotations', get_string('annotationsdisplaymode', 'cobra'), $options);
            //$mform->addHelpButton('descriptions', 'descriptions', 'cobra');
            $mform->setDefault('annotations', COBRA_TRANSLATIONS_CONDITIONAL);

            // Add standard elements, common to all modules.
            $this->standard_coursemodule_elements();
            // Add standard buttons, common to all modules.
            $this->add_action_buttons();
        }
    }
}
