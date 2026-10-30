<?php
require('../../config.php');
 
require_sesskey();

$action = required_param('action', PARAM_ALPHA);
 
if ($action === 'getdata') { 
    $companyid = required_param('companyid', PARAM_INT);
    global $DB;

    $users = $DB->get_records_sql_menu(
        "SELECT 
            u.id,
            CONCAT(u.firstname,' ',u.lastname,' (',u.email,')') AS fullname
        FROM {user} u
        INNER JOIN {company_users} cu ON u.id = cu.userid
        WHERE cu.companyid = :companyid
          AND u.deleted = 0
          AND u.suspended = 0
        ORDER BY u.firstname, u.lastname", 
        ['companyid' => $companyid]); 

    // $users =  $DB->get_records_sql_menu("SELECT u.id, CONCAT(u.firstname, ' ', u.lastname) FROM {company_users} cu INNER JOIN {user} u ON u.id = cu.userid LEFT JOIN {customdepartment_assign} cda ON cda.child_id = cu.userid AND cda.is_departmentmanager = 1 WHERE companyid = :companyid AND (cu.managertype = 1 or managertype = 2 OR cda.id IS NOT NULL) GROUP BY u.id", ['companyid' => $companyid]); 

    $products = $DB->get_records_sql_menu("
        SELECT csc.id, csc.name
        FROM {course_shopsettings} csc
        INNER JOIN {course_shopsettings_courses} ccs ON csc.id = ccs.itemid
        WHERE csc.companyid = :companyid AND csc.enabled = 1
        ORDER BY csc.name
    ", ['companyid' => $companyid]);

    echo json_encode(['users' => $users, 'products' => $products]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid action']);
exit;
