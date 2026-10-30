<?php

use core_completion\progress;

class test_table extends table_sql {
    function __construct($uniqueid) {
        parent::__construct($uniqueid);
        $columns = array('Student_First_Name', 'Student_Last_Name', 'Email', 'Edit_Student', 'Action');
        $this->define_columns($columns);
        $headers = array(get_string('studentfirstname', 'local_customlayout'), get_string('studentlastname', 'local_customlayout'), get_string('email', 'local_customlayout'), get_string('editstudent', 'local_customlayout'), get_string('action', 'local_customlayout'));
        $this->define_headers($headers);
    }

    function col_Student_First_Name($values) {
        return "<a target='_blank' href='$CFG->wwwroot/user/profile.php?id=$values->id'>$values->firstname</a>";
    }
    function col_Student_Last_Name($values) {
        return "<a target='_blank' href='$CFG->wwwroot/user/profile.php?id=$values->id'>$values->lastname</a>";
    }
    function col_Email($values) {
        return $values->email;
    }
    function col_Role($values) {
        return "";
    }
    function col_Edit_Student($values) {
        global $CFG;
        return "<a target='_blank' href='$CFG->wwwroot/blocks/learnpeek_shop/edit_trainee.php?id=$values->id'>".get_string('editusers', 'local_customlayout')."</a>";
    }
    function col_Action($values) {
        global $CFG;

        $id = (int)$values->id;
        $url = htmlspecialchars("$CFG->wwwroot/blocks/learnpeek_shop/updatestatus_trainee.php?id=$id");
        $icon = $values->suspended ? 'fa-eye-slash' : 'fa-eye';
        $title = $values->suspended ? 'Disabled' : 'Enable';

        return "<a title='$title' href='$url'><i class='fa $icon'></i></a>";
    }

}