<?php
/**
 * Simple file test.php to drop into root of Moodle installation.
 * This is the skeleton code to print a downloadable, paged, sorted table of
 * data from a sql query.
 */
require_once('../../config.php');
require_once($CFG->libdir . '/tablelib.php');
require $CFG->dirroot . "/local/customlayout/test_table3.php";
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/local/customlayout/feedbackstatus.php');
$companyid = iomad::get_my_companyid(context_system::instance());
$companydata = $DB->get_record("company",array("id"=>$companyid));
$getcompnayadmin = $DB->get_record_sql("SELECT * FROM {company_users} WHERE companyid = ? AND userid =?",array($companyid,$USER->id));
if (!is_siteadmin() && (!$getcompnayadmin || $getcompnayadmin->managertype != 1)) {
    redirect(new moodle_url($CFG->wwwroot.'/my'),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
     die;
}
$download = optional_param('download', '', PARAM_ALPHA);
$search = optional_param('search', '', PARAM_TEXT);

$table = new test_table('uniqueid');
$table->sortable(true);
$table->no_sorting('Action');
$table->is_downloading($download, 'test', 'testing123');

if (!$table->is_downloading()) {
     $string_coursefeedback = get_string('coursefeedback', 'local_customlayout');
     $PAGE->set_title($string_coursefeedback);
     $PAGE->set_heading($string_coursefeedback);
     $PAGE->navbar->add($string_coursefeedback, new moodle_url('/local/customlayout/feedbackstatus.php'));
     echo $OUTPUT->header();

    // echo '<form method="get" action="" class="mb-3 d-flex align-items-center gap-2">';
    // echo '<input type="text" name="search" placeholder="Search..." value="' . s($search) . '" class="form-control w-25" />';
    // echo '<button type="submit" class="btn btn-primary btn-sm ml-2">Search</button>';
    // echo '<a href="' . new moodle_url('/local/customlayout/feedbackstatus.php') . '" class="btn btn-secondary btn-sm ml-2">Clear</a>';
    // echo '</form>';

    $perpage = optional_param('perpage', 10, PARAM_INT);

    echo '<form method="get" action="" class="mb-3 d-flex align-items-center gap-2">';

    echo '<label class="mr-2">Show</label>';
    echo '<select name="perpage" class="form-control w-auto">';
    $options = [10, 50, 100];
    foreach ($options as $opt) {
        $selected = ($perpage == $opt) ? 'selected' : '';
        echo "<option value='{$opt}' {$selected}>{$opt}</option>";
    }
    echo '</select>';

    echo '<label class="ml-2 mr-2">Entries</label>';

    echo '<input type="text" name="search" placeholder="Search..." value="' . s($search) . '" class="form-control w-25 ml-2" />';
    echo '<button type="submit" class="btn btn-primary btn-sm ml-2">' . get_string('search', 'local_customlayout') . '</button>';

    echo '<a href="' . new moodle_url('/local/customlayout/feedbackstatus.php') . '" class="btn btn-secondary btn-sm ml-2">' . get_string('clear', 'local_customlayout') . '</a>';

    echo '</form>';
}


$where = '1=1';
if (!empty($search)) {
    $like = $DB->sql_like('CONCAT(u.firstname, u.lastname, u.email, c.fullname)', ':search', false);
    $where .= " AND $like";
}


$params = [];
if (!empty($search)) {
    $params['search'] = "%$search%";
}

$companyid = iomad::get_my_companyid(context_system::instance());
if (!is_siteadmin()) {
    $context = context_user::instance($USER->id);
    $current_user_roles = get_user_roles($context, $USER->id);
    $manag_role = array_column($current_user_roles, 'shortname');
    // var_dump($manag_role);
    $where .= " AND cu.companyid = :companyid";
    $params['companyid'] = $companyid;
    if (in_array('companymanager', $manag_role)) {
        $table->set_sql(
            "r.id AS rid,
             u.id AS userid,
             u.firstname,
             u.lastname,
             r.rating AS rating,
             r.review AS review,
             r.reply AS reply,
             r.timecreated,
             r.timemodified AS timemodified,
             r.courseid AS courseid,
             c.fullname AS coursename",
            "{tool_courserating_rating} r
             INNER JOIN {user} u ON u.id = r.userid
             INNER JOIN {course} c ON c.id = r.courseid
             INNER JOIN {company_users} cu ON cu.userid = u.id
             INNER JOIN {company_category_mapping} ccm ON FIND_IN_SET(c.category, ccm.course_category) AND ccm.company_id=cu.companyid",
            $where,
            $params
        );
        /*echo "SELECT r.id AS rid, u.id AS userid, u.firstname, u.lastname, r.rating AS rating, r.review AS review, r.timemodified AS timemodified, r.courseid AS courseid, c.fullname AS coursename, c.category FROM 
        mdl_tool_courserating_rating r 
        INNER JOIN mdl_user u ON u.id = r.userid 
        INNER JOIN mdl_course c ON c.id = r.courseid 
        INNER JOIN mdl_company_users cu ON cu.userid = u.id 
        INNER JOIN mdl_company_category_mapping ccm ON FIND_IN_SET(c.category, ccm.course_category) AND ccm.company_id=cu.companyid 
        WHERE cu.companyid = 32";*/
    }
} else {
    $table->set_sql(
        "r.id AS rid,
         u.id as userid,
         u.firstname,
         u.lastname,
         r.rating AS rating,
         r.review AS review,
         r.reply AS reply,
         r.timemodified AS timemodified,
         r.courseid AS courseid,
         c.fullname AS coursename",
        "{tool_courserating_rating} r
         INNER JOIN {user} u ON u.id = r.userid
         INNER JOIN {course} c ON c.id = r.courseid",
        $where,
        $params
    );
}

$table->sql_order_by = 'r.timemodified DESC';
// $baseurl = new moodle_url('/local/customlayout/feedbackstatus.php');

// if (!empty($search)) {
//     $baseurl->param('search', $search);
// }

//-- updated 1103026
$baseurl = new moodle_url('/local/customlayout/feedbackstatus.php');
if (!empty($search)) {
    $baseurl->param('search', $search);
}
$baseurl->param('perpage', $perpage);
//--end //

$table->define_baseurl($baseurl);
// $table->out(10, true);
$table->out($perpage, true);

if (!$table->is_downloading()) {
    echo $OUTPUT->footer();
}

?>

<style type="text/css">
    .page-header-headings h1 {
        margin-top: 94px;
        padding: 0 33px;
        width: 100%;
        display: block;
    }

    .dataformatselector {
        display: none !important;
    }

    .no-overflow {
        min-width: 100%;
        width: 100%;
        overflow-x: auto;
    }
</style>