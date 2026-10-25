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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Step 1: choose the source and the target course.
 *
 * @package    local_enrolcopy
 * @copyright  2026 Odysseas Tsouvalas (SpinWorks)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class select_courses extends \moodleform {
    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('html', \html_writer::tag('p', get_string('index_intro', 'local_enrolcopy')));
        $mform->addElement('html', \html_writer::tag(
            'p',
            get_string('index_intro_sameok', 'local_enrolcopy'),
            ['class' => 'text-muted']
        ));

        foreach (['source' => 'sourcecourse', 'target' => 'targetcourse'] as $name => $label) {
            $mform->addElement('course', $name, get_string($label, 'local_enrolcopy'), ['multiple' => false]);
            $mform->addRule($name, null, 'required', null, 'client');
        }

        $this->add_action_buttons(false, get_string('next'));
    }
}
