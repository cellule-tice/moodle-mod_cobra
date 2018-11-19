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
 * Privacy Subsystem implementation for mod_cobra.
 *
 * @package    mod_cobra
 * @author     Jean-Roch Meurisse
 * @copyright  2016 - Cellule TICE - Unversite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_cobra\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Implementation of the privacy subsystem plugin provider for the cobra resource.
 *
 * @package    mod_cobra
 * @author     Jean-Roch Meurisse
 * @copyright  2016 onwards - Cellule TICE - Universite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    // This plugin has data.
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * Returns meta data about this system.
     *
     * @param collection $items collection The initialised item collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $items) : collection {

        $items->add_database_table(
            'cobra_click',
            [
                  'course' => 'privacy:metadata:cobra_click:course',
                  'lingentity' => 'privacy:metadata:cobra_click:lingentity',
                  'textid' => 'privacy:metadata:cobra_click:textid',
                  'userid' => 'privacy:metadata:cobra_click:userid',
                  'nbclicks' => 'privacy:metadata:cobra_click:nbclicks',
                  'timecreated' => 'privacy:metadata:cobra_click:timecreated',
                  'inglossary' => 'privacy:metadata:cobra_click:inglossary'
            ],
            'privacy:metadata:cobra_click'
        );

        return $items;
    }

    /**
     * Returns all of the contexts that has information relating to the userid.
     *
     * @param  int $userid The user ID.
     * @return contextlist an object with the contexts related to a userid.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        $contextlist = new \core_privacy\local\request\contextlist();
        $sql = "SELECT c.id
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {cobra} co ON co.id = cm.instance
             LEFT JOIN {cobra_click} cc ON cc.cobra = co.id
                 WHERE cc.userid = :userid";

        $params = [
            'contextlevel' => CONTEXT_MODULE,
            'modname' => 'cobra',
            'userid' => $userid
        ];
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Export personal data for the given approved_contextlist.
     * User and context information is contained within the contextlist.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for export.
     */
    public static function export_user_data(approved_contextlist $contextlist) {

        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        $sql = "SELECT cm.id AS cmid,
                       cc.course,
                       cg.entry,
                       ct.title,
                       cc.nbclicks,
                       cc.timecreated,
                       cc.inglossary

                 FROM {context} c
                 JOIN {course_modules} cm ON cm.id = c.instanceid
                 JOIN {cobra} co ON co.id = cm.instance
                 JOIN {cobra_click} cc ON cc.cobra = co.id
                 JOIN {cobra_text_info_cache} ct ON ct.id = cc.textid
                 JOIN {cobra_glossary_cache} cg ON cg.lingentity = cc.lingentity

                WHERE c.id $contextsql
                  AND cc.userid = :userid

                ORDER BY cm.id, cc.id
        ";

        $params = ['userid' => $user->id] + $contextparams;
        $lastcmid = null;
        $itemdata = [];

        $items = $DB->get_recordset_sql($sql, $params);
        foreach ($items as $item) {
            if ($lastcmid !== $item->cmid) {
                if ($itemdata) {
                    self::export_cobra_data_for_user($itemdata, $lastcmid, $user);
                }
                $itemdata = [];
                $lastcmid = $item->cmid;
            }

            $itemdata[] = (object)[
                'course' => $item->course,
                'glossaryentry' => $item->entry,
                'text' => $item->title,
                'nbrclicks' => $item->nbclicks,
                'inglossary' => $item->inglossary,
                'usertimestamp' => $item->timecreated ? transform::datetime($item->timecreated) : '',
            ];
        }
        $items->close();
        if ($itemdata) {
            self::export_cobra_data_for_user($itemdata, $lastcmid, $user);
        }
    }

    /**
     * Export the supplied personal data for a single cobra activity, along with any generic data or area files.
     *
     * @param array $items the data for each of the items in cobra instance
     * @param int $cmid
     * @param \stdClass $user
     */
    protected static function export_cobra_data_for_user(array $items, int $cmid, \stdClass $user) {
        // Fetch the generic module data.
        $context = \context_module::instance($cmid);
        $contextdata = helper::get_context_data($context, $user);

        // Merge with cobra data and write it.
        $contextdata = (object)array_merge((array)$contextdata, ['items' => $items]);
        writer::with_context($context)->export_data([], $contextdata)->export_metadata(
                [],
                'cobra_click', (object) [
                    'course' => 'course',
                    'lingentity' => 'lingentity',
                    'textid' => 'textid',
                    'nbclicks' => 'nbclicks',
                    'timecreated' => 'timecreated',
                    'inglossary' => 'inglossary'
                ],
                get_string('privacy:metadata:cobra_click', 'mod_cobra')
        );

        // Write generic module intro files.
        helper::export_context_files($context, $user);
    }

    /**
     * Delete all use data which matches the specified context.
     *
     * @param context $context The module context.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        if (!$context) {
            return;
        }
        $instanceid = $DB->get_field('course_modules', 'instance', ['id' => $context->instanceid], MUST_EXIST);
        $itemids = $DB->get_fieldset_select('cobra_click', 'id', 'cobra = ?', [$instanceid]);
        if ($itemids) {
            $DB->delete_records_select('cobra_click', 'cobra = ? AND userid <> 0', [$instanceid]);
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        if (!$contextlist->count()) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            $instanceid = $DB->get_field('course_modules', 'instance', ['id' => $context->instanceid], MUST_EXIST);
            $itemids = $DB->get_fieldset_select('cobra_click', 'id', 'cobra = ?', [$instanceid]);
            if ($itemids) {
                $params = ['instanceid' => $instanceid, 'userid' => $userid];
                $DB->delete_records_select('cobra_click', 'cobra = :instanceid AND userid = :userid', $params);
            }
        }
    }
}