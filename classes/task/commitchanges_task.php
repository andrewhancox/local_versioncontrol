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

namespace local_versioncontrol\task;

use core\task\adhoc_task;
use local_courseresetter\courseresetter;
use local_versioncontrol\repo;

defined('MOODLE_INTERNAL') || die();

class commitchanges_task extends adhoc_task {

    /**
     * Run the task.
     */
    public function execute() {
        $data = $this->get_custom_data();
        $repo = new repo($data->repoid);

        if (empty($repo->get('id'))) {
            return;
        }

        $repo->commitchanges($data->userid, $data->committime, $data->commitmessage);
    }
}
