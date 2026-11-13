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
 * Language strings for tool_murules.
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Rules';
$string['murules:manage'] = 'Manage rules';
$string['privacy:metadata'] = 'The Rules plugin stores rule definitions and match logs for local automation.';
$string['privacy:metadata:match'] = 'Match and apply log rows for rule automation.';
$string['privacy:metadata:match:userid'] = 'The user who matched the rule.';
$string['privacy:metadata:match:ruleid'] = 'The rule that was evaluated.';
$string['privacy:metadata:match:status'] = 'Whether the action was applied, skipped, or failed.';
$string['privacy:metadata:match:reason'] = 'Human-readable reason for the outcome.';
$string['privacy:metadata:match:muprogallocationid'] = 'Related MuTMS program allocation id when applicable.';
$string['privacy:metadata:match:timecreated'] = 'When the match was recorded.';
$string['privacy:metadata:rule'] = 'Rule definitions.';
$string['privacy:metadata:rule:usermodified'] = 'The user who last modified the rule.';

$string['managerules'] = 'Rules';
$string['rulesoverview'] = 'Rules';
$string['norules_title'] = 'No rules yet';
$string['norules'] = 'Create a rule to automate program allocation from quiz scores, course completion, cohorts, or MuTMS events.';
$string['createrule'] = 'Create rule';
$string['rulesaved'] = 'Rule “{$a}” saved.';
$string['wizard_steps_label'] = 'Rule wizard steps';
$string['rulesoverview_help'] = 'Each card is a WHEN → THEN rule. Enable a rule to process matches; open the log to see applied, skipped, and failed users.';
$string['editrule'] = 'Edit rule';
$string['deleterule'] = 'Delete rule';
$string['deleteruleconfirm'] = 'Delete rule “{$a}”? This cannot be undone. Match log entries for this rule will also be removed. Enabled rules stop processing immediately.';
$string['enablerule'] = 'Enable';
$string['disablerule'] = 'Disable';
$string['viewlog'] = 'Log';
$string['ruleenabled'] = 'Enabled';
$string['ruledisabled'] = 'Disabled';
$string['rulebroken'] = 'Broken';
$string['when'] = 'WHEN';
$string['then'] = 'THEN';
$string['matchstats'] = '{$a->applied} applied · {$a->skipped} skipped · {$a->failed} failed';
$string['lastapplied'] = 'Last applied {$a}';

$string['wizard_event'] = 'Event';
$string['wizard_actions'] = 'Actions';
$string['wizard_review'] = 'Review';
$string['wizard_event_help'] = 'Choose what must happen for this rule to match a user. Changing the condition type reloads this step with the right settings.';
$string['wizard_actions_help'] = 'Choose what happens when the condition matches. Only one action is configured per rule.';
$string['wizard_review_help'] = 'Name the rule, set limits, confirm the WHEN → THEN recap, then save.';
$string['wizard_review_recap'] = 'WHEN → THEN recap';
$string['filter_label'] = 'Filter rules';
$string['filter_all'] = 'All';
$string['filter_enabled'] = 'Enabled';
$string['filter_disabled'] = 'Disabled';
$string['filter_broken'] = 'Broken';
$string['filter_empty_title'] = 'No rules in this filter';
$string['filter_empty'] = 'Try another filter or clear the search, or create a new rule.';
$string['searchrules'] = 'Search rules';
$string['searchrules_placeholder'] = 'Search by rule name';
$string['clearsearch'] = 'Clear search';
$string['searchquery_label'] = '“{$a}”';
$string['overview_showing'] = 'Showing: {$a}';
$string['noprograms_manual'] = 'No programs with a manual allocation source are available. Create a MuTMS program with a manual source first.';
$string['nocohorts'] = 'No cohorts are available yet. Create a cohort first, then return to configure this action.';
$string['program_allocate'] = 'Program';
$string['program_allocate_help'] = 'Only programs that have a manual allocation source can be used. The rule allocates matching users through that source.';
$string['program_deallocate'] = 'Program';
$string['program_deallocate_help'] = 'Only removes allocations that this Rules plugin previously created for the user on the selected program.';

