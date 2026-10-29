<?php
require_once('../../../config.php');
require_login();

global $DB;

$data = json_decode(file_get_contents('php://input'));
if (!isset($data->id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid department ID.']);
    exit;
}

$deleted = $DB->delete_records('customdepartment', ['id' => $data->id]);

echo json_encode(['success' => $deleted]);
