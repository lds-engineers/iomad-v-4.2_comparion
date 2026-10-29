<?php
require_once('../../config.php');
require_login();
global $USER;
$lpcourseid = optional_param('lpcourseid', 0, PARAM_INT);
$companyid = iomad::get_my_companyid(context_system::instance());
$userinfo = $DB->get_record_sql("SELECT * FROM {company_users} WHERE companyid = ? and userid = ?",[$companyid,$USER->id]);
$wp_siteurl = get_config(null, "main_site_url{$companyid}");
$userdata = $DB->get_record('user', array('id' => $USER->id), '*', MUST_EXIST);
$userName = $userdata->username;
$trainerids = "";
$user_companyname = "";
if($userinfo->managertype == 2){
    $businesstrainers = $DB->get_records_sql("SELECT u.*,buc.parent_id,buc.created_date,buc.company_id FROM {business_user_child} as buc INNER JOIN {user} as u ON buc.child_id = u.id WHERE parent_id= ? AND company_id = ? AND u.suspended = 0 AND u.deleted =0", array($USER->id, $companyid));
    if($businesstrainers){
        $trainer_ids = array_keys($businesstrainers);
        $trainerids = implode(',', $trainer_ids);
    }
    $userrole = "business";
    $user_companyname = $USER->profile['user_company_name'];
}else{
    $userrole = "individual";
}
// $verified = $DB->get_record('sso_validation', array('user_id' => $USER->id, 'required_validation' => 1));
// if($verified){
//     $is_verified = 1;
// }else{
//     $is_verified = 0;
// }
$is_verified = 1;
// get scret tokenecho
$ssowstoken = get_config('local_user_registration', 'sso_shared_secret');

//user registration validation
$ssoregdisabled = get_config('local_user_registration', 'sso_registration_disable');
if ($ssoregdisabled) {
    $disabledregistartion = 1;
}else{
    $disabledregistartion = 0;

}

/**
 * Task: Get and send the child parent user
 */
$childParentMdlId = $DB->get_record_sql("SELECT * FROM {business_user_child} WHERE `child_id` = ?",array($USER->id));
$childParentMdlId = $childParentMdlId->parent_id ?? 0;

$childParentWplId = $DB->get_record_sql("SELECT * FROM {remote_user_info} WHERE `userid` = ?",array($childParentMdlId));
$childParentWplId = $childParentWplId->wp_userid;

// WordPress AJAX endpoint
$ssovalidateurl = rtrim($wp_siteurl, '/') . '/wp-json/custom/v1/register';
$getuserpassword = $DB->get_record_sql("SELECT * FROM {sso_user_password} WHERE userid = ?",array($USER->id));
if($getuserpassword){
    $userpassword = $getuserpassword->password;
}else{
    $userpassword = "";
}
// Prepare data to send
$request_data = [
    'user_firstname'        => $USER->firstname,
    'user_lastname'         => $USER->lastname,
    'username'              => $userName,
    'childParentMdlId'      => $childParentMdlId,
    'childParentWplId'      => $childParentWplId,
    'user_email'            => $USER->email,
    'lms_userid'            => $USER->id,
    'lms_password'          => $userpassword,
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

if($lpcourseid){
    $redirect_url = $result->redirect_url."&lpcourseid=$lpcourseid";
}else{
    $redirect_url = $result->redirect_url;
}
// Handle result
if (!empty($result) && isset($result->status) && $result->status == 1) {
    //Success  redirect to WordPress site
    redirect($redirect_url);
} else {
    // Failure - show Moodle notification and go to dashboard
    $errormessage = !empty($result->message) ? $result->message : 'SSO Validation failed. Please try again later.';
    redirect( new moodle_url('/my'), $errormessage, 3 , \core\output\notification::NOTIFY_ERROR);
}


