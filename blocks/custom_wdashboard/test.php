<?php
require_once('../../config.php');
global $DB, $CFG, $USER, $PAGE;
use core_completion\progress;
require_login();
$userid = optional_param('userid', $USER->id, PARAM_INT);
if(!is_siteadmin()){
	$userid = $USER->id;  // Owner of the page
}

$strmymoodle = get_string('certificates', 'local_customlayout');
$PAGE->set_title($strmymoodle);
$PAGE->set_heading($strmymoodle);


 
// $enrolled_courses = $DB->get_records_sql("SELECT DISTINCT c.id, c.fullname FROM {user_enrolments} ue JOIN {enrol} e ON ue.enrolid = e.id JOIN {course} c ON e.courseid = c.id WHERE ue.userid = :userid", array('userid' => $userid));

$enrolled_courses = enrol_get_users_courses($USER->id);
$customcert = $DB->get_record('modules', array("name"=>"customcert"));    
$iomadcert = $DB->get_record('modules', array("name"=>"iomadcertificate"));
$scorm_md = $DB->get_record('modules', array("name"=>"scorm"));

$html = '<div class="parent-conatiner d-flex flex-wrap">';
foreach ($enrolled_courses as $course) {
	$percentage = progress::get_course_progress_percentage($course, $USER->id);
	$coursecompleted = $DB->get_record_sql("SELECT * FROM {course_completions} WHERE  userid = ? AND course = ? AND timecompleted IS NOT NULL", array($USER->id ,$course->id));
	if ($percentage === 100 || $coursecompleted) {
		$cm = $DB->get_records('course_modules', array("course"=>$course->id , "module"=>$customcert->id, "deletioninprogress"=>0));   
        $icm = $DB->get_records('course_modules', array("course"=>$course->id , "module"=>$iomadcert->id, "deletioninprogress"=>0));
        $scorm_cm = $DB->get_record_sql('SELECT * FROM {course_modules} WHERE course =? AND module = ? AND deletioninprogress = 0  ORDER BY id ASC', array($course->id ,$scorm_md->id));
        if(!empty($cm)){
            foreach($cm as $ccm){
            	$modinfo = get_fast_modinfo($course->id);
            	$cminfo = $modinfo->get_cm($ccm->id);
            	if ($cminfo->uservisible) {
            		$custcert = $DB->get_record('customcert', array("id"=>$ccm->instance));
					$getcertificateissue = $DB->get_record_sql("SELECT * FROM {customcert_issues} WHERE customcertid = ? AND userid = ?",array($ccm->instance, $USER->id));
					if($getcertificateissue){
			            $html .= '<div class="col-lg-4   rounded my-2 min_style_height" >
						      <div class="d-flex flex-column border-dotted border-secondary p-2" style="border: 1px dashed #ccc; border-radius:10px">
							     <a target="_blank" href="'.$CFG->wwwroot.'/mod/customcert/view.php?id='.$ccm->id.'&downloadown=1">
							     <div class="rounded-top certificate-style" style="background-image:url('.$CFG->wwwroot. '/blocks/custom_latest_certificate_message/img/certificate-icon.png);">
								 </div>
								 </a>
								    <a class="p-2"><span class="text-primary" style="font-size:16px; font-weight:bolder" >'.$course->fullname.'</span></a>
							  </div>
					  	</div>';     
						
					}

            	}
				
            } 
        }
        if(!empty($icm)){
            foreach($icm as $ccm){
            	$modinfo = get_fast_modinfo($course->id);
            	$cminfo = $modinfo->get_cm($ccm->id);
            	if ($cminfo->uservisible) {
					$custcert = $DB->get_record('iomadcertificate', array("id"=>$ccm->instance)); 
		            $html .= '<div class="col-lg-4   rounded my-2 min_style_height" >
					      <div class="d-flex flex-column border-dotted border-secondary p-2"  style="border: 1px dashed #ccc; border-radius:10px">
						     <a target="_blank" href="'.$CFG->wwwroot.'/mod/customcert/view.php?id='.$ccm->id.'&downloadown=1">
						     <div class="rounded-top certificate-style" style="background-image:url('.$CFG->wwwroot. '/blocks/custom_latest_certificate_message/img/certificate-icon.png);">
							 </div>
							 </a>
							    <a class="p-2"><span class="text-primary" style="font-size:16px; font-weight:bolder" >'.$course->fullname.'</span>  
							   </a>
						  </div>
				  	</div>';
				}     
            } 
        }
        if($scorm_cm){
        	$aicc_scorm = $DB->get_record("scorm", array('id' => $scorm_cm->instance));
        	if($aicc_scorm->version == "AICC"){
				  $html .= '<div class="col-lg-4   rounded my-2 min_style_height" >
				      <div class="d-flex flex-column border-dotted border-secondary p-2"  style="border: 1px dashed #ccc; border-radius:10px">
					     <a target="_blank" href="'.$CFG->wwwroot.'/mod/scorm/view.php?id='.$scorm_cm->id.'&downloadown=1">
					     <div class="rounded-top certificate-style" style="background-image:url('.$CFG->wwwroot. '/blocks/custom_latest_certificate_message/img/certificate-icon.png);">
						 </div>
						 </a>
						    <a class="p-2"><span class="text-primary" style="font-size:16px; font-weight:bolder" >'.$course->fullname.'</span>  
						   </a>
					  </div>
			  	</div>'; 

        	}
        } 
	}
}

