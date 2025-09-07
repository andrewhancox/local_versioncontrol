<?php

namespace mod_book;

use local_versioncontrol\repo;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/book/lib.php');

final class commitchanges_test extends \advanced_testcase {
    public function test_commit(): void {
        global $DB;

        $this->resetAfterTest();

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

        $this->setUser($teacher);
        $chapter2 = $bookgenerator->create_chapter(array('bookid' => $book->id, "pagenum" => 2));
        $event = \mod_book\event\chapter_created::create_from_chapter($book, $context, $chapter2);
        $event->trigger();
        $repo = repo::get_record(['id' => $persistent->get('id')]);
        $this->assertTrue($repo->get('possiblechanges'));
        $this->assertEquals($teacher->id, $repo->get('lockedtouserid'));

        repo::get_record(['id' => $persistent->get('id')])
            ->commitchanges($teacher->id, time(), 'chapter 2');

        $repo = repo::get_record(['id' => $persistent->get('id')]);
        $this->assertFalse($repo->get('possiblechanges'));
        $this->assertEquals(0, $repo->get('lockedtouserid'));
    }
}
