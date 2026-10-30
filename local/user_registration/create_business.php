<?php
require_once('../../config.php');
require_once("$CFG->libdir/formslib.php");
require_once("$CFG->libdir/filelib.php");
require_once("./locallib.php");
require_once($CFG->dirroot . '/local/learnpeek_email_template/locallib.php');
require_once("createbusiness_form.php");
require_once($CFG->dirroot . '/local/learnpeek_email_template/locallib.php');
global $DB, $PAGE, $CFG, $OUTPUT;

$pageurl = $CFG->wwwroot."/local/user_registration/create_business.php";
$PAGE->requires->jquery();
$PAGE->set_url($CFG->wwwroot.'/local/user_registration/create_business.php');
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('createaccountformbusiness','local_user_registration'));
$company_id = iomad::get_my_companyid(context_system::instance());
$companyname = $DB->get_record("company", array("id"=>$company_id));
$pagetitle = $companyname->name ." : " . get_string('createbusinessaccount', 'local_user_registration');
$PAGE->set_heading($pagetitle);

$is_hide = check_company_access_restriction();
if($is_hide){
    redirect(new moodle_url($CFG->wwwroot.'/my'),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
    die;
}
$allow_brg = get_config(null, "rm_business_registration{$company_id}");
if($allow_brg){
 redirect(new moodle_url($CFG->wwwroot.'/my'),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
 die();
} 
$getcompnayadmin = $DB->get_record_sql("SELECT * FROM {company_users} WHERE companyid = ? AND userid =?",array($company_id,$USER->id));
if (!is_siteadmin() && (!$getcompnayadmin || $getcompnayadmin->managertype != 1)) {
    redirect(new moodle_url($CFG->wwwroot.'/my'),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
     die;
}

echo $OUTPUT->header();

// $current_host = $_SERVER['HTTP_HOST'];
// $getcompany = $DB->get_record_sql("SELECT id,hostname FROM {company} WHERE hostname LIKE ?", array($current_host));
// if($getcompany){
//   $company_id = $getcompany->id;
// }else{
//   redirect($CFG->wwwroot.'/login/index.php', 'Login with Company Url', null, \core\output\notification::NOTIFY_SUCCESS);
// }
$mform = new create_business_user($pageurl, $args);
if ($mform->is_cancelled()) {
    redirect($CFG->wwwroot.'/local/customlayout/managerbusinessusers.php');
} else if ($fromform = $mform->get_data()) {
    $userd = new stdClass();
    $userd->username            = $fromform->user_name;
    $userd->firstname           = $fromform->first_name;
    $userd->lastname            = $fromform->last_name;
    $userd->email               = $fromform->email;
    $userd->company_id          = $company_id;
    $userd->user_company_name   = $fromform->user_company_name;
    $userd->password            = $fromform->password;
    $data = create_compnay_business_user($userd);
    if($data){
        $newuser = $DB->get_record('user', ['id' => $data]);
        if ($newuser) {
            // send trainee registration email
            trainee_registration_email_to_user($company_id, $newuser->id, $userdata->password);
            trainee_registration_email_to_whitelable($company_id, $newuser->id, $userdata->password);
        }
        redirect($CFG->wwwroot.'/local/customlayout/managerbusinessusers.php', 'Business user account created successfully', null, \core\output\notification::NOTIFY_SUCCESS);
    }
}

$mform->display();
echo $OUTPUT->footer();
?>


