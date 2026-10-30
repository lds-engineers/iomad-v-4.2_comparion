<?php
require_once('../../../config.php');
require_login();

$businessuserid = required_param('businessuserid', PARAM_INT);
$departmentid = required_param('departmentid', PARAM_INT);
$type = required_param('type', PARAM_INT);

$assigned = [];
$potential = [];

$assigned_ids = [];
$assignments = $DB->get_records('customdepartment_assign', [
    'parent_id' => $businessuserid,
    'department_id' => $departmentid,
    'is_departmentmanager' => $type
]);
foreach ($assignments as $assign) {
    $user = $DB->get_record('user', ['id' => $assign->child_id, 'deleted' => 0]);
    if ($user) {
        $assigned[$user->id] = fullname($user);
        $assigned_ids[] = $user->id;
    }
}

// $childusers = $DB->get_records('business_user_child', ['parent_id' => $businessuserid]);
// foreach ($childusers as $child) {
//     if (!in_array($child->child_id, $assigned_ids)) {
//         $user = $DB->get_record('user', ['id' => $child->child_id, 'deleted' => 0]);
//         if ($user) {
//             $potential[$user->id] = fullname($user);
//         }
//     }
// }

// $childusers = $DB->get_records_sql("SELECT bch.* FROM {business_user_child} bch LEFT JOIN {customdepartment_assign} ca ON ca.parent_id = bch.parent_id AND ca.child_id = bch.child_id WHERE ca.child_id IS NULL AND bch.parent_id = $businessuserid");

if($type == 1){
    $childusers = $DB->get_records_sql("SELECT DISTINCT u.id, u.firstname, u.lastname,ca.is_departmentmanager FROM mdl_business_user_child bch INNER JOIN mdl_user u ON u.id = bch.child_id LEFT JOIN mdl_customdepartment_assign ca ON ca.parent_id = bch.parent_id AND ca.child_id = bch.child_id WHERE bch.parent_id = ? AND u.suspended = 0 AND u.deleted = 0 AND (ca.is_departmentmanager =1 OR ca.is_departmentmanager is null)",array($businessuserid));

}else{
   $childusers = $DB->get_records_sql("SELECT DISTINCT u.id, u.firstname, u.lastname,ca.is_departmentmanager FROM mdl_business_user_child bch INNER JOIN mdl_user u ON u.id = bch.child_id LEFT JOIN mdl_customdepartment_assign ca ON ca.parent_id = bch.parent_id AND ca.child_id = bch.child_id WHERE ca.child_id IS NULL AND bch.parent_id = ? AND u.suspended = 0 AND u.deleted = 0",[$businessuserid]); 
}


foreach ($childusers as $child) {
    $checkassign = $DB->get_record_sql("SELECT * FROM {customdepartment_assign} WHERE department_id = ? AND parent_id = ? AND child_id = ?",[$departmentid,$businessuserid,$child->id]);
    if($checkassign){
        continue;
    }
    if($child->is_departmentmanager == 1){
        $isadmin = " (DM)";
    }else{
        $isadmin = "";
    }
    $potential[$child->id] = $child->firstname .' ' .$child->lastname .' ' .$isadmin;
}

echo json_encode([
    'assigned' => $assigned,
    'potential' => $potential
]);
