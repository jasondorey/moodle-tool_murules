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

        // Once-ever: if this user already has any prior applied match for the rule
        // (from an earlier run), skip rematch work and do not write new log rows.
        // Snapshot is taken before this call's actions run so multi-action first apply
        // still executes every action in the same pass. Failed-only rows may still retry.
        if (self::user_has_prior_applied_once_ever($rulerec, $userid)) {
            return [];
        }

        $conditions = rule::get_conditions_decoded($rulerec);
        if (!$conditions) {
            self::disable_broken_rule($rulerec);
            return [self::log_only_result('failed', get_string('reason_broken', 'tool_murules'))];
        }
        foreach ($conditions as $cond) {
            $condition = registry::get_condition($cond['conditiontype']);
            if (!$condition || $condition->is_broken($cond['conditionconfig'])) {
                self::disable_broken_rule($rulerec);
                return [self::log_only_result('failed', get_string('reason_broken', 'tool_murules'))];
            }
            if (!$condition->user_matches($userid, $cond['conditionconfig'], $event)) {
                // AND: any miss means the rule does not match — no log row.
                return [];
            }
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
        $conditions = rule::get_conditions_decoded($rulerec);
        if (!$conditions || rule::is_broken($rulerec)) {
            return 0;
        }

        // Candidate users from the first condition; AND-check the rest per user.
        $primary = registry::get_condition($conditions[0]['conditiontype']);
        if (!$primary) {
            return 0;
        }
        $limit = self::max_backfill_batch();
        // Over-fetch when once-ever so already-applied users don't starve the batch.
        $fetch = $limit;
        if (self::is_once_ever($rulerec)) {
            $fetch = min($limit * 5, max($limit, 1000));
        }
        $rawids = $primary->get_matching_user_ids($conditions[0]['conditionconfig'], $fetch, $fromuserid);
        if (!$rawids) {
            return 0;
        }
        $lastseen = (int)end($rawids);
        $candidates = self::filter_once_ever_candidates($rulerec, $rawids);
        $processed = 0;
        foreach ($candidates as $userid) {
            $userid = (int)$userid;
            self::apply_rule_to_user($rulerec, $userid, null);
            $processed++;
            if ($processed >= $limit) {
                self::queue_backfill($ruleid, $userid);
                return $userid;
            }
        }

        // Exhausted this fetch window; continue if the primary condition still has more users.
        if (count($rawids) >= $fetch) {
            self::queue_backfill($ruleid, $lastseen);
            return $lastseen;
        }
        return 0;
    }

    /**
     * Estimate how many users currently match all AND conditions.
     *
     * Scans candidates from the first condition, then filters with user_matches
     * on the remaining conditions. Caps the scan at $limit.
     *
     * @param array $conditions list of [conditiontype, conditionconfig]
     * @param int $limit
     * @return array{count:int, capped:bool, broken:bool, limit:int}
     */
    public static function estimate_matching_users_and(array $conditions, int $limit = 500): array {
        $limit = max(1, $limit);
        if (!$conditions) {
            return ['count' => 0, 'capped' => false, 'broken' => true, 'limit' => $limit];
        }
        $instances = [];
        foreach ($conditions as $cond) {
            $type = (string)($cond['conditiontype'] ?? '');
            $config = $cond['conditionconfig'] ?? [];
            $condition = registry::get_condition($type);
            if (!$condition || $condition->is_broken($config)) {
                return ['count' => 0, 'capped' => false, 'broken' => true, 'limit' => $limit];
            }
            $instances[] = ['instance' => $condition, 'config' => $config];
        }
        try {
            $candidates = $instances[0]['instance']->get_matching_user_ids(
                $instances[0]['config'],
                $limit + 1,
                0
            );
        } catch (\Throwable $e) {
            return ['count' => 0, 'capped' => false, 'broken' => true, 'limit' => $limit];
        }
        $matched = 0;
        $scanned = 0;
        foreach ($candidates as $userid) {
            $scanned++;
            $ok = true;
            for ($i = 1; $i < count($instances); $i++) {
                if (!$instances[$i]['instance']->user_matches((int)$userid, $instances[$i]['config'], null)) {
                    $ok = false;
                    break;
                }
            }
            if ($ok) {
                $matched++;
                if ($matched >= $limit) {
                    return ['count' => $limit, 'capped' => true, 'broken' => false, 'limit' => $limit];
                }
            }
        }
        return [
            'count' => $matched,
            'capped' => $scanned > $limit,
            'broken' => false,
            'limit' => $limit,
        ];
    }

    /**
     * Estimate how many users currently match a condition config.
     *
     * Caps the scan at $limit and reports whether the result was truncated.
     *
     * @param string $conditiontype
     * @param array $config
     * @param int $limit
     * @return array{count:int, capped:bool, broken:bool, limit:int}
     */
    public static function estimate_matching_users(string $conditiontype, array $config, int $limit = 500): array {
        return self::estimate_matching_users_and([
            ['conditiontype' => $conditiontype, 'conditionconfig' => $config],
        ], $limit);
    }

    /**
     * Human label for {@see estimate_matching_users()}.
     *
     * @param array $estimate
     * @return string
     */
    public static function format_matching_estimate(array $estimate): string {
        if (!empty($estimate['broken'])) {
            return get_string('matchingusers_estimate_broken', 'tool_murules');
        }
        $count = (int)($estimate['count'] ?? 0);
        if ($count <= 0) {
            return get_string('matchingusers_estimate_none', 'tool_murules');
        }
        $a = (object)['count' => $count, 'limit' => (int)($estimate['limit'] ?? $count)];
        if (!empty($estimate['capped'])) {
            return get_string('matchingusers_estimate_capped', 'tool_murules', $a);
        }
        return get_string('matchingusers_estimate', 'tool_murules', $a);
    }

    /**
     * Scan all enabled scheduled conditions.
     */
    public static function run_scheduled(): void {
        if (self::is_killed()) {
            return;
        }
        foreach (rule::get_enabled(true) as $rulerec) {
            $needs = false;
            foreach (rule::get_conditions_decoded($rulerec) as $cond) {
                $condition = registry::get_condition($cond['conditiontype']);
                if ($condition && $condition->is_scheduled()) {
                    $needs = true;
                    break;
                }
            }
            if ($needs) {
                self::queue_backfill((int)$rulerec->id, 0);
            }
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
            // Silent skip: do not insert another "Already applied" log row.
            return self::log_only_result('skipped', get_string('reason_already_applied', 'tool_murules'));
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
     * @return bool
     */
    protected static function is_once_ever(stdClass $rulerec): bool {
        return ($rulerec->actionlimit ?? 'once') === 'once';
    }

    /**
     * True when once-ever and the user already has an applied match for this rule.
     *
     * @param stdClass $rulerec
     * @param int $userid
     * @return bool
     */
    protected static function user_has_prior_applied_once_ever(stdClass $rulerec, int $userid): bool {
        global $DB;
        if (!self::is_once_ever($rulerec)) {
            return false;
        }
        return $DB->record_exists('tool_murules_match', [
            'ruleid' => (int)$rulerec->id,
            'userid' => $userid,
            'status' => 'applied',
        ]);
    }

    /**
     * Drop candidates who already have an applied match for this once-ever rule.
     *
     * @param stdClass $rulerec
     * @param int[] $userids
     * @return int[]
     */
    protected static function filter_once_ever_candidates(stdClass $rulerec, array $userids): array {
        global $DB;
        if (!self::is_once_ever($rulerec) || !$userids) {
            return $userids;
        }

        $userids = array_values(array_map('intval', $userids));
        [$uinsql, $uparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'uid');
        $params = array_merge($uparams, ['ruleid' => (int)$rulerec->id]);
        $sql = "SELECT DISTINCT userid
                  FROM {tool_murules_match}
                 WHERE ruleid = :ruleid
                   AND status = 'applied'
                   AND userid $uinsql";
        $done = $DB->get_records_sql($sql, $params);
        if (!$done) {
            return $userids;
        }
        $doneflip = array_flip(array_map('intval', array_keys($done)));
        return array_values(array_filter(
            $userids,
            static fn(int $id): bool => !isset($doneflip[$id])
        ));
    }

    /**
     * @param stdClass $rulerec
     * @param stdClass $actionrec
     * @param int $userid
     * @return bool true if blocked
     */
    protected static function action_limit_blocks(stdClass $rulerec, stdClass $actionrec, int $userid): bool {
        global $DB;
        if (!self::is_once_ever($rulerec)) {
            return false;
        }
        // Safety net when early rule-level skip did not run: prior applied for this action.
        return $DB->record_exists('tool_murules_match', [
            'ruleid' => $rulerec->id,
            'actionid' => $actionrec->id,
            'userid' => $userid,
            'status' => 'applied',
        ]);
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
