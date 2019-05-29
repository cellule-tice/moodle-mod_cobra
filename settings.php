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
 * Plugin administration pages are defined here.
 *
 * @package     mod_cobra
 * @author      Jean-Roch Meurisse
 * @copyright   2016 onwards - Cellule TICE - University of Namur
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    require_once(__DIR__ . '/locallib.php');
    $PAGE->requires->js_call_amd('mod_cobra/cobra', 'demoapikey');
    $settings->add(new admin_setting_heading('cobra_settings_general', get_string('generalconfig', 'cobra'), ''));

    $settings->add(new admin_setting_configtext('mod_cobra/serviceurl', get_string('cobraserviceurl', 'cobra'),
        '', 'https://webapps.unamur.be/elv/nederlex/services/api.php', PARAM_URL, 40));

    if (empty(get_config('mod_cobra', 'apikey'))) {
        $apikeymessage = '<button type="button" class="btn btn-primary" id="requestapikey">' .
                get_string('requestdemokey', 'cobra') .
                '</button>' .
                '<p>' . get_string('requestdemokey_desc', 'cobra') . '</p>';
    } else {
        $apikeymessage = '';
    }
    $setting = new admin_setting_configtext('mod_cobra/apikey', 'apikey',
        $apikeymessage, '', PARAM_RAW, 60);
    $setting->set_updatedcallback('cobra_fill_cache_tables');
    $settings->add($setting);

    $settings->add(new admin_setting_heading('cobra_settings_display', get_string('displaysettings', 'cobra'), ''));

    $name = 'mod_cobra/userglossary';
    $title = get_string('userglossary', 'cobra');
    $settings->add(new admin_setting_configcheckbox($name, $title, '', 1));

    $name = 'mod_cobra/audioplayer';
    $title = get_string('audioplayer', 'cobra');
    $settings->add(new admin_setting_configcheckbox($name, $title, '', 1));

    $name = 'mod_cobra/examples';
    $title = get_string('examplesdisplaymode', 'cobra');
    $default = COBRA_EXAMPLES_BILINGUAL;
    $choices = array(
        COBRA_EXAMPLES_BILINGUAL => get_string('bilingual', 'cobra'),
        COBRA_EXAMPLES_MONOLINGUAL => get_string('monolingual', 'cobra')
    );
    $settings->add(new admin_setting_configselect($name, $title, '', $default, $choices));

    $name = 'mod_cobra/translations';
    $title = get_string('translationsdisplaymode', 'cobra');
    $default = COBRA_TRANSLATIONS_CONDITIONAL;
    $choices = array(
        COBRA_TRANSLATIONS_ALWAYS => get_string('always'),
        COBRA_TRANSLATIONS_CONDITIONAL => get_string('conditional', 'cobra'),
        COBRA_TRANSLATIONS_NEVER => get_string('never')
    );
    $settings->add(new admin_setting_configselect($name, $title, '', $default, $choices));

    $name = 'mod_cobra/annotations';
    $title = get_string('annotationsdisplaymode', 'cobra');
    $default = COBRA_ANNOTATIONS_CONDITIONAL;
    $choices = array(
        COBRA_ANNOTATIONS_ALWAYS => get_string('always'),
        COBRA_ANNOTATIONS_CONDITIONAL => get_string('conditional', 'cobra'),
        COBRA_ANNOTATIONS_NEVER => get_string('never')
    );
    $settings->add(new admin_setting_configselect($name, $title, '', $default, $choices));

    $settings->add(new admin_setting_heading('cobra_settings_corpus', get_string('defaultcorpusselection', 'cobra'), ''));


    $settings->add(new admin_setting_configtext('mod_cobra/defaultcorpusorderen', get_string('forenglish', 'cobra'),
        get_string('forenglish_desc', 'cobra'), '1,11,21'));

    $settings->add(new admin_setting_configtext('mod_cobra/defaultcorpusordernl', get_string('fordutch', 'cobra'),
        get_string('fordutch_desc', 'cobra'), '1,11,21'));
}
