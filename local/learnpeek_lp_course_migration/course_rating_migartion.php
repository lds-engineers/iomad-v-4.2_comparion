<?php
require_once('../../config.php');
global $DB, $CFG, $PAGE, $USER;
require_login();
if (!is_siteadmin()) {
    redirect(new moodle_url($CFG->wwwroot.'/my'),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
     die;
}
// $courseid = 17643;
// $courseid = 404;
$migartedcourses = $DB->get_records_sql("SELECT cm.id,cm.lp_courseid,cm.courseid,c.fullname FROM {course_migration} as cm INNER JOIN {course} as c ON c.id = cm.courseid WHERE cm.status = 1");
// echo "<pre>";
// print_r($migartedcourses);
if($migartedcourses){
    foreach ($migartedcourses as $migrationdata) {
        // print_r($migrationdata);
        $courseid = $migrationdata->courseid;
        $courseratingmodule = $DB->get_record_sql("SELECT cm.* FROM {course_modules} as cm INNER JOIN {modules} as m ON m.id = cm.module WHERE cm.course = ? AND m.name = 'courserating'",array($courseid));
        if (!$courseratingmodule) {
            echo "Course feedback rating module not found in course : <b>$migrationdata->fullname</b> </br>";
            continue;
        }
        $wp_siteurl = "https://pdhstar.com";
        $migartionurl = rtrim($wp_siteurl, '/') . '/wp-json/custom/v1/lpcourseratingmigration';
        $request_data = [
            'lp_course_id'    => $migrationdata->lp_courseid,
            'moodlewsrestformat'=> 'json'
        ];
        $curl = new curl();
        $response = $curl->post($migartionurl, $request_data);
        $result = json_decode($response);
        if ($result->status=="success") {
            echo $result->message ."<br>";
            continue;
        }
       
        foreach ($result as $reting) {
            $getcompany = $DB->get_record_sql("SELECT id,hostname FROM {company} WHERE hostname LIKE ?", array($reting->company_host));
            $company_id = $getcompany->id;
            $userinfo = $DB->get_record_sql("SELECT u.* FROM {user} u INNER JOIN {company_users} cu on cu.userid = u.id WHERE cu.companyid = :companyid and u.email = :email and u.deleted = 0 LIMIT 1",["email"=>$reting->email, "companyid"=>$company_id]);

            if (!$userinfo) {
                echo "User not found in Moodle: {$rating->email} — Skipping<br>";
                continue;
            }
            $courserating = new stdClass();
            $courserating->courseid = $courseid;
            $courserating->userid = $userinfo->id;
            $courserating->rating = $reting->rating;
            $courserating->review = $reting->review;
            $courserating->hasreview = 1;
            $courserating->status = 1;
            $courserating->timecreated = time();
            $courserating->timemodified = time();
            $insertidcourserating = $DB->insert_record('tool_courserating_rating', $courserating);
            // $insertidcourserating = 1;
            if($insertidcourserating){
                $updateurl = rtrim($wp_siteurl, '/') . '/wp-json/custom/v1/update_rating_migration_status';
                $payload = [
                    'lp_user_id'   => $reting->user_id,
                    'lp_user_email' => $reting->email,
                    'lp_course_id' => $migrationdata->lp_courseid,
                    'moodle_userid'=> $userinfo->id,
                    'status'       => '1'
                ];
                // echo "<pre>";
                $response2 = $curl->post($updateurl, $payload);
                $result2 = json_decode($response2);
                echo "Course : <b>{$migrationdata->fullname}</b> ==> Migrated User : <b>{$reting->email}</b><br>";
                // continue;
            }
        }
    }
}