$string['matchingusers'] = 'Matching users';
$string['matchingusers_estimate'] = '{$a->count} users currently match';
$string['matchingusers_estimate_capped'] = 'At least {$a->count} users currently match (estimate capped at {$a->limit})';
$string['matchingusers_estimate_none'] = 'No users currently match';
$string['matchingusers_estimate_broken'] = 'Cannot estimate — condition references missing data';
$string['matchingusers_estimate_note'] = 'Estimate uses the condition only (before save). Suspended-user inclusion is applied when the rule runs.';
$string['loadingcms'] = 'Loading activities…';
$string['nocms'] = 'No matching activities in this course';

$string['conditiontype'] = 'Condition';
$string['conditionconfig'] = 'Condition settings';
$string['actiontype'] = 'Action';
$string['actionconfig'] = 'Action settings';
$string['rulename'] = 'Rule name';
$string['enabled'] = 'Enabled';
$string['enabled_help'] = 'When enabled, the rule listens for events and/or runs on schedule. Saving an enabled rule queues a backfill for users who already match.';
$string['actionlimit'] = 'Action limit';
$string['actionlimit_help'] = '“Apply once ever” records a successful apply and will not run the action again for that user. “No limit” may apply repeatedly when the condition keeps matching.';
$string['actionlimit_once'] = 'Apply once ever';
$string['actionlimit_none'] = 'No limit';
$string['includesuspended'] = 'Include suspended users';
$string['includesuspended_help'] = 'When off (default), suspended accounts are ignored even if they match the condition.';
$string['deleteduser'] = 'deleted account';
$string['saveandcontinue'] = 'Save and continue';
$string['save'] = 'Save rule';
$string['back'] = 'Back';
$string['cancelwizard'] = 'Cancel';

$string['settings'] = 'Rules settings';
$string['settings_desc'] = 'Global controls for local automation rules.';
$string['killswitch'] = 'Disable all rule processing';
$string['killswitch_desc'] = 'When enabled, observers, backfill, and scheduled scans do not apply actions.';
$string['killswitch_banner_title'] = 'Rule processing is paused.';
$string['killswitch_banner'] = 'The global kill-switch is on — rules will not apply actions until it is turned off.';
$string['killswitch_banner_link'] = 'Open Rules settings';
$string['observersoff_banner_title'] = 'Realtime observers are off.';
$string['observersoff_banner'] = 'Rules will not react to live events. Scheduled conditions and manual backfill can still run.';
$string['log_filter_label'] = 'Filter log by status';
$string['log_filter_all'] = 'All';
$string['log_filter_applied'] = 'Applied';
$string['log_filter_skipped'] = 'Skipped';
$string['log_filter_failed'] = 'Failed';
$string['log_filter_empty_title'] = 'No entries for this status';
$string['log_filter_empty'] = 'Try another status filter.';
$string['log_resultcount'] = 'Showing {$a->shown} of {$a->total} entries';
$string['realtimeobservers'] = 'Realtime event observers';
$string['realtimeobservers_desc'] = 'Queue matching when relevant events fire.';
$string['maxbackfillbatch'] = 'Max backfill batch size';
$string['maxbackfillbatch_desc'] = 'Maximum users processed per on-enable or scheduled backfill run.';
$string['schedulednote'] = 'Scheduled conditions are scanned by the Rules cron task (default every 15 minutes).';

$string['category_general'] = 'General';
$string['category_local'] = 'Local';
$string['category_program'] = 'Programs';
$string['category_certification'] = 'Certifications';
$string['category_relation'] = 'Relations';
$string['category_tenancy'] = 'Tenancy';

$string['condition:quiz_score'] = 'Quiz score threshold';
$string['condition:quiz_score_desc'] = 'Quiz "{$a->quiz}" score {$a->operator} {$a->percent}%';
$string['condition:activity_completed'] = 'Activity successfully completed';
$string['condition:activity_completed_desc'] = 'Activity "{$a->activity}" successfully completed';

