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

use core\output\notification;

require_once(dirname(__FILE__) . '/../../config.php');
$repoid = required_param('repo', PARAM_INT);

$repo = new \local_versioncontrol\repo($repoid);

$instancetype = $repo->get('instancetype');

if ($instancetype !== \local_versioncontrol\repo::INSTANCETYPE_COURSEMODULECONTEXT) {
    print_error('Unsupported repo type');
}

$context = context::instance_by_id($repo->get('instanceid'));

require_login();
require_capability('local/versioncontrol:manage', $context);

$url = new moodle_url('/local/fielddefaults/makecommit.php', ['repo' => $repoid]);
list($course, $cm) = get_course_and_cm_from_cmid($context->instanceid);

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_cm($cm);

$changeset = $repo->commitchanges($USER->id, time());

if ($changeset === false) {
    redirect(new moodle_url($cm->url), get_string('nochanges', 'local_versioncontrol'), 0, notification::NOTIFY_WARNING);
} else {
    redirect(new moodle_url($cm->url), get_string('commitsuccess', 'local_versioncontrol'), 0, notification::NOTIFY_SUCCESS);
}
