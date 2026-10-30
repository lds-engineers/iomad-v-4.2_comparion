<?php
require_once('../../../config.php');
require_login();

global $DB;

$data = json_decode(file_get_contents('php://input'));
if (!isset($data->id) || !isset($data->name)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit;
}

$updated = $DB->update_record('customdepartment', [
    'id' => $data->id,
    'name' => $data->name
]);

echo json_encode(['success' => $updated]);
