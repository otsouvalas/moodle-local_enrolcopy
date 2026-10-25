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
 * Admin page registration (Site administration > Plugins > Local plugins).
 *
 * @package    local_enrolcopy
 * @copyright  2026 Odysseas Tsouvalas (SpinWorks)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// No $hassiteconfig gate: admin_externalpage checks local/enrolcopy:use, so managers see the page too.
// Before Moodle 5.0 the Local plugins category exists only for site admins; other users find the page under Courses.
$ADMIN->add($ADMIN->locate('localplugins') ? 'localplugins' : 'courses', new admin_externalpage(
    'local_enrolcopy_index',
    get_string('pluginname', 'local_enrolcopy'),
    new moodle_url('/local/enrolcopy/index.php'),
    'local/enrolcopy:use'
));
