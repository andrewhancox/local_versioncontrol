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
 * A form for voucher upload.
 *
 * @package    core_voucher
 * @copyright  2014 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(__FILE__).'/../../config.php');

$commitid = required_param('commitid', PARAM_INT);
$comparetohead = optional_param('comparetohead', false, PARAM_BOOL);
$download = optional_param('download', false, PARAM_BOOL);

$commit = new \local_versioncontrol\commit($commitid);
$repo = new \local_versioncontrol\repo($commit->get('repoid'));

if ($repo->get('instancetype') != \local_versioncontrol\repo::INSTANCETYPE_COURSEMODULECONTEXT) {
    print_error('Unsupported instance type');
}

$context = context::instance_by_id($repo->get('instanceid'));

require_login();
require_capability('local/versioncontrol:manage', $context);
$url = new moodle_url('/local/versioncontrol/viewchangeset.php', ['commitid' => $commitid]);
list($course, $cm) = get_course_and_cm_from_cmid($context->instanceid);
$title = get_string('viewcommit', 'local_versioncontrol', $cm->get_formatted_name());

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');
$PAGE->set_cm($cm);
$PAGE->set_title($title);
$PAGE->set_heading($title);

if ($download) {
    header('Content-Disposition: attachment; filename="' . $cm->id . '_' . $commit->get('githash') . '.mbz"');
    echo $repo->archive($commit);
    die();
}

$PAGE->requires->js(new moodle_url('/local/versioncontrol/lib/diff2html.js'));
$PAGE->requires->css(new moodle_url('https://cdn.jsdelivr.net/npm/diff2html/bundles/css/diff2html.min.css'));

$PAGE->requires->js_call_amd('local_versioncontrol/diffrenderer', 'init', ['changeset' => $repo->getchangeset($commit, $comparetohead)]);

echo $OUTPUT->header();
echo html_writer::div('', 'myDiffElement', ['id' => 'myDiffElement']);

echo $OUTPUT->footer();
