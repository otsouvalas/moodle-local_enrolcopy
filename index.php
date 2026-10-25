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
 * Copy enrolment methods: step 1 (courses), step 2 (methods), then the copy and its results.
 *
 * @package    local_enrolcopy
 * @copyright  2026 Odysseas Tsouvalas (SpinWorks)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/enrollib.php');

use local_enrolcopy\copier;
use local_enrolcopy\form\select_courses;
use local_enrolcopy\form\select_methods;

$sourcecourseid = optional_param('sourcecourseid', 0, PARAM_INT);
$targetcourseid = optional_param('targetcourseid', 0, PARAM_INT);

admin_externalpage_setup('local_enrolcopy_index');
require_capability('local/enrolcopy:use', context_system::instance());

$baseurl = new moodle_url('/local/enrolcopy/index.php');
$title = get_string('pluginname', 'local_enrolcopy');
$PAGE->set_title($title);
$PAGE->set_heading($title);

// Step 1: choose source + target course.
if (!$sourcecourseid || !$targetcourseid) {
    $form = new select_courses($baseurl);
    if ($data = $form->get_data()) {
        redirect(new moodle_url($baseurl, [
            'sourcecourseid' => $data->source,
            'targetcourseid' => $data->target,
        ]));
    }
    echo $OUTPUT->header();
    echo $OUTPUT->heading($title);
    $form->display();
    echo $OUTPUT->footer();
    exit;
}

// Step 2: choose which enrolment methods (from the source course) to copy.
if ($sourcecourseid == SITEID || $targetcourseid == SITEID) {
    throw new moodle_exception('invalidcourseid');
}
$source = get_course($sourcecourseid);
$target = get_course($targetcourseid);
$instances = copier::get_course_instances($source->id);

$stepurl = new moodle_url($baseurl, ['sourcecourseid' => $source->id, 'targetcourseid' => $target->id]);
$form = new select_methods($stepurl, ['source' => $source, 'target' => $target, 'instances' => $instances]);
if ($form->is_cancelled()) {
    redirect($baseurl);
}

if (!$instances) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading($title);
    echo $OUTPUT->notification(get_string('error_nomethods', 'local_enrolcopy'), 'warning');
    echo $OUTPUT->continue_button($baseurl);
    echo $OUTPUT->footer();
    exit;
}

$data = $form->get_data();
if (!$data) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading($title);
    $form->display();
    echo $OUTPUT->footer();
    exit;
}

// Step 3: perform the copy, show the results.
echo $OUTPUT->header();
echo $OUTPUT->heading($title);

$instanceids = $form->selected_instance_ids($data);
if (!$instanceids) {
    echo $OUTPUT->notification(get_string('error_nonemethodsselected', 'local_enrolcopy'), 'warning');
    echo $OUTPUT->continue_button($stepurl);
    echo $OUTPUT->footer();
    exit;
}

$result = copier::copy_instances($target->id, $instanceids, (array)($data->groupvalues ?? []));

echo $OUTPUT->notification(
    get_string('results_summary', 'local_enrolcopy', [
        'target' => format_string($target->fullname),
        'copied' => $result->copied,
    ]),
    \core\output\notification::NOTIFY_SUCCESS
);
if ($result->skipped) {
    echo $OUTPUT->notification(
        get_string('results_skipped', 'local_enrolcopy') . '<br>' . implode('<br>', array_map('s', $result->skipped)),
        \core\output\notification::NOTIFY_WARNING
    );
}
if ($result->errors) {
    echo $OUTPUT->notification(implode('<br>', array_map('s', $result->errors)), \core\output\notification::NOTIFY_ERROR);
}

echo $OUTPUT->single_button(
    new moodle_url('/enrol/instances.php', ['id' => $target->id]),
    get_string('gotoenrolmethods', 'local_enrolcopy')
);
echo ' ';
echo $OUTPUT->single_button($baseurl, get_string('copyagain', 'local_enrolcopy'));

echo $OUTPUT->footer();
