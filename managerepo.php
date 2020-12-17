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

require_once(dirname(__FILE__) . '/../../config.php');
$instanceid = required_param('instanceid', PARAM_INT);
$instancetype = required_param('instancetype', PARAM_INT);

if ($instancetype !== \local_versioncontrol\repo::INSTANCETYPE_COURSEMODULECONTEXT) {
    print_error('Unsupported instance type');
}

$context = context::instance_by_id($instanceid);

require_login();
require_capability('local/versioncontrol:manage', $context);

$url = new moodle_url('/local/fielddefaults/managerepo.php', ['instanceid' => $instanceid, 'instancetype' => $instancetype]);
list($course, $cm) = get_course_and_cm_from_cmid($context->instanceid);
$title = get_string('managerepoforactivity', 'local_fielddefaults', $cm->get_formatted_name());

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');
$PAGE->set_cm($cm);
$PAGE->set_title($title);
$PAGE->set_heading($title);


$persistent = \local_versioncontrol\repo::get_record(['instanceid' => $instanceid, 'instancetype' => $instancetype]);
if (!$persistent) {
    $persistent = new \local_versioncontrol\repo();
    $persistent->set('instanceid', $instanceid);
    $persistent->set('instancetype', $instancetype);
} else {
    $url->param('id', $persistent->get('id'));
}

if (isset($productid)) {
    $persistent->set('productid', $productid);
}

$customdata = [
        'persistent' => $persistent
];
$form = new \local_versioncontrol\repoform($url->out(false), $customdata);

if ($form->handlepostback() || $form->is_cancelled()) {
    redirect(new moodle_url($cm->url));
}

echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();
