<?php

use core_completion\progress;

class test_table extends table_sql {
    function __construct($uniqueid) {
        parent::__construct($uniqueid);
        $columns = array('Course', 'Review', 'Reply', 'Star', 'Created_Date', 'Given_By', 'Action');
        $this->define_columns($columns);
        // $headers = array('Course', 'Review', 'Reply', 'Star', 'Created Date', 'Given By', 'Action');
        $headers = array(get_string('course', 'local_customlayout'),
                 get_string('review', 'local_customlayout'),
                 get_string('reply', 'local_customlayout'),
                 get_string('star', 'local_customlayout'),
                 get_string('createddate', 'local_customlayout'),
                 get_string('givenby', 'local_customlayout'),
                 get_string('action', 'local_customlayout')
        );
        $this->define_headers($headers);
    }
    function col_Course($values) {
        global $DB,$CFG;
        $course = $DB->get_record("course",array("id"=>$values->courseid));
        return $course ? $course->fullname : null;
    }
    function col_Review($values) {
        global $DB,$CFG;
        return $values->review;
    }
    function col_Reply($values) {
        global $DB,$CFG;
        return $values->reply ? $values->reply : "no reply";
    }
    function col_Star($values) {
        global $DB,$CFG;
        return $values->rating;
    }
    function col_Created_Date($values) {
        global $DB,$CFG;
        return date("m-d-Y",$values->timecreated);
    }
    function col_Given_By($values) {
        global $DB,$CFG;
        $user = $DB->get_record("user",array("id"=>$values->userid));
        return $user ? $user->firstname." ".$user->lastname : null;
    }
    function col_Action($values) {
        global $DB,$CFG;
        $page = optional_param('page', 0, PARAM_INT);
        $perpage = optional_param('perpage', 10, PARAM_INT);
        $search = optional_param('search', '', PARAM_TEXT);
        $html = ""; 
        $tool_courserating_rating = $DB->get_record("tool_courserating_rating",array("id"=>$values->rid));

        // $html .= "<a href='$CFG->wwwroot/local/customlayout/editfeedback.php?id=$values->rid' class='btn btn-secondary  m-2'>Edit</a>";

        // $html .= "<a href='$CFG->wwwroot/local/customlayout/replyto.php?id=$values->rid' class='btn btn-primary  m-2'>Reply to</a>";
        // $html .= "<a href='$CFG->wwwroot/local/customlayout/editfeedback.php?id=$values->rid' class='btn text-white btn-warning  m-2'>Edit</a>";
        // $html .= "<a href='$CFG->wwwroot/local/customlayout/deletefeedback.php?id=$values->rid' onclick='return confirm(\"Are you sure, want to delete?\")' class='btn btn-danger  m-2'>Delete</a>";

        //-- updated 1103026
        $html .= "<a href='$CFG->wwwroot/local/customlayout/replyto.php?id=$values->rid&page=$page&perpage=$perpage&search=$search' class='btn btn-primary m-2'>" . get_string('replyto', 'local_customlayout') . "</a>";

        $html .= "<a href='$CFG->wwwroot/local/customlayout/editfeedback.php?id=$values->rid&page=$page&perpage=$perpage&search=$search' class='btn text-white btn-warning m-2'>" . get_string('edit', 'local_customlayout') . "</a>";

        $html .= "<a href='$CFG->wwwroot/local/customlayout/deletefeedback.php?id=$values->rid&page=$page&perpage=$perpage&search=$search' onclick='return confirm(\"Are you sure, want to delete?\")' class='btn btn-danger m-2'>" . get_string('delete', 'local_customlayout') . "</a>";
        //---end

        // $html .= "<a href='$CFG->wwwroot/local/customlayout/replyfeedback.php?id=$values->rid' class='btn btn-success  m-2'>Reply</a>";

        if($tool_courserating_rating && !$tool_courserating_rating->status) {
            // $html .= "<a href='$CFG->wwwroot/local/customlayout/updatefeedback.php?id=$values->rid' onclick='return confirm(\"Are you sure, want to approve?\")' class='btn btn-success m-2'>Approve</a>";
            //-- updated 1103026
            $html .= "<a href='$CFG->wwwroot/local/customlayout/updatefeedback.php?id=$values->rid&page=$page&perpage=$perpage&search=$search' onclick='return confirm(\"Are you sure, want to approve?\")' class='btn btn-success m-2'>" . get_string('approve', 'local_customlayout') . "</a>";
            //---end
        } else {
            $html .= "<p href='#' class=' m-2' read-only>Approved</p>";
        }
        return $html;
    }

    function get_sql_sort() {
        $sort = parent::get_sql_sort();

        // Moodle column name -> actual SQL field.
        $sort = str_replace('Course', 'c.fullname', $sort);
        $sort = str_replace('Star', 'r.rating', $sort);
        $sort = str_replace('Created_Date', 'r.timemodified', $sort);
        $sort = str_replace('Given_By', 'u.firstname', $sort);

        return $sort;
    }

}