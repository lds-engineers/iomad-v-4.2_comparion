<?php
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/../iomad_company_admin/lib.php');
require_once("$CFG->libdir/filelib.php");
require_once("create_traineeform.php");
require_once("locallib.php");
require_once($CFG->dirroot . '/local/learnpeek_email_template/locallib.php');
global $DB, $PAGE, $CFG, $USER, $OUTPUT; 

$companyid = iomad::get_my_companyid(context_system::instance());
$context_user = context_user::instance($USER->id);
$current_user_roles = get_user_roles($context_user, $USER->id);

$current_user_roles = array_values($current_user_roles);
$hascompanyreporterrole = (isset($current_user_roles[0]) && $current_user_roles[0]->shortname == 'companyreporter');

$getcompnayadmin = $DB->get_record_sql("SELECT * FROM {company_users} WHERE companyid = ? AND userid =?",array($companyid,$USER->id));

if (!is_siteadmin() && (!$getcompnayadmin || ($getcompnayadmin->managertype != 1 && $getcompnayadmin->managertype != 2)) &&!$hascompanyreporterrole) {
    redirect(new moodle_url($CFG->wwwroot.'/my'),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
     die;
}


require_login();
$PAGE->set_url($CFG->wwwroot.'/blocks/learnpeek_shop/create_trainee.php');
$PAGE->set_pagelayout('standard');
// $PAGE->set_title('Registration Form - Individual');
$PAGE->set_heading('<div>Create New Trainee</div>');
$PAGE->requires->css(new moodle_url($CFG->wwwroot.'/local/user_registration/style.css'));
echo $OUTPUT->header();

$manag_role = array_column($current_user_roles, 'shortname');
$mform = new create_business_trainee();
if ($mform->is_cancelled()) {
    if(in_array('companyreporter', $manag_role)) {
      redirect($CFG->wwwroot.'/blocks/learnpeek_shop/manage_department_user.php');
    } else {
      redirect($CFG->wwwroot.'/blocks/learnpeek_shop/manage_business_user.php');
    }
}else if ($fromform = $mform->get_data()) {
  	$userdata = new stdClass();
    $userdata->username 	   = strtolower($fromform->user_name);
    $userdata->firstname 	   = $fromform->first_name;
    $userdata->lastname 	   = $fromform->last_name;
    $userdata->email 		   = strtolower($fromform->email);
    $userdata->company_id      = $companyid;
    $userdata->parent_id       = $USER->id;
    $userdata->password 	   = $fromform->password;
    $userdata->role      = $manag_role;
  	$data = create_business_trainee($userdata);
  	if($data){
      $newuser = $DB->get_record('user', ['id' => $data]);
      if ($newuser) {
          // send trainee registration email
          trainee_registration_email_to_user($companyid, $newuser->id, $userdata->password);
      }
      if(in_array('companyreporter', $manag_role)) {
        redirect(new moodle_url($CFG->wwwroot.'/blocks/learnpeek_shop/manage_department_user.php'),'Records Created', null, \core\output\notification::NOTIFY_SUCCESS);
      } else {
        redirect(new moodle_url($CFG->wwwroot.'/blocks/learnpeek_shop/manage_business_user.php'),'Records Created', null, \core\output\notification::NOTIFY_SUCCESS);
      } 
  	}
}  

$mform->display();


echo "<style>
.form-control {
  width: 80% !important;
}
.page-header-headings {
  color: #fff;
}

.cst_parent_img {
  position: relative;
  display: flex;
  margin: 26px auto 20px;
  width: 120px;
  height: 120px;
  align-items: center;
  border: 2px solid #202340;
  line-height: 100px;
  border-radius: 50%;
  padding: 25px;
}

.cst_parent_img img {
  width: 100%;
}

h1.cst_parent_img img {
  width: 100%;
}

h1.title {
  font-weight: normal;
  margin: 20px 0;
}

.cst_indv_form form {
  box-shadow: rgba(99, 99, 99, 0.2) 0px 2px 8px 0px;
  background-color: #f2f3f7;
}

.fitem {
  padding: 0 10px;
}

.fdescription.required {
  padding: 11px;
}

</style>";
echo $OUTPUT->footer(); 
?>