$string['condition:cohort_member'] = 'User is member of cohort';
$string['condition:cohort_member_desc'] = 'User is member of cohort "{$a->cohort}"{$a->added}';
$string['condition:cohort_member_added_after'] = ' (added on/after {$a})';
$string['condition:cohort_not_member'] = 'User is not member of cohort';
$string['condition:cohort_not_member_desc'] = 'User is not member of cohort "{$a->cohort}"';
$string['condition:competency_achieved'] = 'User has achieved competency';
$string['condition:competency_achieved_desc'] = 'User has achieved competency "{$a->competency}"';
$string['condition:course_completed'] = 'Course completed';
$string['condition:course_completed_desc'] = 'Course "{$a->course}" completed ({$a->completion}; grade {$a->grade})';
$string['condition:course_last_access'] = 'Course last access';
$string['condition:course_last_access_desc'] = 'Course "{$a->course}" last access {$a->window}';
$string['condition:course_not_completed'] = 'Course not completed';
$string['condition:course_not_completed_desc'] = 'Course "{$a->course}" not completed';
$string['condition:user_created_since'] = 'Time since user was created';
$string['condition:user_created_since_desc'] = 'User created {$a->window}';
$string['condition:user_enrolled'] = 'User enrolled';
$string['condition:user_enrolled_desc'] = 'User enrolled in "{$a->course}" via {$a->method}{$a->start}';
$string['condition:user_first_login'] = 'User first login';
$string['condition:user_first_login_desc'] = 'User first login {$a->window}';
$string['condition:user_last_login'] = 'User last login';
$string['condition:user_last_login_desc'] = 'User last login {$a->window}';
$string['condition:user_not_enrolled'] = 'User not enrolled';
$string['condition:user_not_enrolled_desc'] = 'User not enrolled in "{$a->course}" via {$a->method}';
$string['condition:user_profile_field'] = 'User profile field';
$string['condition:user_profile_field_desc'] = 'Profile field "{$a->field}" {$a->op} "{$a->value}"';

