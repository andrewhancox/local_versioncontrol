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
 * Manage events page
 *
 * @package local_versioncontrol
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');

$systemcontext = context_system::instance();

require_login();
require_capability('local/versioncontrol:manage', $systemcontext);

$url = new moodle_url('/local/versioncontrol/manageevents.php');

$PAGE->set_context($systemcontext);
$PAGE->set_url($url);

echo $OUTPUT->header();
echo $OUTPUT->heading('Events', 3);

$completelist = report_eventlist_list_generator::get_all_events_list();

echo $OUTPUT->box_start('eventlist');
echo $OUTPUT->heading('Table with Events', 4);

$baseurl = new moodle_url('/local/versioncontrol/manageevents.php');
$tablecourse = new local_versioncontrol_events_table('feedback_template_course_table', $baseurl);
$tablecourse->display($completelist);
echo $OUTPUT->box_end();

echo $OUTPUT->footer();

// // Retrieve all events in a list.
// $completelist = report_eventlist_list_generator::get_all_events_list();

// $tabledata = array();
// $components = array();
// $edulevel = array('0' => get_string('all', 'report_eventlist'));
// $crud = array('0' => get_string('all', 'report_eventlist'));
// foreach ($completelist as $value) {
//     $components[] = explode('\\', $value['eventname'])[1];
//     $edulevel[] = $value['edulevel'];
//     $crud[] = $value['crud'];
//     $tabledata[] = (object)$value;
// }
// $components = array_unique($components);
// $edulevel = array_unique($edulevel);
// $crud = array_unique($crud);

// // Create the filter form for the table.
// $filtersection = new report_eventlist_filter_form(null, array('components' => $components, 'edulevel' => $edulevel,
//         'crud' => $crud));

// // Output.
// $renderer = $PAGE->get_renderer('report_eventlist');
// echo $renderer->render_event_list($filtersection, $tabledata);
