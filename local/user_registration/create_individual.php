<?php
require_once('../../config.php');
require_once("$CFG->libdir/filelib.php");
require_once("create_individualform.php");
require_once("locallib.php");
require_once($CFG->dirroot . '/local/learnpeek_email_template/locallib.php');
require_once($CFG->dirroot . '/local/learnpeek_email_template/locallib.php');
global $DB, $PAGE, $CFG, $OUTPUT; 

$pageurl = $CFG->wwwroot."/local/user_registration/create_individual.php";
$PAGE->requires->jquery();
$PAGE->set_url($CFG->wwwroot.'/local/user_registration/create_individual.php');
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('createaccountformindividual','local_user_registration'));
$is_hide = check_company_access_restriction();
if($is_hide){
    redirect(new moodle_url($CFG->wwwroot.'/my'),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
    die;
}
$company_id = iomad::get_my_companyid(context_system::instance());
$companyname = $DB->get_record("company", array("id"=>$company_id));

$getcompnayadmin = $DB->get_record_sql("SELECT * FROM {company_users} WHERE companyid = ? AND userid =?",array($company_id,$USER->id));
if (!is_siteadmin() && (!$getcompnayadmin || $getcompnayadmin->managertype != 1)) {
    redirect(new moodle_url($CFG->wwwroot.'/my'),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
     die;
}
$pagetitle = $companyname->name . " : " . get_string('createindividualaccount', 'local_user_registration');
$PAGE->set_heading($pagetitle);
echo $OUTPUT->header();
$mform = new registration($pageurl, $args);
if ($mform->is_cancelled()) {
    redirect($CFG->wwwroot.'/local/customlayout/manageusers.php');
}else if ($fromform = $mform->get_data()) {
  	$userdata = new stdClass();
    $userdata->username 	   = $fromform->user_name;
    $userdata->firstname 	   = $fromform->first_name;
    $userdata->lastname 	   = $fromform->last_name;
    $userdata->email 		     = $fromform->email;
    $userdata->company_id    = $company_id;
    $userdata->password 	   = $fromform->password;
  	$data = create_individual_user($userdata);
  	if($data){
      $newuser = $DB->get_record('user', ['id' => $data]);
      if ($newuser) {
          // send trainee registration email
          trainee_registration_email_to_user($company_id, $newuser->id, $userdata->password);
          trainee_registration_email_to_whitelable($company_id, $newuser->id, $userdata->password);
      }
  		redirect($CFG->wwwroot.'/local/customlayout/manageusers.php', 'Individual  user account created successfully', null, \core\output\notification::NOTIFY_SUCCESS);
  	}
}  

$mform->display();

echo $OUTPUT->footer(); 
?>