$string['course'] = 'Course';
$string['activity'] = 'Activity';
$string['quiz'] = 'Quiz';
$string['cohort'] = 'Cohort';
$string['competency'] = 'Competency';
$string['operator'] = 'Operator';
$string['operator_gte'] = 'Greater than or equal to';
$string['operator_gt'] = 'Greater than';
$string['operator_lte'] = 'Less than or equal to';
$string['operator_lt'] = 'Less than';
$string['operator_eq'] = 'Equal to';
$string['percent'] = 'Percent';
$string['grademode'] = 'Grade mode';
$string['grademode_quiz_method'] = 'Quiz grade method (gradebook)';
$string['cohort_added_on_after'] = 'Added on or after (optional)';
$string['enrolmethod'] = 'Enrolment method';
$string['enrolmethod_any'] = 'Any enrolment method';
$string['enrol_start_on_after'] = 'Enrolment start on or after (optional)';
$string['enrol_start_after_desc'] = '(start on/after {$a})';
$string['completion_when'] = 'Completion time';
$string['completion_when_any'] = 'Any time';
$string['completion_when_after'] = 'On or after';
$string['completion_when_before'] = 'Before';
$string['completion_after'] = 'Completed on or after';
$string['completion_before'] = 'Completed before';
$string['grade_op'] = 'Course grade';
$string['grade_op_any'] = 'Any grade';
$string['grade_op_lt'] = 'Less than';
$string['grade_op_gt'] = 'Greater than';
$string['grade_op_range'] = 'In range';
$string['grade_lt'] = 'Grade less than (%)';
$string['grade_gt'] = 'Grade greater than (%)';
$string['grade_from'] = 'Grade from (%)';
$string['grade_to'] = 'Grade to (%)';
$string['timewindow'] = 'Time window';
$string['timewindow_ever'] = 'Ever';
$string['timewindow_never'] = 'Never';
$string['timewindow_before'] = 'Before';
$string['timewindow_after'] = 'On or after';
$string['timewindow_during_last'] = 'During last';
$string['timewindow_prior_to_last'] = 'Prior to last';
$string['timewindow_between'] = 'Between';
$string['timewindow_datetime'] = 'Before date';
$string['timewindow_datetime_after'] = 'On or after date';
$string['timewindow_from'] = 'From';
$string['timewindow_to'] = 'To';
$string['timewindow_amount'] = 'Amount';
$string['timewindow_unit'] = 'Unit';
$string['timewindow_unit_hours'] = 'hours';
$string['timewindow_unit_days'] = 'days';
$string['timewindow_unit_weeks'] = 'weeks';
$string['timewindow_desc_ever'] = 'ever';
$string['timewindow_desc_never'] = 'never';
$string['timewindow_desc_before'] = 'before {$a->datetime}';
$string['timewindow_desc_after'] = 'on or after {$a->datetime}';
$string['timewindow_desc_during_last'] = 'during last {$a->amount} {$a->unit}';
$string['timewindow_desc_prior_to_last'] = 'prior to last {$a->amount} {$a->unit}';
$string['timewindow_desc_between'] = 'between {$a->datetime} and {$a->datetime_to}';
$string['profile_field'] = 'Profile field';
$string['profile_value'] = 'Value';
$string['profile_standard'] = 'standard';
$string['profile_custom'] = 'custom';
$string['profile_op_eq'] = 'equals';
$string['profile_op_neq'] = 'does not equal';
$string['profile_op_contains'] = 'contains';
$string['profile_op_notcontains'] = 'does not contain';
$string['profile_op_isempty'] = 'is empty';
$string['profile_op_isnotempty'] = 'is not empty';
$string['profile_op_startswith'] = 'starts with';
$string['profile_op_endswith'] = 'ends with';
$string['error_nocourse'] = 'Select a course.';
$string['error_noquiz'] = 'Select a quiz activity.';
$string['error_noactivity'] = 'Select an activity.';
$string['error_nocohort'] = 'Select a cohort.';
$string['error_nocompetency'] = 'Select a competency.';
$string['error_noprofilefield'] = 'Select a profile field.';
$string['error_profile_value'] = 'Enter a value to compare.';
$string['error_percent'] = 'Enter a percent between 0 and 100.';
$string['error_completion_date'] = 'Select a completion date.';
$string['error_grade_value'] = 'Enter a grade percent.';
$string['error_grade_range'] = 'Enter a valid grade range.';
$string['error_timewindow_amount'] = 'Enter a positive amount.';
$string['error_timewindow_datetime'] = 'Select a date.';
$string['error_timewindow_range'] = 'Enter a valid date range.';
$string['error_noprogram'] = 'Select a program with a manual allocation source.';
$string['error_nosource'] = 'Selected program has no manual allocation source.';
$string['error_condition'] = 'Select a condition type.';
$string['error_action'] = 'Select an action type.';
$string['error_name'] = 'Enter a rule name.';

$string['condition:program_allocated'] = 'User allocated to program';
$string['condition:program_allocated_desc'] = 'User allocated to program "{$a->program}"';
$string['condition:program_not_allocated'] = 'User not allocated to program';
$string['condition:program_not_allocated_desc'] = 'User not allocated to program "{$a->program}"';
$string['condition:program_completed'] = 'Program completed';
$string['condition:program_completed_desc'] = 'Program "{$a->program}" completed';
$string['condition:program_overdue'] = 'Program overdue';
$string['condition:program_overdue_desc'] = 'Program "{$a->program}" overdue';
$string['condition:certification_assigned'] = 'User assigned to certification';
$string['condition:certification_assigned_desc'] = 'User assigned to certification "{$a->certification}"';
$string['condition:certification_certified'] = 'User certified';
$string['condition:certification_certified_desc'] = 'User currently certified in "{$a->certification}"';
$string['condition:certification_expired'] = 'Certification expired';
$string['condition:certification_expired_desc'] = 'Certification "{$a->certification}" expired';
$string['condition:relation_subordinate'] = 'User is subordinate';
$string['condition:relation_subordinate_desc'] = 'User is subordinate in relation "{$a->framework}"';
$string['condition:relation_supervisor'] = 'User is supervisor';
$string['condition:relation_supervisor_desc'] = 'User is supervisor in relation "{$a->framework}"';
$string['condition:tenant_member'] = 'User allocated to tenant';
$string['condition:tenant_member_desc'] = 'User allocated to tenant "{$a->tenant}"';
$string['condition:tenant_not_member'] = 'User not allocated to tenant';
$string['condition:tenant_not_member_desc'] = 'User not allocated to tenant "{$a->tenant}"';

