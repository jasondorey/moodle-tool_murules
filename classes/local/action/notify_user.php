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

namespace tool_murules\local\action;

use MoodleQuickForm;
use stdClass;

/**
 * Send a Moodle notification/message to the matched user.
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notify_user extends base {

    public function get_type(): string {
        return 'notify_user';
    }

    public function get_name(): string {
        return get_string('action:notify_user', 'tool_murules');
    }

    public function get_description(array $config): string {
        $subject = trim((string)($config['subject'] ?? ''));
        return get_string('action:notify_user_desc', 'tool_murules', $subject !== '' ? $subject : '…');
    }

    public function add_config_form_elements(MoodleQuickForm $mform, array $config = []): void {
        $mform->addElement('text', 'nu_subject', get_string('notify_subject', 'tool_murules'), ['size' => 60]);
        $mform->setType('nu_subject', PARAM_TEXT);
        $mform->setDefault('nu_subject', $config['subject'] ?? '');
        $mform->addRule('nu_subject', get_string('error_notify_subject', 'tool_murules'), 'required', null, 'client');
        $mform->addHelpButton('nu_subject', 'notify_subject', 'tool_murules');

        $mform->addElement('textarea', 'nu_body', get_string('notify_body', 'tool_murules'), 'rows="6" cols="60"');
        $mform->setType('nu_body', PARAM_RAW);
        $mform->setDefault('nu_body', $config['body'] ?? '');
        $mform->addRule('nu_body', get_string('error_notify_body', 'tool_murules'), 'required', null, 'client');
        $mform->addHelpButton('nu_body', 'notify_body', 'tool_murules');
    }

    public function validate_config(array $data): array {
        $errors = [];
        if (trim((string)($data['nu_subject'] ?? '')) === '') {
            $errors['nu_subject'] = get_string('error_notify_subject', 'tool_murules');
        }
        if (trim((string)($data['nu_body'] ?? '')) === '') {
            $errors['nu_body'] = get_string('error_notify_body', 'tool_murules');
        }
        return $errors;
    }

    public function extract_config(array $data): array {
        return [
            'subject' => trim((string)($data['nu_subject'] ?? '')),
            'body' => trim((string)($data['nu_body'] ?? '')),
        ];
    }

    public function execute(int $userid, array $config, stdClass $rule, stdClass $actionrec): array {
        global $DB;

        $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0]);
        if (!$user) {
            return [
                'status' => 'failed',
                'reason' => get_string('reason_user_deleted', 'tool_murules'),
            ];
        }

        $subject = (string)($config['subject'] ?? '');
        $body = (string)($config['body'] ?? '');
        if ($subject === '' || $body === '') {
            return [
                'status' => 'failed',
                'reason' => get_string('reason_broken', 'tool_murules'),
            ];
        }

        // Simple placeholders.
        $replacements = [
            '{$a->fullname}' => fullname($user),
            '{$a->firstname}' => $user->firstname,
            '{$a->rulename}' => format_string($rule->name),
        ];
        $subject = strtr($subject, $replacements);
        $body = strtr($body, $replacements);

        $message = new \core\message\message();
        $message->component = 'tool_murules';
        $message->name = 'rule_notification';
        $message->userfrom = \core_user::get_noreply_user();
        $message->userto = $user;
        $message->subject = $subject;
        $message->fullmessage = html_to_text($body);
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml = format_text($body, FORMAT_MOODLE);
        $message->smallmessage = $subject;
        $message->notification = 1;

        try {
            $msgid = @message_send($message);
        } catch (\Throwable $e) {
            $msgid = 0;
            $lasterror = $e->getMessage();
        }

        if (!empty($msgid)) {
            return [
                'status' => 'applied',
                'reason' => get_string('reason_notify_ok', 'tool_murules'),
            ];
        }

        // Dev/CLI hosts without a mailer often return no message id; still record a notification.
        try {
            global $DB;
            if ($DB->get_manager()->table_exists('notifications')) {
                $DB->insert_record('notifications', (object)[
                    'useridfrom' => \core_user::get_noreply_user()->id,
                    'useridto' => $userid,
                    'subject' => $subject,
                    'fullmessage' => html_to_text($body),
                    'fullmessageformat' => FORMAT_PLAIN,
                    'fullmessagehtml' => format_text($body, FORMAT_MOODLE),
                    'smallmessage' => $subject,
                    'component' => 'tool_murules',
                    'eventtype' => 'rule_notification',
                    'contexturl' => null,
                    'contexturlname' => null,
                    'timeread' => null,
                    'timecreated' => time(),
                    'customdata' => null,
                ]);
                return [
                    'status' => 'applied',
                    'reason' => get_string('reason_notify_ok', 'tool_murules'),
                ];
            }
        } catch (\Throwable $e) {
            $lasterror = $e->getMessage();
        }

        return [
            'status' => 'failed',
            'reason' => get_string('reason_notify_failed', 'tool_murules', $lasterror ?? 'no id'),
        ];
    }
}
