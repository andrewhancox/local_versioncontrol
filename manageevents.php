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

$action = optional_param('action', '', PARAM_ALPHA);

$url = new moodle_url('/local/versioncontrol/manageevents.php');

$PAGE->set_context($systemcontext);
$PAGE->set_url($url);

// Handle actions
$eventschecked = optional_param_array('eventschecked', array(), PARAM_RAW);

if ($action === 'enable') {
    if ($eventschecked) {
        require_sesskey();
        // Enable selected events
        foreach ($eventschecked as $eventname) {
            $record = new stdClass();
            $record->eventname = $eventname;
            $record->timecreated = time();
            try {
                $DB->insert_record('local_versioncontrol_enabledevent', $record);
            } catch (dml_exception $e) {
                debugging('Exception: ' . $e->getMessage() . ', event: ' . $eventname . ', record: ' . print_r($record, true));
                // Record might already exist, ignore duplicate errors
            }
        }
        \core\notification::success(get_string('eventsenabled', 'local_versioncontrol'));
    }
    redirect($PAGE->url);
} else if ($action === 'disable') {
    if ($eventschecked) {
        require_sesskey();
        // Disable selected events
        list($insql, $params) = $DB->get_in_or_equal($eventschecked, SQL_PARAMS_NAMED);
        $DB->delete_records_select('local_versioncontrol_enabledevent', "eventname $insql", $params);
        \core\notification::success(get_string('eventsdisabled', 'local_versioncontrol'));
    }
    redirect($PAGE->url);
}

echo $OUTPUT->header();
echo $OUTPUT->heading('Events', 3);

$completelist = report_eventlist_list_generator::get_all_events_list();

// Get enabled events from database
$enabledevents = $DB->get_fieldset_select('local_versioncontrol_enabledevent', 'eventname', '');

echo $OUTPUT->box_start('eventlist');
echo $OUTPUT->heading('Table with Events', 4);

// Render the table and action buttons inside a single form
if (!empty($completelist)) {
    echo '<form class="event-management-form" method="post" action="' . $PAGE->url->out_omit_querystring() . '">';
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    // Action buttons at the top
    echo html_writer::tag('button', get_string('enableselected', 'local_versioncontrol'),
        array('id' => 'event-management-enable-top', 'type' => 'submit',
              'class' => 'eventenableselected btn btn-primary', 'name' => 'action', 'value' => 'enable'));
    echo ' ';
    echo html_writer::tag('button', get_string('disableselected', 'local_versioncontrol'),
        array('id' => 'event-management-disable-top', 'type' => 'submit',
              'class' => 'eventdisableselected btn btn-secondary', 'name' => 'action', 'value' => 'disable'));

    $baseurl = new moodle_url('/local/versioncontrol/manageevents.php');
    $tablecourse = new local_versioncontrol_events_table('feedback_template_course_table', $baseurl, $enabledevents);
    $tablecourse->display($completelist);

    // Action buttons at the bottom
    echo html_writer::tag('button', get_string('enableselected', 'local_versioncontrol'),
        array('id' => 'event-management-enable-bottom', 'type' => 'submit',
              'class' => 'eventenableselected btn btn-primary', 'name' => 'action', 'value' => 'enable'));
    echo ' ';
    echo html_writer::tag('button', get_string('disableselected', 'local_versioncontrol'),
        array('id' => 'event-management-disable-bottom', 'type' => 'submit',
              'class' => 'eventdisableselected btn btn-secondary', 'name' => 'action', 'value' => 'disable'));
    echo '</form>';
}

echo $OUTPUT->box_end();

// Initialize JavaScript for checkbox toggle functionality (like tag management)
$PAGE->requires->js_call_amd('core/tag', 'initManagePage', array());

echo $OUTPUT->footer();
