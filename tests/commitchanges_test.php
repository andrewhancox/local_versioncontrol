<?php

namespace mod_book;

use local_versioncontrol\repo;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/book/lib.php');

final class commitchanges_test extends \advanced_testcase {
    public function setUp(): void {
        $this->resetAfterTest();
    }

    public function test_commit(): void {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $teacher = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course(array('enablecomment' => 1));
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));

        $this->getDataGenerator()->enrol_user($user->id, $course->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, $teacherrole->id);

        // Test book with 3 chapters.
        $book = $this->getDataGenerator()->create_module('book', array('course' => $course->id));
        $cm = get_coursemodule_from_id('book', $book->cmid);
        $context = \context_module::instance($book->cmid);

        $bookgenerator = $this->getDataGenerator()->get_plugin_generator('mod_book');
        $chapter1 = $bookgenerator->create_chapter(array('bookid' => $book->id, "pagenum" => 1));

        $persistent = new repo();
        $persistent->from_record((object)[
            'instancetype' => repo::INSTANCETYPE_COURSEMODULECONTEXT,
            'instanceid' => $context->id,
            'possiblechanges' => true,
            'trackingtype' => repo::TRACKINGTYPE_MANUAL,
        ]);
        $persistent->create();

        $persistent->commitchanges($teacher->id, time(), 'initial commit');
        $this->assertFalse(repo::get_record(['id' => $persistent->get('id')])->get('possiblechanges'));

        $chapter2 = $bookgenerator->create_chapter(array('bookid' => $book->id, "pagenum" => 2));
        $event = \mod_book\event\chapter_created::create_from_chapter($book, $context, $chapter2);
        $event->trigger();
        $this->assertTrue(repo::get_record(['id' => $persistent->get('id')])->get('possiblechanges'));

        repo::get_record(['id' => $persistent->get('id')])
            ->commitchanges($teacher->id, time(), 'chapter 2');

        $this->assertFalse(repo::get_record(['id' => $persistent->get('id')])->get('possiblechanges'));
    }

    public function test_pushtoremote(): void {
        global $DB, $CFG;

        set_config('gitsshkey', $CFG->dirroot . "/local/versioncontrol/tests/sshkey/mootdach2025", 'local_versioncontrol');

        $user = $this->getDataGenerator()->create_user();
        $teacher = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course(array('enablecomment' => 1));
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));

        $this->getDataGenerator()->enrol_user($user->id, $course->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, $teacherrole->id);

        // Test book with 3 chapters.repo
        $book = $this->getDataGenerator()->create_module('book', array('course' => $course->id));
        $cm = get_coursemodule_from_id('book', $book->cmid);
        $context = \context_module::instance($book->cmid);

        $bookgenerator = $this->getDataGenerator()->get_plugin_generator('mod_book');
        $chapter1 = $bookgenerator->create_chapter(array('bookid' => $book->id, "pagenum" => 1));
        $chapter2 = $bookgenerator->create_chapter(array('bookid' => $book->id, "pagenum" => 2));

        $persistent = new repo();
        $persistent->from_record((object)[
            'instancetype' => repo::INSTANCETYPE_COURSEMODULECONTEXT,
            'instanceid' => $context->id,
            'possiblechanges' => true,
            'trackingtype' => repo::TRACKINGTYPE_MANUAL,
            'remote' => 'git@github.com:andrewhancox/bookversioncontroldemo.git',
        ])->create();

        $repo = repo::get_record(['id' => $persistent->get('id')]);
        $repo->commitchanges($teacher->id, time(), 'initialcommit');
        $repo->pushchanges();
    }
}
