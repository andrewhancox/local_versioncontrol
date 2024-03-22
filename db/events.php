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

$observers = [];

$events = [
        '\core\event\course_module_updated',
        '\mod_book\event\chapter_created',
        '\mod_book\event\chapter_deleted',
        '\mod_book\event\chapter_updated',
        '\mod_data\event\field_created',
        '\mod_checklist\event\checklist_updated',
        '\mod_data\event\field_deleted',
        '\mod_data\event\field_updated',
        '\mod_data\event\template_updated',
        '\mod_folder\event\folder_updated',
        '\mod_glossary\event\category_created',
        '\mod_glossary\event\category_deleted',
        '\mod_glossary\event\category_updated',
        '\mod_glossary\event\entry_approved',
        '\mod_glossary\event\entry_created',
        '\mod_glossary\event\entry_deleted',
        '\mod_glossary\event\entry_disapproved',
        '\mod_glossary\event\entry_updated',
        '\mod_lesson\event\page_created',
        '\mod_lesson\event\page_deleted',
        '\mod_lesson\event\page_moved',
        '\mod_lesson\event\page_updated',
        '\mod_wiki\event\wiki',
        '\mod_wiki\event\page_created',
        '\mod_wiki\event\page_deleted',
        '\mod_wiki\event\page_updated',
        '\mod_wiki\event\page_version_deleted',
        '\mod_wiki\event\page_version_restored',
        '\mod_quiz\event\edit_page_viewed',
        '\core\event\course_module_deleted',
        '\core\event\course_module_created',
        '\core\event\course_section_deleted',
        '\core\event\course_section_updated',
        '\core\event\course_section_created',
        '\core\event\course_updated',
];

foreach ($events as $eventname) {
    $observers[] = [
            'eventname' => $eventname,
            'callback'  => 'local_versioncontrol\eventhandlers::recordchange',
            'priority'  => 9999,
    ];
}

$questionevents = [
        '\core\event\question_updated',
        '\core\event\question_created',
];

foreach ($questionevents as $eventname) {
    $observers[] = [
            'eventname' => $eventname,
            'callback'  => 'local_versioncontrol\eventhandlers::questionchange',
            'priority'  => 9999,
    ];
}

$observers[] = [
        'eventname' => '\core\event\course_created',
        'callback'  => 'local_versioncontrol\eventhandlers::setcoursedefaults',
        'priority'  => 9999,
];
