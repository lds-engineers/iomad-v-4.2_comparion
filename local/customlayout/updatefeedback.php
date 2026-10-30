<?php
require_once('../../config.php');
require_login(); 
$id = required_param('id', PARAM_TEXT);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 10, PARAM_INT);
$search = optional_param('search', '', PARAM_TEXT);
$redirecturl = new moodle_url('/local/customlayout/feedbackstatus.php',compact('page', 'perpage','search'));
// $sql = "UPDATE {tool_courserating_rating} SET status = 1 WHERE " . $DB->sql_compare_text('id') . " = " . $DB->sql_compare_text(':id');
// if($data = $DB->execute($sql, ['id' => $id])) {
//  redirect($redirecturl, 'Feedback approved', null, \core\output\notification::NOTIFY_SUCCESS);
// } else {
//  redirect($redirecturl, "Something is went wrong!", null, \core\output\notification::NOTIFY_ERROR);
// }

$feedbackdata = $DB->get_record('tool_courserating_rating', ['id' => $id], '*', MUST_EXIST);
$user   = $DB->get_record('user', ['id' => $feedbackdata->userid]);
$lpcourse_fid = $DB->get_record("customfield_field",array("shortname"=>"learnpress_courseid"));
$lpcoursedata = $DB->get_record_sql("SELECT value FROM {customfield_data} WHERE instanceid = ? AND fieldid =?",[$feedbackdata->courseid, $lpcourse_fid->id]);


$companydata = $DB->get_record('company_users', ['userid' => $user->id]);


// $wordpresscourseid = $DB->get_field_sql("SELECT cfd.charvalue as wordpresscourseid FROM {customfield_data} cfd WHERE cfd.instanceid = ? AND cfd.fieldid = ?",[$course->id, 11]);
    // die();
if(empty(trim($lpcoursedata->value))){
    $DB->set_field('tool_courserating_rating', 'status', 1, ['id' => $id]);
    redirect($redirecturl, 'Feedback approved', null, \core\output\notification::NOTIFY_SUCCESS);
}
$wordpresscourseid = $lpcoursedata->value;

$companyid = $companydata->companyid;
$ssourl = get_config(null, "main_site_url{$companyid}");

if($ssourl){
    $ssovalidateurl = rtrim($ssourl, '/') . '/wp-json/learnpeek/v1/receive-review';
    $ssovalidateurl;
    $payload = [
        'rating'      => (int)$feedbackdata->rating,
        'review'      => $feedbackdata->review, 
        'status'      => 1, 
        'useremail'   => $user->email,             
        'firstname'   => $user->firstname,             
        'lastname'   => $user->lastname,             
        'lp_courseid' => $wordpresscourseid,
        'lms_courseid'    => (int)$feedbackdata->courseid,
        'timecreated'      => $feedbackdata->timecreated,  
        'moodlewsrestformat'=> 'json'
    ];

    $curl = new curl();
    $response = $curl->post($ssovalidateurl, $payload);
    $result = json_decode($response);

    // Handle result
    if (!empty($result) && isset($result->status) && $result->status == 1) {
        $DB->set_field('tool_courserating_rating', 'status', 1, ['id' => $id]);
        redirect($redirecturl, 'Feedback approved', null, \core\output\notification::NOTIFY_SUCCESS);
    }else if(isset($result->data) && $result->data->status == 404){
            $DB->set_field('tool_courserating_rating', 'status', 1, ['id' => $id]);
            redirect($redirecturl, 'Feedback approved', null, \core\output\notification::NOTIFY_SUCCESS);
    }else {
        // Failure - show Moodle notification and go to dashboard
        $errormessage = !empty($result->message) ? $result->message : 'Something is went wrong!. Please try again later.';
        $DB->set_field('tool_courserating_rating', 'status', 1, ['id' => $id]);
        // redirect( new moodle_url('/my'), $errormessage, 3 , \core\output\notification::NOTIFY_ERROR);
        redirect($redirecturl, $errormessage, null, \core\output\notification::NOTIFY_INFO);
    }
}else{
    $DB->set_field('tool_courserating_rating', 'status', 1, ['id' => $id]);
    redirect($redirecturl, 'Feedback approved', null, \core\output\notification::NOTIFY_SUCCESS);
}