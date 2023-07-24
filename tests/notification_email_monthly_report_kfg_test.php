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
 * This is a one-line short description of the file.
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    lytix_measure
 * @author     Viktoria Wieser
 * @copyright  2021 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace lytix_measure;

defined('MOODLE_INTERNAL') || die();

global $CFG;
// Needed for notification_email.php.
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/course/lib.php');
// Needed for the activities generators.
require_once($CFG->dirroot . '/mod/assign/externallib.php');
require_once($CFG->dirroot . '/mod/quiz/lib.php');

use advanced_testcase;
use assign;
use context_module;
use context_user;
use external_api;
use grade_item;
use local_lytix\helper\tests;
use lytix_measure\notification_measure;
use lytix_measure\task\send_report_notifications;
use mod_assign_external;
use question_engine;
use quiz;
use quiz_attempt;
use stdClass;

/**
 * Class notification_email_monthly_report_kfg_test.
 *
 * @group learners_corner
 */
class notification_email_monthly_report_kfg_test extends advanced_testcase {
    /**
     * Variable for course.
     *
     * @var stdClass|null
     */
    private $course = null;

    /**
     * Setup called before any test case.
     */
    public function setUp(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        set_config('platform', 'course_dashboard', 'local_lytix');

        // Set semester start one month ago, so that the monthly report can trigger.
        $onemonthago = new \DateTime('2 months ago');
        date_add($onemonthago, date_interval_create_from_date_string('2 weeks'));
        set_config('semester_start', $onemonthago->format('Y-m-d'), 'local_lytix');

        $semend = new \DateTime('2 months ago');
        date_add($semend, date_interval_create_from_date_string('2 weeks'));
        date_add($semend, date_interval_create_from_date_string('4 months'));
        set_config('semester_end', $semend->format('Y-m-d'), 'local_lytix');

        $this->course = $this->getDataGenerator()->create_course(['startdate'        => $onemonthago->getTimestamp(),
                                                                  'enablecompletion' => 1]);

        set_config('course_list', $this->course->id, 'local_lytix');

    }

    /**
     * Execute send_report_notifications.
     *
     * @throws \dml_exception
     */
    public function executetask() {
        $task = new send_report_notifications();
        $task->execute();
    }

    // Helper for ASSIGNMENTS.

    /**
     * Simulates a grade.
     *
     * @param null|stdClass $teacher
     * @param null|stdClass $student
     * @param mixed         $instance
     * @param int           $gradeval
     * @return array|bool|mixed
     * @throws \file_exception
     * @throws \invalid_response_exception
     * @throws \stored_file_creation_exception
     */
    private function simulate_grade($teacher, $student, $instance, $gradeval) {

        $this->setUser($teacher);

        // Create a file in a draft area.
        $draftidfile = file_get_unused_draft_itemid();

        $usercontext = context_user::instance($teacher->id);
        $filerecord  = array(
                'contextid' => $usercontext->id,
                'component' => 'user',
                'filearea'  => 'draft',
                'itemid'    => $draftidfile,
                'filepath'  => '/',
                'filename'  => 'testtext.txt',
        );

        $fs = get_file_storage();
        $fs->create_file_from_string($filerecord, 'text contents');

        // Now try a grade.
        $feedbackpluginparams                                  = array();
        $feedbackpluginparams['files_filemanager']             = $draftidfile;
        $feedbackeditorparams                                  = array('text'   => 'Yeeha!',
                                                                       'format' => 1);
        $feedbackpluginparams['assignfeedbackcomments_editor'] = $feedbackeditorparams;
        $result                                                = mod_assign_external::save_grade($instance->id,
                                                                                                 $student->id,
                                                                                                 $gradeval,
                                                                                                 -1,
                                                                                                 true,
                                                                                                 'released',
                                                                                                 false,
                                                                                                 $feedbackpluginparams);
        // No warnings.
        $this->assertNull($result);

        $result = mod_assign_external::get_grades(array($instance->id));
        $result = external_api::clean_returnvalue(mod_assign_external::get_grades_returns(), $result);

        return $result;
    }

