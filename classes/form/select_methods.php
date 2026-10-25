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

namespace local_enrolcopy\form;

use local_enrolcopy\copier;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Step 2: choose which enrolment methods of the source course to copy.
 *
 * Custom data: source (course record), target (course record), instances (source {enrol} rows).
 * Methods are listed per enrol_fieldrules "level", each list with its own select all/none control.
 *
 * @package    local_enrolcopy
 * @copyright  2026 Odysseas Tsouvalas (SpinWorks)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class select_methods extends \moodleform {
    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;
        $source = $this->_customdata['source'];
        $target = $this->_customdata['target'];
        $samecourse = ($source->id == $target->id);

        $mform->addElement('hidden', 'sourcecourseid', $source->id);
        $mform->setType('sourcecourseid', PARAM_INT);
        $mform->addElement('hidden', 'targetcourseid', $target->id);
        $mform->setType('targetcourseid', PARAM_INT);

        $mform->addElement('html', \html_writer::tag('p', get_string(
            $samecourse ? 'step2_intro_samecourse' : 'step2_intro',
            'local_enrolcopy',
            ['source' => format_string($source->fullname), 'target' => format_string($target->fullname)]
        )));

        $grouped = copier::group_instances_by_level($this->_customdata['instances']);
        $haslevels = array_diff_key($grouped, [copier::OTHER_METHODS_KEY => true]) !== [];

        $groupid = 0;
        foreach ($grouped as $level => $instances) {
            $groupid++;
            if ($level !== copier::OTHER_METHODS_KEY) {
                $heading = get_string(
                    'level_heading',
                    'local_enrolcopy',
                    $level !== '' ? s($level) : get_string('nolevel', 'local_enrolcopy')
                );
            } else {
                $heading = $haslevels ? get_string('othermethods_heading', 'local_enrolcopy') : '';
            }
            if ($heading !== '') {
                $mform->addElement('html', \html_writer::tag('h4', $heading, ['class' => 'h5 mt-3']));
            }

            $copyable = 0;
            foreach ($instances as $instance) {
                $label = s(copier::describe_instance($instance));
                if (copier::can_copy_to($instance, $target->id)) {
                    $mform->addElement('advcheckbox', "instance[{$instance->id}]", '', $label, ['group' => $groupid]);
                    $mform->setDefault("instance[{$instance->id}]", 1);
                    $copyable++;
                } else {
                    $mform->addElement(
                        'static',
                        "cannot{$instance->id}",
                        '',
                        $label . ' ' . \html_writer::span(get_string('cannotcopy', 'local_enrolcopy'), 'text-muted')
                    );
                }
            }
            if ($copyable > 1) {
                // Everything copyable starts ticked (original value 1).
                $this->add_checkbox_controller($groupid, null, null, 1);
            }
        }
        $mform->addElement('html', \html_writer::tag(
            'p',
            get_string('step2_hint', 'local_enrolcopy'),
            ['class' => 'text-muted mt-3']
        ));

        if ($samecourse && copier::fieldrules_installed()) {
            $mform->addElement('header', 'groupclone', get_string('groupclone_heading', 'local_enrolcopy'));
            $mform->setExpanded('groupclone');
            $mform->addElement('html', \html_writer::tag(
                'p',
                get_string('groupclone_hint', 'local_enrolcopy'),
                ['class' => 'text-muted']
            ));
            $choices = copier::fieldrules_group_choices();
            $mform->addElement(
                'autocomplete',
                'groupvalues',
                get_string('groupclone_values', 'local_enrolcopy'),
                array_combine($choices, $choices),
                ['multiple' => true, 'tags' => !$choices]
            );
            $mform->setType('groupvalues', PARAM_TEXT);
        }

        $this->add_action_buttons(true, get_string('confirmcopy', 'local_enrolcopy'));
    }

    /**
     * Ids of the ticked instances, restricted to the source course's own instances.
     *
     * @param \stdClass $data from get_data()
     * @return int[]
     */
    public function selected_instance_ids(\stdClass $data): array {
        $ticked = array_keys(array_filter((array)($data->instance ?? [])));
        return array_values(array_intersect(array_map('intval', $ticked), array_keys($this->_customdata['instances'])));
    }
}
