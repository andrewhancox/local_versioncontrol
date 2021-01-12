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
 * A form for fielddefault upload.
 *
 * @package    core_fielddefault
 * @copyright  2014 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_versioncontrol\repo;
use local_versioncontrol\repoform;

require_once(dirname(__FILE__) . '/../../config.php');
$instanceid = required_param('instanceid', PARAM_INT);
$instancetype = required_param('instancetype', PARAM_INT);

$context = context::instance_by_id($instanceid);

require_login();
require_capability('local/versioncontrol:manage', $context);

$url = new moodle_url('/local/versioncontrol/managerepo.php', ['instanceid' => $instanceid, 'instancetype' => $instancetype]);

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');

if ($context->contextlevel == CONTEXT_MODULE) {
    list($course, $cm) = get_course_and_cm_from_cmid($context->instanceid);
    $title = get_string('managerepoforactivity', 'local_versioncontrol', $cm->get_formatted_name());
    $PAGE->set_cm($cm);
    $redirect = new moodle_url($cm->url);
} else if ($context->contextlevel == CONTEXT_COURSE) {
    $course = get_course($context->instanceid);
    $title = get_string('managerepoforcourse', 'local_versioncontrol', format_string($course->fullname));
    $PAGE->set_course($course);
    $redirect = new moodle_url("/course/view.php", ['id' => $course->id]);
}

$PAGE->set_title($title);
$PAGE->set_heading($title);

$repo = repo::get_record(['instanceid' => $instanceid, 'instancetype' => $instancetype]);
if (!$repo) {
    $repo = new \local_versioncontrol\repo();
    $repo->set('instanceid', $instanceid);
    $repo->set('instancetype', $instancetype);
} else {
    $url->param('id', $repo->get('id'));
}

$customdata = [
        'persistent' => $repo
];
$form = new repoform($url->out(false), $customdata);

if ($form->handlepostback() || $form->is_cancelled()) {
    redirect($redirect);
}

if ($repo->get('id')) {
    $table = new \local_versioncontrol\commits_table(['repoid' => $repo->get('id')],
            optional_param('tsort', 'id', PARAM_ALPHA));
    $table->define_baseurl($url);
}

echo $OUTPUT->header();

$form->display();

if (isset($table)) {
    $table->out(5000, true);
}

echo $OUTPUT->footer();