    /**
     * Creates an assign instance.
     *
     * @param int $duedate
     * @param int $allowsubmissionsfromdate
     * @return mixed
     * @throws \coding_exception
     */
    private function create_assign_instance($duedate = 0, $allowsubmissionsfromdate = 0) {
        $dg = $this->getDataGenerator();

        $generator                                 = $dg->get_plugin_generator('mod_assign');
        $params['course']                          = $this->course->id;
        $params['assignfeedback_file_enabled']     = 1;
        $params['assignfeedback_comments_enabled'] = 1;
        $params['duedate']                         = $duedate;
        $params['allowsubmissionsfromdate']        = $allowsubmissionsfromdate;
        return $generator->create_instance($params);
    }

    // TESTS Part.

    /**
     * Test monthly report with assign instance.
     *
     * @throws \dml_exception
     */
    public function test_kfg_monthly_report_assign() {
        $this->resetAfterTest(true);

        $teacher = tests::create_enrol_teacher($this->course);
        $this->setUser($teacher);

        $student1 = tests::create_enrol_student($this->course, 'student1@example.org');
        self::assertNotNull($student1);

        $student2 = tests::create_enrol_student($this->course, 'student2@example.org');
        self::assertNotNull($student2);

        $student3 = tests::create_enrol_student($this->course, 'student3@example.org');
        self::assertNotNull($student3);

        // Create assign instance.
        $instance = $this->create_assign_instance();

        // Create an assignment.
        $assign = tests::create_assignment($this->course, $instance);
        self::assertNotNull($assign);

        // Simulate a grade for CASE1 grade < 40%.
        $result = $this->simulate_grade($teacher, $student1, $instance, 39);
        $this->assertEquals(floatval($result['assignments'][0]['grades'][0]['grade']), floatval('39.0'));
        // Simulate a grade for CASE2 grade > 40% && grade < 80%.
        $result = $this->simulate_grade($teacher, $student2, $instance, 79);
        $this->assertEquals(floatval($result['assignments'][0]['grades'][1]['grade']), floatval('79.0'));
        // Simulate a grade for CASE3 grade > 80%.
        $result = $this->simulate_grade($teacher, $student3, $instance, 100);
        $this->assertEquals(floatval($result['assignments'][0]['grades'][2]['grade']), floatval('100.0'));

        $sink = $this->redirectEmails();

        $this->executetask();

        $messages = $sink->get_messages();
        self::assertEquals(3, count($messages));
    }

    /**
     * Tests monthly report with quiz instance.
     *
     * @throws \dml_exception
     */
    public function test_kfg_monthly_report_quiz() {
        global $CFG, $DB;
        $this->resetAfterTest(true);

        // Enable completion before creating modules, otherwise the completion data is not written in DB.
        $CFG->enablecompletion = true;

        // Create teacher and students.
        $teacher     = tests::create_enrol_teacher($this->course);
        $passstudent = tests::create_enrol_student($this->course, 'pass@example.com');
        self::assertNotNull($passstudent);
        $failstudent = tests::create_enrol_student($this->course, 'fail@example.com');
        self::assertNotNull($failstudent);

        // Create a quiz.
        $quiz = tests::create_quiz($this->course, 100);

        // Create a numerical question.
        $quizobj = tests::create_quiz_question($this->course, $quiz, $teacher, 50);

        $timenow = time();

        // Start the passing attempt.
        $attempt = tests::create_quiz_attempt($quizobj, $passstudent, $timenow, '3.14');

        // Finish the passing attempt.
        tests::finish_quiz_attempt($attempt, $timenow);

        // Start the failing attempt.
        $attempt = tests::create_quiz_attempt($quizobj, $failstudent, $timenow, '0');

        // Finish the failing attempt.
        tests::finish_quiz_attempt($attempt, $timenow);

        // Check the grades.
        $grade = $DB->get_records('quiz_grades', ['userid' => $passstudent->id]);
        self::assertEquals(100.0, reset($grade)->grade);
        $grade = $DB->get_records('quiz_grades', ['userid' => $failstudent->id]);
        self::assertEquals(0.0, reset($grade)->grade);

        // Send report.
        $sink = $this->redirectEmails();

        $this->executetask();

        $messages = $sink->get_messages();
        self::assertEquals(2, count($messages));
    }

