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
 * @package   lytix_measure
 * @copyright 2021 Educational Technologies, Graz, University of Technology
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace lytix_measure;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/grade/lib.php');
require_once($CFG->dirroot . '/grade/querylib.php');

use core_plugin_manager;
use local_lytix\helper\plugin_check;

/**
 * Class measure
 */
class measure {

    /**
     * Get data for measure.
     *
     * @param int  $courseid
     * @param int  $userid
     * @return array|void
     * @throws \dml_exception
     */
    public static function get_measure_data($courseid, $userid) {
        global $DB, $USER;

        $plugininstalled = plugin_check::is_installed('measure');

        $returndata = array();

        if (!$plugininstalled || $USER->id != $userid) {
            return $returndata;
        }
        if (in_array($courseid, explode(',', get_config('local_lytix', 'course_list')))) {

            $context = \context_course::instance($courseid);

            $studentroleid = $DB->get_record('role', ['shortname' => 'student'], '*')->id;
            $users         = get_role_users($studentroleid, $context);

            // Gauge calculations.
            $studentcount  = 0;
            $maxscore      = -1;
            $sumscore     = 0.0;
            $bestpeerscore = 0.0;
            $myscore       = 0.0;
            $minpeerscore  = 999999999.0;

            // Get studentcount and all points for "total".
            foreach ($users as $user) {
                $studentcount++;

                $grades = grade_get_course_grades($courseid, $user->id);
                if ($maxscore == -1) {
                    $maxscore = floatval($grades->grademax);
                }

                $score = floatval($grades->grades[$user->id]->grade);
                $sumscore += $score;

                if ($bestpeerscore < $score) {
                    $bestpeerscore = $score;
                }

                if ($user->id == $userid) {
                    $myscore = $score;
                }

                if ($minpeerscore > $score) {
                    $minpeerscore = $score;
                }
            }

            // Get available categories and their grade points.
            $sqlmax = "SELECT mgc.fullname, SUM(mgi.grademax)
        FROM {grade_items} mgi inner join {grade_categories} mgc ON(mgi.categoryid = mgc.id)
        WHERE mgi.courseid = " . $courseid . " AND mgi.itemname IS NOT NULL AND mgi.aggregationcoef = 0
        group by mgc.fullname ORDER BY mgc.fullname;";

            $categoriesdata = $DB->get_records_sql($sqlmax);

            $categories = array();
            array_push($categories, 'total');

            $categoriesmax = array();
            array_push($categoriesmax, (float) $maxscore);

            foreach ($categoriesdata as $item) {
                $name = $item->fullname;
                // Handle case if Moodle stores a ? category, unsure how these grade points occure.
                if (strcmp($name, '?') == 0) {
                    continue;
                }

                array_push($categoriesmax, (float) end($item));

                array_push($categories, $name);
            }

            $usergrading                   = array();
            $usergrading[$userid]['total'] = $myscore;

            $avgcategory          = array();
            $avgcategory['total'] = 0;

            // Perform average category calculations by iterating over each users grade points.
            foreach ($users as $user) {
                $query = "SELECT mgc.fullname, SUM(mgg.finalgrade) AS points
            FROM mdl_grade_items mgi INNER JOIN mdl_grade_grades mgg ON(mgi.id = mgg.itemid)
            inner join mdl_grade_categories mgc ON(mgi.categoryid = mgc.id)
            WHERE mgi.courseid = " . $courseid . "  AND mgg.userid = " . $user->id . " AND mgi.itemname IS NOT NULL
            group by mgc.fullname ORDER BY mgc.fullname";

                $data = $DB->get_records_sql($query);

                foreach ($data as $item) {
                    $category = $item->fullname;
                    if (strcmp($category, '?') == 0) {
                        continue;
                    }

                    // Prevent overflow with bonus points.
                    $maxpossible = $categoriesmax[array_search($category, $categories)];
                    if ($maxpossible != null && $item->points > $maxpossible) {
                        $item->points = $maxpossible;
                    }

                    $usergrading[$user->id][$category] = (float)$item->points;

                    if (array_key_exists($category, $avgcategory)) {
                        $avgcategory[$category] += (float)$item->points;
                    } else {
                        $avgcategory[$category] = (float)$item->points;
                    }
                }
            }

            $arraysum = 0;
            for ($i = 1; $i < count($categoriesmax); $i++) {
                $arraysum += $categoriesmax[$i];
            }

            // Get values of the grade items which are not part of a category.
            // Be aware of bonuspoints, arraysum can be bigger than the sum because of them.
            if ($categoriesmax[0] - $arraysum >= 0) {
                array_push($categories, 'no category');
                array_push($categoriesmax, ($categoriesmax[0] - $arraysum));

                foreach ($users as $user) {
                    $query = "SELECT SUM(mgg.finalgrade)
                    FROM mdl_grade_items mgi INNER JOIN mdl_grade_grades mgg ON(mgi.id = mgg.itemid)
                    WHERE mgi.courseid = " . $courseid . "  AND mgg.userid = " . $user->id . " AND mgi.itemname IS NOT NULL
                    AND mgi.categoryid = 1";

                    $data = $DB->get_records_sql($query);
                    $category = 'no category';
                    foreach ($data as $item) {
                        $usergrading[$user->id][$category] = (float)end($item);

                        if (array_key_exists($category, $avgcategory)) {
                            $avgcategory[$category] += (float)end($item);
                        } else {
                            $avgcategory[$category] = (float)end($item);
                        }
                    }
                }

            }

            $avgcategory['total'] = $sumscore;

            // Calculate average, highest and lowest score per category.
            $lowestscores = array();
            array_push($lowestscores, $minpeerscore);

            $highestscores = array();
            array_push($highestscores, $bestpeerscore);

            foreach ($categories as $category) {
                if ($category == null || $category == 'total') {
                    continue;
                }
                $highestscore = 0;
                $lowestscore  = PHP_FLOAT_MAX;
                foreach ($users as $user) {
                    if ($usergrading[$user->id][$category] > $highestscore) {
                        $highestscore = $usergrading[$user->id][$category];
                    }
                    if ($usergrading[$user->id][$category] < $lowestscore) {
                        $lowestscore = $usergrading[$user->id][$category];
                    }
                }
                array_push($highestscores, (float) $highestscore);
                array_push($lowestscores, (float) $lowestscore);

            }

            $timelineevents = $DB->get_records('lytix_planner_events', ['courseid' => $courseid]);

            $activitycountinfuture = 0;
            $activitycountinpast   = 0;

            foreach ($timelineevents as $event) {
                $duedatepast = 0;

                $now  = new \DateTime('now');
                $date = (new \DateTime())->setTimestamp($event->startdate);

                $offset = $now->diff($date);
                $offset = $offset->format('%r%a');

                if ($offset <= $duedatepast) {
                    $activitycountinpast++;
                } else {
                    $activitycountinfuture++;
                }
            }
            $activitycount = [
                    'Past'   => $activitycountinpast,
                    'Future' => $activitycountinfuture
            ];

            $avg  = array();
            $mine = array();
            foreach ($categories as $category) {
                if ($category == null) {
                    continue;
                }
                if (array_key_exists($category, $usergrading[$userid])) {
                    array_push($mine, (float) $usergrading[$userid][$category]);
                } else {
                    array_push($mine, (float) 0);
                }
                if (array_key_exists($category, $avgcategory)) {
                    if ($studentcount == 0) {
                        array_push($avg, (float) $avgcategory[$category]);
                    } else {
                        array_push($avg, (float) $avgcategory[$category] / $studentcount);
                    }
                } else {
                    array_push($avg, (float) 0);
                }
            }

            $scores = [
                    'Activity' => $categories,
                    'Mine'     => $mine,
                    'Lowest'   => $lowestscores,
                    'Highest'  => $highestscores,
                    'Avg'      => $avg,
                    'Max'      => $categoriesmax,
            ];

            $returndata['Name']          = $USER->firstname . ' ' . $USER->lastname;
            $returndata['StudentCount']  = $studentcount;
            $returndata['ActivityCount'] = $activitycount;
            $returndata['Scores']        = $scores;

            return $returndata;
        } else {
            return [];
        }
    }

