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
 * Base class for the table used by a {@link local_report_cohort_coursestatus}.
 *
 * @package   local_report_cohort_coursestatus
 * @copyright 2019 E-Learn Design Ltd. (https://www.e-learndesign.co.uk)
 * @author    Mike Churchward
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/tablelib.php');

/**
 * Base class for the table used by local_report_cohort_coursestatus
 *
 * @copyright 2019 E-Learn Design Ltd. (https://www.e-learndesign.co.uk)
 * @author    Mike Churchward
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_report_cohort_coursestatus_table extends table_sql {

    /**
     * Generate the display of the user's full name.
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_fullname($row) {
        $userurl = '/local/report_users/userdisplay.php';
        $fullname = fullname($row);
        if (!$this->is_downloading() && iomad::has_capability('local/report_users:view', context_system::instance())) {
            return '<a href="' . new moodle_url($userurl, ['userid' => $row->userid]) . '">' . $fullname . '</a>';
        } else {
            return $fullname;
        }
    }

    /**
     * Generate the display of the user's course status
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function other_cols($column, $row) {
        global $DB;

        // Only process columns that are marked incomplete.
        if ((strpos($column, 'complete') !== 0) || ($row->$column == 'Complete') || empty($row->$column)) {
            return null;
        }

        $data = explode('_', $row->$column);

        $select = 'SELECT cm.id, cm.instance, m.name ';
        $from = 'FROM {course_modules} cm ' .
            'INNER JOIN {course} c ON cm.course = c.id ' .
            'INNER JOIN {course_modules_completion} cmc ON cm.id = cmc.coursemoduleid '.
            'INNER JOIN {modules} m ON cm.module = m.id ' .
            '';
        $where = 'WHERE c.id = :courseid AND cmc.userid = :userid AND cmc.completionstate != 0 ' .
            'AND cmc.timemodified = (SELECT MAX(timemodified) FROM {course_modules_completion} WHERE cmc.userid = :userid2 ' .
            'AND cmc.completionstate != 0)';
        $params = ['courseid' => $data[1], 'userid' => $row->userid, 'userid2' => $row->userid];
        $record = $DB->get_record_sql($select.$from.$where, $params);

        if (empty($record)) {
            $colval = get_string('notstarted', 'local_report_users');
        } else {
            $colval = $DB->get_field($record->name, 'name', ['id' => $record->instance]);
        }

        return $colval;
    }
}
