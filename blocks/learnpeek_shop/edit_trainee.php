<?php
require_once(dirname(__FILE__) . '/../../config.php');
require_once("edit_traineeform.php");
require_once("locallib.php");
global $DB, $PAGE, $CFG, $USER, $OUTPUT; 
$userid = required_param('id',PARAM_INT); 
require_login();
$PAGE->set_url($CFG->wwwroot.'/blocks/learnpeek_shop/edit_trainee.php');
$PAGE->set_pagelayout('standard');
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

// echo "<pre>";
// print_r($manag_role);
// echo "</pre>";
// echo $manag_role[0];


if(is_siteadmin() || $getcompnayadmin->managertype ==1){
  if($manag_role){
    redirect($CFG->wwwroot.'/user/profile.php?id='.$userid);
  }
  $PAGE->set_heading(get_string('updateuser','block_learnpeek_shop'));
  // $PAGE->requires->css(new moodle_url($CFG->wwwroot.'/local/user_registration/style.css'));
  echo $OUTPUT->header();
  $mform = new create_business_trainee();

  if ($mform->is_cancelled()) {
      redirect($CFG->wwwroot.'/local/customlayout/userslisting.php');
  }else if ($fromform = $mform->get_data()) {
      // echo "<pre>";
      // print_r($fromform);
      // echo "</pre>";
      // die();
      if($fromform->parent_id){
            $parentdata = $DB->get_record_sql('SELECT * FROM {business_user_child} WHERE child_id = ?',array($userid));
          if($parentdata){
            $userdata = new stdClass();
            $userdata->id             = $parentdata->id;
            $userdata->company_id     = $fromform->companyid;
            $userdata->parent_id      = $fromform->parent_id;
            $userdata->child_id       = $fromform->id;
            $userdata->old_parent_id  = $parentdata->parent_id;
            $userdata->updated_date   = time();
            $updaterecords = $DB->update_record('business_user_child', $userdata);
        }else{
          $companydata = $DB->get_record('company_users', array('userid' => $fromform->id));
          if($companydata){
            $businesusrchild = new stdclass();
            $businesusrchild->company_id    = $companydata->companyid;
            $businesusrchild->parent_id     = $fromform->parent_id;
            $businesusrchild->child_id      = $fromform->id;
            $businesusrchild->created_date  = time();
            $businesusrchildid = $DB->insert_record('business_user_child', $businesusrchild);
          }
        }
      } 
      $userdata = new stdClass();
      $userdata->id         = $fromform->id;
      // $userdata->firstname  = $fromform->firstname;
      // $userdata->lastname   = $fromform->lastname;
      if($fromform->password){
        $userdata->password            = md5($fromform->password);
        $updaterecords        = $DB->update_record('user', $userdata);
      }
      redirect(new moodle_url($CFG->wwwroot.'/local/customlayout/userslisting.php'),'Records updated', null, \core\output\notification::NOTIFY_SUCCESS);
  } 

  $userdata = $DB->get_record_sql('SELECT * FROM {user} WHERE id = ?',array($userid));
  if($userdata){
      $parentdata = $DB->get_record_sql('SELECT * FROM {business_user_child} WHERE child_id = ?',array($userid));
      $data = new stdClass(); 
       $data->id       = $userid;
       $data->username       = $userdata->username;
       $data->firstname      = $userdata->firstname;
       $data->lastname       = $userdata->lastname;
       $data->email          = $userdata->email;
       $data->parent_id      = $parentdata->parent_id;
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
