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
 * @author Andrew Hancox <andrewdchancox@googlemail.com>
 * @package local
 * @subpackage certification
 */

namespace local_versioncontrol;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/lib/tablelib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

class commits_table extends \table_sql {
    public function __construct($filterparams, $sortcolumn) {
        parent::__construct('managecommits_table');

        $this->filterparams = $filterparams;

        $this->define_columns(['time', 'message', 'fullname', 'actions']);
        $this->define_headers([
                get_string('time', 'local_versioncontrol'),
                get_string('message', 'local_versioncontrol'),
                'unused',
                ''
        ]);
        $this->collapsible(false);
        $this->sortable(true);
        $this->pageable(true);
        $this->is_downloadable(false);
        $this->sort_default_column = $sortcolumn;
        $this->useridfield = 'usermodified';

        $usernamesql = get_all_user_name_fields(true, 'u');
        $this->set_sql('c.id, c.timecreated, c.usermodified, c.message, ' . $usernamesql,
                '{local_versioncontrol_commit} c
                inner join {user} u on u.id = c.usermodified',
                'repoid = :repoid',
                ['repoid' => $filterparams['repoid']]);
    }

    public function col_time($commit) {
        if (empty($commit->timecreated)) {
            return '';
        }

        return userdate_htmltime($commit->timecreated);
    }

    public function col_actions($commit) {
        global $OUTPUT;

        $out = '';

        $icon = $OUTPUT->pix_icon('t/viewdetails', get_string('viewdetails', 'local_versioncontrol'));
        $url = new \moodle_url('/local/versioncontrol/viewchangeset.php', ['commitid' => $commit->id]);
        $out .= $OUTPUT->action_link($url, $icon);

        $icon = $OUTPUT->pix_icon('t/copy', get_string('comparetohead', 'local_versioncontrol'));
        $url = new \moodle_url('/local/versioncontrol/viewchangeset.php', ['commitid' => $commit->id, 'comparetohead' => true]);
        $out .= $OUTPUT->action_link($url, $icon);

        $icon = $OUTPUT->pix_icon('t/download', get_string('download', 'local_versioncontrol'));
        $url = new \moodle_url('/local/versioncontrol/viewchangeset.php', ['commitid' => $commit->id, 'download' => true]);
        $out .= $OUTPUT->action_link($url, $icon);

        return $out;
    }
}
