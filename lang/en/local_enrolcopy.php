<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * English language strings.
 *
 * @package    local_enrolcopy
 * @copyright  2026 Odysseas Tsouvalas (SpinWorks)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Copy enrolment methods';
$string['enrolcopy:use'] = 'Copy enrolment methods between courses';

$string['index_intro'] = 'This tool copies enrolment METHOD configurations (e.g. an "Enrol users from profile field" rule, a self-enrolment method, a cohort sync, etc.) from one course into another — the automation/rule itself, not the currently enrolled users. Once copied, the target course will compute its own membership going forward using that same rule.';
$string['index_intro_sameok'] = 'You can pick the same course as both source and target — useful for cloning Field rules enrolment methods within the same course for other groups (a related option appears on the next step).';
$string['sourcecourse'] = 'Source course (copy from)';
$string['targetcourse'] = 'Target course (copy to)';

$string['step2_intro'] = 'Copying enrolment methods from <strong>{$a->source}</strong> to <strong>{$a->target}</strong>. Tick the methods you want to copy.';
$string['step2_intro_samecourse'] = 'Cloning enrolment methods within the same course <strong>{$a->source}</strong>. Tick the methods you want to clone.';
$string['step2_hint'] = 'Methods that cannot be added again to the target course (e.g. it already has a Manual enrolment or Guest access instance) are shown disabled. Note: a few plugins store course-specific IDs (like a default group) in their settings — these are copied as raw values and are worth double-checking after copying.';
$string['level_heading'] = 'Level: {$a}';
$string['othermethods_heading'] = 'Other enrolment methods';
$string['nolevel'] = '(no value)';
$string['active'] = 'active';
$string['disabled'] = 'disabled';
$string['cannotcopy'] = '(cannot be added to the target course — already exists there)';
$string['cannotcopy_exists'] = 'an identical level+group rule already exists in this course';
$string['confirmcopy'] = 'Confirm and copy';

$string['groupclone_heading'] = 'Clone Field rules enrolment for other groups';
$string['groupclone_hint'] = 'Only applies to ticked "Field rules enrolment" methods above. For each group value you add here, a copy of every selected method is created with the same level/role; only the group changes. Skipped if an identical level+group rule already exists.';
$string['groupclone_values'] = 'Group values';

$string['results_summary'] = '{$a->copied} enrolment method(s) copied into <strong>{$a->target}</strong>.';
$string['results_skipped'] = 'Not copied (already exist / not allowed in the target course):';
$string['gotoenrolmethods'] = 'Go to target course enrolment methods';
$string['copyagain'] = 'Copy more methods';

$string['error_nomethods'] = 'The source course has no enrolment methods configured.';
$string['error_nonemethodsselected'] = 'No enrolment methods were selected.';
$string['error_plugin_missing'] = 'plugin not installed';
$string['error_plugin_missing_named'] = 'The enrolment plugin "{$a}" is not installed/enabled on this site.';
$string['error_instance_missing'] = 'Enrolment method #{$a} no longer exists.';
$string['error_add_failed'] = 'Could not add "{$a}" to the target course.';
$string['privacy:metadata'] = 'The plugin copies enrolment method settings between courses. It stores no personal data of its own.';
