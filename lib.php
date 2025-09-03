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

use core\notification;
use local_versioncontrol\repo;

/**
 * Inject the competencies elements into all moodle module settings forms.
 *
 * @param moodleform $formwrapper The moodle quickforms wrapper object.
 * @param MoodleQuickForm $mform The actual form object (required to modify the form).
 */
function local_versioncontrol_coursemodule_standard_elements($formwrapper, $mform) {
    global $PAGE;

    if (!has_capability('local/versioncontrol:manage', $PAGE->context)) {
        return;
    }

    $repo = repo::get_record([
            'instancetype' => repo::INSTANCETYPE_COURSEMODULECONTEXT,
            'instanceid'   => $PAGE->context->id
    ]);

    $mform->addElement('header', 'versioncontrol', get_string('pluginname', 'local_versioncontrol'));
    $mform->addElement('select', 'local_versioncontrol_trackingtype', get_string('trackingtype', 'local_versioncontrol'),
            repo::gettrackingtypes(),
            ['required' => 'required']);

    $defaultforactivity = get_config('local_versioncontrol', 'autoenableforactivitytype_' . $formwrapper->get_current()->modulename);
    if ($repo) {
        $default = $repo->get('trackingtype');
    } else if (!empty($formwrapper->get_coursemodule)) {
        $default = repo::TRACKINGTYPE_NONE;
    } else if (!empty($defaultforactivity)) {
        $default = $defaultforactivity;
    } else {
        $default = repo::TRACKINGTYPE_NONE;
    }

    $mform->setDefault('local_versioncontrol_trackingtype', $default);
}

/**
 * Hook the add/edit of the course module.
 *
 * @param stdClass $data Data from the form submission.
 * @param stdClass $course The course.
 */
function local_versioncontrol_coursemodule_edit_post_actions($data, $course) {
    global $USER;

    if (!isset($data->local_versioncontrol_trackingtype)) {
        return $data;
    }

    $context = context_module::instance($data->coursemodule);
    $repo = repo::get_record([
            'instancetype' => repo::INSTANCETYPE_COURSEMODULECONTEXT,
            'instanceid'   => $context->id
    ]);

    if ($repo) {
        $repo->set('trackingtype', $data->local_versioncontrol_trackingtype);
        $repo->update();
    } else {
        $repo = new repo();
        $repo->set('trackingtype', $data->local_versioncontrol_trackingtype);
        $repo->set('instancetype', repo::INSTANCETYPE_COURSEMODULECONTEXT);
        $repo->set('instanceid', $context->id);

        if ($repo->get('trackingtype') == repo::TRACKINGTYPE_MANUAL) {
            $repo->set('possiblechanges', true);
            $repo->set('lockedtouserid',  $USER->id);
        }

        $repo->create();
    }

    if ($repo->get('trackingtype') == repo::TRACKINGTYPE_AUTOMATIC) {
        $repo->queuecommitchangestask($USER->id, time());
    } else if ($repo->get('trackingtype') == repo::TRACKINGTYPE_MANUAL) {
        $repo->set('possiblechanges', true);
        $repo->set('lockedtouserid',  $USER->id);
        $repo->update();
    }

    return $data;
}

function local_versioncontrol_extend_settings_navigation(settings_navigation $nav, context $context) {
    if (!has_capability('local/versioncontrol:manage', $context)) {
        return;
    }

    if ($context->contextlevel == CONTEXT_MODULE) {
        $navigation_node = $nav->get('modulesettings');
        $instancetype = repo::INSTANCETYPE_COURSEMODULECONTEXT;
    } else if ($context->contextlevel == CONTEXT_COURSE) {
        $navigation_node = $nav->get('courseadmin');
        $instancetype = repo::INSTANCETYPE_COURSECONTEXT;
    } else {
        return;
    }

    if ($navigation_node) {
        $navigation_node->add(
                get_string('managerepo', 'local_versioncontrol'),
                new moodle_url('/local/versioncontrol/managerepo.php',
                        ['instanceid' => $context->id, 'instancetype' => $instancetype])
        );

        $repo = repo::get_record([
                'instancetype' => $instancetype,
                'instanceid'   => $context->id
        ]);

        if ($repo && $repo->get('trackingtype') == repo::TRACKINGTYPE_MANUAL) {
            $navigation_node->add(
                    get_string('makecommit', 'local_versioncontrol'),
                    new moodle_url('/local/versioncontrol/makecommit.php',
                            ['repo' => $repo->get('id')])
            );
        }
    }
}
