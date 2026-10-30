<?php
require_once('../../../config.php');
require_login();

$deptmanagerid = required_param('deptmanagerid', PARAM_INT);

$departments = $DB->get_records('customdepartment', ['businessuserid' => $deptmanagerid]);
$departmentList = [];
foreach ($departments as $department) {
    $departmentList[] = [
        'id' => $department->id,
        'name' => $department->name
    ] ;
}
echo json_encode($departmentList);
