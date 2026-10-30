<?php
require_once('../../../config.php');
require_login();
global $USER, $DB;

// $managerid = required_param('managerid', PARAM_INT);
$companyId = required_param('companyId', PARAM_INT);

if (!$companyId) {
    echo json_encode(['success' => false, 'message' => 'Missing data.']);
    exit;
}

$businessList = [];
// $company_users = $DB->get_records('business_user_child', ['company_id'=>$companyId]);
$company_users = $DB->get_records('company_users', array("companyid"=>$companyId,"managertype"=>2));

foreach ($company_users as $user) {
    if (!isset($businessList[$user->userid])) {
        $usersdata = $DB->get_record('user', ['id' => $user->userid, 'deleted' => 0]);
        if ($usersdata) {
            $fullname = fullname($usersdata);
            $businessList[$user->userid] = [
                'id' => $usersdata->id,
                'name' => $fullname
            ];
        }
    }
}
$businessList = array_values($businessList);
echo json_encode($businessList);
