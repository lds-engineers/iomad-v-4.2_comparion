<?php
/**
 * Simple file test.php to drop into root of Moodle installation.
 * This is the skeleton code to print a downloadable, paged, sorted table of
 * data from a sql query.
 */
require_once('../../config.php');
require_once($CFG->libdir . '/tablelib.php');
require $CFG->dirroot."/local/customlayout/test_table2.php";
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/local/customlayout/userslisting.php');
$companyid = iomad::get_my_companyid(context_system::instance());
$getcompnayadmin = $DB->get_record_sql("SELECT * FROM {company_users} WHERE companyid = ? AND userid =?",array($companyid,$USER->id));
if (!is_siteadmin() && (!$getcompnayadmin || $getcompnayadmin->managertype != 1)) {
    redirect(new moodle_url($CFG->wwwroot.'/my'),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
     die;
}
$download = optional_param('download', '', PARAM_ALPHA);
$search = optional_param('search', '', PARAM_TEXT);

$table = new test_table('uniqueid');
$table->is_downloading($download, 'test', 'testing123');

if (!$table->is_downloading()) {
    $PAGE->set_title(get_string('editusers', 'local_customlayout'));
    $PAGE->set_heading(get_string('editusers', 'local_customlayout'));
    $PAGE->navbar->add('Edit users', new moodle_url('/local/customlayout/userslisting.php'));
    echo $OUTPUT->header();

    echo '<form method="get" action="" class="mb-3 d-flex align-items-center gap-2">';
    echo '<input type="text" name="search" placeholder="Search..." value="' . s($search) . '" class="form-control w-25" />';
    echo '<button type="submit" class="btn btn-primary btn-sm ml-2">'.get_string('search', 'local_customlayout').'</button>';
    echo '<a href="' . new moodle_url('/local/customlayout/userslisting.php') . '" class="btn btn-secondary btn-sm ml-2">'.get_string('clear', 'local_customlayout').'</a>';
    echo '</form>';


}

$clause = "";
if (!is_siteadmin()) {
    $clause = "LEFT JOIN {company_users} cu ON cu.userid = u.id AND cu.companyid = $companyid";
} else {
    $clause = "LEFT JOIN {company_users} cu ON cu.userid = u.id";
}

$where = '1=1 AND u.deleted = 0 AND u.suspended = 0 AND cu.managertype = 0';

if (!empty($search)) {
    $like = $DB->sql_like($DB->sql_concat('u.firstname', "' '", 'u.lastname', "' '", 'u.email'), ':search', false);
    $where .= " AND $like";
}

$params = [];
if (!empty($search)) {
    $params['search'] = "%$search%";
}

$table->set_sql(
    "u.id, u.firstname, u.lastname, u.email, u.suspended",
    "{user} u $clause",
    $where,
    $params
);


$baseurl = new moodle_url('/local/customlayout/userslisting.php');
if (!empty($search)) {
    $baseurl->param('search', $search);
}
$table->define_baseurl($baseurl);
$table->out(10, true);

if (!$table->is_downloading()) {
    echo $OUTPUT->footer();
}

?>

<style type="text/css">
.dataformatselector {
    display: none !important;
}
</style>