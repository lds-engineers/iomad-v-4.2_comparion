<?php
require_once('../../../config.php');
require_login();

header('Content-Type: application/json');

global $USER, $DB;
$data = json_decode(file_get_contents("php://input"));

$name = trim($data->name ?? '');
$deptmanagerid = intval($data->deptmanagerid ?? 0);
$companyid = intval($data->companyid ?? 0);

if (empty($name) || !$deptmanagerid) {
    echo json_encode(['success' => false, 'message' => 'Missing data.']);
    exit;
}

// Check if department already exists
if($deptmanagerid) {
    if ($existing = $DB->get_record('customdepartment', ['name' => $name, 'businessuserid' => $deptmanagerid])) {
        echo json_encode(['success' => true, 'id' => $existing->id, 'name' => $existing->name]);
        exit;
    }
} else {
    if ($existing = $DB->get_record('customdepartment', ['name' => $name, 'businessuserid' => $deptmanagerid])) {
        echo json_encode(['success' => true, 'id' => $existing->id, 'name' => $existing->name]);
        exit;
    }
}

// Insert new department
$newdept = new stdClass();
$newdept->name = $name;
$newdept->businessuserid = $deptmanagerid;
$newdept->companyid = $companyid;
$newdept->createdby = $USER->id;
$newdept->timecreated = time();

$newid = $DB->insert_record('customdepartment', $newdept);

echo json_encode(['success' => true, 'id' => $newid, 'name' => $name]);
