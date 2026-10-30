<?php
/**
 * Simple file businessusers.php to drop into root of Moodle installation.
 * This is an example of using a sql_table class to format data.
 */
require_once('../../config.php'); 
require_once($CFG->libdir . '/tablelib.php');
require $CFG->dirroot."/local/customlayout/businessusers_table.php";
require_login();
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/local/customlayout/businessusers.php');

$companyuser = $DB->get_record('company_users', ['userid' => $USER->id]);

// $companyid = $companyuser->companyid;
// $companydata = $DB->get_record("company",array("id"=>$companyid));
$companyid = iomad::get_my_companyid(context_system::instance());
$getcompnayadmin = $DB->get_record_sql("SELECT * FROM {company_users} WHERE companyid = ? AND userid =?",array($companyid,$USER->id));
if (!is_siteadmin() && (!$getcompnayadmin || $getcompnayadmin->managertype != 1)) {
    redirect(new moodle_url($CFG->wwwroot.'/my'),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
     die;
}

$current_host = $_SERVER['HTTP_HOST'];
$getcompany = $DB->get_record_sql("SELECT * FROM {company} WHERE hostname LIKE ?", array($current_host));



$download = optional_param('download', '', PARAM_ALPHA);
$search = optional_param('search', '', PARAM_TEXT);

$table = new businessusers_table('uniqueid');
$table->is_downloading($download, 'test', 'testing123');

if (!$table->is_downloading()) {
    // Only print headers if not asked to download data.
    // Print the page header.
    $PAGE->set_title(get_string('businessusers','local_customlayout'));
    $PAGE->set_heading(get_string('businessusers','local_customlayout'));
    $PAGE->navbar->add('Business Users', new moodle_url('/local/customlayout/businessusers.php'));
    echo $OUTPUT->header();

    echo '<form method="get" action="" class="mb-3 d-flex align-items-center gap-2">';
    echo '<input type="text" name="search" placeholder="Search..." value="' . s($search) . '" class="form-control w-25" />';
    echo '<button type="submit" class="btn btn-primary btn-sm ml-2">'.get_string('search','local_customlayout').'</button>';
    echo '<a href="' . new moodle_url('/local/customlayout/businessusers.php') . '" class="btn btn-secondary btn-sm ml-2">'.get_string('clear','local_customlayout').'</a>';
    echo '</form>';
}

$where = '1=1 AND u.suspended = 0';

$params = [];
if (!empty($search)) {
    $like = $DB->sql_like($DB->sql_concat('u.firstname', "' '", 'u.lastname', "' '", 'u.email'), ':search', false);
    $where .= " AND $like";
    $params['search'] = "%$search%";
}
if (!empty($companyid)) {
    $where .= " AND cu.companyid = :companyid";
    $params['companyid'] = $companyid;
}
$userfield = $DB->get_record('user_info_field', array('shortname' => 'user_company_name'));
// echo "SELECT u.id, u.firstname, u.lastname, u.email, cu.companyid, uif.data FROM mdl_company_users as cu 
//      INNER JOIN mdl_user as u ON cu.userid = u.id 
//      LEFT JOIN mdl_user_info_data as uif ON uif.userid = u.id AND uif.fieldid = $userfield->id WHERE cu.managertype = 2 AND u.suspended = 0 AND 1=1 AND u.deleted = 0";
$table->set_sql(
    'u.id, u.firstname, u.lastname, u.email, cu.companyid, uif.data',
    "{company_users} as cu 
     INNER JOIN {user} as u ON cu.userid = u.id 
     LEFT JOIN {user_info_data} as uif ON uif.userid = u.id AND uif.fieldid = $userfield->id",
    'cu.managertype = 2 AND u.suspended = 0 AND u.deleted = 0 AND ' . $where,
    $params
);

$table->define_baseurl("$CFG->wwwroot/local/customlayout/businessusers.php");
$table->out(10, true);

if (!$table->is_downloading()) {
    echo $OUTPUT->footer();
}
echo "<style>
.dataformatselector{
    display:none;
}

</style>";