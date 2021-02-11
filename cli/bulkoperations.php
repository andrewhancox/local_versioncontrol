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

use local_versioncontrol\repo;

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
global $DB, $CFG;
require_once($CFG->libdir . '/clilib.php');

// Define the input options.
$longparams = [
        'help'         => false,
        'activitytype' => '',
        'trackingtype' => '',
        'overridecurrent' => '',
];

$shortparams = [
        'h' => 'help',
        'a' => 'activitytype',
        't' => 'trackingtype',
        'o' => 'overridecurrent'
];

// now get cli options
list($options, $unrecognized) = cli_get_params($longparams, $shortparams);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help =
            "Bulk configure version control

There are no security checks here because anybody who is able to
execute this file may execute any PHP too.

Options:
-h, --help                    Print out this help
-a, --activitytype=activitytype       Activity type
-t, --trackingtype=trackingtype    Tracking type
-o, --overridecurrent   Override current settings

Example:
\$sudo -u www-data /usr/bin/php admin/cli/bulkoperations.php
\$sudo -u www-data /usr/bin/php admin/cli/bulkoperations.php --activitytype=page --trackingtype=auto --overridecurrent
";

    echo $help;
    die;
}
$activitytype = $options['activitytype'];
$trackingtype = $options['trackingtype'];
$overridecurrent = !empty($options['overridecurrent']);
if ($activitytype !== '' && $trackingtype !== '') {
    if (!in_array($trackingtype, array_keys(repo::gettrackingtypes_machinenames()))) {
        echo "Unknown tracking type: " . $trackingtype . "\n";
        echo "Valid tracking type are: \n" . print_r(repo::gettrackingtypes_machinenames(), true);
        die;
    }

    $ctxs = $DB->get_records_sql('
        select ctx.* from {course_modules} cm
        inner join {modules} m on cm.module = m.id
        inner join {context} ctx on ctx.instanceid = cm.id and ctx.contextlevel = :modctxlvl
        where m.name = :activitytype
        ', ['activitytype' => $activitytype, 'modctxlvl' => CONTEXT_MODULE]);

    echo 'Found ' . count($ctxs) . ' instances of ' . $activitytype . "\n";

    if (count($ctxs) == 0) {
        die;
    }

    cron_setup_user();
    $admin = get_admin();

    foreach ($ctxs as $ctx) {
        $cmrepo = repo::get_record([
                'instancetype' => repo::INSTANCETYPE_COURSEMODULECONTEXT,
                'instanceid'   => $ctx->id
        ]);

        if (!$overridecurrent && $cmrepo && !empty($cmrepo->get('id'))) {
            continue;
        }

        if (!$cmrepo || empty($cmrepo->get('id'))) {
            $cmrepo = new repo();
            $cmrepo->from_record((object) [
                    'instancetype' => repo::INSTANCETYPE_COURSEMODULECONTEXT,
                    'instanceid'   => $ctx->id,
                    'trackingtype' => repo::gettrackingtypes_machinenames()[$trackingtype]
            ]);
            $cmrepo->save();
        } else {
            $cmrepo->set('trackingtype', repo::gettrackingtypes_machinenames()[$trackingtype]);
            $cmrepo->update();
        }
        $cmrepo->commitchanges($admin->id, time(), 'Bulk enabled');

        echo 'Enabled tracking for activity ' . $ctx->id . "\n";
    }

    echo "All done\n";
}
