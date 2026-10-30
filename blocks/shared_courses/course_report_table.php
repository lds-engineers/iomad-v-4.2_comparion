<?php

use core_completion\progress;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');

class course_report_table extends table_sql {

    function __construct($uniqueid) {
        parent::__construct($uniqueid);

        $columns = [
            'firstname',
            'lastname',
            'email',
            'coursename',
            'progress',
            'score',
            'completedtime'
        ];

        $this->define_columns($columns);

        $headers = [
            'First Name',
            'Last Name',
            'Email',
            'Course',
            'Progress',
            'Score',
            'Completed Time'
        ];

        $this->define_headers($headers);
    }

    function col_firstname($values) {
        global $CFG;
        return "<a target='_blank' href='$CFG->wwwroot/user/profile.php?id=$values->userid'>$values->firstname</a>";
    }

    function col_lastname($values) {
        global $CFG;
        return "<a target='_blank' href='$CFG->wwwroot/user/profile.php?id=$values->userid'>$values->lastname</a>";
    }

    function col_email($values) {
        return $values->email;
    }

    function col_coursename($values) {
        return $values->coursename;
    }

    function col_progress($values) {
        $course = get_course($values->courseid);

        $percentage = progress::get_course_progress_percentage($course, $values->userid);

        return ($percentage !== null) ? round($percentage, 2) . '%' : '-';
    }

    function col_score($values) {
        return ($values->score !== null) ? $values->score : '-';
    }

    function col_completedtime($values) {
        return !empty($values->timecompleted)
            ? date('Y-m-d H:i:s', $values->timecompleted)
            : '-';
    }
}