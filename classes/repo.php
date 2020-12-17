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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR changeset.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Class for loading/storing data changesets from the DB.
 *
 * @package    local_versioncontrol
 * @copyright  2018 David Monllao
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_versioncontrol;

use backup;
use backup_controller;
use Cz\Git\GitRepository;
use PharData;

defined('MOODLE_INTERNAL') || die();

/**
 * Class for loading/storing data changesets from the DB.
 *
 * @copyright  2018 David Monllao
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class repo extends \core\persistent {

    /**
     * Database table.
     */
    const TABLE = 'local_versioncontrol_repo';

    const INSTANCETYPE_COURSEMODULECONTEXT = 10;

    const TRACKINGTYPE_NONE = 10;
    const TRACKINGTYPE_MANUAL = 20;
    const TRACKINGTYPE_AUTOMATIC = 30;

    public static function gettrackingtypes() {
        return [
                self::TRACKINGTYPE_NONE      => get_string("trackingtype_" . self::TRACKINGTYPE_NONE, 'local_versioncontrol'),
                self::TRACKINGTYPE_MANUAL    => get_string("trackingtype_" . self::TRACKINGTYPE_MANUAL, 'local_versioncontrol'),
                self::TRACKINGTYPE_AUTOMATIC => get_string("trackingtype_" . self::TRACKINGTYPE_AUTOMATIC, 'local_versioncontrol'),
        ];
    }

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return array(
                'instancetype' => array(
                        'type'    => PARAM_INT,
                        'choices' => [self::INSTANCETYPE_COURSEMODULECONTEXT],
                ),
                'instanceid'   => array(
                        'type' => PARAM_INT,
                ),
                'possiblechanges'   => array(
                        'type' => PARAM_INT,
                        'default' => 0
                ),
                'trackingtype' => array(
                        'type'    => PARAM_INT,
                        'choices' => [self::TRACKINGTYPE_NONE, self::TRACKINGTYPE_MANUAL, self::TRACKINGTYPE_AUTOMATIC],
                        'default' => self::TRACKINGTYPE_NONE
                ),
        );
    }

    public function commitchanges($userid, $timecreated) {
        global $CFG;

        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/local/versioncontrol/lib/IGit.php');
        require_once($CFG->dirroot . '/local/versioncontrol/lib/GitRepository.php');

        $contextid = $this->get('instanceid');
        $instancetype = $this->get('instancetype');

        if ($instancetype !== self::INSTANCETYPE_COURSEMODULECONTEXT) {
            print_error('Unsupported repo type');
        }

        $tempfilename = 'backup' . '_' . $timecreated . '_' . $contextid;

        $bc = new backup_controller(backup::TYPE_1ACTIVITY, $contextid, backup::FORMAT_MOODLE,
                backup::INTERACTIVE_NO, backup::MODE_GENERAL, $userid);
        $bc->get_plan()->get_setting('anonymize')->set_value(true);
        $bc->get_plan()->get_setting('filename')->set_value($tempfilename);
        $bc->execute_plan();
        $results = $bc->get_results();
        $file = $results['backup_destination'];

        $reporoot = "$CFG->dataroot/local_versioncontrol/$instancetype/$contextid/";
        @mkdir($reporoot, 0777, true);

        $tempfolder = make_temp_directory('local_versioncontrol' . '_' . $timecreated . '_' . $contextid);
        $file->copy_content_to($tempfolder . '/' . $tempfilename . '.tar.gz');
        $file->delete();

        $files = glob($reporoot . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        if (!file_exists($reporoot . ".git")) {
            $repo = GitRepository::init($reporoot);
        } else {
            $repo = new GitRepository($reporoot);
        }

        $phar = new PharData($tempfolder . '/' . $tempfilename . '.tar.gz');
        $phar->decompress(); // creates /path/to/my.tar
        $phar->extractTo($reporoot, null, true);
        unlink($tempfolder . '/' . $tempfilename . '.tar.gz');

        $archive_index = $reporoot . '.ARCHIVE_INDEX';
        if (!file_exists($archive_index)) {
            unlink($archive_index); // delete file
        }

        if (!$repo->hasChanges()) {
            return false;
        }

        $githash = $repo->addAllChanges()->commit($timecreated)->getLastCommitId();

        $changeset = new commit();
        $changeset->set('githash', $githash);
        $changeset->set('repoid', $this->get('id'));
        $changeset->set('usermodified', $userid);
        $changeset->set('timecreated', $timecreated);
        $changeset->set('timemodified', $timecreated);
        $changeset->save();

        return $changeset;
    }
}
