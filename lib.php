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
 * Library of interface functions and constants for module cobra
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 *
 * All the cobra specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package    mod_cobra
 * @copyright  2016 - Cellule TICE - Unversite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/* Moodle core API */

/**
 * Returns the information on whether the module supports a feature
 *
 * See {@link plugin_supports()} for more info.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function cobra_supports($feature) {

    switch($feature) {
        case FEATURE_MOD_ARCHETYPE :
            return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_MOD_INTRO :
            return true;
        case FEATURE_GROUPS:
            return false;
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the cobra into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param stdClass $cobra Submitted data from the form in mod_form.php
 * @param mod_cobra_mod_form $mform The form instance itself (if needed)
 * @return int The id of the newly inserted cobra record
 */
function cobra_add_instance(stdClass $cobra, mod_cobra_mod_form $mform = null) {
    global $DB;

    $cobra->timecreated = time();

    // You may have to add extra stuff in here.

    $cobra->id = $DB->insert_record('cobra', $cobra);

    $completiontimeexpected = !empty($cobra->completionexpected) ? $cobra->completionexpected : null;
    \core_completion\api::update_completion_date_event($cobra->coursemodule, 'url', $cobra->id, $completiontimeexpected);

    return $cobra->id;
}

/**
 * Updates an instance of the cobra in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param stdClass $cobra An object from the form in mod_form.php
 * @param mod_cobra_mod_form $mform The form instance itself (if needed)
 * @return boolean Success/Fail
 */
function cobra_update_instance(stdClass $cobra, mod_cobra_mod_form $mform = null) {
    global $DB;

    $cobra->timemodified = time();
    $cobra->id = $cobra->instance;
    $result = $DB->update_record('cobra', $cobra);

    $completiontimeexpected = !empty($cobra->completionexpected) ? $cobra->completionexpected : null;
    \core_completion\api::update_completion_date_event($cobra->coursemodule, 'cobra', $cobra->id, $completiontimeexpected);

    return $result;
}

/**
 * Removes an instance of the cobra from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function cobra_delete_instance($id) {
    global $DB;

    if (!$cobra = $DB->get_record('cobra', array('id' => $id))) {
        return false;
    }

    $cm = get_coursemodule_from_instance('cobra', $id);
    \core_completion\api::update_completion_date_event($cm->id, 'cobra', $id, null);

    // Delete any dependent records here.
    $DB->delete_records('cobra_clic', array('course' => $cobra->course));
    $DB->delete_records('cobra_glossaire', array('course' => $cobra->course));
    $DB->delete_records('cobra_ordre_concordances', array('course' => $cobra->course));
    $DB->delete_records('cobra_prefs', array('course' => $cobra->course));
    $DB->delete_records('cobra_registered_collections', array('course' => $cobra->course));
    $DB->delete_records('cobra_texts_config', array('course' => $cobra->course));
    $DB->delete_records('cobra', array('id' => $cobra->id));

    return true;
}

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 *
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @param stdClass $course The course record
 * @param stdClass $user The user record
 * @param cm_info|stdClass $mod The course module info object or record
 * @param stdClass $cobra The cobra instance record
 * @return stdClass|null
 */
function cobra_user_outline($course, $user, $mod, $cobra) {

    $return = new stdClass();
    $return->time = 0;
    $return->info = '';
    return $return;
}

/**
 * Prints a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * It is supposed to echo directly without returning a value.
 *
 * @param stdClass $course the current course record
 * @param stdClass $user the record of the user we are generating report for
 * @param cm_info $mod course module info
 * @param stdClass $cobra the module instance record
 */
function cobra_user_complete($course, $user, $mod, $cobra) {
}



/**
 * Returns all other caps used in the module
 *
 * For example, this could be array('moodle/site:accessallgroups') if the
 * module uses that capability.
 *
 * @return array
 */
function cobra_get_extra_capabilities() {
    return array();
}


/**
 * Serves the files from the cobra file areas
 *
 * @package mod_cobra
 * @category files
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the cobra's context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 */
function cobra_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options=array()) {
    global $DB, $CFG;

    if ($context->contextlevel != CONTEXT_MODULE) {
        send_file_not_found();
    }

    require_login($course, true, $cm);

    send_file_not_found();
}

function  cobra_extend_navigation_course(navigation_node $parentnode, stdClass $course, context_course $context) {

}

/**
 * This function extends the settings navigation block for the site.
 *
 * It is safe to rely on PAGE here as we will only ever be within the module
 * context when this is called
 *
 * @param settings_navigation $settings
 * @param navigation_node $cobranode
 * @return void
 */
