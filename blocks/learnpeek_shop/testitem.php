<?php
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/../iomad_company_admin/lib.php');
// require $CFG->dirroot.'/local/customlayout/file.php');
require_once("locallib.php");
require_login();
\block_iomad_commerce\helper::require_commerce_enabled();
$licenseid = 1896;
$userid = 2930;
$licensedata = $DB->get_record_sql("SELECT * FROM {companylicense} WHERE id = ?",[$licenseid]);
echo $OUTPUT->header();

$companylicenseinfo = $DB->get_record_sql("SELECT ci.* , ii.groupid FROM {companylicense_info} ci INNER JOIN {invoiceitem} ii ON ii.id = ci.invoiceitem_id WHERE companylicense_id = ?",[$licenseid]);
if($companylicenseinfo->groupid){
    groups_add_member($companylicenseinfo->groupid, $userid);
}
echo "<pre>";
print_r($companylicenseinfo);
echo "</pre>";


echo $OUTPUT->footer();
