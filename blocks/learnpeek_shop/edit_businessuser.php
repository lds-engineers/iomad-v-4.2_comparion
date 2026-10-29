<?php
require_once(dirname(__FILE__) . '/../../config.php');
require_once("edit_businessuserform.php");
require_once("locallib.php");
// require $CFG->dirroot."/local/user_registration/locallib.php";
global $DB, $PAGE, $CFG, $USER, $OUTPUT; 
$userid = required_param('id',PARAM_INT); 
require_login();
$PAGE->set_url($CFG->wwwroot.'/blocks/learnpeek_shop/edit_businessuser.php');
$PAGE->set_pagelayout('base');
$PAGE->set_title(get_string('businessusers','block_learnpeek_shop'));
$PAGE->set_heading(get_string('businessusers','block_learnpeek_shop'));
// $companyid = iomad::get_my_companyid(context_system::instance());
// $PAGE->set_title('Registration Form - Individual');
$companydata = $DB->get_record('company_users', array('userid' => $userid));
$companyid = $companydata->companyid;
$getcompnayadmin = $DB->get_record_sql("SELECT * FROM {company_users} WHERE companyid = ? AND userid =?",array($companyid,$USER->id));
// echo "<pre>";
// print_r($getcompnayadmin);
// echo "</pre>";
$context = context_user::instance($userid); // User context
$current_user_roles = get_user_roles($context, $userid);
$manag_role = array_column($current_user_roles, 'shortname');


if(is_siteadmin() || $getcompnayadmin->managertype ==1){
  // $PAGE->requires->css(new moodle_url($CFG->wwwroot.'/local/user_registration/style.css'));
  if (!in_array('companydepartmentmanager', $manag_role)) {
    redirect(new moodle_url($CFG->wwwroot.'/local/customlayout/businessusers.php'),'This user does not have the required Business User role.', null, \core\output\notification::NOTIFY_ERROR);
  }

  echo $OUTPUT->header();
  $mform = new edit_business_user();

  if ($mform->is_cancelled()) {
      redirect($CFG->wwwroot.'/local/customlayout/businessusers.php');
  }else if ($fromform = $mform->get_data()) {
    $userdata = new stdClass();
    $userdata->id                  = $fromform->id;
    // $userdata->firstname           = $fromform->firstname;
    // $userdata->lastname            = $fromform->lastname;
    $userdata->company_id          = $fromform->companyid;
    if($fromform->profile_field_user_company_name){
      $userdata->profile_field_user_company_name   = $fromform->profile_field_user_company_name;
    }
    if($fromform->password){
      $userdata->password            = md5($fromform->password);
      $updaterecords = $DB->update_record('user', $userdata);
    } 
    $adcompnayname = save_user_profile_data($userdata);
    if($adcompnayname){
      redirect(new moodle_url($CFG->wwwroot.'/local/customlayout/businessusers.php'),'Records updated', null, \core\output\notification::NOTIFY_SUCCESS);
    }
  } 

  $userdata = $DB->get_record_sql('SELECT * FROM {user} WHERE id = ?',array($userid));
  if($userdata){
      $fielddata = $DB->get_record('user_info_field',array('shortname'=>'user_company_name'));
      $userfielddata = $DB->get_record('user_info_data', array('userid'=>$userid,'fieldid'=>$fielddata->id));
      $data = new stdClass(); 
       $data->id       = $userid;
       $data->username       = $userdata->username;
       $data->firstname      = $userdata->firstname;
       $data->lastname       = $userdata->lastname;
       $data->email          = $userdata->email;
       $data->profile_field_user_company_name      = $userfielddata->data;
  }
  $mform->set_data($data);
  $mform->display();

}else{
  redirect(new moodle_url($CFG->wwwroot.'/my'),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
}



echo "<style>

</style>";
echo $OUTPUT->footer(); 
?>
