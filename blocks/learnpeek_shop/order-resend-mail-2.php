<?php
require('../../config.php');
require_once($CFG->dirroot . '/local/learnpeek_email_template/locallib.php');
require_login();

global $DB, $USER, $CFG;

$invoiceid = required_param('invoiceid', PARAM_INT);
$userid    = required_param('userid', PARAM_INT);

// Get user's company.
$usercompany = $DB->get_record(
    'company_users',
    ['userid' => $userid],
    'companyid'
);

$companyid = $usercompany ? $usercompany->companyid : 0;

if ($userid && $invoiceid) {
    order_complete_email_to_whitelable_2($companyid, $userid, $invoiceid);
    resend_order_complete_email_to_usersssssss(
        $invoiceid,
        $userid
    );

}
