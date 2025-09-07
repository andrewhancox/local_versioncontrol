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

namespace local_versioncontrol;

use core\form\persistent;

class repoform extends persistent {

    /** @var string Persistent class name. */
    protected static $persistentclass = 'local_versioncontrol\\repo';

    function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'instancetype');
        $mform->setType('instancetype', PARAM_INT);

        $mform->addElement('hidden', 'instanceid');
        $mform->setType('instanceid', PARAM_INT);

        $mform->addElement('select', 'trackingtype', get_string('trackingtype', 'local_versioncontrol'), repo::gettrackingtypes(),
                ['required' => 'required']);

        $this->add_action_buttons();
    }

    public function handlepostback() {
        global $USER;

        $datacleaned = $this->get_data();

        if (!$datacleaned) {
            return false;
        }

        $persistent = $this->get_persistent();
        if (empty($persistent->get('id'))) {
            $persistent = new repo();
            $persistent->from_record($datacleaned);
            $persistent->create();
        } else {
            $persistent->from_record($datacleaned);
            $persistent->update();
        }

        if ($persistent->get('trackingtype') == repo::TRACKINGTYPE_AUTOMATIC) {
            $persistent->queuecommitchangestask($USER->id, time());
        } else if ($persistent->get('trackingtype') == repo::TRACKINGTYPE_MANUAL) {
            $persistent->update_possiblechanges(true, $USER->id);
        }

        return true;
    }
}