// $backupsql = "SELECT cb.* 
//               FROM {customcert_backup} cb
//               LEFT JOIN {user_enrolments} ue ON cb.current_enrollment = ue.id
//               WHERE cb.userid = :userid
//                 AND ue.id IS NULL";
// $backupcerts = $DB->get_records_sql($backupsql, ['userid' => $userid]);

$backupcerts = $DB->get_records_sql("SELECT id,current_enrollment,course_completion_date,userid,courseid FROM {customcert_backup} WHERE userid = ?", [$userid]);
$usercontext = \context_user::instance($userid);
$contextid   = $usercontext->id;

if ($backupcerts) {
    foreach ($backupcerts as $cert) {
    	$userenollmrntid = $DB->get_record_sql("SELECT ue.id
                    FROM {user_enrolments} ue
                    JOIN {enrol} e ON ue.enrolid = e.id
                    WHERE ue.userid = ? AND e.courseid = ?
                    ORDER BY ue.id DESC",[$cert->userid,$cert->courseid]);

    	if ($cert->current_enrollment == $userenollmrntid->id) {
            continue;
        }
        $docfile = $DB->get_record_sql("
            SELECT * FROM {files}
             WHERE contextid = :ctxid
               AND component = 'user'
               AND itemid = :itemid
               AND filesize > 0
               AND filearea = 'certificate'
               AND filename != '.'",
            ['ctxid' => $contextid, 'itemid' => $cert->id]);

        if ($docfile) {

        	$coursedata = $DB->get_record("course",array("id"=>$cert->courseid));

            $license_url = $CFG->wwwroot.'/local/customlayout/file.php?id='.$docfile->pathnamehash;

            $html.= '<div class="col-lg-4 rounded my-2 min_style_height">
                    <div class="d-flex flex-column border-dotted border-secondary p-2"  
                         style="border: 1px dashed #ccc; border-radius:10px">
                        <a target="_blank" href="'.$license_url.'">
                            <div class="rounded-top certificate-style" 
                                 style="background-image:url('.$CFG->wwwroot.'/blocks/custom_latest_certificate_message/img/certificate-icon.png);">
                            </div>
                        </a>
                        <a class="p-2">
                            <span class="text-primary" style="font-size:16px; font-weight:bolder">
                                '.$coursedata->fullname.' ('. date("m/d/Y", $cert->course_completion_date) .') 
                            </span>
                        </a>
                    </div>
                 </div>';
        }
    }
}
$html .= '</div>

<style>

.certificate-style{
	background-size: contain;
	background-position: center;
	width: 100%;
	object-fit: cover;
	height: 200px;
	background-repeat: no-repeat;
	border: 1px solid #cdc6c68a;
}
.page-context-header .page-header-headings h1 {
    margin: 25px 0 0 40px;
    font-size: calc(0.9740625rem + 0.88875vw) !important;
}

</style>



';

// function coursecolor($courseid)
// {
// 	$basecolors = ['#81ecec', '#74b9ff', '#a29bfe', '#dfe6e9', '#00b894', '#0984e3', '#b2bec3', '#fdcb6e', '#fd79a8', '#6c5ce7'];
// 	$color = $basecolors[$courseid % 10];
// 	return $color;
// }
// function getcourse_image($courseid)
// {
// 	global $DB, $CFG;
// 	require_once($CFG->dirroot . '/course/classes/list_element.php');
// 	$course = $DB->get_record('course', array('id' => $courseid));
// 	$course = new core_course_list_element($course);
// 	foreach ($course->get_course_overviewfiles() as $file) {
// 		$isimage = $file->is_valid_image();
// 		$imageurl = file_encode_url("$CFG->wwwroot/pluginfile.php", '/' . $file->get_contextid() . '/' . $file->get_component() . '/' . $file->get_filearea() . $file->get_filepath() . $file->get_filename(), !$isimage);
// 		return $imageurl;
// 	}
// 	if (empty($imageurl)) {
// 		$color = coursecolor($course->id);
// 		$pattern = new \core_geopattern();
// 		$pattern->setColor($color);
// 		$pattern->patternbyid($courseid);
// 		$classes = 'coursepattern';
// 		$imageurl = $pattern->datauri();
// 	}
// 	return $imageurl;
// }

//SELECT DISTINCT st.userid FROM {scorm_scoes_track st INNER JOIN {scorm_scoes_track st1 ON st1.userid = st.userid AND st1.scormid = st.scormid AND st1.attempt = st.attempt INNER JOIN {user u ON u.id = st.userid WHERE st.scormid = 129 AND st.element = 'cmi.core.lesson_status' AND st.value = 'failed' AND st1.value = 'Scene14_Slide10_FreeFormPickOne_0_0' AND u.deleted = 0 AND u.suspended = 0;
echo $OUTPUT->header();

// $courseid = 77;
// $scormid = 129;
// $gardeitem = $DB->get_record_sql("SELECT * FROM {grade_items} WHERE courseid = 77 AND iteminstance = 129");
// $gardeitmid = $gardeitem->id;
// $scormallfailduser = $DB->get_records_sql("SELECT DISTINCT st.userid 
// 									FROM {scorm_scoes_track} st 
// 									INNER JOIN {scorm_scoes_track} st1 ON st1.userid = st.userid AND st1.scormid = st.scormid AND st1.attempt = st.attempt 
// 									INNER JOIN {user} u ON u.id = st.userid 
// 									WHERE st.scormid = 129 
// 									AND st.element = 'cmi.core.lesson_status' 
// 									AND st.value = 'failed' 
// 									AND st1.value = 'Scene14_Slide10_FreeFormPickOne_0_0' 
// 									AND u.deleted = 0 
// 									AND u.suspended = 0");
// if($scormallfailduser){
// 	foreach ($scormallfailduser as $failduser) {
// 		// echo "SELECT * FROM {grade_grades} WHERE itemid = $gardeitmid AND userid = $failduser->userid AND finalgrade != 100";
// 		$updatedgrade = $DB->get_record_sql("SELECT * FROM {grade_grades} WHERE itemid = ? AND userid = ? AND finalgrade != 100",[$gardeitmid, $failduser->userid]);
// 		if($updatedgrade){
// 			$gradedata = new stdClass();
// 			$gradedata ->id = $updatedgrade->id;
// 			$gradedata ->finalgrade = 100;
// 			$gradedata ->overridden = time();
// 			$gradedata ->timemodified = time();
// 			$DB->update_record('grade_grades', $gradedata);

// 		}
// 	}

// }

// echo "<pre>";
// print_r($scormallfailduser);
// echo "</pre>";



echo $OUTPUT->footer();
