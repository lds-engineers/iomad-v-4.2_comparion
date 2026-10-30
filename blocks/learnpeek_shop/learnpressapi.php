<?php
require_once('../../config.php');
global $CFG,$USER,$DB,$PAGE;
$productid = required_param('itemid', PARAM_INT);
$email = required_param('email', PARAM_RAW);
$wp_siteurl = required_param('wp_siteurl', PARAM_RAW);

$current_host = $_SERVER['HTTP_HOST'];
$getcompany = $DB->get_record_sql("SELECT id,hostname FROM {company} WHERE hostname LIKE ?", array($current_host));
$company_id = $getcompany->id;

$userinfo = $DB->get_record_sql("SELECT u.* FROM {user} u INNER JOIN {company_users} cu on cu.userid = u.id WHERE cu.companyid = :companyid and u.email = :email and u.deleted = 0 LIMIT 1",["email"=>$email, "companyid"=>$company_id]);
if($userinfo){
	$checkproducstatus = $DB->get_record_sql("SELECT i.*,it.invoiceableitemid FROM {invoiceitem} as it INNER JOIN {invoice} as i ON it.invoiceid =i.id WHERE it.invoiceableitemid=? AND i.userid =? AND i.status='p'",[$productid,$userinfo->id]);
	if($checkproducstatus){
		$status = 1;
	}else{
		$status = 0;
	}
}else{
	$status = 0;
}
echo $status;

