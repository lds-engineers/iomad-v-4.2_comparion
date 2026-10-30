<?php
require_once('../../config.php');
require_login();

global $DB, $USER;

$userid    = required_param('userid', PARAM_INT);
$status    = required_param('status', PARAM_INT);
$companyid = required_param('companyid', PARAM_INT);

if (is_siteadmin()) {
    die(json_encode(['error' => 'Not allowed']));
}


try {
    $DB->execute("
        UPDATE {sst_cart_allow}
        SET is_current = 0
        WHERE company_id = ?
        AND user_id = ?
        AND is_current = 1
    ", [$companyid, $userid]);
    
    $record = new stdClass();
    $record->company_id = $companyid;
    $record->user_id    = $userid;
    $record->allowed_by = $USER->id;
    $record->status     = $status;
    $record->is_current = 1;
    $record->created_at = time();
    $DB->insert_record('sst_cart_allow', $record, false);

    $formatteddate = date('m-d-Y', $record->created_at);

    // echo json_encode(['success' => true]);
    echo json_encode([
        'success' => true,
        'date' => $status == 1 ? $formatteddate : ''
    ]);

} catch (Exception $e) {
    print_r($e);
    echo json_encode(['error' => 'Database error']);
}
