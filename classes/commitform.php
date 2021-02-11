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

require_once($CFG->libdir . '/formslib.php');

class commitform extends \moodleform {

    /** @var string Persistent class name. */
    protected static $commitclass = 'local_versioncontrol\\commit';

    function definition() {
        $mform = $this->_form;

        $mform->addElement('textarea', 'message', get_string('message', 'local_versioncontrol'), array('rows' => 4, 'cols' => 60));
        $mform->addRule('message', get_string('required'), 'required');
        $mform->setType('message', PARAM_TEXT);

        $this->add_action_buttons();
    }
}
