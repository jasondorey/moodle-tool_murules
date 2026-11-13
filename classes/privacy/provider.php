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

namespace tool_murules\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider for tool_murules.
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('tool_murules_match', [
            'userid' => 'privacy:metadata:match:userid',
            'ruleid' => 'privacy:metadata:match:ruleid',
            'status' => 'privacy:metadata:match:status',
            'reason' => 'privacy:metadata:match:reason',
            'muprogallocationid' => 'privacy:metadata:match:muprogallocationid',
            'timecreated' => 'privacy:metadata:match:timecreated',
        ], 'privacy:metadata:match');
        $collection->add_database_table('tool_murules_rule', [
            'usermodified' => 'privacy:metadata:rule:usermodified',
        ], 'privacy:metadata:rule');
        return $collection;
    }

    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                 WHERE ctx.contextlevel = :level
                   AND (
                        EXISTS (SELECT 1 FROM {tool_murules_match} m WHERE m.userid = :userid)
                     OR EXISTS (SELECT 1 FROM {tool_murules_rule} r WHERE r.usermodified = :userid2)
                   )";
        $contextlist->add_from_sql($sql, [
            'level' => CONTEXT_SYSTEM,
            'userid' => $userid,
            'userid2' => $userid,
        ]);
        return $contextlist;
    }

    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_SYSTEM) {
            return;
        }
        $userlist->add_from_sql('userid', 'SELECT userid FROM {tool_murules_match}', []);
        $userlist->add_from_sql('userid', 'SELECT usermodified AS userid FROM {tool_murules_rule}', []);
    }

    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;
        if (!$contextlist->count()) {
            return;
        }
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_SYSTEM) {
                continue;
            }
            $matches = $DB->get_records('tool_murules_match', ['userid' => $userid]);
            if ($matches) {
                writer::with_context($context)->export_data(
                    [get_string('pluginname', 'tool_murules')],
                    (object)['matches' => array_values($matches)]
                );
            }
        }
    }

    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;
        if ($context->contextlevel === CONTEXT_SYSTEM) {
            $DB->delete_records('tool_murules_match');
        }
    }

    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel === CONTEXT_SYSTEM) {
                $DB->delete_records('tool_murules_match', ['userid' => $userid]);
            }
        }
    }

    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;
        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_SYSTEM) {
            return;
        }
        $userids = $userlist->get_userids();
        if (!$userids) {
            return;
        }
        list($insql, $params) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $DB->delete_records_select('tool_murules_match', "userid $insql", $params);
    }
}
