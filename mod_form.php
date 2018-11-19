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
 * The main mod_cobra configuration form.
 *
 * @package     mod_cobra
 * @copyright   2016 onwards - Cellule TICE - University of Namur
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/cobra/locallib.php');

/**
 * Module instance settings form.
 *
 * @package    mod_cobra
 * @copyright  2016 onwards - Cellule TICE - University of Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_cobra_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG, $DB, $COURSE, $PAGE;

        $mform = $this->_form;

        if (empty(get_config('mod_cobra', 'apikey'))) {
            $mform->addElement('header', 'registrationrequired', get_string('registrationrequired', 'cobra'));
            $mform->addElement('static', 'registrationwarning', '', get_string('registrationwarning', 'cobra'));
            $mform->addElement('hidden', 'registered', 0);
            $mform->setType('registered', PARAM_INT);

        } else {
            $mform->addElement('hidden', 'registered', 1);
            $mform->setType('registered', PARAM_INT);
        }

        // Prepare params for js calls.
        $jsparams = array();
        $jsparams['en'] = cobra_get_default_corpus_order($COURSE->id, 'EN');
        $jsparams['nl'] = cobra_get_default_corpus_order($COURSE->id, 'NL');

        // Load js calls.
        $PAGE->requires->js_call_amd('mod_cobra/cobra', 'initData', array(json_encode($jsparams)));
        $PAGE->requires->js_call_amd('mod_cobra/cobra', 'modFormTriggers');

        // Get edition mode, behaviour is slightly different for add and edit.
        $mode = optional_param('add', null, PARAM_ALPHA);
        if (empty($mode)) {
            $mode = 'edit';
        } else {
            $mode = 'add';
        }


        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Language select box.
        $recordset = $DB->get_records('cobra', array('course' => $COURSE->id, 'language' => 'EN'), 'id DESC', '*', 0, 1);
        $lastinstance = null;

        if (count($recordset)) {
            // Language can only be set for first instance: 1 course = 1 language.
            $lastinstance = array_pop($recordset);
            $options[$lastinstance->language] = $lastinstance->language;
            $firstinstance = 0;
        } else {
            $options = cobra_get_available_languages();
            array_unshift($options, '-');
            $firstinstance = 1;
        }

        // Add hidden fields to keep information after dynamic reload (definition_after_data).
        // Edition mode.
        $mform->addElement('hidden', 'mode', $mode);
        $mform->setType('mode', PARAM_ALPHA);
        if ($lastinstance) {
            // Last used collection will be selected.
            $mform->addElement('hidden', 'lastcollection', $lastinstance->collection);
            $mform->setType('lastcollection', PARAM_INT);
        }
        // Are we adding a first instance or not (for pre-selecting language).
        $mform->addElement('hidden', 'firstinstance', $firstinstance);
        $mform->setType('firstinstance', PARAM_INT);

        // In add mode collect course level default display settings if any, otherwise collect platform defaults.
        if ($mode == 'add') {
            $defaultsettings = $DB->get_record('cobra', array('course' => $COURSE->id, 'isdefaultdisplayprefs' => 1));
        }
        if (empty($defaultsettings)) {
            // If first instance, get default settings from platform configuration.
            $defaultsettings = new stdClass();
            $defaultsettings->userglossary = get_config('mod_cobra', 'userglossary');
            $defaultsettings->audioplayer = get_config('mod_cobra', 'audioplayer');
            $defaultsettings->examples = get_config('mod_cobra', 'examples');
            $defaultsettings->translations = get_config('mod_cobra', 'translations');
            $defaultsettings->annotations = get_config('mod_cobra', 'annotations');
        }

        $langselect = $mform->addElement('select', 'language', get_string('language', 'cobra'), $options);
        $mform->addHelpButton('language', 'language', 'cobra');
        $mform->addRule('language', get_string('languageismandatory', 'cobra'), 'lettersonly');
        // If not first instance, disable language select box and preselect language.
        $mform->disabledIf('language', 'firstinstance', 'eq', 0);
        if ($lastinstance) {
            $langselect->setSelected($lastinstance->language);
        }
        $mform->addRule('language', null, 'required', null, 'client');
        $mform->disabledIf('language', 'registered', 'eq', 0);

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

        // Adding the standard "intro" and "introformat" fields.
        $this->standard_intro_elements();

        // Add display preferences elements.
        $mform->addElement('header', 'displaysettings', get_string('displaysettings', 'cobra'));

        // Add checkbox to enable/disable user glossary.
        $mform->addElement('advcheckbox',
            'userglossary',
            get_string('userglossary', 'cobra'),
            null,
            null,
            array(0, 1)
        );
        $mform->addHelpButton('userglossary', 'userglossary', 'cobra');
        $mform->setDefault('userglossary', $defaultsettings->userglossary);

        // Add checkbox to enable/disable audio player display.
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
        $mform->setDefault('annotations', $defaultsettings->annotations);

        // Add checkbox to flag display settings as default for future instances.
        $mform->addElement('advcheckbox',
            'isdefaultdisplayprefs',
            get_string('defaultflag', 'cobra'),
            null,
            null,
            array(0, 1)
        );
        $mform->setDefault('isdefaultdisplayprefs', 0);
        $mform->addHelpButton('isdefaultdisplayprefs', 'defaultflag', 'cobra');

        // Corpus type selection and order.
        $mform->addElement('header', 'corpusselection', get_string('corpusselection', 'cobra'));
        $mform->addElement('text', 'corpusorder', get_string('corpusselection', 'cobra'));
        $mform->setType('corpusorder', PARAM_TEXT);
        $mform->setDefault('corpusorder', cobra_get_default_corpus_order($COURSE->id, $mform->getElementValue('language')));
        $mform->addHelpButton('corpusorder', 'corpusselection', 'cobra');

        // Add checkbox to flag corpus selection as default for future instances.
        $mform->addElement('advcheckbox',
            'isdefaultcorpusorder',
            get_string('defaultflag', 'cobra'),
            null,
            null,
            array(0, 1)
        );
        $mform->setDefault('isdefaultcorpusorder', 0);
        $mform->addHelpButton('isdefaultcorpusorder', 'defaultflag', 'cobra');

        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }

    /**
     * Load collection list when language set; load text list on collection change
     * @throws coding_exception
     */
    public function definition_after_data() {

        $mform = $this->_form;
        parent::definition_after_data();

        // Get collections for selected language.
        $languageset = $mform->getElementValue('language');

        // Add collection select box and default corpus selection and order box when language is set.
        if (is_array($languageset) && !empty($languageset[0])) {

            $collections = cobra_get_collections_options_list($languageset[0]);
            $collections = array('0' => '-') + $collections;
            $colselect = $mform->addElement('select', 'collection', get_string('collection', 'cobra'), $collections);

            if ($mform->elementExists('lastcollection')) {
                $colselect->setSelected($mform->getElementValue('lastcollection'));
            }
            $mform->addRule('collection', get_string('collectionnamecannotbeempty', 'cobra'), 'nonzero');
            $mform->insertElementBefore($mform->removeElement('collection', false), 'addcollectionshere');
        }

        if ($mform->elementExists('collection')) {
            $collectionset = $mform->getElementValue('collection');
            if (is_array($collectionset) && !empty($collectionset[0])) {
                $texts = cobra_get_texts_options_list($collectionset[0]);
                $texts = array('0' => '-') + $texts;
                $mform->addElement('select', 'text', get_string('text', 'cobra'), $texts);
                $mform->addRule('text', 'You must select a text', 'nonzero');
                $mform->insertElementBefore($mform->removeElement('text', false),
                    'addtextshere');
            }
        }
    }
}
