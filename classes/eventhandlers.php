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
use core\event\base;
use core\event\course_created;
use core\event\course_module_updated;
use core\event\question_base;

class eventhandlers {
    static $repodebouncer = [];

    public static function verifyevents(base $event) {
        // Get list of enabled events from cache
        $enabledevents = self::get_enabled_events();

        // Check if current event is not enabled
        if (!isset($enabledevents[$event->eventname])) {
            // Event is not enabled, do nothing more!
            return;
        }

        // Check for specific events otherwise go to the default 'get_enabled_events'
        switch ($event->eventname) {
            case '\core\event\course_created':
                self::questionchange($event);
                break;
            case '\core\event\course_updated':
            case '\core\event\course_created':
                self::setcoursedefaults($event);
                break;
            default:
                self::get_enabled_events();
                break;
        }

        return;
    }

    /**
     * @param course_module_updated $event
     */
    public static function recordchange(base $event) {
        global $CFG;

        require_once("$CFG->dirroot/local/versioncontrol/lib.php");

        $cmrepo = repo::get_record([
                'instancetype' => repo::INSTANCETYPE_COURSEMODULECONTEXT,
                'instanceid'   => $event->contextid,
        ]);

        $coursecontext = context_course::instance($event->courseid);
        $courserepo = repo::get_record([
                'instancetype' => repo::INSTANCETYPE_COURSECONTEXT,
                'instanceid'   => $coursecontext->id,
        ]);

        $repos = [];
        if ($cmrepo) {
            $repos[] = $cmrepo;
        }
        if ($courserepo) {
            $repos[] = $courserepo;
        }

        foreach ($repos as $repo) {
            if (isset(self::$repodebouncer[$repo->get('id')])) {
                continue;
            }

            if ($repo->get('trackingtype') == repo::TRACKINGTYPE_AUTOMATIC) {
                $repo->queuecommitchangestask($event->userid, $event->timecreated);
            } else if ($repo->get('trackingtype') == repo::TRACKINGTYPE_MANUAL && !$repo->get('possiblechanges')) {
                $repo->set('possiblechanges', true);
                $repo->save();
            }

            self::$repodebouncer[$repo->get('id')] = $repo->get('id');
        }

        lib::showwarnings();
    }

    /**
     * @param course_created $event
     */
    public static function setcoursedefaults(base $event) {
        $repo = repo::get_record(['instanceid' => $event->contextid, 'instancetype' => repo::INSTANCETYPE_COURSECONTEXT]);

        if (!empty($repo)) {
            return;
        }

        $defaultfornewcourses = get_config('local_versioncontrol', 'autoenablefornewcourses');

        if (empty($defaultfornewcourses) || $defaultfornewcourses == repo::TRACKINGTYPE_NONE) {
            return;
        }

        $repo = new repo();
        $repo->from_record((object)
        [
                'instanceid'      => $event->contextid,
                'instancetype'    => repo::INSTANCETYPE_COURSECONTEXT,
                'trackingtype'    => $defaultfornewcourses,
                'possiblechanges' => true,
        ]
        );

        $repo->create();

        if ($defaultfornewcourses == repo::TRACKINGTYPE_AUTOMATIC) {
            $repo->queuecommitchangestask($event->userid, time());
        }
    }

    public static function questionchange(question_base $event) {
        global $DB;

        $questionid = $event->objectid;

        $reporecords = $DB->get_records_sql('select repo.* from {context} ctx
         inner join {course_modules} cm on cm.id = ctx.instanceid
         inner join {quiz_slots} qs on qs.quizid = cm.instance
         inner join {question_references} qr on qr.itemid = qs.id
         inner JOIN {question_bank_entries} qbe ON qbe.id = qr.questionbankentryid
         inner JOIN {question_versions} qv ON qv.questionbankentryid = qbe.id
         inner join {question} q on q.id = qv.questionid
         inner join {local_versioncontrol_repo} repo on repo.instanceid = ctx.id and repo.instancetype = :instancetype
         where q.id = :questionid', ['questionid' => $questionid, 'instancetype' => repo::INSTANCETYPE_COURSEMODULECONTEXT]);

        foreach ($reporecords as $reporecord) {
            $repo = new repo($reporecord->id);

            if ($repo->get('trackingtype') == repo::TRACKINGTYPE_AUTOMATIC) {
                $repo->queuecommitchangestask($event->userid, $event->timecreated);
            } else if ($repo->get('trackingtype') == repo::TRACKINGTYPE_MANUAL && !$repo->get('possiblechanges')) {
                $repo->set('possiblechanges', true);
                $repo->save();
            }
        }
    }

    /**
     * Store all enabled event records in the 'enabledevents' cache.
     */
    private static function get_enabled_events() {
        global $DB;

        $cache = \cache::make('local_versioncontrol', 'enabledevents');
        $enabledevents = $cache->get('enabledevents');

        // Set cache list when not present
        if ($enabledevents === false) {
            $records = $DB->get_records('local_versioncontrol_enabledevent');
            $cache = \cache::make('local_versioncontrol', 'enabledevents');
            $cache->set('enabledevents', $records);

            $enabledevents = $cache->get('enabledevents');
        }

        return $enabledevents;
    }
}
