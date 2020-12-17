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

namespace local_versioncontrol;

use core\event\base;
use core\event\course_module_updated;
use core\event\question_base;

class eventhandlers {
    /**
     * @param course_module_updated $event
     */
    public static function recordchange(base $event) {
        $repo = repo::get_record([
                'instancetype' => repo::INSTANCETYPE_COURSEMODULECONTEXT,
                'instanceid'   => $event->contextid
        ]);

        if (!$repo) {
            return;
        }

        if ($repo->get('trackingtype') == repo::TRACKINGTYPE_AUTOMATIC) {
            $repo->commitchanges($event->userid, $event->timecreated);
        } else if ($repo->get('trackingtype') == repo::TRACKINGTYPE_MANUAL && !$repo->get('possiblechanges')) {
            $repo->set('possiblechanges', true);
            $repo->save();
        }
    }

    public static function questionchange(question_base $event) {
        global $DB;

        $questionid = $event->objectid;

        $reporecords = $DB->get_records_sql('select repo.* from {context} ctx
         inner join {course_modules} cm on cm.id = ctx.instanceid
         inner join {quiz_slots} qs on qs.quizid = cm.instance
         inner join {question} q on q.id = qs.questionid
         inner join {local_versioncontrol_repo} repo on repo.instanceid = ctx.id and rep.instancetype = :instancetype
         where q.id = :questionid', ['questionid' => $questionid, 'instancetype' => repo::INSTANCETYPE_COURSEMODULECONTEXT]);

        foreach ($reporecords as $reporecord) {
            $repo = new repo($reporecord->id);

            if ($repo->get('trackingtype') == repo::TRACKINGTYPE_AUTOMATIC) {
                $repo->commitchanges($event->userid, $event->timecreated);
            } else if ($repo->get('trackingtype') == repo::TRACKINGTYPE_MANUAL && !$repo->get('possiblechanges')) {
                $repo->set('possiblechanges', true);
                $repo->save();
            }
        }
    }
}
