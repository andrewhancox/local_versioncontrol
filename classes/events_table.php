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
 * Class local_versioncontrol_events_table
 *
 * @package local_versioncontrol
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use core\output\checkbox_toggleall;

global $CFG;
require_once($CFG->libdir . '/tablelib.php');

class local_versioncontrol_events_table extends flexible_table {

    /** @var array List of enabled event names */
    protected $enabledevents = [];

    /**
     * Constructor
     * @param int $uniqueid all tables have to have a unique id, this is used
     *      as a key when storing table properties like sort order in the session.
     * @param moodle_url $baseurl
     * @param array $enabledevents Array of enabled event names
     */
    public function __construct($uniqueid, $baseurl, $enabledevents = []) {
        global $OUTPUT;

        parent::__construct($uniqueid);

        // Store the enabled events
        $this->enabledevents = $enabledevents;

        // Create the "select all" checkbox for the header
        $checkboxall = new checkbox_toggleall('events-select', true, [
            'id' => 'select-all-events',
            'name' => 'select-all-events',
            'checked' => false,
            'label' => get_string('selectall'),
            'labelclasses' => 'accesshide',
        ]);

        $tablecolumns = array('select', 'enabled', 'eventname', 'component', 'crud', 'edulevel');
        $tableheaders = array(
            $OUTPUT->render($checkboxall),
            get_string('enabled', 'local_versioncontrol'),
            get_string('eventname', 'report_eventlist'),
            get_string('component', 'report_eventlist'),
            get_string('crud', 'report_eventlist'),
            get_string('edulevel', 'report_eventlist'),
        );

        $this->set_attribute('class', 'eventslist');
        $this->define_columns($tablecolumns);
        $this->define_headers($tableheaders);
        $this->define_baseurl($baseurl);
        $this->column_class('select', 'select mdl-align');
        $this->column_class('enabled', 'enabled mdl-align');
        $this->column_class('eventname', 'eventname');
        $this->column_class('component', 'component');
        $this->column_class('crud', 'crud');
        $this->column_class('edulevel', 'edulevel');
        $this->sortable(true);
    }

    /**
     * Displays the table with the given set of events
     * @param array $events
     */
    public function display($events) {
        global $OUTPUT;
        if (empty($events)) {
            echo $OUTPUT->box(get_string('noeventsavailable', 'report_eventlist'), 'generalbox boxaligncenter');
            return;
        }

        $this->setup();

        foreach ($events as $eventid => $event) {
            // Skip event when it is only for viewing something.
            if ((isset($event['crud']) && $event['crud'] === get_string('read', 'report_eventlist'))) {
                continue;
            }
            if (isset($event['edulevel']) && $event['edulevel'] === get_string('participating', 'report_eventlist')) {
                continue;
            }

            $data = [];

            // Add checkbox for row selection
            $checkbox = new checkbox_toggleall('events-select', false, [
                'id' => 'eventselect' . $eventid,
                'name' => 'eventschecked[]',
                'value' => $eventid,
                'checked' => false,
                'label' => get_string('selectevent', 'local_versioncontrol', $event['eventname'] ?? ''),
                'labelclasses' => 'accesshide',
            ]);
            $data[] = $OUTPUT->render($checkbox);

            // Add enabled status column
            $data[] = $this->get_event_enabled_status($event);

            // Event name as a link if possible, fallback to eventname, else blank.
            if (isset($event['fulleventname']) && !empty($event['fulleventname'])) {
                $data[] = $event['fulleventname'];
            } else if (isset($event['eventname'])) {
                $data[] = format_string($event['eventname']);
            } else {
                $data[] = '';
            }
            $data[] = isset($event['component']) ? $event['component'] : '';
            $data[] = isset($event['crud']) ? $event['crud'] : '';
            $data[] = isset($event['edulevel']) ? $event['edulevel'] : '';

            $this->add_data($data);
        }
        $this->finish_output();
    }

    /**
     * Get the enabled status of an event
     *
     * @param array $event Event data
     * @return string HTML representation of enabled status
     */
    protected function get_event_enabled_status($event) {
        global $OUTPUT;

        // Check if event is in the enabled events array
        $eventname = isset($event['eventname']) ? $event['eventname'] : '';
        $isenabled = in_array($eventname, $this->enabledevents);

        if ($isenabled) {
            return $OUTPUT->pix_icon('i/valid', get_string('enabled', 'local_versioncontrol'), 'moodle',
                ['class' => 'icon text-success']);
        } else {
            return $OUTPUT->pix_icon('i/invalid', get_string('disabled', 'local_versioncontrol'), 'moodle',
                ['class' => 'icon text-danger']);
        }
    }
}
