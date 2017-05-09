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
 * @package    mod_cobra
 * @author     Jean-Roch Meurisse
 * @copyright  2016 - Cellule TICE - Unversite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once(__DIR__ . '/locallib.php');

    $settings->add(new admin_setting_heading('cobra_settings_general', get_string('generalconfig', 'cobra'), ''));

    $settings->add(new admin_setting_configtext('mod_cobra/serviceurl', get_string('cobraserviceurl', 'cobra'),
        '', '', PARAM_URL, 60));

    $settings->add(new admin_setting_heading('cobra_settings_display', get_string('displaysettings', 'cobra'), ''));

    $name = 'mod_cobra/userglossary';
    $title = get_string('userglossary', 'cobra');
    //$description = get_string('audioplayer_desc', 'theme_receptic');
    $description = '';
    $settings->add(new admin_setting_configcheckbox($name, $title, $description, 1));

    $name = 'mod_cobra/audioplayer';
    $title = get_string('audioplayer', 'cobra');
    //$description = get_string('audioplayer_desc', 'theme_receptic');
    $description = '';
    $settings->add(new admin_setting_configcheckbox($name, $title, $description, 1));
    //$page->add($setting);

    $name = 'mod_cobra/examples';
    $title = get_string('examplesdisplaymode', 'cobra');
    //$description = get_string('examplesdisplaymode_desc', 'cobra');
    $description = '';
    $default = COBRA_EXAMPLES_BILINGUAL;
    $choices = array(
        COBRA_EXAMPLES_BILINGUAL => get_string('bilingual', 'cobra'),
        COBRA_EXAMPLES_MONOLINGUAL => get_string('monolingual', 'cobra')
    );
    $settings->add(new admin_setting_configselect($name, $title, $description, $default, $choices));

    $name = 'mod_cobra/translations';
    $title = get_string('translationsdisplaymode', 'cobra');
    //$description = get_string('translationsdisplaymode_desc', 'cobra');
    $description = '';
    $default = COBRA_TRANSLATIONS_CONDITIONAL;
    $choices = array(
        COBRA_TRANSLATIONS_ALWAYS => get_string('always'),
        COBRA_TRANSLATIONS_CONDITIONAL => get_string('conditional', 'cobra'),
        COBRA_TRANSLATIONS_NEVER => get_string('never')
    );
    $settings->add(new admin_setting_configselect($name, $title, $description, $default, $choices));

    $name = 'mod_cobra/annotations';
    $title = get_string('annotationsdisplaymode', 'cobra');
    //$description = get_string('annotationsdisplaymode_desc', 'cobra');
    $description = '';
    $default = COBRA_ANNOTATIONS_CONDITIONAL;
    $choices = array(
        COBRA_ANNOTATIONS_ALWAYS => get_string('always'),
        COBRA_ANNOTATIONS_CONDITIONAL => get_string('conditional', 'cobra'),
        COBRA_ANNOTATIONS_NEVER => get_string('never')
    );
    $settings->add(new admin_setting_configselect($name, $title, $description, $default, $choices));

    $settings->add(new admin_setting_heading('cobra_settings_corpus', get_string('defaultcorpusselection', 'cobra'), ''));


    $settings->add(new admin_setting_configtext('mod_cobra/defaultcorpusorderen', get_string('forenglish', 'cobra'),
        get_string('forenglish_desc', 'cobra'), '1,11,21'));

    $settings->add(new admin_setting_configtext('mod_cobra/defaultcorpusordernl', get_string('fordutch', 'cobra'),
        get_string('fordutch_desc', 'cobra'), '1,11,21'));
}
