<?php

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
    if ($repo) {
        $mform->setDefault('local_versioncontrol_trackingtype', $repo->get('trackingtype'));
    } else {
        $mform->setDefault('local_versioncontrol_trackingtype', repo::TRACKINGTYPE_NONE);
    }
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
        $repo->create();
    }

    if ($repo->get('trackingtype') == repo::TRACKINGTYPE_AUTOMATIC) {
        $repo->commitchanges($USER->id, time());
    } else if ($repo->get('trackingtype') == repo::TRACKINGTYPE_MANUAL) {
        $repo->set('possiblechanges', true);
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

function local_versioncontrol_before_standard_top_of_body_html() {
    local_versioncontrol_showwarnings();
}
function local_versioncontrol_showwarnings() {
    global $PAGE;

    $context = $PAGE->context;

    if (!has_capability('local/versioncontrol:manage', $context)) {
        return;
    }

    if ($PAGE->pagetype == 'local-versioncontrol-makecommit') {
        return;
    }

    if ($context->contextlevel == CONTEXT_MODULE) {
        $cmrepo = repo::get_record([
                'instancetype' => repo::INSTANCETYPE_COURSEMODULECONTEXT,
                'instanceid'   => $context->id,
                'possiblechanges' => true
        ]);
    }

    $coursecontext = $PAGE->context->get_course_context();
    if ($coursecontext) {
        $courserepo = repo::get_record([
                'instancetype' => repo::INSTANCETYPE_COURSECONTEXT,
                'instanceid'   => $coursecontext->id,
                'possiblechanges' => true
        ]);
    }

    $repos = [];
    if ($cmrepo) {
        $repos[] = $cmrepo;
    }
    if ($courserepo) {
        $repos[] = $courserepo;
    }

    foreach ($repos as $repo) {
        if ($repo->get('instancetype') == repo::INSTANCETYPE_COURSECONTEXT) {
            $str = 'changesdetectedcourse';
        } else if ($repo->get('instancetype') == repo::INSTANCETYPE_COURSEMODULECONTEXT) {
            $str = 'changesdetectedactivity';
        }

        \core\notification::warning(
                get_string($str, 'local_versioncontrol')
                .
                ": "
                .
                html_writer::link(
                        new moodle_url('/local/versioncontrol/makecommit.php',
                                ['repo' => $repo->get('id')]),
                        get_string('makecommit', 'local_versioncontrol')
                )
        );
    }
}
