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

use context_course;
use core\notification;
use html_writer;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * Class for loading/storing data changesets from the DB.
 *
 * @copyright  2018 David Monllao
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lib {
    public static function js_call_amd_suppress_warning($fullmodule, $func = null, $params = array()) {
        global $CFG, $PAGE;

        $temp = $CFG->debugdeveloper;
        $CFG->debugdeveloper = false;

        $PAGE->requires->js_call_amd($fullmodule, $func, $params);

        $CFG->debugdeveloper = $temp;
    }

    public static function showwarnings() {
        global $PAGE, $SESSION;

        static $debounced;

        if ($debounced == true) {
            return;
        }
        $debounced = true;

        $context = $PAGE->context;

        if (!has_capability('local/versioncontrol:manage', $context)) {
            return;
        }

        if ($PAGE->pagetype == 'local-versioncontrol-makecommit') {
            return;
        }

        // Have we already done notifications (on this page load or a previous one).
        if (isset($SESSION->notifications)) {
            foreach ($SESSION->notifications as $notification) {
                if (strpos($notification->message, 'makecommit.php') !== false) {
                    return;
                }
            }
        }

        if ($context->contextlevel == CONTEXT_MODULE) {
            $cmrepo = repo::get_record([
                'instancetype'    => repo::INSTANCETYPE_COURSEMODULECONTEXT,
                'instanceid'      => $context->id,
                'possiblechanges' => true
            ]);
        }

        $coursecontext = context_course::instance($PAGE->course->id);
        $courserepo = repo::get_record([
            'instancetype'    => repo::INSTANCETYPE_COURSECONTEXT,
            'instanceid'      => $coursecontext->id,
            'possiblechanges' => true
        ]);

        $repos = [];
        if (!empty($cmrepo)) {
            $repos[] = $cmrepo;
        }
        if (!empty($courserepo)) {
            $repos[] = $courserepo;
        }

        foreach ($repos as $repo) {
            if ($repo->get('instancetype') == repo::INSTANCETYPE_COURSECONTEXT) {
                $str = 'changesdetectedcourse';
            } else if ($repo->get('instancetype') == repo::INSTANCETYPE_COURSEMODULECONTEXT) {
                $str = 'changesdetectedactivity';
            }

            notification::warning(
                get_string($str, 'local_versioncontrol')
                .
                ": "
                .
                html_writer::link(
                    new moodle_url('/local/versioncontrol/makecommit.php',
                        ['repo' => $repo->get('id')]),
                    get_string('makecommit', 'local_versioncontrol')
                )
            );
        }
    }
}
