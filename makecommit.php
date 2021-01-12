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
use local_versioncontrol\commitform;

require_once(dirname(__FILE__) . '/../../config.php');
$repoid = required_param('repo', PARAM_INT);

$repo = new \local_versioncontrol\repo($repoid);

$instancetype = $repo->get('instancetype');

$context = context::instance_by_id($repo->get('instanceid'));

require_login();
require_capability('local/versioncontrol:manage', $context);

$url = new moodle_url('/local/versioncontrol/makecommit.php', ['repo' => $repoid]);
list($course, $cm) = get_course_and_cm_from_cmid($context->instanceid);

$PAGE->set_context($context);
$PAGE->set_url($url);

if ($context->contextlevel == CONTEXT_MODULE) {
    list($course, $cm) = get_course_and_cm_from_cmid($context->instanceid);
    $redirect = new moodle_url($cm->url);
    $title = get_string('commitforactivity', 'local_versioncontrol', $cm->get_formatted_name());
    $PAGE->set_cm($cm);
} else if ($context->contextlevel == CONTEXT_COURSE) {
    $course = get_course($context->instanceid);
    $redirect = new moodle_url("/course/view.php", ['id' => $course->id]);
    $title = get_string('commitforcourse', 'local_versioncontrol', format_string($course->fullname));
    $PAGE->set_course($course);
}

$PAGE->set_title($title);
$PAGE->set_heading($title);

$form = new commitform($url->out(false));

if ($form->is_cancelled()) {
    redirect($redirect);
} else if ($data = $form->get_data()) {
    $changeset = $repo->commitchanges($USER->id, time(), $data->message);

    if ($changeset === false) {
        redirect($redirect, get_string('nochanges', 'local_versioncontrol'), 0, notification::NOTIFY_WARNING);
    } else {
        redirect($redirect, get_string('commitsuccess', 'local_versioncontrol'), 0, notification::NOTIFY_SUCCESS);
    }
}

echo $OUTPUT->header();

echo $OUTPUT->box_start();

$form->display();

echo $OUTPUT->box_end();

echo $OUTPUT->footer();
