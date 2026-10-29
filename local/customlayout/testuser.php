<?php
require_once(dirname(__FILE__) . '/../../config.php');
global $DB,$USER;
require_once($CFG->dirroot. '/local/manage_course/locallib.php');
$role = $DB->get_record('role', array('shortname'=>'editingteacher'));
$getcompnatlist = $DB->get_records_sql("SELECT * FROM {company_users} WHERE userid = ? AND managertype = 1",array($USER->id));
foreach ($getcompnatlist as $company) {
	$companyid = $company->companyid;
	$compnaycourses = $DB->get_records_sql("SELECT cc.* 
												FROM {company_course} as cc 
												INNER JOIN {course} as c ON c.id = cc.courseid 
												WHERE cc.companyid = ?",array($companyid));
	if($compnaycourses){
		foreach ($compnaycourses as $compnaycourse) {
			 $context = context_course::instance($compnaycourse->courseid);
			$coursealreadyassigned =  $DB->record_exists('role_assignments', array('userid'=>$USER->id, 'roleid'=>$role->id, 'contextid'=>$context->id));
			if(!$coursealreadyassigned){
				enrolteacherCourse($compnaycourse->courseid, $USER->id, $role->id,0,time());
			}
		}
	}
}



			//  $
			//  echo $compnaycourse->courseid;
			//  echo "<pre>";
			// print_r($context);
			// echo "</pre>";
			// echo $context->id;








// $enrolledroleteacher = enrolteacherCourse($USER->id, $courseid = 235);
// $role = $DB->get_record('role', array('shortname'=>'editingteacher'));
// $enrolledroleteacher = enrolteacherCourse(262, $USER->id,$role->id,0,time());
// echo "<pre>";
// var_dump($enrolledroleteacher);
// echo "</pre>";