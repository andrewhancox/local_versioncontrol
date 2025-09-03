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
 * @package local_versioncontrol
 * @author Andrew Hancox <andrewdchancox@googlemail.com>
 * @author Open Source Learning <enquiries@opensourcelearning.co.uk>
 * @link https://opensourcelearning.co.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2021, Andrew Hancox
 */

namespace local_versioncontrol;

use backup;
use backup_controller;
use context;
use core\persistent;
use core_user;
use Cz\Git\GitException;
use Cz\Git\GitRepository;
use local_versioncontrol\task\commitchanges_task;
use PharData;
use core\task\manager;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/local/versioncontrol/lib/GitException.php');
require_once($CFG->dirroot . '/local/versioncontrol/lib/GitRepository.php');
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

/**
 * Class for loading/storing data changesets from the DB.
 *
 * @copyright  2018 David Monllao
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class repo extends persistent {

    /**
     * Database table.
     */
    const TABLE = 'local_versioncontrol_repo';

    const INSTANCETYPE_COURSEMODULECONTEXT = 10;
    const INSTANCETYPE_COURSECONTEXT = 20;

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

    public static function gettrackingtypes_machinenames() {
        return [
                'none'   => self::TRACKINGTYPE_NONE,
                'manual' => self::TRACKINGTYPE_MANUAL,
                'auto'   => self::TRACKINGTYPE_AUTOMATIC,
        ];
    }

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
                'instancetype'    => [
                        'type'    => PARAM_INT,
                        'choices' => [self::INSTANCETYPE_COURSEMODULECONTEXT, self::INSTANCETYPE_COURSECONTEXT],
                ],
                'instanceid'      => [
                        'type' => PARAM_INT,
                ],
                'possiblechanges' => [
                        'type'    => PARAM_BOOL,
                        'default' => 0,
                ],
                'remote' => [
                        'type'    => PARAM_TEXT,
                        'default' => '',
                ],
                'trackingtype'    => [
                        'type'    => PARAM_INT,
                        'choices' => [self::TRACKINGTYPE_NONE, self::TRACKINGTYPE_MANUAL, self::TRACKINGTYPE_AUTOMATIC],
                        'default' => self::TRACKINGTYPE_NONE,
                ],
        ];
    }

    private function getrepodirectory() {
        global $CFG;

        $contextid = $this->get('instanceid');
        $instancetype = $this->get('instancetype');
        return "$CFG->dataroot/local_versioncontrol/$instancetype/$contextid/";
    }

    public function queuecommitchangestask($userid, $timecreated, $message = null) {
        $task = new commitchanges_task();
        $task->set_custom_data(['repoid'        => $this->get('id'),
                                'userid'        => $userid,
                                'committime'    => $timecreated,
                                'commitmessage' => $message, ]);
        manager::queue_adhoc_task($task);

        $this->set('possiblechanges', false);
        $this->update();
    }

    public function commitchanges($userid, $timecreated, $message = null) {
        global $CFG, $DB;

        if (!isset($message)) {
            $message = $timecreated;
        }

        $contextid = $this->get('instanceid');
        $instancetype = $this->get('instancetype');

        $backuptype = null;
        if ($instancetype == self::INSTANCETYPE_COURSEMODULECONTEXT) {
            $backuptype = backup::TYPE_1ACTIVITY;
        } else if ($instancetype == self::INSTANCETYPE_COURSECONTEXT) {
            $backuptype = backup::TYPE_1COURSE;
        } else {
            throw new \moodle_exception('Unsupported repo type');
        }

        $tempfilename = 'backup' . '_' . $timecreated . '_' . $contextid . '_' . random_string(5);

        $context = context::instance_by_id($contextid);

        $bc = new backup_controller($backuptype, $context->instanceid, backup::FORMAT_MOODLE,
                backup::INTERACTIVE_NO, backup::MODE_GENERAL, get_admin()->id);
        $bc->get_plan()->get_setting('anonymize')->set_value(true);
        $bc->get_plan()->get_setting('filename')->set_value($tempfilename);
        $bc->get_plan()->get_setting('users')->set_value(false);
        $bc->get_plan()->get_setting('logs')->set_value(false);
        $bc->execute_plan();
        $results = $bc->get_results();
        $bc->destroy();
        $file = $results['backup_destination'];

        $reporoot = $this->getrepodirectory();
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

        $branchname = "context_$contextid" . "_$CFG->siteidentifier";

        if (!file_exists($reporoot . ".git")) {
            $repo = GitRepository::init($reporoot);
            $repo->moveBranch("$branchname");
        } else {
            $repo = $this->getgitrepo();

            if (!in_array($branchname, $repo->getBranches())) {
                $repo->createBranch($branchname, true);
            }

            if ($repo->getCurrentBranchName() != $branchname) {
                $repo->checkout($branchname);
            }
        }

        $tempfolder = rtrim($tempfolder, '/');
        $phar = new PharData($tempfolder . '/' . $tempfilename . '.tar.gz');
        $phar->decompress(); // creates /path/to/my.tar
        $phar->extractTo($reporoot, null, true);

        $files = glob($tempfolder . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        rmdir($tempfolder);

        $unwantedfiles = ['moodle_backup.log', '.ARCHIVE_INDEX'];
        foreach ($unwantedfiles as $unwantedfile) {
            $archiveindex = $reporoot . $unwantedfile;
            if (file_exists($archiveindex)) {
                unlink($archiveindex); // delete file
            }
        }

        if ($repo->filestatus('moodle_backup.xml') == 'M') {
            $repo->checkout('moodle_backup.xml');
        }

        if (!$repo->hasChanges()) {
            return false;
        }

        $user = core_user::get_user($userid);
        $fullname = fullname($user);
        $author = "$fullname <$user->email>";
        $githash = $repo->addAllChanges()->commit($message, ['--author' => $author])->getLastCommitId();

        $changeset = new commit();
        $changeset->set('githash', $githash);
        $changeset->set('message', $message);
        $changeset->set('repoid', $this->get('id'));
        $changeset->save();

        $DB->update_record(commit::TABLE, (object)['id' => $changeset->get('id'), 'usermodified' => $userid]);

        $this->set('possiblechanges', false);
        $this->update();

        return $changeset;
    }

    private function getgitrepo(): ?GitRepository {
        try {
            $repo = new GitRepository($this->getrepodirectory());
        } catch (GitException $ex) {
            return null;
        }

        return $repo;
    }

    public function pushchanges() {
        if (empty($this->get('remote'))) {
            return false;
        }

        $repo = $this->getgitrepo();

        if (empty($repo)) {
            return false;
        }

        $remotes = $repo->listRemotes();
        if (key_exists('origin', $remotes) && $remotes['origin'] != $this->get('remote')) {
            $repo->removeRemote('origin');
            $repo->addRemote('origin', $this->get('remote'));
        } else if (!key_exists('origin', $remotes)) {
            $repo->addRemote('origin', $this->get('remote'));
        }

        $repo->config('core.sshCommand', "ssh -i " . get_config('local_versioncontrol', 'gitsshkey'));
        $repo->config('push.autoSetupRemote', 'true');
        $repo->push('origin');
    }

    public function archive(commit $commit) {
        $repo = new GitRepository($this->getrepodirectory());
        return $repo->archive($commit->get('githash'));
    }

    public function getchangeset(commit $commit, $comparetohead) {

        if ($comparetohead) {
            $compareto = 'HEAD';
        } else {
            $previouscommitsinmoodle = commit::get_records_select('repoid = :repoid and id < :id', [
                    'repoid' => $commit->get('repoid'),
                    'id'     => $commit->get('id'),
            ], 'id desc');
            if (!$previouscommitsinmoodle) {
                $compareto = '4b825dc642cb6eb9a060e54bf8d69288fbee4904'; // Empty sha (prior to first commit)
            } else {
                $previouscommitinmoodle = reset($previouscommitsinmoodle);
                $compareto = $previouscommitinmoodle->get('githash');
            }
        }

        $githash = $commit->get('githash');

        $repo = new GitRepository($this->getrepodirectory());
        $changeset = $repo->getDiff($compareto, $githash, '');
        $changeset = implode("\n", $changeset);

        return $changeset;
    }
}