    /**
     * Tests monthly report in the past.
     *
     * @throws \dml_exception
     */
    public function test_kfg_monthly_report_month_past() {
        global $CFG;
        $this->resetAfterTest(true);

        $teacher = tests::create_enrol_teacher($this->course);
        $this->setUser($teacher);
        $student1 = tests::create_enrol_student($this->course, 'User1@example.org');
        self::assertNotNull($student1);

        $student2 = tests::create_enrol_student($this->course, 'User2@example.org');
        self::assertNotNull($student2);

        $student3 = tests::create_enrol_student($this->course, 'User3@example.org');
        self::assertNotNull($student3);

        // Assign part.
        // Create assign instance.
        $instance = $this->create_assign_instance();

        // Create an assignment.
        $assign = tests::create_assignment($this->course, $instance);
        self::assertNotNull($assign);

        // Simulate a grade for CASE1 grade < 40%.
        $result = $this->simulate_grade($teacher, $student1, $instance, 39);
        $this->assertEquals(39.0, $result['assignments'][0]['grades'][0]['grade']);
        // Simulate a grade for CASE2 grade > 40% && grade < 80%.
        $result = $this->simulate_grade($teacher, $student2, $instance, 79);
        $this->assertEquals(79.0, $result['assignments'][0]['grades'][1]['grade']);
        // Simulate a grade for CASE3 grade > 80%.
        $result = $this->simulate_grade($teacher, $student3, $instance, 100);
        $this->assertEquals(100.0, $result['assignments'][0]['grades'][2]['grade']);

        // Quiz part.
        // Enable completion before creating modules, otherwise the completion data is not written in DB.
        $CFG->enablecompletion = true;

        // Create a quiz.
        $quiz = tests::create_quiz($this->course, 100);

        // Create a numerical question.
        $quizobj = tests::create_quiz_question($this->course, $quiz, $teacher, 50);

        $timenow = time();

        // Start the failing attempt.
        $attempt = tests::create_quiz_attempt($quizobj, $student1, $timenow, '0');
        tests::finish_quiz_attempt($attempt, $timenow);

        // Start the passing attempt.
        $attempt = tests::create_quiz_attempt($quizobj, $student2, $timenow, '3.14');
        tests::finish_quiz_attempt($attempt, $timenow);
        $attempt = tests::create_quiz_attempt($quizobj, $student3, $timenow, '3.14');
        tests::finish_quiz_attempt($attempt, $timenow);

        // Send report.
        $sink = $this->redirectEmails();

        $this->executetask();

        $messages = $sink->get_messages();
        self::assertEquals(3, count($messages));

        // Send report.
        $sink = $this->redirectEmails();

        $this->executetask();

        $messages = $sink->get_messages();
        self::assertEquals(0, count($messages));
    }

    /**
     * Tests monthly report with no activities.
     *
     * @throws \dml_exception
     */
    public function test_kfg_monthly_report_no_activities() {
        // Assign part.
        $this->resetAfterTest(true);
        // Create and enrol teacher.
        $teacher = tests::create_enrol_teacher($this->course);
        $this->setUser($teacher);

        // Create and enrol students.
        $student1 = tests::create_enrol_student($this->course, 'user1@example.org');
        self::assertNotNull($student1);

        // Send report.
        $sink = $this->redirectEmails();

        $this->executetask();

        $messages = $sink->get_messages();
        self::assertEquals(0, count($messages));
    }

