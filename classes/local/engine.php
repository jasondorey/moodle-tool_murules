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

namespace tool_murules\local;

use stdClass;

/**
 * Rule evaluation and apply engine.
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class engine {

    /**
     * Global kill-switch.
     *
     * @return bool
     */
    public static function is_killed(): bool {
        return (bool)get_config('tool_murules', 'killswitch');
    }

    /**
     * Realtime observers enabled.
     *
     * @return bool
     */
    public static function observers_enabled(): bool {
        $val = get_config('tool_murules', 'realtimeobservers');
        return $val === false || $val === null ? true : (bool)$val;
    }

    /**
     * @return int
     */
    public static function max_backfill_batch(): int {
        $n = (int)get_config('tool_murules', 'maxbackfillbatch');
        return $n > 0 ? $n : 200;
    }

    /**
     * Queue ad-hoc processing for a user (optionally limited to rule ids).
     *
     * @param int $userid
     * @param int[] $ruleids
     * @param string|null $eventname
     */
    public static function queue_process_event(int $userid, array $ruleids = [], ?string $eventname = null): void {
        if (self::is_killed() || !self::observers_enabled()) {
            return;
        }
        $task = new \tool_murules\task\process_event();
        $task->set_custom_data([
            'userid' => $userid,
            'ruleids' => array_values(array_map('intval', $ruleids)),
            'eventname' => $eventname,
        ]);
        \core\task\manager::queue_adhoc_task($task, true);
    }

    /**
     * Queue on-enable / scheduled backfill for one rule.
     *
     * @param int $ruleid
     * @param int $fromuserid
     */
    public static function queue_backfill(int $ruleid, int $fromuserid = 0): void {
        if (self::is_killed()) {
            return;
        }
        $task = new \tool_murules\task\backfill_rule();
        $task->set_custom_data([
            'ruleid' => $ruleid,
            'fromuserid' => $fromuserid,
        ]);
        \core\task\manager::queue_adhoc_task($task, true);
    }

    /**
     * Process one user against enabled rules.
     *
     * @param int $userid
     * @param int[] $ruleids empty = all enabled that declare the event (or all if no event filter)
     * @param \core\event\base|null $event
     * @return array results
     */
    public static function process_user(int $userid, array $ruleids = [], ?\core\event\base $event = null): array {
        if (self::is_killed()) {
            return [];
        }

        $rules = rule::get_enabled(true);
        if ($ruleids) {
            $wanted = array_flip($ruleids);
            $rules = array_filter($rules, static fn(stdClass $r) => isset($wanted[(int)$r->id]));
        }

        $results = [];
        foreach ($rules as $rulerec) {
            $results[(int)$rulerec->id] = self::apply_rule_to_user($rulerec, $userid, $event);
        }
        return $results;
    }

    /**
     * Apply one rule to one user.
     *
     * @param stdClass $rulerec
     * @param int $userid
     * @param \core\event\base|null $event
     * @return array[] list of action results
     */
    public static function apply_rule_to_user(stdClass $rulerec, int $userid, ?\core\event\base $event = null): array {
        global $DB;

        if (self::is_killed()) {
            return [self::log_only_result('skipped', get_string('reason_killswitch', 'tool_murules'))];
        }

        $user = $DB->get_record('user', ['id' => $userid]);
        if (!$user || (int)$user->deleted === 1) {
            return [self::log_only_result('skipped', get_string('reason_user_deleted', 'tool_murules'))];
        }
        if ((int)$user->suspended === 1 && empty($rulerec->includesuspended)) {
            return [self::log_only_result('skipped', get_string('reason_user_suspended', 'tool_murules'))];
        }

        $now = time();
        if (!empty($rulerec->timestart) && $now < (int)$rulerec->timestart) {
            return [self::log_only_result('skipped', get_string('reason_outside_window', 'tool_murules'))];
        }
        if (!empty($rulerec->timeend) && $now > (int)$rulerec->timeend) {
            return [self::log_only_result('skipped', get_string('reason_outside_window', 'tool_murules'))];
        }

        $condition = registry::get_condition($rulerec->conditiontype);
        if (!$condition) {
            self::disable_broken_rule($rulerec);
            return [self::log_only_result('failed', get_string('reason_broken', 'tool_murules'))];
        }
        $config = rule::condition_config($rulerec);
        if ($condition->is_broken($config)) {
            self::disable_broken_rule($rulerec);
            return [self::log_only_result('failed', get_string('reason_broken', 'tool_murules'))];
        }

        if (!$condition->user_matches($userid, $config, $event)) {
            // Not a match — no log row (avoid noise).
            return [];
        }

        $actions = rule::get_actions((int)$rulerec->id);
        $out = [];
        foreach ($actions as $actionrec) {
            $out[] = self::execute_action($rulerec, $actionrec, $userid);
        }
        return $out;
    }

    /**
     * Backfill a rule in batches.
     *
     * @param int $ruleid
     * @param int $fromuserid
     * @return int next fromuserid (0 if done)
     */
    public static function backfill_rule(int $ruleid, int $fromuserid = 0): int {
        if (self::is_killed()) {
            return 0;
        }
        $rulerec = rule::get($ruleid);
        if (!(int)$rulerec->enabled) {
            return 0;
        }
        $condition = registry::get_condition($rulerec->conditiontype);
        if (!$condition) {
            return 0;
        }
        $config = rule::condition_config($rulerec);
        if ($condition->is_broken($config)) {
            return 0;
        }

        $limit = self::max_backfill_batch();
        $userids = $condition->get_matching_user_ids($config, $limit, $fromuserid);
        $last = $fromuserid;
        foreach ($userids as $userid) {
            $last = (int)$userid;
            self::apply_rule_to_user($rulerec, $last, null);
        }

        if (count($userids) >= $limit) {
            self::queue_backfill($ruleid, $last);
            return $last;
        }
        return 0;
    }

    /**
     * Scan all enabled scheduled conditions.
     */
    public static function run_scheduled(): void {
        if (self::is_killed()) {
            return;
        }
        foreach (rule::get_enabled(true) as $rulerec) {
            $condition = registry::get_condition($rulerec->conditiontype);
            if (!$condition || !$condition->is_scheduled()) {
                continue;
            }
            self::queue_backfill((int)$rulerec->id, 0);
        }
    }

    /**
     * @param stdClass $rulerec
     * @param stdClass $actionrec
     * @param int $userid
     * @return array
     */
    protected static function execute_action(stdClass $rulerec, stdClass $actionrec, int $userid): array {
        global $DB;

        if (self::action_limit_blocks($rulerec, $actionrec, $userid)) {
            $result = [
                'status' => 'skipped',
                'reason' => get_string('reason_already_applied', 'tool_murules'),
            ];
            self::write_match($rulerec, $actionrec, $userid, $result);
            return $result;
        }

        $action = registry::get_action($actionrec->actiontype);
        if (!$action) {
            $result = [
                'status' => 'failed',
                'reason' => get_string('reason_broken', 'tool_murules'),
            ];
            self::write_match($rulerec, $actionrec, $userid, $result);
            return $result;
        }
        $config = action\base::decode_config($actionrec->actionconfig);
        if ($action->is_broken($config)) {
            $result = [
                'status' => 'failed',
                'reason' => get_string('reason_broken', 'tool_murules'),
            ];
            self::write_match($rulerec, $actionrec, $userid, $result);
            return $result;
        }

        $result = $action->execute($userid, $config, $rulerec, $actionrec);
        self::write_match($rulerec, $actionrec, $userid, $result);
        return $result;
    }

    /**
     * @param stdClass $rulerec
     * @param stdClass $actionrec
     * @param int $userid
     * @return bool true if blocked
     */
    protected static function action_limit_blocks(stdClass $rulerec, stdClass $actionrec, int $userid): bool {
        global $DB;
        $limit = $rulerec->actionlimit ?? 'once';
        if ($limit === 'none') {
            return false;
        }
        // Wave A: once-ever = any prior applied row for this rule+action+user.
        if ($limit === 'once') {
            return $DB->record_exists('tool_murules_match', [
                'ruleid' => $rulerec->id,
                'actionid' => $actionrec->id,
                'userid' => $userid,
                'status' => 'applied',
            ]);
        }
        return false;
    }

    /**
     * @param stdClass $rulerec
     * @param stdClass $actionrec
     * @param int $userid
     * @param array $result
     */
    protected static function write_match(stdClass $rulerec, stdClass $actionrec, int $userid, array $result): void {
        global $DB;
        $record = (object)[
            'ruleid' => (int)$rulerec->id,
            'userid' => $userid,
            'actionid' => (int)$actionrec->id,
            'status' => (string)($result['status'] ?? 'failed'),
            'reason' => (string)($result['reason'] ?? ''),
            'muprogallocationid' => isset($result['muprogallocationid']) ? (int)$result['muprogallocationid'] : null,
            'timecreated' => time(),
        ];
        $DB->insert_record('tool_murules_match', $record);
    }

    /**
     * @param string $status
     * @param string $reason
     * @return array
     */
    protected static function log_only_result(string $status, string $reason): array {
        return ['status' => $status, 'reason' => $reason, 'logged' => false];
    }

    /**
     * Auto-disable an enabled rule whose condition is broken / missing.
     *
     * @param stdClass $rulerec
     */
    protected static function disable_broken_rule(stdClass $rulerec): void {
        global $DB;
        if (empty($rulerec->enabled) || empty($rulerec->id)) {
            return;
        }
        $DB->set_field('tool_murules_rule', 'enabled', 0, ['id' => (int)$rulerec->id]);
        $DB->set_field('tool_murules_rule', 'timemodified', time(), ['id' => (int)$rulerec->id]);
        $rulerec->enabled = 0;
    }
}
