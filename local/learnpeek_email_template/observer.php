<?php
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/local/iomad/lib/company.php');
require_once($CFG->dirroot . '/local/learnpeek_email_template/lib.php');

function course_completed_email_template(\core\event\course_completed $event){
	global $DB, $USER,$CFG;
 	$courseid = $event->courseid;
 	$userid = $event->relateduserid;
 	$userdata = $DB->get_record("user",array("id"=>$userid));

 	$companyinfo = $DB->get_record('company_users', ['userid' => $userid]);
 	$companyid = $companyinfo->companyid;
 	// $companyid = iomad::get_my_companyid(context_system::instance());
	  $gettemplate = $DB->get_record_sql("SELECT * FROM {custom_email_template} WHERE company_id = ? AND email_template = ?",array($companyid,'course-completion'));
	  if($gettemplate){
	    $emailtemplatedata_user = $gettemplate->email_message;
	  }else{
	    $get_deflt_template = $DB->get_record_sql("SELECT * FROM {custom_email_template} WHERE company_id = ? AND email_template = ?",array(0,'course-completion'));
	    if($get_deflt_template){
	      $emailtemplatedata_user = $get_deflt_template->email_message;
	    }else{
	      $emailtemplatedata_user = 0;
	    }
	  }
	    //get compnay logo
	  $comlogodetails = $DB->get_record_sql('SELECT * FROM {company_logo} WHERE company_id = ?',array($companyid));
	  if($comlogodetails){
	    $companylogo = $DB->get_record_sql("SELECT * FROM {files} WHERE contextid= 1 AND component='landingpage' AND itemid=? AND filesize > 0 AND filearea = ?", array($comlogodetails->id,'logo_image'));
	    $logourl = $CFG->wwwroot.'/blocks/custom_mycourse/file.php?id='.$companylogo->pathnamehash;
	    // $company_logo = "<img src='" . $logourl . "' style='width:125px;max-height:150px;object-fit:cover;'>";
	    $company_logo = "<img src='{$logourl}' width='125' height='auto' style='display:block;border:0;'>";
	  }else{
	    $company_logo = "";
	  }
	  $coursedata = $DB->get_record("course",array("id"=>$courseid));
	  // print_r($coursedata);

	 $user_display_name = fullname($userdata);
	 $user_email = $userdata->email;
	 $course_name = $coursedata->fullname;
	 // $checkedcoursescorm = $DB->get_record("scorm",array("course"=>$courseid));
	 // if($checkedcoursescorm){
	 //    $scorm_track = $DB->get_record_sql("SELECT * FROM {scorm_scoes_track} WHERE userid = ? AND scormid =? AND element LIKE ?",array($userdata->id,$checkedcoursescorm->id,'cmi.core.score.raw'));
	 //    if($scorm_track->value >= 70 ){
	 //      $course_grade = $scorm_track->value .' (PASSED)';
	 //    }else{
	 //      $course_grade = $scorm_track->value .' (FAILED)';
	 //    }
	 // }else{
	 //    $course_grade = "N/A";
	 // }

	// $course_grade = "N/A";
    // $coursegradeitem = $DB->get_record('grade_items', [
    //     'courseid' => $courseid,
    //     'itemtype' => 'course'
    // ]);
    // if($coursegradeitem){

    //     $usergrade = $DB->get_record('grade_grades', [
    //         'userid' => $userdata->id,
    //         'itemid' => $coursegradeitem->id
    //     ]);
       

    //     if($usergrade){
    //         $gradep = 0;
    //         if($usergrade->finalgrade){
    //             $gradep = $usergrade->finalgrade;
    //         }

    //         if($gradep >= 70 ){
    //           $finalgrade = (float)$usergrade->finalgrade;
    //           // $course_grade = round($finalgrade, 2) . " (PASSED)";
    //           $course_grade = round($gradep, 2) . "% (PASSED)";

    //         }else{
    //           $finalgrade = (float)$usergrade->finalgrade;
    //            $course_grade = round($gradep, 2) . "% (FAILED)";
    //         }        
    //     } else {
    //           $finalgrade = (float)$usergrade->finalgrade;
    //            $course_grade = round($gradep, 2) . "%";

    //     }
    // }

	$course_grade = "N/A";
	$coursegradeitem = $DB->get_record('grade_items', [
	  'courseid' => $courseid,
	  'itemtype' => 'course'
	]);

	if($coursegradeitem){

	  $usergrade = $DB->get_record('grade_grades', [
	      'userid' => $userdata->id,
	      'itemid' => $coursegradeitem->id
	  ]);     

	  if($usergrade){
	      $gradep = 0;
	      if($usergrade->finalgrade){
	          $gradep = $usergrade->finalgrade;
	      }

	      if($usergrade->rawgrademax){
	        $gradem = $usergrade->rawgrademax;
	        $gradep = ($usergrade->finalgrade / $gradem) * 100;
	      }



	      if($gradep >= 70 ){
	        $finalgrade = (float)$usergrade->finalgrade;
	        // $course_grade = round($finalgrade, 2) . " (PASSED)";
	        $course_grade = round($gradep, 2) . "% (PASSED)";

	      }else{
	        $finalgrade = (float)$usergrade->finalgrade;
	         $course_grade = round($gradep, 2) . "% (FAILED)";
	      }        
	  } else {
	        $finalgrade = (float)$usergrade->finalgrade;
	        $course_grade = round($gradep, 2) . "%";

	  }
	}

	$placeholders = ['{{user_display_name}}','{{course_name}}','{{company_logo}}','{{course_grade}}','{{user_email}}'];
	$replacements = [$user_display_name, $course_name, $company_logo, $course_grade , $user_email];
	$email_body = str_replace($placeholders, $replacements, $emailtemplatedata_user);

	$messagehtml =   "<!DOCTYPE html>
	                            <html>
	                                <head>
	                                    <title>Mail</title>
	                                    <link rel='stylesheet' type='text/css' href='https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css'>
	                                </head>
	                                <body>".$email_body."
	                                </body>
	                            </html>";  
	// $fromUser = core_user::get_support_user();

	$getcompany_wu = $DB->get_record_sql(
        "SELECT * FROM {company_users} WHERE companyid = ? AND managertype = ?",
        [$companyid, 1]
    );
    if ($getcompany_wu) {
        $whitelabeluser = $DB->get_record('user', ['id' => $getcompany_wu->userid]);
        $fromUser = $whitelabeluser ? fullname($whitelabeluser) : core_user::get_support_user();
    } else {
        $fromUser = core_user::get_support_user();
    }
    // echo $fromUser;
    $messagehtml;

	  $subject = $gettemplate->email_subject;
	  $emailuser = (object) [];
	  // $emailuser->email = $userdata->email;
	   if(!empty($gettemplate->destination_email)){
	    $emailuser->email = $gettemplate->destination_email;
	  } else {
	    $emailuser->email = $userdata->email;
	  }
	  // $emailuser->email = "suneet.sharma695@gmail.com";
	  $emailuser->firstname = $userdata->firstname;
	  $emailuser->lastname= $userdata->lastname;
	  $emailuser->maildisplay = true;
	  $emailuser->mailformat = 1; // 0 (zero) text-only emails, 1 (one) for HTML/Text emails.
	  $emailuser->id = 1;
	  $emailuser->firstnamephonetic = false;
	  $emailuser->lastnamephonetic = false;
	  $emailuser->middlename = false;
	  $emailuser->username = false;
	  $emailuser->alternatename = false;
	  if($emailtemplatedata_user){
	    // email_to_user($emailuser, $fromUser, $subject, $message = '', $messagehtml, '', '', true, '', '', 79, $companyid);
	    email_learnpeek_to_user($emailuser, $fromUser, $subject, $message = '' , $messagehtml, '', '', true, '', '', 79, $companyid);
	    // echo "email send  $userdata->email #compayid $companyid <br>";

	  }

	// uday
	$sitehomecompany = $DB->get_record_sql("SELECT shortname FROM {company} WHERE id = ?",[$companyid]);
	if (in_array($sitehomecompany->shortname, ['PDH STAR'])) {
		$fromUser = core_user::get_support_user();
		$whitelabeluser = null;
		if ($getcompany_wu = $DB->get_record_sql(
		    "SELECT * FROM {company_users} WHERE companyid = ? AND managertype = ?", 
		    [$companyid, 1]
		)) {
		    if ($whitelabeluser = $DB->get_record('user', ['id' => $getcompany_wu->userid])) {
		        $fromUser = fullname($whitelabeluser);
	
				$emailuser->email = $whitelabeluser->email;
				// $emailuser->email = $whitelabeluser->email;
				$emailuser->firstname = $whitelabeluser->firstname;
				$emailuser->lastname= $whitelabeluser->lastname;
		    }
		}
		if($whitelabeluser){
			email_learnpeek_to_user($emailuser, $fromUser, $subject, $message = '' , $messagehtml, '', '', true, '', '', 79, $companyid);
		}
	}
}


function user_enrollment_status(\core\event\user_enrolment_created $event) {

	$courseid = $event->courseid;
	$userid = $event->relateduserid ;
	delete_user_data_in_course($userid, $courseid);
}