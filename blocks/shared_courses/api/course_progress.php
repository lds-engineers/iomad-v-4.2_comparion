<?php
require('../../../config.php');

use core_completion\progress;

global $DB, $USER;

header('Content-Type: application/json');

$entityid = required_param('entityid', PARAM_TEXT);
// $entityid = 'vt-lms-idp';

// Optional params
$startdate = optional_param('startdate', '', PARAM_TEXT);
$enddate   = optional_param('enddate', '', PARAM_TEXT);

// echo "entity id - $entityid";
// echo "start date - $startdate";
// echo "end date - $enddate";


/* 1️⃣ Rate limit check */

$todaystart = strtotime('today');
$todayend   = strtotime('tomorrow');

$count = $DB->count_records_sql("
SELECT COUNT(id)
FROM {shared_courses_progress_logs}
WHERE entityid = ?
AND request_time >= ?
AND request_time < ?
", [
    $entityid,
    $todaystart,
    $todayend
]);

if ($count >= 15) {
    echo json_encode([
        'status'=>'error',
        'message'=>'Daily API limit reached (5 requests per day)'
    ]);
    exit;
}

/* 2️⃣ Log request */

$log = new stdClass();
$log->entityid = $entityid;
$log->request_time = time();

$DB->insert_record('shared_courses_progress_logs',$log);


/* 3️⃣ Date Logic */

$currenttime = time();

if (!empty($startdate) && !empty($enddate)) {

    $starttime = strtotime($startdate);
    $endtime   = strtotime($enddate);

} else if (!empty($startdate) && empty($enddate)) {

    $starttime = strtotime($startdate);
    $endtime   = $currenttime;

} else if (empty($startdate) && !empty($enddate)) {

    $endtime   = strtotime($enddate);
    $starttime = $endtime - 86400;

} else {

    $starttime = $currenttime - 86400;
    $endtime   = $currenttime;
}


/* 4️⃣ Fetch users course completion */

$sql = "
SELECT
scel.id AS logid,

u.id AS userid,
u.firstname,
u.lastname,
u.email,
u.username,

c.id AS courseid,
c.fullname AS coursename,

cc.timecompleted AS completedtime,

gg.finalgrade AS coursescore

FROM {shared_courses_enrol_logs} scel

JOIN {user} u
ON u.id = scel.userid

JOIN {course} c
ON c.id = scel.courseid

LEFT JOIN {course_completions} cc
ON cc.course = c.id
AND cc.userid = u.id

LEFT JOIN {grade_items} gi
ON gi.courseid = c.id
AND gi.itemtype = 'course'

LEFT JOIN {grade_grades} gg
ON gg.itemid = gi.id
AND gg.userid = u.id

WHERE scel.idp_entityid = ?
AND (cc.timecompleted BETWEEN ? AND ? OR cc.timecompleted IS NULL)
AND u.deleted = ?
";

$records = $DB->get_records_sql($sql, [
    $entityid,
    $starttime,
    $endtime,
    0
]);

$response = [];


/* 5️⃣ Build response */

foreach ($records as $rec) {

    $course = $DB->get_record('course', ['id'=>$rec->courseid]);

    $percentage = progress::get_course_progress_percentage($course, $rec->userid);
    $progress = null;

    if ($percentage !== null) {
        $progress = round($percentage,2).'%' ;
    }

    $completedtime = '';

    if (!empty($rec->completedtime)) {
        $completedtime = date('Y-m-d H:i:s', $rec->completedtime);
    }

    $response[] = [
        'user_id' => $rec->userid,
        'first name' => $rec->firstname,
        'last name' => $rec->lastname,
        'email' => $rec->email,
        'name_id' => $rec->username,
        'course_name' => $rec->coursename,
        'course_progress' => $progress,
        'course_score' => $rec->coursescore,
        'completed_time' => $completedtime,
        'time_zone' => date_default_timezone_get()
    ];
}


/* 6️⃣ Final Response */
// echo "<pre>";
// print_r($response);
// echo "</pre>";

echo json_encode([
    'status'=>'success',
    'data'=>$response
]);