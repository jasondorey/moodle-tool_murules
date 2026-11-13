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

namespace tool_murules\local\condition;

use MoodleQuickForm;
use stdClass;

/**
 * Quiz score threshold condition.
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz_score extends base {

    public function get_type(): string {
        return 'quiz_score';
    }

    public function get_name(): string {
        return get_string('condition:quiz_score', 'tool_murules');
    }

    public function get_category(): string {
        return 'local';
    }

    public function get_description(array $config): string {
        $quizname = $this->resolve_quiz_name($config);
        $op = $config['operator'] ?? 'gte';
        $oplabel = get_string('operator_' . $op, 'tool_murules');
        $a = (object)[
            'quiz' => $quizname,
            'operator' => $oplabel,
            'percent' => (string)($config['percent'] ?? 0),
        ];
        return get_string('condition:quiz_score_desc', 'tool_murules', $a);
    }

    public function get_events(): array {
        return [
            \mod_quiz\event\attempt_graded::class,
            \core\event\user_graded::class,
        ];
    }

    public function add_config_form_elements(MoodleQuickForm $mform, array $config = []): void {
        $mform->addElement('course', 'qs_courseid', get_string('course', 'tool_murules'), [
            'multiple' => false,
            'includefrontpage' => false,
        ]);
        $mform->addRule('qs_courseid', get_string('error_nocourse', 'tool_murules'), 'required', null, 'client');
        if (!empty($config['courseid'])) {
            // Course autocomplete expects an array of ids even for single select.
            $mform->setDefault('qs_courseid', [(int)$config['courseid']]);
        }

        // Prefer saved config; on redisplay after submit also read posted course so AJAX-filled
        // CM options still exist in the select (Moodle rejects values missing from options).
        $courseid = (int)($config['courseid'] ?? 0);
        if (!$courseid) {
            $courseid = self::normalize_courseid($_POST['qs_courseid'] ?? 0);
        }
        $quizoptions = [0 => get_string('choosedots')];
        if ($courseid) {
            $quizoptions += \tool_murules\local\course_modules::options_map($courseid, 'quiz');
        }
        $postedcm = (int)($config['cmid'] ?? ($_POST['qs_cmid'] ?? 0));
        if ($postedcm && !isset($quizoptions[$postedcm])) {
            $quizoptions[$postedcm] = get_string('quiz', 'tool_murules') . ' (#' . $postedcm . ')';
        }
        $mform->addElement('select', 'qs_cmid', get_string('quiz', 'tool_murules'), $quizoptions);
        $mform->addRule('qs_cmid', get_string('error_noquiz', 'tool_murules'), 'required', null, 'client');
        if ($postedcm) {
            $mform->setDefault('qs_cmid', $postedcm);
        }

        $ops = [
            'gte' => get_string('operator_gte', 'tool_murules'),
            'gt' => get_string('operator_gt', 'tool_murules'),
            'lte' => get_string('operator_lte', 'tool_murules'),
            'lt' => get_string('operator_lt', 'tool_murules'),
            'eq' => get_string('operator_eq', 'tool_murules'),
        ];
        $mform->addElement('select', 'qs_operator', get_string('operator', 'tool_murules'), $ops);
        $mform->setDefault('qs_operator', $config['operator'] ?? 'gte');

        $mform->addElement('text', 'qs_percent', get_string('percent', 'tool_murules'));
        $mform->setType('qs_percent', PARAM_FLOAT);
        $mform->setDefault('qs_percent', $config['percent'] ?? 80);
        $mform->addRule('qs_percent', get_string('error_percent', 'tool_murules'), 'required', null, 'client');

        $mform->addElement('select', 'qs_grademode', get_string('grademode', 'tool_murules'), [
            'quiz_method' => get_string('grademode_quiz_method', 'tool_murules'),
        ]);
        $mform->setDefault('qs_grademode', $config['grademode'] ?? 'quiz_method');
    }

    public function validate_config(array $data): array {
        $errors = [];
        $courseid = self::normalize_courseid($data['qs_courseid'] ?? 0);
        $cmid = (int)($data['qs_cmid'] ?? 0);
        $percent = $data['qs_percent'] ?? null;
        if (!$courseid) {
            $errors['qs_courseid'] = get_string('error_nocourse', 'tool_murules');
        }
        if (!$cmid) {
            $errors['qs_cmid'] = get_string('error_noquiz', 'tool_murules');
        } else if ($courseid) {
            $cm = get_coursemodule_from_id('quiz', $cmid, $courseid, false);
            if (!$cm) {
                $errors['qs_cmid'] = get_string('error_noquiz', 'tool_murules');
            }
        }
        if ($percent === null || $percent === '' || (float)$percent < 0 || (float)$percent > 100) {
            $errors['qs_percent'] = get_string('error_percent', 'tool_murules');
        }
        return $errors;
    }

    public function extract_config(array $data): array {
        return [
            'courseid' => self::normalize_courseid($data['qs_courseid'] ?? 0),
            'cmid' => (int)($data['qs_cmid'] ?? 0),
            'operator' => (string)($data['qs_operator'] ?? 'gte'),
            'percent' => (float)($data['qs_percent'] ?? 0),
            'grademode' => (string)($data['qs_grademode'] ?? 'quiz_method'),
        ];
    }

    /**
     * @param mixed $value
     * @return int
     */
    protected static function normalize_courseid($value): int {
        if (is_array($value)) {
            $value = reset($value);
        }
        return (int)$value;
    }

    public function user_matches(int $userid, array $config, ?\core\event\base $event = null): bool {
        $percent = $this->get_user_percent($userid, $config);
        if ($percent === null) {
            return false;
        }
        return $this->compare($percent, (float)($config['percent'] ?? 0), $config['operator'] ?? 'gte');
    }

    public function get_matching_user_ids(array $config, int $limit = 500, int $fromuserid = 0): array {
        global $DB;

        $courseid = (int)($config['courseid'] ?? 0);
        $cmid = (int)($config['cmid'] ?? 0);
        if (!$courseid || !$cmid) {
            return [];
        }
        $cm = get_coursemodule_from_id('quiz', $cmid, $courseid, false);
        if (!$cm) {
            return [];
        }

        // Candidates: enrolled users with a gradebook grade for this quiz.
        $sql = "SELECT DISTINCT u.id
                  FROM {user} u
                  JOIN {user_enrolments} ue ON ue.userid = u.id
                  JOIN {enrol} e ON e.id = ue.enrolid AND e.courseid = :courseid
                  JOIN {grade_items} gi ON gi.itemtype = 'mod' AND gi.itemmodule = 'quiz'
                       AND gi.iteminstance = :quizid AND gi.courseid = e.courseid
                  JOIN {grade_grades} gg ON gg.itemid = gi.id AND gg.userid = u.id
                 WHERE u.deleted = 0
                   AND u.id > :fromuserid
                   AND gg.finalgrade IS NOT NULL
              ORDER BY u.id ASC";
        $params = [
            'courseid' => $courseid,
            'quizid' => $cm->instance,
            'fromuserid' => $fromuserid,
        ];
        $records = $DB->get_records_sql($sql, $params, 0, $limit);
        $matched = [];
        foreach ($records as $rec) {
            $userid = (int)$rec->id;
            if ($this->user_matches($userid, $config)) {
                $matched[] = $userid;
            }
        }
        return $matched;
    }

    public function is_broken(array $config): bool {
        $courseid = (int)($config['courseid'] ?? 0);
        $cmid = (int)($config['cmid'] ?? 0);
        if (!$courseid || !$cmid) {
            return true;
        }
        return !get_coursemodule_from_id('quiz', $cmid, $courseid, false);
    }

    /**
     * @param array $config
     * @return string
     */
    protected function resolve_quiz_name(array $config): string {
        $cmid = (int)($config['cmid'] ?? 0);
        $courseid = (int)($config['courseid'] ?? 0);
        if (!$cmid) {
            return '?';
        }
        $cm = get_coursemodule_from_id('quiz', $cmid, $courseid ?: 0, false);
        return $cm ? format_string($cm->name) : '#'.$cmid;
    }

    /**
     * Gradebook percent for user on quiz (honours quiz grade method once synced).
     *
     * @param int $userid
     * @param array $config
     * @return float|null
     */
    protected function get_user_percent(int $userid, array $config): ?float {
        global $CFG;
        require_once($CFG->libdir . '/gradelib.php');

        $courseid = (int)($config['courseid'] ?? 0);
        $cmid = (int)($config['cmid'] ?? 0);
        $cm = get_coursemodule_from_id('quiz', $cmid, $courseid, false);
        if (!$cm) {
            return null;
        }

        $grades = grade_get_grades($courseid, 'mod', 'quiz', $cm->instance, $userid);
        if (empty($grades->items[0])) {
            return null;
        }
        $item = $grades->items[0];
        if (empty($item->grades[$userid]) || $item->grades[$userid]->grade === null || $item->grademax <= 0) {
            return null;
        }
        return ((float)$item->grades[$userid]->grade / (float)$item->grademax) * 100.0;
    }

    /**
     * @param float $value
     * @param float $threshold
     * @param string $operator
     * @return bool
     */
    protected function compare(float $value, float $threshold, string $operator): bool {
        return match ($operator) {
            'gt' => $value > $threshold,
            'lte' => $value <= $threshold,
            'lt' => $value < $threshold,
            'eq' => abs($value - $threshold) < 0.00001,
            default => $value >= $threshold,
        };
    }
}
