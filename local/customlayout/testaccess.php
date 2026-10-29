<?php
require_once('../../config.php');
global $DB, $CFG, $USER, $PAGE;
require_login();

$coursecontext = context_course::instance(331);


if (\core\session\manager::is_loggedinas()) {
    // Make sure the REAL person can access this course first.
    $realuser = \core\session\manager::get_realuser();
    if (!is_enrolled($coursecontext, $realuser->id, '', true) and
        !is_viewing($coursecontext, $realuser->id) and !is_siteadmin($realuser->id)) {
        if ($preventredirect) {
            throw new require_login_exception('Invalid course login-as access');
        }
        $PAGE->set_context(null);
        echo $OUTPUT->header();
        notice(get_string('studentnotallowed', '', fullname($USER, true)), $CFG->wwwroot .'/');
    }
        // echo "<pre>";
        // print_r($realuser);
        // echo "</pre>";
        var_dump(!is_enrolled($coursecontext, $USER->id, '', true));
        var_dump(!is_viewing($coursecontext, $realuser->id));
        var_dump(!is_siteadmin($realuser->id));

}

// if (\core\session\manager::is_loggedinas()) {

//     // Allow access if the LOGGED-IN-AS user is enrolled
//     if (!is_enrolled($coursecontext, $USER->id, '', true) &&
//         !is_viewing($coursecontext, $USER->id) &&
//         !is_siteadmin($realuser->id)) {

//         if ($preventredirect) {
//             throw new require_login_exception('Invalid course login-as access');
//         }

//         $PAGE->set_context(null);
//         echo $OUTPUT->header();
//         notice(get_string('studentnotallowed', '', fullname($USER, true)), $CFG->wwwroot .'/');
//     }
// }

die();
        global $DB, $CFG, $USER;
        require_once($CFG->dirroot. '/local/manage_course/locallib.php');
        require_once($CFG->dirroot . '/blocks/custom_mycourse/locallib.php');
        $categoryids = blocks_custom_mycourse_get_all_category_ids(64);
        $role = $DB->get_record('role', array('shortname'=>'editingteacher'));
        $companycoursenoneditorrole = $DB->get_record('role', array('shortname' => 'companycoursenoneditor'));
        $getcompnatlist = $DB->get_records_sql("SELECT * FROM {company_users} WHERE userid = ? AND managertype = 1",array($USER->id));
        foreach ($getcompnatlist as $company) {
            $companyid = $company->companyid;

            $companydata = $DB->get_record_sql("SELECT * FROM {company} WHERE id = ?",array($companyid));
            $companycategory = $companydata->category;

            $checkcompcourseenroll = $DB->get_records_sql("SELECT cc.*,c.category as coursecategory 
                                                        FROM {company_course} as cc 
                                                        INNER JOIN {course} as c ON c.id = cc.courseid 
                                                        WHERE cc.companyid = ? ",array($companyid));
            if($checkcompcourseenroll){
            	foreach ($checkcompcourseenroll as $courseenroll) {
            		if (in_array($courseenroll->coursecategory, $categoryids)) {
                        continue;
                    }
                 	$context = context_course::instance($courseenroll->courseid);
                 	$coursealreadyassigned =  $DB->record_exists('role_assignments', array('userid'=>$USER->id, 'roleid'=>$companycoursenoneditorrole->id, 'contextid'=>$context->id));
                 	if(!$coursealreadyassigned){
                 		echo $courseenroll->courseid ."<br>";
                        enrolteacherCourse($courseenroll->courseid, $USER->id, $companycoursenoneditorrole->id,0,time());
                    }
                 	 
            	}

            }



         


            $compnaycourses = $DB->get_records_sql("SELECT cc.*,c.category as coursecategory 
                                                        FROM {company_course} as cc 
                                                        INNER JOIN {course} as c ON c.id = cc.courseid 
                                                        WHERE cc.companyid = ? AND c.category = ?",array($companyid,$companycategory));

            if($compnaycourses){
                foreach ($compnaycourses as $compnaycourse) {
                    if (in_array($compnaycourse->coursecategory, $categoryids)) {
                        continue;
                    }
                        // print_r($compnaycourse);
                     $context = context_course::instance($compnaycourse->courseid);
                    $coursealreadyassigned =  $DB->record_exists('role_assignments', array('userid'=>$USER->id, 'roleid'=>$role->id, 'contextid'=>$context->id));
                    if(!$coursealreadyassigned){
                    	echo "compnaycourse " . $compnaycourse->courseid ."<br>";
                        enrolteacherCourse($compnaycourse->courseid, $USER->id, $role->id,0,time());
                    }
                }
            }

            // Get white-label-grade allowed courses
            $allwogarecourse = $DB->get_records_sql("SELECT cc.* FROM {customfield_data} cfd 
                                                        JOIN {customfield_field} cf ON cf.id = cfd.fieldid 
                                                        JOIN {company_course} cc ON cc.courseid = cfd.instanceid 
                                                        WHERE cc.companyid = ? 
                                                        AND cf.shortname = 'allow_white_level_grade' 
                                                        AND cfd.value = 1", [$companyid]);
            // whitelable admin enrolled allow garding course with teacher role
            if ($allwogarecourse) {
                foreach ($allwogarecourse as $wogcourse) {

                    $context = context_course::instance($wogcourse->courseid);

                    $alreadyassigned = $DB->record_exists(
                        'role_assignments',
                        [
                            'userid' => $USER->id,
                            'roleid' => $role->id,
                            'contextid' => $context->id
                        ]
                    );

                    if (!$alreadyassigned) {
                        enrolteacherCourse($wogcourse->courseid, $USER->id, $role->id, 0, time());
                    }
                }
            }
                    
        }

?>