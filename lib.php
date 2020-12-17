<?php

use local_versioncontrol\repo;

function local_versioncontrol_extend_settings_navigation(settings_navigation $nav, context $context) {
    if ($context->contextlevel !== CONTEXT_MODULE) {
        return;
    }

    if (!has_capability('local/versioncontrol:manage', $context)) {
        return;
    }

    $navigation_node = $nav->get('modulesettings');

    if ($navigation_node) {
        $navigation_node->add(
                get_string('managerepo', 'local_versioncontrol'),
                new moodle_url('/local/versioncontrol/managerepo.php',
                        ['instanceid' => $context->id, 'instancetype' => repo::INSTANCETYPE_COURSEMODULECONTEXT])
        );


        $repo = repo::get_record([
                'instancetype' => repo::INSTANCETYPE_COURSEMODULECONTEXT,
                'instanceid'   => $context->id
        ]);

        if ($repo && $repo->get('trackingtype') == repo::TRACKINGTYPE_MANUAL) {
            if ($repo->get('possiblechanges') == true) {
                $navigation_node->add(
                        get_string('makecommitdetectedchanges', 'local_versioncontrol'),
                        new moodle_url('/local/versioncontrol/makecommit.php',
                                ['repo' => $repo->id])
                );
            } else {
                $navigation_node->add(
                        get_string('makecommit', 'local_versioncontrol'),
                        new moodle_url('/local/versioncontrol/makecommit.php',
                                ['repo' => $repo->id])
                );
            }
        }
    }
}