function cobra_extend_settings_navigation($settings, $cobranode) {
    global $PAGE, $CFG;

    // We want to add these new nodes after the Edit settings node, and before the
    // Locally assigned roles node. Of course, both of those are controlled by capabilities.
    $keys = $cobranode->get_children_key_list();
    $beforekey = null;
    $i = array_search('modedit', $keys);
    if ($i === false and array_key_exists(0, $keys)) {
        $beforekey = $keys[0];
    } else if (array_key_exists($i + 1, $keys)) {
        $beforekey = $keys[$i + 1];
    }

    /*if (has_capability('mod/quiz:manageoverrides', $PAGE->cm->context)) {
        $url = new moodle_url('/mod/quiz/overrides.php', array('cmid'=>$PAGE->cm->id));
        $node = navigation_node::create(get_string('groupoverrides', 'quiz'),
            new moodle_url($url, array('mode'=>'group')),
            navigation_node::TYPE_SETTING, null, 'mod_quiz_groupoverrides');
        $quiznode->add_node($node, $beforekey);

        $node = navigation_node::create(get_string('useroverrides', 'quiz'),
            new moodle_url($url, array('mode'=>'user')),
            navigation_node::TYPE_SETTING, null, 'mod_quiz_useroverrides');
        $quiznode->add_node($node, $beforekey);
    }*/

    if (has_capability('mod/cobra:settings', $PAGE->cm->context)) {
        $node = navigation_node::create(get_string('managetextcollections', 'cobra'),
            new moodle_url('/mod/cobra/collectionmanagement.php', array('id' => $PAGE->cm->id)),
            navigation_node::TYPE_SETTING, null, 'mod_cobra_collections',
            new pix_icon('i/navigationitem', ''));
        $cobranode->add_node($node, $beforekey);
    }

    if (has_capability('mod/cobra:settings', $PAGE->cm->context)) {
        $node = navigation_node::create(get_string('corpusselection', 'cobra'),
            new moodle_url('/mod/cobra/corpusselection.php', array('id' => $PAGE->cm->id)),
            navigation_node::TYPE_SETTING, null, 'mod_cobra_corpus',
            new pix_icon('i/navigationitem', ''));
        $cobranode->add_node($node, $beforekey);
    }

    if (has_capability('mod/cobra:glossaryedit', $PAGE->cm->context)) {
        $node = navigation_node::create(get_string('glossary', 'cobra'));
        $node->icon = null;
        $glossarynode = $cobranode->add_node($node, $beforekey);
        $url = new moodle_url('/mod/cobra/glossary.php',
            array('id' => $PAGE->cm->id, 'cmd' => 'rqexport'));
        $glossarynode->add_node(navigation_node::create(get_string('exportglossary', 'cobra'), $url,
            navigation_node::TYPE_SETTING,
            null, null, new pix_icon('i/item', '')));
        $url = new moodle_url('/mod/cobra/glossary.php',
            array('id' => $PAGE->cm->id, 'cmd' => 'rqcompare'));
        $glossarynode->add_node(navigation_node::create(get_string('comparetextwithglossary', 'cobra'), $url,
            navigation_node::TYPE_SETTING,
            null, null, new pix_icon('i/item', '')));
    }

    if (has_capability('mod/cobra:stat', $PAGE->cm->context)) {
        $node = navigation_node::create(get_string('statistics', 'cobra'));
        $node->icon = null;
        $statisticsnode = $cobranode->add_node($node, $beforekey);
        $url = new moodle_url('/mod/cobra/statistics.php',
            array('id' => $PAGE->cm->id, 'view' => '1'));
        $statisticsnode->add_node(navigation_node::create(get_string('mostclickedentries', 'cobra'), $url,
            navigation_node::TYPE_SETTING,
            null, null, new pix_icon('i/item', '')));
        $url = new moodle_url('/mod/cobra/statistics.php',
            array('id' => $PAGE->cm->id, 'view' => '2'));
        $statisticsnode->add_node(navigation_node::create(get_string('mostclickedpertext', 'cobra'), $url,
            navigation_node::TYPE_SETTING,
            null, null, new pix_icon('i/item', '')));
        $url = new moodle_url('/mod/cobra/statistics.php',
            array('id' => $PAGE->cm->id, 'view' => '3'));
        $statisticsnode->add_node(navigation_node::create(get_string('mostclickedtexts', 'cobra'), $url,
            navigation_node::TYPE_SETTING,
            null, null, new pix_icon('i/item', '')));
        $url = new moodle_url('/mod/cobra/statistics.php',
            array('id' => $PAGE->cm->id, 'view' => '4'));
        $statisticsnode->add_node(navigation_node::create(get_string('statisticspertext', 'cobra'), $url,
            navigation_node::TYPE_SETTING,
            null, null, new pix_icon('i/item', '')));
        $url = new moodle_url('/mod/cobra/statistics.php',
            array('id' => $PAGE->cm->id, 'view' => '5'));
        $statisticsnode->add_node(navigation_node::create(get_string('statisticsperuser', 'cobra'), $url,
            navigation_node::TYPE_SETTING,
            null, null, new pix_icon('i/item', '')));
        $url = new moodle_url('/mod/cobra/statistics.php',
            array('id' => $PAGE->cm->id, 'cmd' => 'cleanstats'));
        $statisticsnode->add_node(navigation_node::create(get_string('cleanclickstats', 'cobra'), $url,
            navigation_node::TYPE_SETTING,
            null, null, new pix_icon('i/item', '')));
    }
}

/**
 * This function receives a calendar event and returns the action associated with it, or null if there is none.
 *
 * This is used by block_myoverview in order to display the event appropriately. If null is returned then the event
 * is not displayed on the block.
 *
 * @param calendar_event $event
 * @param \core_calendar\action_factory $factory
 * @return \core_calendar\local\event\entities\action_interface|null
 */
function mod_cobra_core_calendar_provide_event_action(calendar_event $event,
                                                    \core_calendar\action_factory $factory) {
    $cm = get_fast_modinfo($event->courseid)->instances['cobra'][$event->instance];

    $completion = new \completion_info($cm->get_course());

    $completiondata = $completion->get_data($cm, false);

    if ($completiondata->completionstate != COMPLETION_INCOMPLETE) {
        return null;
    }

    return $factory->create_instance(
        get_string('view'),
        new \moodle_url('/mod/cobra/view.php', ['id' => $cm->id]),
        1,
        true
    );
}
