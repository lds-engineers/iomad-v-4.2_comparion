<?php
require_once("../../config.php");
require_once($CFG->dirroot. '/mod/scorm/locallib.php');
global $CFG,$USER,$DB;
// require_login();

$usertokenkey = $_REQUEST['usertokenkey'];
// $usertokenkey = "ce4d23ef737eb770e697d901674efbf2";
$userid = $_REQUEST['accesstoken'];
$courseid = $_REQUEST['accessvailidc'];
$launchurl = urldecode($_REQUEST['launchurl']);

$validatetoken = $DB->get_record('user_private_key', array('script' => "sso_lms_learnpeek", 'userid' => $userid, 'value' => $usertokenkey));

if($validatetoken){
	// delete_user_key('sso_lms_learnpeek', $userid);
	$DB->delete_records('user_private_key', array('script' => 'sso_lms_learnpeek', 'userid' => $userid,'value' => $usertokenkey));
	$userdata = $DB->get_record("user",array("id"=>$userid));
	if($userid == $USER->id){
		// echo "user login"; 
		// die();
		redirect($launchurl);
	}else{
		// echo "user not login"; 
		// die(); 
		complete_user_login($userdata);
    \core\session\manager::apply_concurrent_login_limit($userdata->id, session_id());
		redirect($launchurl);
	}
	
}else{
	redirect(new moodle_url($CFG->wwwroot.'/course/view.php?id='.$courseid),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
}