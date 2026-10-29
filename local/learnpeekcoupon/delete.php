<?php

require("../../config.php");
require_login();
global $DB, $OUTPUT, $PAGE;

// Get required coupon ID
$id = required_param('id', PARAM_INT);

// Setup page
$PAGE->set_url(new moodle_url('/local/learnpeekcoupon/delete.php', ['id' => $id]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title("Delete coupon");

// Optional: require capability
// require_capability('local/learnpeekcoupon:delete', context_system::instance());

// Fetch the record or show error if not found
$record = $DB->get_record('local_coupon', ['id' => $id], '*', MUST_EXIST);

// Check if confirmed and sesskey is valid
$confirm = optional_param('confirm', 0, PARAM_BOOL);
if ($confirm && confirm_sesskey()) {
    $DB->delete_records('local_coupon', ['id' => $id]);
    redirect(new moodle_url('/local/learnpeekcoupon/index.php'), "Coupon deleted Successfully");
}

// Show confirmation page 
echo $OUTPUT->header();

$com_id = iomad::get_my_companyid(context_system::instance());
// $companydata = $DB->get_record("company",array("id"=>$com_id));

$getcompnayadmin = $DB->get_record_sql("SELECT * FROM {company_users} WHERE companyid = ? AND userid =?",array($com_id,$USER->id));

if(is_siteadmin() || $getcompnayadmin->managertype ==1 && $record->companyid == $com_id){

$yesurl = new moodle_url('/local/learnpeekcoupon/delete.php', [
    'id' => $id,
    'confirm' => 1,
    'sesskey' => sesskey()
]);

$nourl = new moodle_url('/local/learnpeekcoupon/index.php');

echo $OUTPUT->confirm(get_string("areyousureyouwanttodeletecoupon","local_learnpeekcoupon"),
    $yesurl,
    $nourl
);
}else{
    redirect(new moodle_url($CFG->wwwroot.'/local/learnpeekcoupon/index.php'),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
}

echo $OUTPUT->footer();
