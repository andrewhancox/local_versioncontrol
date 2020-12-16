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

use Cz\Git\GitRepository;

require_once(dirname(__FILE__).'/../../config.php');

require_once($CFG->dirroot . '/local/versioncontrol/lib/IGit.php');
require_once($CFG->dirroot . '/local/versioncontrol/lib/GitRepository.php');



$repo = new GitRepository("/Users/andrewhancox/MoodleData/oslmoodle/www/local_versioncontrol/171");
$changeset =  $repo->getDiff('HEAD' ,'HEAD^', "':(exclude)moodle_backup.*' ':(exclude).ARCHIVE_INDEX'");
$changeset = implode("\n", $changeset);


$PAGE->requires->js(new moodle_url('/local/versioncontrol/lib/diff2html.js'));
$PAGE->requires->css(new moodle_url('https://cdn.jsdelivr.net/npm/diff2html/bundles/css/diff2html.min.css'));

$PAGE->requires->js_call_amd('local_versioncontrol/diffrenderer', 'init', ['changeset' => $changeset]);
$PAGE->set_url('/local/versioncontrol/viewchangeset.php');

echo $OUTPUT->header();
echo html_writer::div('', 'myDiffElement', ['id' => 'myDiffElement']);

echo $OUTPUT->footer();
