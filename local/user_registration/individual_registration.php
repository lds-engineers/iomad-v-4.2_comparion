<?php
global $DB, $PAGE, $CFG, $OUTPUT; 
require_once('../../config.php');
require_once("$CFG->libdir/filelib.php");
require_once("individual_registrationform.php");
require_once("user_registration_email.php");
require_once("locallib.php");
require_once($CFG->dirroot . '/local/learnpeek_email_template/locallib.php');

$pageurl = $CFG->wwwroot."/local/user_registration/individual_registration.php";
$PAGE->requires->jquery();
$PAGE->set_url($CFG->wwwroot.'/local/user_registration/individual_registration.php');
$PAGE->set_pagelayout('standard');
$PAGE->set_title('Registration Form - Individual');
$PAGE->set_heading('<div>' . get_string('individual_account_registration_heading', 'local_user_registration') . '</div>');
$PAGE->requires->css(new moodle_url($CFG->wwwroot.'/local/user_registration/style.css'));
echo '<script src="https://www.google.com/recaptcha/api.js" async defer></script>';
if (isloggedin()){
    redirect($CFG->wwwroot.'/my');
    exit();
}
$companyaccess = check_company_access_restriction();
if($companyaccess){
  redirect($CFG->wwwroot . '/login/index.php');
}


echo $OUTPUT->header();
$current_host = $_SERVER['HTTP_HOST'];
$getcompany = $DB->get_record_sql("SELECT id,hostname FROM {company} WHERE hostname LIKE ?", array($current_host));
if($getcompany){
  $company_id = $getcompany->id;
}else{
  redirect($CFG->wwwroot.'/login/index.php', 'Login with Company Url', null, \core\output\notification::NOTIFY_SUCCESS);
}
$mform = new registration($pageurl, $args);
if ($mform->is_cancelled()) {
    redirect($CFG->wwwroot.'/local/user_registration/index.php');
}else if ($fromform = $mform->get_data()) {

    if(!isset($_POST['g-recaptcha-response'])) {
      redirect($CFG->wwwroot.'/local/user_registration/individual_registration.php', 'Something is wrong!', null, \core\output\notification::NOTIFY_WARNING);
      return;
    }

    // Google reCAPTCHA API keys settings 
    $secretKey  = '6Lf9XDsrAAAAAEYrCWc7sny3rfgAJ3lPGzTZcknK'; 
    $admin = get_admin();
    $recipientEmail = $admin->email; 
    $verifyResponse = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret='.$secretKey.'&response='.$_POST['g-recaptcha-response']);
    $responseData = json_decode($verifyResponse);

    if(!$responseData->success){ 
      redirect($CFG->wwwroot.'/local/user_registration/individual_registration.php', 'Something is wrong!', null, \core\output\notification::NOTIFY_WARNING);
      return;
    } 

  	$userdata = new stdClass();
    $userdata->username 	   = strtolower($fromform->user_name);
    $userdata->firstname 	   = $fromform->first_name;
    $userdata->lastname 	   = $fromform->last_name;
    $userdata->email 		     = strtolower($fromform->email);
    $userdata->company_id    = $company_id;
    $userdata->password 	   = $fromform->password;
  	$data = create_individual_user($userdata);
  	if($data){
          $userdata = $DB->get_record("user", array("id"=>$data));
          if(!empty($userdata)){

            /*** Start Create User in Wordpress*******/

            $validatecompany = $DB->get_record_sql(
                "SELECT shortname FROM {company} 
                    WHERE id = ?",
                    [$company_id]
                );

            if ($validatecompany) {
                if (in_array($validatecompany->shortname, ['PDH STAR'])) {
          
                    $wp_siteurl = get_config(null, "main_site_url{$company_id}");

                    // WordPress AJAX endpoint
                    $ssovalidateurl = rtrim($wp_siteurl, '/') . '/wp-json/custom/v1/register';

                    //Get and send the child parent user
                    $childParentMdlId = 0;
                    $childParentWplId = 0;

                    $childParent = $DB->get_record(
                        'business_user_child',
                        ['child_id' => $userdata->id]
                    );

                    if ($childParent) {

                        $childParentMdlId = (int)$childParent->parent_id;

                        $parentRemoteUser = $DB->get_record(
                            'remote_user_info',
                            ['userid' => $childParentMdlId]
                        );

                        if ($parentRemoteUser) {
                            $childParentWplId = (int)$parentRemoteUser->wp_userid;
                        }
                    }

                    // Business trainers

                    $trainerids = "";
                    $user_companyname = "";
                    $is_verified = 1;
                    $userrole = "individual";

                    // get scret tokenecho
                    $ssowstoken = get_config('local_user_registration', 'sso_shared_secret');

                    //user registration validation
                    $ssoregdisabled = get_config('local_user_registration', 'sso_registration_disable');

                    if ($ssoregdisabled) {
                        $disabledregistartion = 1;
                    }else{
                        $disabledregistartion = 0;

                    }
                    
                    // Prepare data to send
                    $request_data = [
                        'user_firstname'        => $userdata->firstname,
                        'user_lastname'         => $userdata->lastname,
                        'username'              => strtolower($userdata->username),
                        'childParentMdlId'      => $childParentMdlId,
                        'childParentWplId'      => $childParentWplId,
                        'user_email'            => strtolower($userdata->email),
                        'lms_userid'            => $userdata->id,
                        'lms_password'          => md5($fromform->password),
                        'userrole'              => $userrole,
                        'user_trainee'          => $trainerids,
                        'user_companyname'      => $user_companyname,
                        'auth_token'            => $ssowstoken,
                        'is_verified'           => $is_verified,
                        'disabled_registartion' => $disabledregistartion,
                        'moodlewsrestformat'=> 'json',
                    ];

                    $curl = new curl();
                    $response = $curl->post($ssovalidateurl, $request_data);
                    $result = json_decode($response);
                    if (!empty($result) && isset($result->status) && $result->status == 1) {
                        $status = "Account Created IN wordpress";
                    }
                }
            }
          
            /*** End Create User in Wordpress*******/
            user_registration_email_to_user($company_id, $userdata->id);
            trainee_registration_email_to_whitelable($company_id, $userdata->id);
            complete_user_login($userdata);
              \core\session\manager::apply_concurrent_login_limit($userdata->id, session_id());
            redirect(new moodle_url($CFG->wwwroot.'/my/index.php'));
          } else {
            redirect(new moodle_url('/'));
          }
  		// redirect($CFG->wwwroot.'/login/index.php', 'Individual  user account created successfully', null, \core\output\notification::NOTIFY_SUCCESS);
  	}
}  
echo '<div class="row prnt"><div class="col-md-6">
<h1 class="title">' . get_string('individual_account_title', 'local_user_registration') . '</h1>
  <p>' . get_string('individual_account_description', 'local_user_registration') . '</p>
  <div class="cst_parent_img">
     <img src="'.$CFG->wwwroot.'/local/user_registration/src/user-icon.webp"/>
  </div>
     <p>
      <p>' . get_string('register_as_business_text', 'local_user_registration', $CFG->wwwroot) . '</p>
   </div>
<div class="col-md-6 cst_indv_form">';
$mform->display();
echo "</div></div>";
echo "<style>
.primary-navigation [data-key='home']{
  display:none;
}
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




