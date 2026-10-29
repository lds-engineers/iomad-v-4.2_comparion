<?php
require_once('../../config.php');
require_once("$CFG->libdir/filelib.php");
require_once($CFG->dirroot . '/user/editlib.php');
require_once($CFG->dirroot .'/blocks/iomad_company_admin/lib.php');
require_once("create_whitelabelform.php");
require_once("locallib.php");
global $DB, $PAGE, $CFG, $OUTPUT, $USER; 
$pageurl = $CFG->wwwroot."/local/user_registration/create_whitelabel.php";
$PAGE->requires->jquery();
$PAGE->set_url($CFG->wwwroot.'/local/user_registration/create_whitelabel.php');
$PAGE->set_pagelayout('standard');
$PAGE->set_title('Create Account Form - White label Admin');

$companyid = iomad::get_my_companyid(context_system::instance());
$companyname = $DB->get_record("company", array("id"=>$companyid));
$departmentid = $DB->get_record('department', array('company' => $companyid));

$getcompnayadmin = $DB->get_record_sql("SELECT * FROM {company_users} WHERE companyid = ? AND userid =?",array($companyid,$USER->id));
if (!is_siteadmin() && (!$getcompnayadmin || $getcompnayadmin->managertype != 1)) {
    redirect(new moodle_url($CFG->wwwroot.'/my'),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
     die;
}
// $pagetitle = $companyname->name ." : Create White label Admin Account";
// $PAGE->set_heading($pagetitle);
$pagetitle = $companyname->name . " : " . get_string('createwhitelabeladmin', 'local_user_registration');
$PAGE->set_heading($pagetitle);
$is_hide = check_company_access_restriction();
if($is_hide){
    redirect(new moodle_url($CFG->wwwroot.'/my'),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
    die;
}
echo $OUTPUT->header();
$mform = new whitelabelregistration($pageurl, $args);
if ($mform->is_cancelled()) {
    redirect($CFG->wwwroot.'/local/customlayout/manageusers.php');
}else if ($fromform = $mform->get_data()) {
  	$data = new stdClass();
    $data->username 	   = trim($fromform->username);
    $data->firstname 	   = trim($fromform->firstname);
    $data->lastname 	   = trim($fromform->lastname);
    $data->email 		   = trim($fromform->email);
    $data->companyid       = $companyid;
    $data->deptid          = $departmentid->id;
    $data->newpassword 	   = $fromform->password;
    $data->managertype     = 1;
    $data->educator     = 0;
    $data->licenseid     = 0;
    $data->preference_auth_forcepasswordchange     = 0;
    $data->userid = $USER->id;
    $data->currentcourses = [];
    $data->licensecourses = [];

    // Create user
    if (!$userid = company_user::create($data)) {
        throw new moodle_exception('Error creating user');
    }

    $data->id = $userid;

    // Save profile fields
    profile_save_data($data);

    \core\event\user_updated::create_from_userid($userid)->trigger();

    // Assign role
    if (!empty($data->managertype) || !empty($data->educator)) {
        company::upsert_company_user(
            $userid,
            $companyid,
            $data->deptid,
            $data->managertype,
            $data->educator
        );
    }

    // Assign the user to the default company department.
    company::assign_user_to_department($data->deptid, $userid);
    purge_all_caches();
    redirect(
        $CFG->wwwroot.'/local/user_registration/whitelabeladminlist.php',
        'White label Admin user created successfully',
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}
$mform->display();
echo $OUTPUT->footer(); 
?>




