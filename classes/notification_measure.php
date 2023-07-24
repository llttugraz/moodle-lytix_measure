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

use context_course;
use lytix_helper\course_settings;

/**
 * Class notification_measure
 */
class notification_measure {

    /**
     * Name of the plugin.
     * @var string
     */
    private $component;

    /**
     * notification_email constructor.
     */
    public function __construct() {
        $this->component = 'lytix_measure';
    }

    /**
     * Sets last report by inersting the record in lytix_planner_last_report.
     *
     * @param int $courseid
     * @param int $timestamp
     * @return \stdClass
     * @throws \dml_exception
     */
    private function set_last_report($courseid, $timestamp) {
        global $DB;

        $record            = new \stdClass();
        $record->courseid  = $courseid;
        $record->timestamp = $timestamp;

        $record->id = $DB->insert_record('lytix_measure_last_report', $record);
        return $record;
    }

    /**
     * Sends email to a specified user.
     *
     * @param \stdClass $user
     * @param string    $subject
     * @param string    $body
     */
    private function email_to_user(\stdClass $user, string $subject, string $body) {
        static $noreplyuser = null;
        if ($noreplyuser === null) {
            $noreplyuser = \core_user::get_noreply_user();
        }
        email_to_user($user, $noreplyuser, $subject, $body, text_to_html($body));
    }

    /**
     * Gets email data.
     *
     * @param \stdClass|null $course
     * @param \stdClass|null $user
     * @param int $othersreached
     * @param int $myscore
     * @param int $maxscore
     * @return array
     */
    public function email_data($course, $user, $othersreached, $myscore, $maxscore) {
        global $CFG;
        static $supportuser = null;
        if ($supportuser === null) {
            $supportuser = \core_user::get_support_user();
        }

        $data = [
                'firstname'     => $user->firstname,
                'email'         => $user->email,
                'wwwroot'       => $CFG->wwwroot,
                'supportemail'  => $supportuser->email,
                'course'        => $course->fullname,
                'othersreached' => $othersreached,
                'myscore'       => $myscore,
                'maxscore'      => $maxscore,
                'courseurl'     => $CFG->wwwroot . '/local/lytix/index.php?id=' . $course->id,
        ];

        return $data;
    }

    /**
     * Send email Case1.
     *
     * @param \stdClass|null $course
     * @param \stdClass|null $user
     * @param int $othersreached
     * @param int $myscore
     * @param int $maxscore
     */
    public function monthly_email_send_case1($course, $user, $othersreached, $myscore, $maxscore) {
        $emaildata = self::email_data($course, $user, $othersreached, $myscore, $maxscore);

        $emailsubject = get_string('emailsubjectcase1', $this->component, $emaildata);
        $emailbody    = get_string('emailtextcase1', $this->component, $emaildata);

        $this->email_to_user($user, $emailsubject, $emailbody);
    }

    /**
     * Send email Case2.
     *
     * @param \stdClass|null $course
     * @param \stdClass|null $user
     * @param int $othersreached
     * @param int $myscore
     * @param int $maxscore
     */
    public function monthly_email_send_case2($course, $user, $othersreached, $myscore, $maxscore) {
        $emaildata = self::email_data($course, $user, $othersreached, $myscore, $maxscore);

        $emailsubjectcase2 = get_string('emailsubjectcase2', $this->component, $emaildata);
        $emailbodycase2    = get_string('emailtextcase2', $this->component, $emaildata);

        $this->email_to_user($user, $emailsubjectcase2, $emailbodycase2);
    }

    /**
     * Send email Case3.
     *
     * @param \stdClass|null $course
     * @param \stdClass|null $user
     * @param int $othersreached
     * @param int $myscore
     * @param int $maxscore
     */
    public function monthly_email_send_case3($course, $user, $othersreached, $myscore, $maxscore) {
        $emaildata = self::email_data($course, $user, $othersreached, $myscore, $maxscore);

        $emailsubjectcase3 = get_string('emailsubjectcase3', $this->component, $emaildata);
        $emailbodycase3    = get_string('emailtextcase3', $this->component, $emaildata);

        $this->email_to_user($user, $emailsubjectcase3, $emailbodycase3);
    }

    /**
     * Sends monthly email report.
     *
     * @param int $courseid
     * @throws \dml_exception
     */
    public function monthly_email_report($courseid) {

        global $DB;
        // Check if course is valid.
        $course = get_course($courseid);
        if (is_null($course)) {
            return;
        }

        // Time check.
        $params['courseid'] = $courseid;
        $sql = "SELECT * FROM {lytix_measure_last_report} reports WHERE reports.courseid = :courseid
                    ORDER BY timestamp DESC";
        $records = $DB->get_records_sql($sql, $params);

        $now = new \DateTime('now');
        $month = null;
        if ($records && reset($records) && reset($records)->timestamp) {
            $month = (new \DateTime())->setTimestamp(reset($records)->timestamp);
            $month->modify('+1 month');
        } else {
            $month = course_settings::getcoursestartdate($courseid);
            $month->modify('+1 month');
        }

        $end = course_settings::getcourseenddate($courseid)->getTimestamp();

        if ($now->getTimestamp() > $month->getTimestamp() && $now->getTimestamp() <= $end) {
            // Get students.
            $context       = context_course::instance($courseid);
            $studentroleid = $DB->get_record('role', ['shortname' => 'student'], '*')->id;
            $users         = get_role_users($studentroleid, $context);

            $students = [];
            $threshold = 0;
            // Fill students array with get_measure_data.
            foreach ($users as $user) {
                $tmp = [];
                $tmp['user'] = $user;
                $tmp['data'] = measure::get_measure_report_data($courseid, $user->id);
                $students[] = $tmp;
                if ($tmp['data']['PerMyScore'] >= 50.0) {
                    $threshold++;
                }
            }

            if ($threshold >= (count($users) / 2.0)) {
                $othersreached = ($threshold / count($users)) * 100;
                foreach ($students as $student) {
                    $percentage = $student['data']['PerMyScore'];
                    switch ($percentage) {
                        case ($percentage < 40.0):
                            $this->monthly_email_send_case1($course, $student['user'],
                                round($othersreached, 2),
                                round($percentage, 2),
                                round($student['data']['PerBestPeerScore'], 2)
                            );
                            break;
                        case ($percentage >= 40.0 && $percentage <= 80.0):
                            $this->monthly_email_send_case2($course, $student['user'],
                                round($othersreached, 2),
                                round($percentage, 2),
                                round($student['data']['PerBestPeerScore'], 2)
                            );
                            break;
                        case ($percentage >= 80.0):
                            $this->monthly_email_send_case3($course, $student['user'],
                                round($othersreached, 2),
                                round($percentage, 2),
                                round($student['data']['PerBestPeerScore'], 2)
                            );
                            break;
                        default:
                            break;
                    }
                }
            }

            // Set new timestamp.
            $this->set_last_report($course->id, $now->getTimestamp());
        }
    }
}

