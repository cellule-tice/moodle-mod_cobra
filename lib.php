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
 * Library of interface functions and constants.
 *
 * @package     mod_cobra
 * @copyright   2016 onwards - Cellule TICE - University of Namur
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature Constant representing the feature.
 * @return true | null True if the feature is supported, null otherwise.
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
 * Saves a new instance of the mod_cobra into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param object $cobra An object from the form.
 * @return int The id of the newly inserted record.
 */
function cobra_add_instance($cobra) {
    global $DB;

    $cobra->timecreated = time();

    $cobra->id = $DB->insert_record('cobra', $cobra);

    $completiontimeexpected = !empty($cobra->completionexpected) ? $cobra->completionexpected : null;
    \core_completion\api::update_completion_date_event($cobra->coursemodule, 'cobra', $cobra->id, $completiontimeexpected);

    return $cobra->id;
}

/**
 * Updates an instance of the mod_cobra in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object $cobra An object from the form in mod_form.php.
 * @return bool True if successful, false otherwise.
 */
function cobra_update_instance($cobra) {
    global $DB;

    $cobra->timemodified = time();
    $cobra->id = $cobra->instance;

    $completiontimeexpected = !empty($cobra->completionexpected) ? $cobra->completionexpected : null;
    \core_completion\api::update_completion_date_event($cobra->coursemodule, 'cobra', $cobra->id, $completiontimeexpected);

    return $DB->update_record('cobra', $cobra);
}

/**
 * Removes an instance of the mod_cobra from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function cobra_delete_instance($id) {
    global $DB;

    $exists = $DB->get_record('cobra', array('id' => $id));
    if (!$exists) {
        return false;
    }

    $DB->delete_records('cobra', array('id' => $id));

    return true;
}

/**
 * Extends the course navigation with mod_cobra nodes.
 *
 * @param navigation_node $parentnode main course navigation node
 * @param stdClass $course
 * @param context_course $context
 */
function  cobra_extend_navigation_course(navigation_node $parentnode, stdClass $course, context_course $context) {
    global $DB;

    if ($DB->record_exists('cobra', array('course' => $course->id))) {
        global $CFG;
        if (has_capability('mod/cobra:addinstance', $context)) {
            $cobranode = $parentnode->add(get_string('glossary', 'mod_cobra') . ' ' . get_string('cobra', 'mod_cobra'));
            $params = array('id' => $context->instanceid, 'cmd' => 'rqexport');
            $cobranode->add(get_string('glossary', 'mod_cobra') . ' ' . get_string('cobra', 'mod_cobra'),
                new moodle_url($CFG->wwwroot .'/mod/cobra/glossary.php', $params),
                navigation_node::TYPE_SETTING, null, 'mod_cobra_export_glossary');

            $params = array('id' => $context->instanceid, 'cmd' => 'rqcompare');
            $cobranode->add(get_string('comparetextwithglossary', 'mod_cobra'),
                new moodle_url($CFG->wwwroot .'/mod/cobra/glossary.php', $params),
                navigation_node::TYPE_SETTING, null, 'mod_cobra_compare_glossary');
        }
    }
}

/**
 * Hook for plugins to take action when a module is created or updated.
 * Here to keep only one instance as defaut for corpus order and display preferences
 *
 * @param stdClass $moduleinfo the module info
 * @param stdClass $course the course of the module
 *
 * @return stdClass moduleinfo updated by plugins.
 */
function cobra_coursemodule_edit_post_actions($moduleinfo, $course) {
    global $DB;

    if ($moduleinfo->modulename != 'cobra') {
        return $moduleinfo;
    }
    if (!PHPUNIT_TEST) {
        if (empty($moduleinfo->id)) {
            $cobraid = $DB->get_field_sql('SELECT MAX(id) FROM {cobra} WHERE course = :course', array('course' => $course->id));
        } else {
            $cobraid = $moduleinfo->id;
        }

        if ($moduleinfo->isdefaultdisplayprefs) {
            $statement = "UPDATE {cobra}
                             SET isdefaultdisplayprefs = 0
                           WHERE course = :course
                             AND id != :newid";
            $DB->execute($statement, array('newid' => $cobraid, 'course' => $course->id));
        }
        if ($moduleinfo->isdefaultcorpusorder) {
            $statement = "UPDATE {cobra}
                             SET isdefaultcorpusorder = 0
                           WHERE course = :course
                             AND id != :newid";
            $DB->execute($statement, array('newid' => $cobraid, 'course' => $course->id));
        }
    }
    return $moduleinfo;
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

/**
 * Actual implementation of the reset course functionality, clear all
 * personal glossaries and clear course level default cobra preferences for
 * course $data->courseid.
 *
 * @param object $data the data submitted from the reset course.
 * @return array status array
 */
function cobra_reset_userdata($data) {
    global $DB;

    $componentstr = get_string('modulename', 'cobra');
    $status = array();

    if (!empty($data->reset_cobra_defaults)) {
        $sql = "UPDATE {cobra}
                   SET isdefaultdisplayprefs = 0,
                       isdefaultcorpusorder = 0
                 WHERE course=:course";

        $params = array('course' => $data->courseid);
        $success = $DB->execute($sql, $params);

        $status[] = array(
            'component' => $componentstr,
            'item' => get_string('resetdefaults', 'cobra'),
            'error' => !$success
        );
    }

    if (!empty($data->reset_cobra_click_history) && !empty($data->reset_cobra_personal_glossaries)) {

        $params = array('course' => $data->courseid);
        $success = $DB->delete_records('cobra_click', $params);

        $status[] = array(
            'component' => $componentstr,
            'item' => get_string('resetglossaries', 'cobra'),
            'error' => !$success
        );
        $status[] = array(
            'component' => $componentstr,
            'item' => get_string('resetclichistory', 'cobra'),
            'error' => !$success
        );
    } else if (!empty($data->reset_cobra_personal_glossaries)) {
        $sql = "UPDATE {cobra_click}
                   SET inglossary = 0
                 WHERE course=:course";

        $params = array('course' => $data->courseid);
        $success = $DB->execute($sql, $params);

        $status[] = array(
            'component' => $componentstr,
            'item' => get_string('resetglossaries', 'cobra'),
            'error' => !$success
        );
    }

    return $status;
}

/**
 * Called by course/reset.php
 * Module reset form elements.
 * @param moodleform $mform form passed by reference
 */
function cobra_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'cobraheader', get_string('modulename', 'cobra'));

    $mform->addElement('checkbox', 'reset_cobra_defaults', get_string('resetdefaults', 'cobra'));

    $mform->addElement('checkbox', 'reset_cobra_personal_glossaries', get_string('resetglossaries', 'cobra'));

    $mform->addElement('checkbox', 'reset_cobra_click_history', get_string('resetclichistory', 'cobra'));
    $mform->disabledIf('reset_cobra_click_history', 'reset_cobra_personal_glossaries', 'notchecked');
}

/**
 * Course reset form defaults.
 * @param stdClass $course
 * @return array
 */
function cobra_reset_course_form_defaults($course) {
    return array(
        'reset_cobra_defaults' => 0,
        'reset_cobra_personal_glossaries' => 1,
        'reset_cobra_click_history' => 0
    );
}
