<?php
require "../../config.php";
require_once $CFG->libdir . "/adminlib.php";

require_sesskey();
require_login();

$PAGE->set_context(context_system::instance());
global $DB, $USER;

$action = required_param("action", PARAM_ALPHANUMEXT);
$companyid = required_param("companyid", PARAM_INT);

if ($action === "getdata") {
    $categoryid = required_param("categoryid", PARAM_INT);

    if (!$categoryid) {
        http_response_code(400);
        echo json_encode(["error" => "Missing category ID"]);
        exit();
    }

    $getcomfieldid = $DB->get_record_sql(
        "SELECT * FROM {customfield_field} WHERE shortname = ?",
        ["allow_white_level_grade"]
    );

    if (!$getcomfieldid) {
        http_response_code(404);
        echo json_encode(["error" => "Custom field not found"]);
        exit();
    }

    $allowedcourseids = $DB->get_fieldset_sql(
        "
        SELECT instanceid 
        FROM {customfield_data} 
        WHERE fieldid = ? AND value = 1
    ",
        [$getcomfieldid->id]
    );

    if (empty($allowedcourseids)) {
        $allowcourses = 0;
    } else {
        $allowcourses = implode(",", $allowedcourseids);
    }

    // list($placeholders, $params) = $DB->get_in_or_equal($allowedcourseids, SQL_PARAMS_QM);
    $all_allowcourse = [];
    $companycourseids = $DB->get_records_sql(
        "
        SELECT cc.courseid 
        FROM {company_course} cc 
        WHERE cc.companyid = ? AND cc.courseid in (" .
            $allowcourses .
            ")
    ",
        [$companyid]
    );
    foreach ($companycourseids as $companycourseid) {
        array_push($all_allowcourse, $companycourseid->courseid);
    }

    $course1 = [];

    if (!empty($all_allowcourse)) {
        list($in_sql, $params) = $DB->get_in_or_equal($all_allowcourse, SQL_PARAMS_NAMED);
        $course1 = $DB->get_records_sql(
            "SELECT c.id, c.fullname
            FROM {course} c
            WHERE c.visible = 1 AND c.id $in_sql
            ORDER BY c.fullname", 
            $params
        );
    }


      $course2 = $DB->get_records_sql("
        SELECT c.id, c.fullname 
        FROM {course} as c
        INNER join {course_modules} as cm ON cm.course = c.id
        INNER JOIN {modules} as m ON cm.module = m.id
        WHERE c.category = :categoryid AND c.visible = 1 AND (m.name = 'reservation' OR m.name = 'zoom')
        ORDER BY fullname
    ", ['categoryid' => $categoryid]);  
      $courses = array_merge($course1, $course2);

    echo json_encode([
        "course" => array_values($courses),
        "getallowcourse" => $companycourseids, 
    ]);
    exit();
}

http_response_code(400);
echo json_encode(["error" => "Invalid action"]);
exit();
