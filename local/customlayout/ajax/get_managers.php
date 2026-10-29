<?php
require_once('../../../config.php');
require_login();
global $USER, $DB;

$companyid = required_param('companyid', PARAM_INT);
if (!$companyid) {
    echo json_encode(['success' => false, 'message' => 'Missing data.']);
    exit;
}

$company_users = $DB->get_records('company_users', ['managertype'=>1,'companyid'=>$companyid]);
$managerList = [];
foreach ($company_users as $user) {
    $user = $DB->get_record('user', ['id' => $user->userid, 'deleted' => 0]);
    $fullname = $user->firstname." ".$user->lastname;
    $managerList[] = [
        'id' => $user->id,
        'name' => $fullname 
    ] ;
}

echo json_encode($managerList);

