<?php
/**
 * Test table class to be put in businessusers_table.php of root of Moodle installation.
 *  for defining some custom column names and proccessing
 * Username and Password feilds using custom and other column methods.
 */
class businessusers_table extends table_sql {

    /**
     * Constructor
     * @param int $uniqueid all tables have to have a unique id, this is used
     *      as a key when storing table properties like sort order in the session.
     */
    function __construct($uniqueid) {
        parent::__construct($uniqueid);
        // Define the list of columns to show.
        // $columns = array('firstname', 'email', 'data', 'usercourses','edituser','viewtrainy');
        $columns = array('firstname', 'email', 'data','edituser','viewtrainy','course_rectriction');
        $this->define_columns($columns);
        // Define the titles of columns to show in header.
        $headers = array(get_string('name','local_customlayout'), get_string('email','local_customlayout'), get_string('company','local_customlayout'),get_string('editusers','local_customlayout'),get_string('viewdetails','local_customlayout'),get_string('courserectriction','local_customlayout'));
        $this->define_headers($headers);
    }

    /**
     * This function is called for each data row to allow processing of the
     * username value.
     *
     * @param object $values Contains object with all the values of record.
     * @return $string Return username with link to profile or username only
     *     when downloading.
     */
     function col_firstname($values) {
        // If the data is being downloaded than we don't want to show HTML.

        // return $values->firstname .' '.$lastname;
        return "<a target='_blank' href='" . $CFG->wwwroot . "/user/profile.php?id=" . $values->id . "'>" . $values->firstname . ' ' . $values->lastname . "</a>";

    }
    // function col_usercourses($values) {

    //         // return '<a href="#">View User Courses</a>';
    //         return "<a class='btn btn-primary' href='$CFG->wwwroot/blocks/learnpeek_shop/license_management.php?buserid=$values->id'>View User Courses</a>";
    // }
    function col_edituser($values) {

            return "<a class='btn btn-primary' href='$CFG->wwwroot/blocks/learnpeek_shop/edit_businessuser.php?id=$values->id'>".get_string('edituser','local_customlayout')."</a>";
    }

    function col_viewtrainy($values) {

            return "<a class='btn btn-primary' href='$CFG->wwwroot/blocks/learnpeek_shop/manage_business_user.php?buserid=$values->id'>".get_string('viewtraineelist','local_customlayout')."</a>";
    }


    // function col_username($values) {
    //     // If the data is being downloaded than we don't want to show HTML.
    //     if ($this->is_downloading()) {
    //         return $values->username;
    //     } else {
    //         return '<a class="btn btn-primary" href="/user/editadvanced.php?id='.$values->id.'">'.$values->username.'</a>';
    //     }
    // }

    /**
     * This function is called for each data row to allow processing of
     * columns which do not have a *_cols function.
     * @return string return processed value. Return NULL if no change has
     *     been made.
     */
    function other_cols($colname, $value) {
        // For security reasons we don't want to show the password hash.
        if ($colname == 'password') {
            return "****";
        }
    }
    function col_course_rectriction($values) {

            return "<a traget='_blank' class='btn btn-primary' href='$CFG->wwwroot/local/customlayout/businessuser_course_rectriction.php?buserid=$values->id'>".get_string('managecategory','local_customlayout')."</a>";
    }
}