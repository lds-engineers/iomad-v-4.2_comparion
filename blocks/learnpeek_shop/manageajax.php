<?php 
if ($_POST['action'] == 'manage_company') {
	require_once(dirname(__FILE__) . '/../../config.php');

	global $DB, $PAGE, $CFG, $OUTPUT;
	$companyid = $_POST['company_id'];
	$companies = $DB->get_record_sql("SELECT * FROM {company}  WHERE id =".$companyid);
	if($companies){
		// $allcategory = $DB->get_records_sql("SELECT * FROM {course_categories} WHERE visible = 1");
		$allcategory = $DB->get_records_sql("SELECT cc.* FROM {course_categories} AS cc LEFT JOIN {company} AS c ON cc.id = c.category WHERE c.category IS NULL OR c.category=".$companies->category);
		$companies->allcategory = array_values($allcategory);
	}
    echo json_encode($companies);
    // print_r($_POST); 
}
if ($_POST['action'] == 'create_department') {
	require_once(dirname(__FILE__) . '/../../config.php');
	global $DB, $PAGE, $CFG, $OUTPUT;
	$companyid = $_POST['company_id'];
	 $busnessusers = $DB->get_records_sql("SELECT u.id,u.firstname,u.lastname,u.email,cu.companyid,uif.data FROM {company_users} as cu INNER JOIN {user} as u ON cu.userid = u.id LEFT JOIN {user_info_data} as uif ON uif.userid = u.id AND uif.fieldid = ? WHERE cu.managertype = 2 AND u.suspended = 0 AND cu.companyid =?", array($userfield->id,$companyid));
	 // foreach ($busnessusers as $key => $busnessuser) {
	 // 	echo '<option value='.$busnessuser->key.'> '.$busnessuser->firstname .' '.lastname'</option>'
	 // }

	 // print_r($busnessuser);



}