    /**
     * Get data for measure report.
     *
     * @param int $courseid
     * @param int $userid
     * @return array|void
     * @throws \dml_exception
     */
    public static function get_measure_report_data($courseid, $userid) {
        global $DB, $USER;

        $plugininstalled = core_plugin_manager::instance()->get_plugins_of_type('measure');

        $data = array();

        if ($plugininstalled || $USER->id != $userid) {
            if (!in_array($courseid, explode(',', get_config('local_lytix', 'course_list')))) {
                return $data;
            }

            $context = \context_course::instance($courseid);

            $studentroleid = $DB->get_record('role', ['shortname' => 'student'], '*')->id;
            $users         = get_role_users($studentroleid, $context);

            $maxscore     = 0.0;
            $bestpeerscore = 0.0;
            $myscore       = 0.0;

            foreach ($users as $user) {

                $grades = grade_get_course_grades($courseid, $user->id);
                if ($maxscore == 0.0) {
                    $maxscore = floatval($grades->grademax);
                }

                $score    = floatval($grades->grades[$user->id]->grade);

                if ($bestpeerscore < $score) {
                    $bestpeerscore = $score;
                }

                if ($user->id == $userid) {
                    $myscore = $score;
                }
            }

            $perbestpeerscore = ($maxscore != 0.0) ? (($bestpeerscore * 100) / $maxscore) : 0;
            $permyscore       = ($maxscore != 0.0) ? (($myscore * 100) / $maxscore) : 0;

            $data['PerMyScore'] = $permyscore;
            $data['PerBestPeerScore'] = $perbestpeerscore;

            return $data;
        } else {
            return [];
        }
    }

}

