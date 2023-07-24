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
 * @author     Guenther Moser <moser@tugraz.at>
 * @copyright  2021 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace lytix_measure;


use core_plugin_manager;

/**
 * Class measure_lib.
 */
class measure_lib extends \external_api {
    /**
     * Checks parameters.
     * @return \external_function_parameters
     */
    public static function measure_get_parameters() {
        return new \external_function_parameters(
            [
                'contextid' => new \external_value(PARAM_INT, 'Context Id', VALUE_REQUIRED),
                'courseid' => new \external_value(PARAM_INT, 'Course Id', VALUE_REQUIRED),
                'userid' => new \external_value(PARAM_INT, 'Course Id', VALUE_REQUIRED),
            ]
        );
    }

    /**
     * Checks return values.
     * @return \external_single_structure
     */
    public static function measure_get_returns() {
        return new \external_single_structure(
            [
                'Name' =>
                    new \external_value(PARAM_TEXT, 'name of student/teacher', VALUE_REQUIRED),
                'StudentCount' =>
                    new \external_value(PARAM_INT, 'cnt of students', VALUE_REQUIRED),
                'ActivityCount' =>
                    new \external_single_structure(
                        [
                            'Past' =>
                                new \external_value(PARAM_INT, 'activity count in past', VALUE_REQUIRED),
                            'Future' =>
                                new \external_value(PARAM_INT, 'activity count in future', VALUE_REQUIRED),
                        ], 'desc', true
                    ),
                'Scores' =>
                    new \external_single_structure(
                        [
                            'Activity' => new \external_multiple_structure(
                                new \external_value(PARAM_TEXT, 'name of category', VALUE_REQUIRED)
                            ),
                            'Mine' => new \external_multiple_structure(
                                    new \external_value(PARAM_FLOAT, 'my score', VALUE_REQUIRED)
                            ),
                            'Lowest' => new \external_multiple_structure(
                                    new \external_value(PARAM_FLOAT, 'lowest score', VALUE_REQUIRED)
                            ),
                            'Highest' => new \external_multiple_structure(
                                    new \external_value(PARAM_FLOAT, 'Max Points of category', VALUE_REQUIRED)
                            ),
                            'Avg' => new \external_multiple_structure(
                                    new \external_value(PARAM_FLOAT, 'avg peer score', VALUE_REQUIRED)
                            ),
                            'Max' => new \external_multiple_structure(
                                    new \external_value(PARAM_FLOAT, 'best peer score', VALUE_REQUIRED)
                            ),
                        ], 'desc', true
                    )
            ]
        );
    }

    /**
     * Gets data for measure.
     * @param int $contextid
     * @param int $courseid
     * @param int $userid
     * @return mixed
     * @throws \coding_exception
     * @throws \invalid_parameter_exception
     * @throws \restricted_context_exception
     */
    public static function measure_get($contextid, $courseid, $userid) {
        $params = self::validate_parameters(self::measure_get_parameters(), [
            'contextid' => $contextid,
            'courseid' => $courseid,
            'userid' => $userid
        ]);

        // We always must call validate_context in a webservice.
        $context = \context::instance_by_id($params['contextid'], MUST_EXIST);
        self::validate_context($context);

        return measure::get_measure_data((int)$courseid, (int)$userid);
    }
}
