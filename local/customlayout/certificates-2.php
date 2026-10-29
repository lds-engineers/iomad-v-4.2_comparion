<?php
require_once('../../config.php');
global $DB, $CFG, $USER, $PAGE;
use core_completion\progress;

require_login();

$userid = optional_param('userid', $USER->id, PARAM_INT);
if(!is_siteadmin()){
	$userid = $USER->id;
}

$strmymoodle = get_string('certificates', 'local_customlayout');
$PAGE->set_title($strmymoodle);
$PAGE->set_heading($strmymoodle);

$enrolled_courses = enrol_get_users_courses($USER->id);

$customcert = $DB->get_record('modules', array("name"=>"customcert"));    
$iomadcert = $DB->get_record('modules', array("name"=>"iomadcertificate"));
$scorm_md = $DB->get_record('modules', array("name"=>"scorm"));

$html = '<div class="parent-conatiner d-flex flex-wrap">';

$hascertificate = false;

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
						$hascertificate = true;
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
            		$hascertificate = true;
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
        		$hascertificate = true;
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
        	$hascertificate = true;

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
if (!$hascertificate) {
    $html .= '<div class="col-12 text-center my-4">
        <p style="font-size:18px; color:#888;">No certificates available</p>
    </div>';
}


	// --- Trainee Certificates ---- //

	$companyid = iomad::get_my_companyid(context_system::instance());
	$usercontext = context_user::instance($USER->id);
	$current_user_roles = get_user_roles($usercontext, $USER->id);
	$manag_role = array_column($current_user_roles, 'shortname');

	$allowed_roles = ['companyreporter', 'companydepartmentmanager'];

	if (array_intersect($allowed_roles, $manag_role)) {

		$html .= '<div class="col-12 mt-4">';
		$html .= '<h3 class="mb-3">Trainee Certificates</h3>';
		$html .= '<div class="d-flex flex-wrap">';

		$params = [];
		$where  = "u.deleted = 0 AND u.suspended = 0";
		$clause = "";

		// ----- Busniss User ---- //
		if ($manag_role[0] == 'companydepartmentmanager') {

		    $clause = "LEFT JOIN {company_users} cu 
		                   ON cu.userid = u.id AND cu.companyid = :companyid
		               LEFT JOIN {business_user_child} buc 
		                   ON buc.child_id = u.id";

		    $where .= " AND buc.parent_id = :parentid";

		    $params['companyid'] = $companyid;
		    $params['parentid']  = $USER->id;
		}

		// -- Department Manage ---- //
		elseif ($manag_role[0] == 'companyreporter') {

		    $sql = "SELECT ca.department_id
		            FROM {customdepartment_assign} ca
		            WHERE ca.child_id = :userid
		              AND ca.is_departmentmanager = 1";

		    $departments = $DB->get_records_sql($sql, ['userid' => $USER->id]);

		    $department_ids = [];
		    foreach ($departments as $dept) {
		        $department_ids[] = $dept->department_id;
		    }

		    $clause = "LEFT JOIN {company_users} cu 
		                   ON cu.userid = u.id AND cu.companyid = :companyid
		               JOIN {customdepartment_assign} cda 
		                   ON cda.child_id = u.id";

		    $params['companyid'] = $companyid;

		    if (!empty($department_ids)) {
		        list($insql, $inparams) = $DB->get_in_or_equal($department_ids, SQL_PARAMS_NAMED);
		        $where .= " AND cda.department_id $insql";
		        $params = array_merge($params, $inparams);
		    } else {
		        $where .= " AND 1=0";
		    }
		}

		// enrolled users
		$where .= " AND EXISTS (
		    SELECT 1
		    FROM {user_enrolments} ue
		    JOIN {enrol} e ON e.id = ue.enrolid
		    WHERE ue.userid = u.id
		)";


		$trainees = $DB->get_records_sql("
		    SELECT DISTINCT u.id, u.firstname, u.lastname
		    FROM {user} u
		    $clause
		    WHERE $where
		", $params);

		if ($trainees) {

		    foreach ($trainees as $trainee) {

		    	if($trainee->id == $USER->id){
		    		continue;
		    	}

		        // Get ALL courses (enrolled and backup)
		        $courses = enrol_get_users_courses($trainee->id);

		        $backupcerts = $DB->get_records_sql(
		            "SELECT * FROM {customcert_backup} WHERE userid = ?",
		            [$trainee->id]
		        );

		        $backupcourseids = array_column($backupcerts, 'courseid');
		        $enrolledids = array_keys($courses);

		        $allcourseids = array_unique(array_merge($backupcourseids, $enrolledids));

		        if (empty($allcourseids)) continue;

		        list($insql, $inparams) = $DB->get_in_or_equal($allcourseids, SQL_PARAMS_NAMED);

		        $allcourses = $DB->get_records_sql(
		            "SELECT * FROM {course} WHERE id $insql",
		            $inparams
		        );

		        foreach ($allcourses as $course) {

				    $backupcerts = $DB->get_records_sql("
				        SELECT *
				        FROM {customcert_backup}
				        WHERE userid = ? AND courseid = ?
				        ORDER BY course_completion_date DESC, id DESC
				    ", [$trainee->id, $course->id]);

				    if (!$backupcerts) continue;

				    // $first = true;
				    $enrolments = $DB->get_records_sql("
					    SELECT ue.id
					    FROM {user_enrolments} ue
					    JOIN {enrol} e ON e.id = ue.enrolid
					    WHERE ue.userid = ?
					", [$trainee->id]);

					$current_enrol_ids = array_column($enrolments, 'id');

				    foreach ($backupcerts as $backupcert) {

				        // Completion date
				        $completiondate = !empty($backupcert->course_completion_date)
				            ? date("m/d/Y", $backupcert->course_completion_date)
				            : '';

				        $coursename = $course->fullname;
				        if (in_array($backupcert->current_enrollment, $current_enrol_ids)) {
						    $displayname = $coursename;
						} else {
						    $displayname = $coursename . " (" . $completiondate . ")";
						}

				        $usercontext = context_user::instance($trainee->id);

				        $file = $DB->get_record_sql("
				            SELECT *
				            FROM {files}
				            WHERE contextid = ?
				            AND component = 'user'
				            AND filearea = 'certificate'
				            AND itemid = ?
				            AND filename != '.'
				            AND filesize > 0
				        ", [$usercontext->id, $backupcert->id]);

				        if (!$file) continue;

				        $certurl = $CFG->wwwroot.'/local/customlayout/file.php?id='.$file->pathnamehash;

				        $html .= '<div class="col-lg-3 rounded my-2">
				            <div class="p-2" style="border:1px dashed #ccc; border-radius:10px">
				                <a target="_blank" href="'.$certurl.'">
				                    <div class="certificate-style" 
				                         style="background-image:url('.$CFG->wwwroot.'/blocks/custom_latest_certificate_message/img/certificate-icon.png);">
				                    </div>
				                </a>
				                <div class="p-2 text-center">
				                    <div class="text-primary" style="font-size:16px; font-weight:bold">'.$displayname.'</div>
				                    <div style="font-size:13px; color:#666;">
				                        '.$trainee->firstname.' '.$trainee->lastname.'
				                    </div>
				                </div>
				            </div>
				        </div>';
				    }
				}
		    }

		} else {
		    $html .= '<div class="col-12 text-center">
		        <p style="color:#888;">No trainee certificates found</p>
		    </div>';
		}

		$html .= '</div></div>';
	    
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

echo $OUTPUT->header();
echo $html;
echo $OUTPUT->footer();
