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
 * @copyright  2015 Laurence Dumortier UNamur
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
        case FEATURE_MOD_ARCHETYPE:           return MOD_ARCHETYPE_RESOURCE;
        case    FEATURE_MOD_INTRO : return false;
        case FEATURE_GROUPS:                  return false;
        case FEATURE_GROUPINGS:               return false;
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

    // You may have to add extra stuff in here.

    $result = $DB->update_record('cobra', $cobra);
   
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

    if (! $cobra = $DB->get_record('cobra', array('id' => $id))) {
        return false;
    }

    // Delete any dependent records here.

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

/* Navigation API */

/**
 * Extends the global navigation tree by adding cobra nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $navref An object representing the navigation tree node of the cobra module instance
 * @param stdClass $course current course record
 * @param stdClass $module current cobra instance record
 * @param cm_info $cm course module information
 */
/*function cobra_extend_navigation(navigation_node $navref, stdClass $course, stdClass $module, cm_info $cm) {
    // TODO Delete this function and its docblock, or implement it.
//    $navref->add('CoBRA', './mod/cobra/view.php', navigation_node::TYPE_SETTING, null , null, new pix_icon('icon' , ''));
}

function cobra_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $cobranode=null) {
    // TODO Delete this function and its docblock, or implement it.
}
*/

function  cobra_extend_navigation_course(navigation_node $parentnode, stdClass $course, context_course $context)
{
}