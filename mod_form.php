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
 * Cobra resource add/edit form.
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 *147 @package    mod_cobra
 * @author     Jean-Roch Meurisse
 * @author     Laurence Dumortier
 * @copyright  2016 onwards - Cellule TICE - Universite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once(__DIR__ . '/locallib.php');

class mod_cobra_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {

        global $DB, $CFG, $PAGE, $COURSE;
        $jsparams = array();
        $jsparams['en'] = cobra_get_default_corpus_order($COURSE->id, 'EN');
        $jsparams['nl'] = cobra_get_default_corpus_order($COURSE->id, 'NL');
        $PAGE->requires->css('/mod/cobra/css/cobra.css');
        $PAGE->requires->js_call_amd('mod_cobra/cobra', 'mod_form_triggers', array(json_encode($jsparams)));

        // Are we adding or updating a CoBRA resource
        $mode = optional_param('add', null, PARAM_ALPHA);
        if (empty($mode)) {
            $mode = 'edit';
        } else {
            $mode = 'add';
        }

        $mform = $this->_form;

        // Adding the "General" fieldset, where all the main settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding a hidden field to keep mode in definition after data.
        $mform->addElement('hidden', 'mode', $mode);
        $mform->setType('mode', PARAM_ALPHA);

        $options = cobra_get_foreign_languages();

        if ($mode == 'add') {
            array_unshift($options, '-');
            $defaultsettings = $DB->get_record('cobra', array('course' => $COURSE->id, 'isdefaultdisplayprefs' => 1));
        }

        if (empty($defaultsettings)) {
            $defaultsettings = new stdClass();
            $defaultsettings->userglossary = get_config('mod_cobra', 'userglossary');
            $defaultsettings->audioplayer = get_config('mod_cobra', 'audioplayer');
            $defaultsettings->examples = get_config('mod_cobra', 'examples');
            $defaultsettings->translations = get_config('mod_cobra', 'translations');
            $defaultsettings->annotations = get_config('mod_cobra', 'annotations');
        }

        $mform->addElement('select', 'language', get_string('language', 'cobra'), $options);

        $mform->addHelpButton('language', 'language', 'cobra');
        $mform->addRule('language', 'You must select a language', 'lettersonly');
        $mform->disabledIf('language', 'mode', 'eq', 'edit');

        // Button to update collection fieldset on language change (will be hidden by JavaScript).
        $mform->registerNoSubmitButton('updatelanguage');
        $mform->addElement('submit', 'updatelanguage', 'languageudpate', array('class' => 'hidden'));

        // Just a placeholder for inserting collection select after language change.
        $mform->addElement('hidden', 'addcollectionshere');
        $mform->setType('addcollectionshere', PARAM_BOOL);

        // Button to update collection fieldset on collection change (will be hidden by JavaScript).
        $mform->registerNoSubmitButton('selectcollection');
        $mform->addElement('submit', 'selectcollection', 'collectionselect', array('class' => 'hidden'));

        // Just a placeholder for inserting text select after collection change.
        $mform->addElement('hidden', 'addtextshere');
        $mform->setType('addtextshere', PARAM_BOOL);

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

        // Introduction.
        $this->standard_intro_elements();

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

        // Add checkbox to enable/disable user glossary
        $mform->addElement('advcheckbox',
            'userglossary',
            get_string('userglossary', 'cobra'),
            null,
            null,
            array(0, 1)
        );
        $mform->addHelpButton('userglossary', 'userglossary', 'cobra');
        $mform->setDefault('userglossary', $defaultsettings->userglossary);

        // Add checkbox to enable/disable audio player display
        $mform->addElement('advcheckbox',
            'audioplayer',
            get_string('audioplayer', 'cobra'),
            null,
            null,
            array(0, 1)
        );
        $mform->addHelpButton('audioplayer', 'audioplayer', 'cobra');
        $mform->setDefault('audioplayer', $defaultsettings->audioplayer);

        // Add select box for examples display mode.
        $options = array(
            COBRA_EXAMPLES_BILINGUAL => get_string('bilingual', 'cobra'),
            COBRA_EXAMPLES_MONOLINGUAL => get_string('monolingual', 'cobra')
        );
        $mform->addElement('select', 'examples', get_string('examplesdisplaymode', 'cobra'), $options);
        $mform->addHelpButton('examples', 'examplesdisplaymode', 'cobra');
        $mform->setDefault('examples', $defaultsettings->examples);

        // Add select box for translations display mode.
        $options = array(
            COBRA_TRANSLATIONS_ALWAYS => get_string('always'),
            COBRA_TRANSLATIONS_CONDITIONAL => get_string('conditional', 'cobra'),
            COBRA_TRANSLATIONS_NEVER => get_string('never')
        );
        $mform->addElement('select', 'translations', get_string('translationsdisplaymode', 'cobra'), $options);
        $mform->setDefault('translations', $defaultsettings->translations);

