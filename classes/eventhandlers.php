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

namespace local_versioncontrol;

use backup;
use backup_controller;
use core\event\course_module_updated;
use Cz\Git\GitRepository;
use PharData;

class eventhandlers {
    /**
     * @param course_module_updated $event
     */
    public static function course_module_updated(course_module_updated $event) {
        global $CFG;

        $now = time();

        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/local/versioncontrol/lib/IGit.php');
        require_once($CFG->dirroot . '/local/versioncontrol/lib/GitRepository.php');

        $tempfilename = 'backup' . '_' . $now . '_' . $event->objectid;

        $bc = new backup_controller(backup::TYPE_1ACTIVITY, $event->objectid, backup::FORMAT_MOODLE,
                backup::INTERACTIVE_NO, backup::MODE_GENERAL, $event->userid);
        $bc->get_plan()->get_setting('anonymize')->set_value(true);
        $bc->get_plan()->get_setting('filename')->set_value($tempfilename);
        $bc->execute_plan();
        $results = $bc->get_results();
        $file = $results['backup_destination'];

        $reporoot = $CFG->dataroot . '/local_versioncontrol/' . $event->objectid . '/';
        @mkdir($reporoot, 0777, true);

        $tempfolder = make_temp_directory('local_versioncontrol' . '_' . $now . '_' . $event->objectid);
        $file->copy_content_to($tempfolder.'/'.$tempfilename . '.tar.gz');
        $file->delete();

        $files = glob($reporoot . '*'); // get all file names
        foreach($files as $file){ // iterate files
            if(is_file($file)) {
                unlink($file); // delete file
            }
        }

        if (!file_exists($reporoot . ".git")) {
            $repo = GitRepository::init($reporoot);
        } else {
            $repo = new GitRepository($reporoot);
        }

        $phar = new PharData($tempfolder.'/'.$tempfilename . '.tar.gz');
        $phar->decompress(); // creates /path/to/my.tar
        $phar->extractTo($reporoot,null, true);
        unlink($tempfolder.'/'.$tempfilename . '.tar.gz');

        $archive_index = $reporoot . '.ARCHIVE_INDEX';
        if (!file_exists($archive_index)) {
            unlink($archive_index); // delete file
        }

        $repo->addAllChanges();
        $repo->commit($event->timecreated);
    }
}
