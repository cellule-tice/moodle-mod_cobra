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

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading('cobra_method_heading', get_string('generalconfig', 'cobra'),
                       get_string('explaingeneralconfig', 'cobra')));

    $options = array();

    $settings->add(new admin_setting_configtext('cobra_serverhost', get_string('cobraserverhost', 'cobra'),
                       get_string('cobraserverhost', 'cobra'), get_host_from_url($CFG->wwwroot)));

    $settings->add(new admin_setting_configtext('cobra_mail_receiver', get_string('mail_receiver', 'cobra'),
                       get_string('mail_receiver', 'cobra'), '1'));
}
