<?php
function generatecourseshortname($coursename, $randamno){
   global$DB, $CFG;
    $coursename=str_replace(" ","",trim($coursename));
    $courseshortname=strtolower($coursename).'-'.$randamno;
    $error = false;
    if ( !$error && $DB->record_exists('course', array('shortname' => $courseshortname))){
        $randamno = rand(100,1000);
        $courseshortname = generatecourseshortname($coursename,$randamno);
    }
    return $courseshortname;
}
function enrolteacherCourse($courseid, $userid, $roleid,$endtime,$startime) {  
    global $DB, $CFG;
    $query = 'SELECT * FROM {enrol} WHERE enrol = "manual" AND courseid = '.$courseid;
    $enrollmentID = $DB->get_record_sql($query);
    if(!empty($enrollmentID->id)) {
        if (!$DB->record_exists('user_enrolments', array('enrolid'=>$enrollmentID->id, 'userid'=>$userid))) {
            $userenrol = new stdClass();
            $userenrol->status = 0;
            $userenrol->userid = $userid;
            $userenrol->enrolid = $enrollmentID->id;
            $userenrol->timestart  = $startime;
            $userenrol->timeend = $endtime;
            $userenrol->modifierid  = 2;
            $userenrol->timecreated  = time();
            $userenrol->timemodified  = time();
            $enrol_manual = enrol_get_plugin('manual');
            $enrol_manual->enrol_user($enrollmentID, $userid, $roleid, $userenrol->timestart, $userenrol->timeend);
            return true;
        } else {
            $context = context_course::instance($courseid);
            $contextid= $context->id;
            role_assign($roleid, $userid, $contextid);
            return true;
        }
    } else {
    	// echo "\n manual Enrollment not found for course id: {$courseid}";
    }
}
function create_event_course($eventdata,$editoroptions){
	global$DB, $CFG;
	// $CFG->dirroot.'/course/lib.php';
	require_once($CFG->dirroot.'/course/lib.php');
	require_once($CFG->libdir."/moodlelib.php");
	$coursedata = new stdClass();
	$coursedata->fullname = $eventdata->fullname;
	$coursedata->category = $eventdata->category;
	$coursedata->summary = $eventdata->summary;
	$randamn = rand(100,1000);
	$coursedata->shortname = generatecourseshortname($eventdata->fullname,$randamn);
	$coursedata->idnumber = '';
	$coursedata->idnumber = '';
	$coursedata->visible = 1;
	$coursedata->summaryformat = 1;
	$coursedata->overviewfiles_filemanager = $eventdata->product_image;
	$coursedata->format = 'topics';
	$coursedata->showgrades = 0;
	$coursedata->newsitems = 0;
	$coursedata->maxbytes = 2122317824;
	$coursedata->enablecompletion = 1;
	$coursedata->showactivitydates = 1;
	$coursedata->startdate = time();
	$coursedata->enddate = 0;
	$course = create_course($coursedata, NULL);
	if($course){
		$role = $DB->get_record('role', array('shortname'=>'editingteacher'));
		 // $allcompanymanageruser = $DB->get_records_sql("SELECT * FROM {company_users} WHERE companyid = ? AND managertype =1",array($eventdata->company_id));
		 $allcompanymanageruser = $DB->get_records_sql("SELECT cu.* FROM {company_users} AS cu INNER JOIN {user} AS u ON u.id = cu.userid WHERE cu.companyid = ? AND cu.managertype = 1 AND u.deleted = 0 AND u.suspended = 0;",array($eventdata->company_id));
		 foreach ($allcompanymanageruser as $manageruser) {
		 	enrolteacherCourse($course->id, $manageruser->userid,$role->id,0,time());
		 }
		purge_all_caches();
	}
	return $course->id;
}


?>
