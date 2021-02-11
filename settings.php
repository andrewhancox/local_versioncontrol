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
 * @package local_versioncontrol
 * @author Andrew Hancox <andrewdchancox@googlemail.com>
 * @author Open Source Learning <enquiries@opensourcelearning.co.uk>
 * @link https://opensourcelearning.co.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2021, Andrew Hancox
 */

use local_versioncontrol\repo;

if (!empty($hassiteconfig) && !empty($ADMIN)) {

    $ADMIN->add('localplugins', new admin_category('versioncontrol', new lang_string('pluginname', 'local_versioncontrol')));

    $versioncontrolettings = new admin_settingpage('versioncontrolettings',
            new lang_string('versioncontrolettings', 'local_versioncontrol'));

    $versioncontrolettings->add(new admin_setting_configselect('local_versioncontrol/autoenablefornewcourses',
            new lang_string('autoenablefornewcourses', 'local_versioncontrol'),
            '',
            repo::TRACKINGTYPE_NONE, repo::gettrackingtypes()));

    $versioncontrolettings->add(new admin_setting_heading('autoenableforactivitytype',
            new lang_string('autoenableforactivitytype', 'local_versioncontrol'), ''));

    foreach (get_module_types_names() as $key => $name) {
        $versioncontrolettings->add(new admin_setting_configselect('local_versioncontrol/autoenableforactivitytype_' . $key,
                $name,
                '',
                repo::TRACKINGTYPE_NONE, repo::gettrackingtypes()));
    }

    $ADMIN->add('versioncontrol', $versioncontrolettings);
}
