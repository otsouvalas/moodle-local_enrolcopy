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

/**
 * Copies enrolment METHOD configurations between courses.
 *
 * A method is a row in the core {enrol} table: e.g. a self enrolment method, a cohort sync or an
 * "Enrol by user profile fields" rule. This does NOT copy which users are currently enrolled; it copies
 * the rule itself, so the target course's own enrolment plugin computes its own members from then on.
 *
 * Each enrol plugin instance's configuration lives entirely in the generic {enrol} columns
 * (customint1-8, customchar1-3, customtext1-4, customdec1-2, whose meaning is defined by the plugin).
 * Copying those columns verbatim and creating the new instance through the plugin's own add_instance()
 * is what makes this work for any enrol plugin without knowing its internal schema.
 *
 * Caveat: a few plugins store course-local ids in those columns (e.g. a default group id for self
 * enrolment). Those numbers are copied as-is and may point to a wrong group in the target course.
 *
 * @package    local_enrolcopy
 * @copyright  2026 Odysseas Tsouvalas (SpinWorks)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class copier {
    /** Key under which group_instances_by_level() returns non-fieldrules instances (they have no level). */
    const OTHER_METHODS_KEY = '__other__';

    /** Generic {enrol} columns whose meaning is plugin-defined; copied verbatim into the new instance. */
    const GENERIC_FIELDS = [
        'status', 'name', 'roleid', 'enrolperiod', 'enrolstartdate', 'enrolenddate',
        'expirynotify', 'expirythreshold', 'notifyall', 'password', 'cost', 'currency',
        'customint1', 'customint2', 'customint3', 'customint4', 'customint5',
        'customint6', 'customint7', 'customint8',
        'customchar1', 'customchar2', 'customchar3',
        'customdec1', 'customdec2',
        'customtext1', 'customtext2', 'customtext3', 'customtext4',
    ];

    /**
     * All enrolment method instances configured in a course.
     *
     * @param int $courseid
     * @return \stdClass[] keyed by enrol.id
     */
    public static function get_course_instances(int $courseid): array {
        global $DB;
        return $DB->get_records('enrol', ['courseid' => $courseid], 'sortorder ASC');
    }

    /**
     * Human-readable label for an enrol instance, e.g. "Self enrolment (Student) — active".
     *
     * @param \stdClass $instance row of the {enrol} table
     * @return string
     */
    public static function describe_instance(\stdClass $instance): string {
        $plugin = enrol_get_plugin($instance->enrol);
        if (!$plugin) {
            return $instance->enrol . ' (' . get_string('error_plugin_missing', 'local_enrolcopy') . ')';
        }
        $name = $plugin->get_instance_name($instance);
        $status = $instance->status == ENROL_INSTANCE_ENABLED
            ? get_string('active', 'local_enrolcopy')
            : get_string('disabled', 'local_enrolcopy');
        return $name . ' — ' . $status;
    }

    /**
     * Whether the instance's plugin can have another instance added to the target course.
     *
     * Some plugins (e.g. manual, guest) only allow a single instance per course.
     *
     * @param \stdClass $instance row of the {enrol} table
     * @param int $targetcourseid
     * @return bool
     */
    public static function can_copy_to(\stdClass $instance, int $targetcourseid): bool {
        $plugin = enrol_get_plugin($instance->enrol);
        if (!$plugin) {
            return false;
        }
        return (bool)$plugin->can_add_instance($targetcourseid);
    }

    /**
     * Copies the given source enrol instances into the target course.
     *
     * When the target course is the SAME as an instance's own course (a same-course clone) and that
     * instance is an enrol_fieldrules rule, $groupvalues (if non-empty) switches this from "duplicate
     * as-is" to "clone once per group value": the level/role/etc. are kept, only the group value
     * (customchar2) is swapped, one new instance per value. A clone that would duplicate an existing
     * rule (same level + group already present in the course) is skipped.
     *
     * @param int $targetcourseid
     * @param int[] $sourceinstanceids ids in the {enrol} table to copy
     * @param string[] $groupvalues new customchar2 (group) values to clone fieldrules instances into,
     *        only used for same-course clones
     * @return \stdClass copied (int), skipped (string[]), errors (string[])
     */
    public static function copy_instances(int $targetcourseid, array $sourceinstanceids, array $groupvalues = []): \stdClass {
        global $DB;

        $result = new \stdClass();
        $result->copied = 0;
        $result->skipped = [];
        $result->errors = [];

        $targetcourse = get_course($targetcourseid);
        $groupvalues = array_values(array_unique(array_filter(array_map('trim', $groupvalues), fn($v) => $v !== '')));

        foreach ($sourceinstanceids as $instanceid) {
            $instance = $DB->get_record('enrol', ['id' => $instanceid]);
            if (!$instance) {
                $result->errors[] = get_string('error_instance_missing', 'local_enrolcopy', $instanceid);
                continue;
            }

            $plugin = enrol_get_plugin($instance->enrol);
            if (!$plugin) {
                $result->errors[] = get_string('error_plugin_missing_named', 'local_enrolcopy', $instance->enrol);
                continue;
            }

            $fields = [];
            foreach (self::GENERIC_FIELDS as $field) {
                if (property_exists($instance, $field)) {
                    $fields[$field] = $instance->$field;
                }
            }

            $samecourseclone = ((int)$instance->courseid === $targetcourseid);
            if ($samecourseclone && $instance->enrol === 'fieldrules' && $groupvalues) {
                if (!$plugin->can_add_instance($targetcourseid)) {
                    $result->skipped[] = self::describe_instance($instance);
                    continue;
                }
                foreach ($groupvalues as $groupvalue) {
                    $exists = $DB->record_exists('enrol', [
                        'courseid' => $targetcourseid,
                        'enrol' => 'fieldrules',
                        'customchar1' => $instance->customchar1,
                        'customchar2' => $groupvalue,
                    ]);
                    if ($exists) {
                        $result->skipped[] = self::describe_instance($instance) . ' → ' . $groupvalue .
                            ' (' . get_string('cannotcopy_exists', 'local_enrolcopy') . ')';
                        continue;
                    }
                    $clonefields = $fields;
                    $clonefields['customchar2'] = $groupvalue;
                    self::add_instance($plugin, $targetcourse, $instance, $clonefields, $result);
                }
                continue;
            }

            if (!$plugin->can_add_instance($targetcourseid)) {
                $result->skipped[] = self::describe_instance($instance);
                continue;
            }
            self::add_instance($plugin, $targetcourse, $instance, $fields, $result);
        }

        return $result;
    }

    /**
     * Adds one instance through the plugin's own add_instance() and records the outcome in $result.
     *
     * @param \enrol_plugin $plugin
     * @param \stdClass $targetcourse
     * @param \stdClass $source the instance being copied (for messages)
     * @param array $fields
     * @param \stdClass $result see copy_instances()
     */
    private static function add_instance(
        \enrol_plugin $plugin,
        \stdClass $targetcourse,
        \stdClass $source,
        array $fields,
        \stdClass $result
    ): void {
        try {
            if ($plugin->add_instance($targetcourse, $fields)) {
                $result->copied++;
            } else {
                $result->errors[] = get_string('error_add_failed', 'local_enrolcopy', self::describe_instance($source));
            }
        } catch (\Throwable $e) {
            $result->errors[] = self::describe_instance($source) . ': ' . $e->getMessage();
        }
    }

    /**
     * Group values available for fieldrules cloning.
     *
     * Read from the same "Menu of choices" custom profile field that enrol_fieldrules itself uses (its
     * groupfield setting), so the two stay in sync. Returns [] if enrol_fieldrules isn't installed or its
     * group field isn't a menu field; the form then accepts free-text values instead.
     *
     * @return string[]
     */
    public static function fieldrules_group_choices(): array {
        global $DB;

        if (!self::fieldrules_installed()) {
            return [];
        }
        $groupfield = trim((string)get_config('enrol_fieldrules', 'groupfield'));
        if ($groupfield === '') {
            return [];
        }
        $field = $DB->get_record('user_info_field', ['shortname' => $groupfield, 'datatype' => 'menu']);
        if (!$field || trim((string)$field->param1) === '') {
            return [];
        }

        $choices = [];
        foreach (explode("\n", $field->param1) as $line) {
            $line = trim($line);
            if ($line !== '') {
                $choices[] = $line;
            }
        }
        return $choices;
    }

    /**
     * Whether enrol_fieldrules is installed, i.e. whether the group-clone section can be offered.
     *
     * @return bool
     */
    public static function fieldrules_installed(): bool {
        return (bool)enrol_get_plugin('fieldrules');
    }

    /**
     * Splits enrol instances into buckets keyed by "level" (customchar1) for enrol_fieldrules instances.
     *
     * A course with 4 levels x N groups splits into 4 buckets of N instances, so the form can offer one
     * "select all" per level. Every non-fieldrules instance is returned under OTHER_METHODS_KEY, kept last.
     *
     * @param \stdClass[] $instances
     * @return array<string, \stdClass[]> level value => instances, plus OTHER_METHODS_KEY => instances
     *         (only present if non-empty)
     */
    public static function group_instances_by_level(array $instances): array {
        $groups = [];
        foreach ($instances as $instance) {
            $key = $instance->enrol === 'fieldrules' ? trim((string)$instance->customchar1) : self::OTHER_METHODS_KEY;
            $groups[$key][] = $instance;
        }

        $others = $groups[self::OTHER_METHODS_KEY] ?? null;
        unset($groups[self::OTHER_METHODS_KEY]);
        uksort($groups, 'strnatcasecmp');
        if ($others !== null) {
            $groups[self::OTHER_METHODS_KEY] = $others;
        }

        return $groups;
    }
}