    /**
     * Tests monthly report with closed activities.
     *
     * @throws \dml_exception
     */
    public function test_kfg_monthly_report_closed_activities() {
        global $CFG;
        $this->resetAfterTest(true);

        // Enable completion before creating modules, otherwise the completion data is not written in DB.
        $CFG->enablecompletion = true;
        // Create and enrol teacher.
        $teacher = tests::create_enrol_teacher($this->course);
        $this->setUser($teacher);

        // Create and enrol students.
        $student1 = tests::create_enrol_student($this->course, 'user1@example.org');
        self::assertNotNull($student1);

        $now = new \DateTime('now');
        date_add($now, date_interval_create_from_date_string('1 day'));

        // Assign part.
        // Create assign instance.
        $instance = $this->create_assign_instance(0, $now->getTimestamp());

        // Create an assignment.
        $assign = tests::create_assignment($this->course, $instance);
        self::assertNotNull($assign);

        // Quiz part.
        // Create a quiz.
        $quiz = tests::create_quiz($this->course, 100, 0, $now->getTimestamp());

        // Create a numerical question.
        tests::create_quiz_question($this->course, $quiz, $teacher, 50);

        // Send report.
        $sink = $this->redirectEmails();

        $this->executetask();

        $messages = $sink->get_messages();
        self::assertEquals(0, count($messages));
    }

    /**
     * Tests monthly report with no attempts.
     *
     * @throws \dml_exception
     */
    public function test_kfg_monthly_report_no_attempts() {
        global $CFG;
        $this->resetAfterTest(true);
        // Create and enrol teacher.
        $teacher = tests::create_enrol_teacher($this->course);
        $this->setUser($teacher);

        // Create and enrol students.
        $student1 = tests::create_enrol_student($this->course, 'user1@example.org');
        self::assertNotNull($student1);

        $student2 = tests::create_enrol_student($this->course, 'user2@example.org');
        self::assertNotNull($student2);

        $student3 = tests::create_enrol_student($this->course, 'user3@example.org');
        self::assertNotNull($student3);

        // Assign part.
        // Create assign instance.
        $instance = $this->create_assign_instance();

        // Create an assignment.
        $assign = tests::create_assignment($this->course, $instance);
        self::assertNotNull($assign);
        // Quiz part.
        // Enable completion before creating modules, otherwise the completion data is not written in DB.
        $CFG->enablecompletion = true;

        // Create a quiz.
        $quiz = tests::create_quiz($this->course, 100);

        // Create a numerical question.
        $quizobj = tests::create_quiz_question($this->course, $quiz, $teacher, 50);

        // Send report.
        $sink = $this->redirectEmails();

        $this->executetask();

        $messages = $sink->get_messages();
        self::assertEquals(0, count($messages));
    }

