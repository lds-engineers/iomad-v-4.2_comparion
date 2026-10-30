<?php 
function wordpresslogout(\core\event\user_loggedout $event){
	global $DB, $USER,$CFG;
    if($event->objecttable=="user" && $event->action=="loggedout"){ 
    	$logoutuser = $DB->get_record("remote_user_info",["userid"=>$event->userid]);
    	if($logoutuser){
    		$wpurl=$logoutuser->wp_siteurl.'/wp-sso-logout/?key='.$logoutuser->wp_userid;
        	redirect($wpurl);

    	}
    }
}

// function course_completed_genratecertificate(\core\event\course_completed $event){
//     require_once(dirname(__FILE__) . '/../../config.php');
//     global $DB, $USER,$CFG;
//     require_once($CFG->dirroot . '/mod/customcert/classes/template.php');
//     $courseid = $event->courseid;
//     $userid = $event->relateduserid;
//     $userdata = $DB->get_record("user",array("id"=>$userid));

//     $certificatemodule = $DB->get_record("modules", array("name" => "customcert"));

//     $coursmodul = $DB->get_record("course_modules", array('module' => $certificatemodule->id, 'course' => $courseid));

//     $customcert = $DB->get_record('customcert', ['id' => $coursmodul->instance], '*', MUST_EXIST);

//     $template = $DB->get_record('customcert_templates', ['id' => $customcert->templateid], '*', MUST_EXIST);
//     $sql = "SELECT MAX(c.timecompleted) as timecompleted
//                           FROM {course_completions} c
//                          WHERE c.userid = :userid
//                            AND c.course = :courseid";                     
//     // print_r(array($sql, ['userid' => $userid, 'courseid' => $courseid]));
//     $timecompleted = $DB->get_record_sql($sql, ['userid' => $userid, 'courseid' => $courseid]);
//     // var_dump($timecompleted);
//     if(!empty($timecompleted) && !empty($timecompleted->timecompleted)){
//         $template = new \mod_customcert\template($template);
//         $pdf = $template->generate_pdf(false, $userid, true);
//     }

// }

function course_completed_genratecertificate(\core\event\course_completed $event){
    require_once(dirname(__FILE__) . '/../../config.php');
    global $DB, $USER,$CFG;
    require_once($CFG->dirroot . '/mod/customcert/classes/template.php');
    $courseid = $event->courseid;
    $userid = $event->relateduserid;
    $userdata = $DB->get_record("user",array("id"=>$userid));

    $companydata = $DB->get_record('company_users', ['userid' => $userid], '*', MUST_EXIST);
    $companyid   = $companydata->companyid;

    $certificatemodule = $DB->get_record("modules", array("name" => "customcert"));

    // $coursmodul = $DB->get_record("course_modules", array('module' => $certificatemodule->id, 'course' => $courseid));

    // $customcert = $DB->get_record('customcert', ['id' => $coursmodul->instance], '*', MUST_EXIST);

    // $template = $DB->get_record('customcert_templates', ['id' => $customcert->templateid], '*', MUST_EXIST);
    // $sql = "SELECT MAX(c.timecompleted) as timecompleted
    //                       FROM {course_completions} c
    //                      WHERE c.userid = :userid
    //                        AND c.course = :courseid";                     
    // // print_r(array($sql, ['userid' => $userid, 'courseid' => $courseid]));
    // $timecompleted = $DB->get_record_sql($sql, ['userid' => $userid, 'courseid' => $courseid]);
    // // var_dump($timecompleted);
    // if(!empty($timecompleted) && !empty($timecompleted->timecompleted)){
    //     $template = new \mod_customcert\template($template);
    //     $pdf = $template->generate_pdf(false, $userid, true);
    // }

    //updated code 

    $coursmodul = $DB->get_records("course_modules", array('module' => $certificatemodule->id, 'course' => $courseid));
    $allowedmodules = [];
    foreach ($coursmodul as $cm) {

        // Skip hidden / deleting modules
        if (!$cm->visible || $cm->deletioninprogress) {
            continue;
        }

        // Empty availability  allowed
        if (empty($cm->availability)) {
            $allowedmodules[$cm->id] = $cm;
            continue;
        }

        $availability = json_decode($cm->availability, true);

        // Invalid JSON  allow (matches Moodle behaviour)
        if (empty($availability['c'])) {
            $allowedmodules[$cm->id] = $cm;
            continue;
        }

        $hascompanyrule = false;
        $companymatch   = false;

        foreach ($availability['c'] as $cond) {
            if (!empty($cond['type']) && $cond['type'] === 'company') {
                $hascompanyrule = true;

                if ((int)$cond['id'] === (int)$companyid) {
                    $companymatch = true;
                    break;
                }
            }
        }
        if (!$hascompanyrule || $companymatch) {
            $allowedmodules[$cm->id] = $cm;
        }
    }

    //check course completion

    $sql = "SELECT MAX(c.timecompleted) as timecompleted
                          FROM {course_completions} c
                         WHERE c.userid = :userid
                           AND c.course = :courseid";                     
    // print_r(array($sql, ['userid' => $userid, 'courseid' => $courseid]));
    $timecompleted = $DB->get_record_sql($sql, ['userid' => $userid, 'courseid' => $courseid]);

    if(!empty($timecompleted) && !empty($timecompleted->timecompleted)){

        foreach ($allowedmodules as $courscrtmodul) {
            $customcert = $DB->get_record('customcert', ['id' => $courscrtmodul->instance], '*', MUST_EXIST);
            if (!$DB->record_exists('customcert_issues', ['userid' => $userid, 'customcertid' => $customcert->id])) {
                \mod_customcert\certificate::issue_certificate($customcert->id, $userid);
            }
            $template = $DB->get_record('customcert_templates', ['id' => $customcert->templateid], '*', MUST_EXIST);
            $template = new \mod_customcert\template($template);
            echo "$courscrtmodul->id <br> companyid $companyid <br>";
            $pdf = $template->generate_pdf(false, $userid, true);
            // print_r($pdf);
        }

    }
}
