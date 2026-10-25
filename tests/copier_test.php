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

namespace local_enrolcopy;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/adminlib.php');

/**
 * Tests for the enrolment method copier.
 *
 * @package    local_enrolcopy
 * @category   test
 * @copyright  2026 Odysseas Tsouvalas (SpinWorks)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(copier::class)]
final class copier_test extends \advanced_testcase {
    /**
     * Adds a self enrolment instance with distinctive settings to a course.
     *
     * @param \stdClass $course
     * @return \stdClass the new {enrol} row
     */
    private function add_self_instance(\stdClass $course): \stdClass {
        global $DB;
        $plugin = enrol_get_plugin('self');
        $id = $plugin->add_instance($course, [
            'name' => 'Spring intake',
            'password' => 'secret-key',
            'status' => ENROL_INSTANCE_DISABLED,
            'customint3' => 25,
            'enrolperiod' => DAYSECS * 30,
        ]);
        return $DB->get_record('enrol', ['id' => $id]);
    }

    public function test_copies_settings_to_another_course(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('enrol_plugins_enabled', 'manual,self');

        $source = $this->getDataGenerator()->create_course();
        $target = $this->getDataGenerator()->create_course();
        $instance = $this->add_self_instance($source);

        $result = copier::copy_instances($target->id, [$instance->id]);

        $this->assertSame(1, $result->copied);
        $this->assertSame([], $result->errors);
        $copy = $DB->get_record('enrol', ['courseid' => $target->id, 'name' => 'Spring intake'], '*', MUST_EXIST);
        $this->assertNotEquals($instance->id, $copy->id);
        foreach (['name', 'password', 'status', 'customint3', 'enrolperiod', 'roleid'] as $field) {
            $this->assertEquals($instance->$field, $copy->$field, $field);
        }
        // The source course is left untouched.
        $this->assertSame(1, $DB->count_records('enrol', ['courseid' => $source->id, 'name' => 'Spring intake']));
    }

    public function test_does_not_copy_users(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $source = $this->getDataGenerator()->create_course();
        $target = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $source->id, null, 'self');
        $instance = $DB->get_record('enrol', ['courseid' => $source->id, 'enrol' => 'self'], '*', MUST_EXIST);

        copier::copy_instances($target->id, [$instance->id]);

        $this->assertFalse(is_enrolled(\context_course::instance($target->id), $user));
    }

    public function test_single_instance_plugin_is_skipped(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        // New courses get a manual instance, and manual allows only one per course.
        $source = $this->getDataGenerator()->create_course();
        $target = $this->getDataGenerator()->create_course();
        $manual = $DB->get_record('enrol', ['courseid' => $source->id, 'enrol' => 'manual'], '*', MUST_EXIST);

        $this->assertFalse(copier::can_copy_to($manual, $target->id));
        $result = copier::copy_instances($target->id, [$manual->id]);

        $this->assertSame(0, $result->copied);
        $this->assertCount(1, $result->skipped);
        $this->assertSame(1, $DB->count_records('enrol', ['courseid' => $target->id, 'enrol' => 'manual']));
    }

    public function test_missing_instance_is_reported(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $target = $this->getDataGenerator()->create_course();

        $result = copier::copy_instances($target->id, [-1]);

        $this->assertSame(0, $result->copied);
        $this->assertCount(1, $result->errors);
    }

    public function test_same_course_copy_duplicates_instance(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $instance = $this->add_self_instance($course);

        // Group values only apply to enrol_fieldrules; other plugins are duplicated as-is.
        $result = copier::copy_instances($course->id, [$instance->id], ['ignored']);

        $this->assertSame(1, $result->copied);
        $this->assertSame(2, $DB->count_records('enrol', ['courseid' => $course->id, 'name' => 'Spring intake']));
    }

    public function test_group_instances_by_level(): void {
        $make = fn(int $id, string $enrol, string $level = '') =>
            (object)['id' => $id, 'enrol' => $enrol, 'customchar1' => $level];
        $instances = [
            $make(1, 'self'),
            $make(2, 'fieldrules', 'Level 10'),
            $make(3, 'fieldrules', 'Level 2'),
            $make(4, 'fieldrules', 'Level 2'),
            $make(5, 'cohort'),
        ];

        $grouped = copier::group_instances_by_level($instances);

        // Natural order, other methods last.
        $this->assertSame(['Level 2', 'Level 10', copier::OTHER_METHODS_KEY], array_keys($grouped));
        $this->assertSame([3, 4], array_column($grouped['Level 2'], 'id'));
        $this->assertSame([1, 5], array_column($grouped[copier::OTHER_METHODS_KEY], 'id'));
        $this->assertSame([], copier::group_instances_by_level([]));
    }

    public function test_form_accepts_only_source_course_ids(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $source = $this->getDataGenerator()->create_course();
        $target = $this->getDataGenerator()->create_course();
        $other = $this->getDataGenerator()->create_course();
        $instances = copier::get_course_instances($source->id);
        [$first, $second] = array_keys($instances);
        $foreign = $DB->get_field('enrol', 'id', ['courseid' => $other->id, 'enrol' => 'self']);

        $form = new form\select_methods(null, ['source' => $source, 'target' => $target, 'instances' => $instances]);
        $data = (object)['instance' => [$first => 1, $second => 0, $foreign => 1]];

        $this->assertSame([$first], $form->selected_instance_ids($data));
        $this->assertSame([], $form->selected_instance_ids((object)[]));
    }

    public function test_manager_can_open_the_page(): void {
        $this->resetAfterTest();
        $manager = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->role_assign('manager', $manager->id, \context_system::instance()->id);
        $this->setUser($manager);

        $page = admin_get_root(true, false)->locate('local_enrolcopy_index');

        $this->assertNotNull($page);
        $this->assertTrue($page->check_access());
    }

    public function test_fieldrules_group_clone(): void {
        global $DB;
        if (!copier::fieldrules_installed()) {
            $this->markTestSkipped('enrol_fieldrules is not installed.');
        }
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $plugin = enrol_get_plugin('fieldrules');
        $id = $plugin->add_instance($course, ['customchar1' => 'L1', 'customchar2' => 'A',
            'roleid' => $DB->get_field('role', 'id', ['shortname' => 'student'])]);

        $result = copier::copy_instances($course->id, [$id], ['B', ' C ', 'A', 'B']);

        // B and C are new; A already exists and is skipped; duplicates and blanks are collapsed.
        $this->assertSame(2, $result->copied);
        $this->assertCount(1, $result->skipped);
        $groups = $DB->get_fieldset_select(
            'enrol',
            'customchar2',
            "courseid = ? AND enrol = 'fieldrules' AND customchar1 = 'L1'",
            [$course->id]
        );
        sort($groups);
        $this->assertSame(['A', 'B', 'C'], $groups);
    }
}