    /**
     * Tests monthly report with threshold not reached.
     *
     * @throws \dml_exception
     */
    public function test_kfg_monthly_report_threshold_not_reached() {
        global $DB;
        $this->resetAfterTest(true);
        // Create and enrol teacher.
        $teacher = tests::create_enrol_teacher($this->course);
        $this->setUser($teacher);

        // Create and enrol students.
        $student1 = tests::create_enrol_student($this->course, 'user1@example.org');
        self::assertNotNull($student1);

        $student2 = tests::create_enrol_student($this->course, 'user2@example.org');
        self::assertNotNull($student2);

        $student3 = tests::create_enrol_student($this->course, 'user3@example.org');
        self::assertNotNull($student3);

        // Assign part.
        // Create assign instance.
        $instance = $this->create_assign_instance();

        // Create an assignment.
        $assign = tests::create_assignment($this->course, $instance);
        self::assertNotNull($assign);

        // Simulate a grade for CASE3 grade > 80%.
        $result = $this->simulate_grade($teacher, $student1, $instance, 100);
        $this->assertEquals(floatval($result['assignments'][0]['grades'][0]['grade']), floatval('100.0'));
        // Simulate a grade for CASE1 grade < 40%.
        $result = $this->simulate_grade($teacher, $student2, $instance, 39);
        $this->assertEquals(floatval($result['assignments'][0]['grades'][1]['grade']), floatval('39.0'));
        // Simulate a grade for CASE1 grade < 40%.
        $result = $this->simulate_grade($teacher, $student3, $instance, 39);
        $this->assertEquals(floatval($result['assignments'][0]['grades'][2]['grade']), floatval('39.0'));

        // Quiz part.
        // Create a quiz.
        $quiz = tests::create_quiz($this->course, 100);

        // Create a numerical question.
        $quizobj = tests::create_quiz_question($this->course, $quiz, $teacher, 50);

        $timenow = time();

        // Start the passing attempt.
        $attempt = tests::create_quiz_attempt($quizobj, $student1, $timenow, '3.14');
        // Finish the passing attempt.
        tests::finish_quiz_attempt($attempt, $timenow);

        // Start the failing attempt.
        $attempt = tests::create_quiz_attempt($quizobj, $student2, $timenow, '0');
        // Finish the failing attempt.
        tests::finish_quiz_attempt($attempt, $timenow);

        // Start the failing attempt.
        $attempt = tests::create_quiz_attempt($quizobj, $student3, $timenow, '0');
        // Finish the failing attempt.
        tests::finish_quiz_attempt($attempt, $timenow);

        // Check the grades.
        $grade = $DB->get_records('quiz_grades', ['userid' => $student1->id]);
        self::assertEquals(100.0, reset($grade)->grade);
        $grade = $DB->get_records('quiz_grades', ['userid' => $student2->id]);
        self::assertEquals(0.0, reset($grade)->grade);
        $grade = $DB->get_records('quiz_grades', ['userid' => $student3->id]);
        self::assertEquals(0.0, reset($grade)->grade);

        // Send report.
        $sink = $this->redirectEmails();

        $this->executetask();

        $messages = $sink->get_messages();
        self::assertEquals(0, count($messages));
    }

    /**
     * Tests monthly report with 30 students.
     *
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \file_exception
     * @throws \invalid_response_exception
     * @throws \moodle_exception
     * @throws \stored_file_creation_exception
     */
    public function test_kfg_monthly_report_mass_user() {
        global $DB;
        $this->resetAfterTest(true);
        // Create and enrol teacher.
        $teacher = tests::create_enrol_teacher($this->course);
        $this->setUser($teacher);

        $students = [];
        for ($i = 0; $i < 30; $i++) {
            // Create and enrol student.
            $student = tests::create_enrol_student($this->course, 'user' . $i . '@example.org');
            self::assertNotNull($student);
            $students[] = $student;
        }

        // Assign part.
        // Create assign instance.
        $instance = $this->create_assign_instance();

        // Create an assignment.
        $assign = tests::create_assignment($this->course, $instance);
        self::assertNotNull($assign);

        foreach ($students as $key => $student) {
            $grade  = rand(70, 100);
            $result = $this->simulate_grade($teacher, $student, $instance, $grade);
            $this->assertEquals(floatval($result['assignments'][0]['grades'][$key]['grade']), floatval($grade));
        }

        // Quiz part.
        // Create a quiz.
        $quiz = tests::create_quiz($this->course, 100);

        // Create a numerical question.
        $quizobj = tests::create_quiz_question($this->course, $quiz, $teacher, 50);

        $timenow = time();

        foreach ($students as $student) {
            // Start the passing attempt.
            $attempt = tests::create_quiz_attempt($quizobj, $student, $timenow, '3.14');
            // Finish the passing attempt.
            tests::finish_quiz_attempt($attempt, $timenow);
            // Check the grades.
            $grade = $DB->get_records('quiz_grades', ['userid' => $student->id]);
            self::assertEquals(100.0, reset($grade)->grade);
        }

        // Send report.
        $sink = $this->redirectEmails();

        $this->executetask();

        $messages = $sink->get_messages();
        self::assertEquals(count($students), count($messages));
    }
}