        // Add select box for descriptions display mode.
        $options = array(
            COBRA_TRANSLATIONS_ALWAYS => get_string('always'),
            COBRA_TRANSLATIONS_CONDITIONAL => get_string('conditional', 'cobra'),
            COBRA_TRANSLATIONS_NEVER => get_string('never')
        );
        $mform->addElement('select', 'annotations', get_string('annotationsdisplaymode', 'cobra'), $options);
        //$mform->addHelpButton('descriptions', 'descriptions', 'cobra');
        $mform->setDefault('annotations', $defaultsettings->annotations);

        // Add checkbox to flag display settings as default for future instances
        $mform->addElement('advcheckbox',
            'isdefaultdisplayprefs',
            get_string('defaultflag', 'cobra'),
            null,
            null,
            array(0, 1)
        );
        //$mform->addHelpButton('isdef', 'userglossary', 'cobra');
        $mform->setDefault('isdefaultdisplayprefs', 0);
        $mform->addHelpButton('isdefaultdisplayprefs', 'defaultflag', 'cobra');

        // Hidden button to flag display settings as default.
        $mform->registerNoSubmitButton('updatedefaultdisplayprefs');
        $mform->addElement('submit', 'updatedefaultdisplayprefs', 'defaultdisplayupdate', array('class' => 'hidden'));
        $mform->addElement('header', 'corpusselection', get_string('corpusselection', 'cobra'));
        $mform->addElement('text', 'corpusorder', get_string('corpusselection', 'cobra'));
        $mform->setType('corpusorder', PARAM_TEXT);
        $mform->setDefault('corpusorder', cobra_get_default_corpus_order($COURSE->id, $mform->getElementValue('language')));
        $mform->addHelpButton('corpusorder', 'corpusselection', 'cobra');

        // Add checkbox to flag corpus selection as default for future instances
        $mform->addElement('advcheckbox',
            'isdefaultcorpusorder',
            get_string('defaultflag', 'cobra'),
            null,
            null,
            array(0, 1)
        );
        //$mform->addHelpButton('isdef', 'userglossary', 'cobra');
        $mform->setDefault('isdefaultcorpusorder', 0);
        $mform->addHelpButton('isdefaultcorpusorder', 'defaultflag', 'cobra');

        // Hidden button to flag corpus selection and order as default.
        $mform->registerNoSubmitButton('updatedefaultcorpusorder');
        $mform->addElement('submit', 'updatedefaultcorpusorder', 'defaultcorpusorderupdate', array('class' => 'hidden'));

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();
        // Add standard buttons, common to all modules.
        $this->add_action_buttons();
    }

    function definition_after_data() {
        global $DB, $COURSE;

        $mform = $this->_form;
        parent::definition_after_data();

        // Get collections for selected language;
        $languageset = $mform->getElementValue('language');

        // Add collection select and default corpus selection and order box when language is set
        if (is_array($languageset) && !empty($languageset[0])) {
            $collections = cobra_get_filtered_collections_optionslist($languageset[0]);
            $collections = array('0' => '-') + $collections;
            $mform->addElement('select', 'collection', get_string('collection', 'cobra'), $collections);
            //$mform->addHelpButton('collection', '', '');
            $mform->addRule('collection', 'You must select a collection', 'nonzero');

            $mform->insertElementBefore($mform->removeElement('collection', false),
                'addcollectionshere');
        }

        if ($mform->elementExists('collection')) {
            $collectionset = $mform->getElementValue('collection');
            if (is_array($collectionset) && !empty($collectionset[0])) {
                $texts = cobra_get_text_optionslist($collectionset[0]);
                $texts = array('0' => '-') + $texts;
                $mform->addElement('select', 'text', get_string('text', 'cobra'), $texts);
                //$mform->addHelpButton('collection', '', '');
                $mform->addRule('text', 'You must select a text', 'nonzero');
                $mform->insertElementBefore($mform->removeElement('text', false),
                    'addtextshere');
            }
        }

        if ($mform->getElementValue('isdefaultdisplayprefs')) {

            $DB->execute('UPDATE {cobra}
                             SET isdefaultdisplayprefs = 0
                           WHERE course = :courseid',
                array('courseid' => $COURSE->id));
            $mform->removeElement('scrolltodisplay');
        };
        if ($mform->getElementValue('isdefaultcorpusorder')) {

            $DB->execute('UPDATE {cobra}
                             SET isdefaultcorpusorder = 0
                           WHERE course = :courseid',
                array('courseid' => $COURSE->id));
        };

    }
}