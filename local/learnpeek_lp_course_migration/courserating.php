<?php
require_once('../../config.php');
// require_login();

global $DB;

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

$courseid = optional_param('courseid', 0, PARAM_INT);
// die();

if (!$courseid) {

    echo json_encode([
        'status'  => false,
        'message' => 'Course ID is required.',
        'data'    => []
    ]);

    exit;
}
$course = $DB->get_record(
    'course',
    [
        'id' => $courseid
    ]
);

if (!$course) {

    echo json_encode([
        'status'  => false,
        'message' => 'Invalid or missing course ID.',
        'data'    => []
    ]);

    exit;
}

$shortname = 'PDH STAR';
$company = $DB->get_record(
    'company',
    [
        'shortname' => $shortname,
        'suspended' => 0,
    ]
);

if (!$company) {

    echo json_encode([
        'status'  => false,
        'message' => 'Invalid  company.',
        'data'    => []
    ]);

    exit;
}

$companyid = $company->id;


// $ratings  = $DB->get_records_sql(
//             "SELECT * FROM 
//             {tool_courserating_rating} 
//             WHERE courseid = ? AND status = ?",
//             [$course->id , 1]
//         );

$ratings = $DB->get_records_sql(
    "SELECT r.*
       FROM {tool_courserating_rating} r
      WHERE r.courseid = ?
        AND r.status = ?
        AND (
            r.guest = 1
            OR EXISTS (
                SELECT 1
                  FROM {company_users} cu
                 WHERE cu.userid = r.userid
                   AND cu.companyid = ?
            )
        )",
    [$course->id, 1, $companyid]
);

$data = [];
$total = 0;
$count = 0;

foreach ($ratings as $rating) {

    // if((int)$rating->guest === 0){

    //     $usercompany = $DB->get_record(
    //         'company_users',
    //         [
    //             'userid'   => $rating->userid,
    //             'companyid' => $companyid
    //         ],
    //         'id'
    //     );
        
    //     if (!$usercompany) {
    //         continue;
    //     }

    // }

    $email     = $rating->email;
    $firstname = $rating->firstname;
    $lastname  = $rating->lastname;

    if (empty($email) || empty($firstname) || empty($lastname)) {

        $user = $DB->get_record(
            'user',
            ['id' => $rating->userid],
            'id, email, firstname, lastname'
        );

        if ($user) {
            if (empty($email)) {
                $email = $user->email;
            }

            if (empty($firstname)) {
                $firstname = $user->firstname;
            }

            if (empty($lastname)) {
                $lastname = $user->lastname;
            }
        }
    }


    $data[] = [
        'user_id'   => $rating->userid,
        'firstname' => $firstname,
        'lastname'  => $lastname,
        'email'     => $email,
        'rating'    => (int)$rating->rating,
        'review'    => $rating->review,
        'status'    => $rating->status,
        'reply'     => $rating->reply,
        'time'      => $rating->timecreated
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
