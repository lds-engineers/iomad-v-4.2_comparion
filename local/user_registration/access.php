<?php
require_once('../../config.php');
global $CFG,$USER,$DB,$PAGE;
$usertokenkey = $_REQUEST['usertokenkey'];
$userid = $_REQUEST['accesstoken'];
$productid = $_REQUEST['moodle_productid'];
// $verified = $_REQUEST['verified'];
$validatetoken = $DB->get_record('user_private_key', array('script' => "sso_lms_learnpeek", 'userid' => $userid, 'value' => $usertokenkey));
if($productid){
	// die("inside if");
	$checkproducstatus = $DB->get_record_sql("SELECT i.*,it.invoiceableitemid FROM {invoiceitem} as it INNER JOIN {invoice} as i ON it.invoiceid =i.id WHERE it.invoiceableitemid=? AND i.userid =? AND i.status='p'",[$productid,$userid]);
	if($checkproducstatus){
		$producttype = $DB->get_record_sql("SELECT  COUNT(*) AS itemcount FROM {course_shopsettings_courses} WHERE itemid=?",[$productid]);
		$itemcount = $producttype->itemcount;
		if($itemcount == 1) {
		$mappedcourse = $DB->get_record("course_shopsettings_courses",array("itemid"=>$productid));
			$courseid = $mappedcourse->courseid;
			$lunchurl = $CFG->wwwroot.'/course/view.php?id='.$courseid;
		}else if ($itemcount > 1) {
			$lunchurl = $CFG->wwwroot.'/my/courses.php';
		}
	}else{
		$lunchurl = $CFG->wwwroot.'/blocks/learnpeek_shop/product.php?itemid='.$productid;
	}

	// $checkuserenrollement = "SELECT ue.id AS userenrolid, e.id AS enrolid, c.id AS courseid
	//     FROM {user_enrolments} ue
	//     JOIN {enrol} e ON e.id = ue.enrolid
	//     JOIN {course} c ON c.id = e.courseid
	//     WHERE ue.userid = :userid
	//       AND e.courseid = :courseid
	// ";
	// $params = [
	//     'userid' => $userid,
	//     'courseid' => $courseid
	// ];

	// $record = $DB->get_record_sql($checkuserenrollement, $params);

	// if ($record) {
	//     echo "User enrolled";
	// } else {
	//     echo "User NOT enrolled";
	// }
}else if(!empty($_REQUEST['lmscourseid'])){
	$lunchurl =$CFG->wwwroot."/course/view.php?id=".$_REQUEST['lmscourseid'];
}else{
	$lunchurl =$CFG->wwwroot.'/my';
}
if($validatetoken){
	$userdata = $DB->get_record("user",array("id"=>$userid));
	if($userdata){
		if($userdata->suspended){
			redirect(new moodle_url($CFG->wwwroot.'/my'),'Access denied. This account has been suspended.', null, \core\output\notification::NOTIFY_ERROR);
		}
	 	$verified = $DB->get_record('sso_validation', array('user_id' => $userid, 'required_validation' => 1));
		if(!$verified){
			// $verify = true;
			// if ($verify) {
				// Before continuing, send user to verification page
				$params = [
					'userid' => $userid,
					'usertokenkey' => $usertokenkey,
					'productid' => $productid,
					'lunchurl' => urlencode($lunchurl),
					'sourceurl' => urlencode($_SERVER['HTTP_REFERER']), // WordPress page that sent this request
				];
			
				$verifyurl = new moodle_url('/local/user_registration/user_verification.php', $params);
				redirect($verifyurl);
			// }
		 }
	}
	// echo "userid - $userid<br>";
	// echo "usertokenkey - $usertokenkey<br>";
	// echo "productid - $productid<br>";
	// echo "lunchurl - $lunchurl<br>";
	// die("insdie if");
	// delete_user_key('sso_lms_learnpeek', $userid);
	$DB->delete_records('user_private_key', array('script' => 'sso_lms_learnpeek', 'userid' => $userid,'value' => $usertokenkey));
	// $userdata = $DB->get_record("user",array("id"=>$userid));
	if($userid == $USER->id){
		redirect($lunchurl);
	}else{
		if (isloggedin() && !isguestuser()) {
	        // require_logout(false); // IMPORTANT
	        if (isset($SESSION->basketid)) {
	            unset($SESSION->basketid);
	        }
	    }
		complete_user_login($userdata);
    	\core\session\manager::apply_concurrent_login_limit($userdata->id, session_id());
		redirect($lunchurl);
	}
	
}else{
	// die("insdie else");
	redirect(new moodle_url($CFG->wwwroot.'/my'),'Access denied', null, \core\output\notification::NOTIFY_ERROR); 
}