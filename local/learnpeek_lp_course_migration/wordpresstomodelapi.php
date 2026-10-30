<?php
require_once(__DIR__ . '/../../config.php');
global $DB;
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data) {
    http_response_code(400);

    echo json_encode([
        "status"  => false,
        "message" => "Invalid JSON payload"
    ]);
    exit;
}
$wordpress_courseid = (int) ($data['wordpress_courseid']);
$moodle_courseid    = (int) ($data['moodle_courseid']);
$useremail          = trim($data['useremail']);
$review             = $data['review'];
$rating             = (int) ($data['rating']);
$commentstatus      = $data['status'];

$status = false;
$message = '';
$errors = array();
if (!$moodle_courseid || !$useremail) {
    $message = "Missing required fields";
    echo json_encode(array("status"=>$status, "message"=>$message));
    exit;
}
 

$course = $DB->get_record('course', ['id' => $moodle_courseid], '*', IGNORE_MISSING);
if (!$course) {
    $message = "Invalid course";
    echo json_encode(array("status"=>$status, "message"=>$message));
    exit;
}

$company = $DB->get_record('company', array('hostname' => $_SERVER["SERVER_NAME"]));
if (!$company) {
    $message = "Invalid company";
    echo json_encode(array("status"=>$status, "message"=>$message));
    exit;
}

$company_id = $company->id;

$userinfo = $DB->get_record_sql("SELECT u.* FROM {user} u 
    INNER JOIN {company_users} cu on cu.userid = u.id 
    WHERE cu.companyid = :companyid 
    AND u.email = :email 
    AND u.deleted = 0 LIMIT 1",
    ["email"=>$useremail, "companyid"=>$company_id]
);

if (!$userinfo) {
    $message = "User are not found";
    echo json_encode(array("status"=>$status, "message"=>$message));
    exit;
}

$checkrating  = $DB->get_record_sql("SELECT * FROM {tool_courserating_rating} 
    WHERE courseid = ? 
    AND userid = ?",
    [$moodle_courseid,$userinfo->id]
);

if($checkrating){
    $updatfeedback = new stdClass();
    $updatfeedback->id         = $checkrating->id;
    $updatfeedback->rating         = $rating;
    $updatfeedback->review         = $review;
    $updatfeedback->hasreview      = 1;
    $updatfeedback->status         = $commentstatus;
    $updatfeedback->timemodified   = time();
    $updatfeedback = $DB->update_record('tool_courserating_rating', $updatfeedback);
    echo json_encode([
            "status"  => true,
            "message" => "Review updated successfully"
        ]);

}else{
    $insertfeedback = new stdClass();
    $insertfeedback->courseid       = $moodle_courseid;
    $insertfeedback->userid         = $userinfo->id;
    $insertfeedback->backup_userid  = 0;
    $insertfeedback->rating         = $rating;
    $insertfeedback->review         = $review;
    $insertfeedback->hasreview      = 1;
    $insertfeedback->status         = $commentstatus; // default/pending
    $insertfeedback->timecreated    = time();
    $insertfeedback->timemodified   = time();
    $insertedfeedback = $DB->insert_record('tool_courserating_rating', $insertfeedback);
     echo json_encode([
            "status"  => true,
            "message" => "Review inserted successfully"
        ]);

}

// echo json_encode(array("status"=>$status, "message"=>$message));