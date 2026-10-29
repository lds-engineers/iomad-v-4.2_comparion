<?php
require_once('../../config.php');
// require_login();

global $DB;

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

$allowed_itemids = [63, 401];
$itemid = optional_param('itemid', 0, PARAM_INT);
if (!$itemid || !in_array($itemid, $allowed_itemids)) {
    echo json_encode([
        'status' => false,
        'message' => 'Invalid or missing itemid. Allowed values: 63, 401'
    ]);
    exit;
}

$sql = "
    SELECT 
        r.id,
        r.userid,
        r.rating,
        r.review,
        r.reply,
        r.timecreated,
        r.status,
        u.email,
        u.firstname,
        u.lastname
    FROM {tool_courserating_rating} r
    JOIN {course_shopsettings_courses} csc
        ON csc.courseid = r.courseid
    JOIN {user} u
        ON u.id = r.userid
    WHERE csc.itemid = :itemid
      AND r.status = 1
    ORDER BY r.timecreated DESC
";

$ratings = $DB->get_records_sql($sql, ['itemid' => $itemid]);
$data = [];
$total = 0;
$count = 0;

foreach ($ratings as $rating) {
    $user = $DB->get_record('user', [
        'id' => $rating->userid,
        'deleted' => 0
    ]);
    if (!$user) continue;

    $data[] = [
        'user_id'   => $user->id,
        'user_name' => fullname($user),
        'firstname' => $user->firstname,
        'lastname' => $user->lastname,
        'email'     => $rating->email,
        'rating'    => (int)$rating->rating,
        'review'    => $rating->review,
        'status'    => $rating->status,
        'reply'     => $rating->reply,
        'time'      => date('Y-m-d H:i:s', $rating->timecreated)
    ];

    $total += $rating->rating;
    $count++;
}

$avg = $count ? round($total / $count, 2) : 0;
echo json_encode([
    'status' => true,
    'itemid' => $itemid,
    'summary' => [
        'average_rating' => $avg,
        'total_reviews'  => $count
    ],
    'data' => $data
]);

exit;