$string['action:allocate_muprog'] = 'Allocate to program';
$string['action:allocate_muprog_desc'] = 'Allocate to program "{$a}" (manual source)';
$string['action:deallocate_muprog'] = 'Deallocate from program';
$string['action:deallocate_muprog_desc'] = 'Deallocate from program "{$a}" (rule-owned only)';
$string['action:notify_user'] = 'Notify user';
$string['action:notify_user_desc'] = 'Notify user: {$a}';
$string['action:cohort_add'] = 'Add to cohort';
$string['action:cohort_add_desc'] = 'Add to cohort "{$a}"';
$string['action:cohort_remove'] = 'Remove from cohort';
$string['action:cohort_remove_desc'] = 'Remove from cohort "{$a}"';
$string['program'] = 'Program';
$string['certification'] = 'Certification';
$string['tenant'] = 'Tenant';
$string['relation_framework'] = 'Relation framework';
$string['notify_subject'] = 'Subject';
$string['notify_subject_help'] = 'Short notification subject. You can use placeholders: {$a->fullname}, {$a->firstname}, {$a->rulename}.';
$string['notify_body'] = 'Message';
$string['notify_body_help'] = 'Message body sent to the matched user. Placeholders (include the braces literally): {$a->fullname}, {$a->firstname}, {$a->rulename}.';
$string['backtorules'] = '← Back to rules';
$string['messageprovider:rule_notification'] = 'Rule notification';
$string['error_nocertification'] = 'Select a certification.';
$string['error_notenant'] = 'Select a tenant.';
$string['error_noframework'] = 'Select a relation framework.';
$string['error_notify_subject'] = 'Enter a subject.';
$string['error_notify_body'] = 'Enter a message.';
$string['reason_already_cohort_member'] = 'User already in cohort';
$string['reason_not_cohort_member'] = 'User not in cohort';
$string['reason_cohort_add_ok'] = 'Added to cohort';
$string['reason_cohort_remove_ok'] = 'Removed from cohort';
$string['reason_cohort_failed'] = 'Cohort change failed: {$a}';
$string['reason_notify_ok'] = 'Notification sent';
$string['reason_notify_failed'] = 'Notification failed: {$a}';
$string['reason_not_allocated'] = 'User not allocated to program';
$string['reason_not_rule_owned'] = 'Allocation was not created by Rules (not deallocated)';
$string['reason_deallocate_ok'] = 'Deallocated from program';
$string['reason_deallocate_failed'] = 'Deallocation failed: {$a}';

$string['rulelog'] = 'Rule log';
$string['status'] = 'Status';
$string['status_applied'] = 'Applied';
$string['status_skipped'] = 'Skipped';
$string['status_failed'] = 'Failed';
$string['reason'] = 'Reason';
$string['user'] = 'User';
$string['time'] = 'Time';
$string['allocation'] = 'Allocation';
$string['nologentries_title'] = 'No matches logged yet';
$string['nologentries'] = 'This rule has not applied to any users yet.';
$string['nologentries_help'] = 'Entries appear when the condition matches and an action runs (or is skipped). Enable the rule to process existing matches.';
$string['task_process_event'] = 'Process rule event';
$string['task_backfill_rule'] = 'Backfill rule matches';
$string['task_cron'] = 'Scan scheduled rules';

$string['reason_already_applied'] = 'Already applied (once-ever limit)';
$string['reason_already_allocated'] = 'User already allocated to program';
$string['reason_killswitch'] = 'Global kill-switch enabled';
$string['reason_observers_off'] = 'Realtime observers disabled';
$string['reason_condition_not_met'] = 'Condition not met';
$string['reason_user_suspended'] = 'User suspended';
$string['reason_user_deleted'] = 'User deleted';
$string['reason_outside_window'] = 'Outside rule time window';
$string['reason_broken'] = 'Rule condition or action is broken';
$string['reason_allocate_ok'] = 'Allocated via manual source';
$string['reason_allocate_failed'] = 'Allocation failed: {$a}';
$string['reason_no_allocation_returned'] = 'No new allocation created (already allocated or skipped by MuTMS)';
