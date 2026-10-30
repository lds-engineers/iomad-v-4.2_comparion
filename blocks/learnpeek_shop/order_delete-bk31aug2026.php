<?php
require_once('../../config.php');
global $DB;
require_login(); 
$id = required_param('id', PARAM_TEXT);

$sql = $sql = "
    SELECT 
        ii.*, 
        i.userid AS userid,
        csc.courseid AS courseid
    FROM 
        {invoice} i
    INNER JOIN 
        {invoiceitem} ii ON ii.invoiceid = i.id
    LEFT JOIN 
        {course_shopsettings_courses} csc ON ii.invoiceableitemid = csc.itemid
    WHERE 
        i.id = $id
";


$data = $DB->get_records_sql($sql);
// echo "<pre>";
// print_r($data);
// die('stop');
foreach($data as $item){
	// echo "<br> ================== full data ======================<br>";
	// print_r($item);
	// echo "<br> ================== full data ======================<br>";
	if($item->invoiceableitemtype == 'licenseblock'){
		$query = "SELECT cu.*,ci.companylicense_id FROM {companylicense_info} AS ci LEFT JOIN {companylicense_users} AS cu ON ci.companylicense_id = cu.licenseid
				  WHERE ci.invoiceitem_id = $item->id";
		$result = $DB->get_records_sql($query);
				// echo "<pre>";
		// print_r($data);
		// print_r($result);
		// print_r($result->companylicense_id);
		$companylicense_id = '';
		foreach($result as $res){
			$companylicense_id = $res->companylicense_id;
			if($res->id){
				unenrol_user($res->userid, $res->licensecourseid, $item->groupid, true);
				$DB->delete_records('companylicense_users', ['id' => $res->id]);
			}else{
				$DB->delete_records('companylicense_info', ['invoiceitem_id' => $item->id]);
			}
		}
		
		$DB->delete_records('companylicense', ['id' => $companylicense_id]);

	}else{
		unenrol_user($item->userid, $item->courseid, $item->groupid, false);
	}
	// if($item->invoiceableitemtype == 'singlepurchase'){
	// 	$query = "SELECT * FROM {course_shopsettings_courses} csc WHERE itemid = $item->invoiceableitemid";
	// 	$result = $DB->get_records_sql($query);
	// 	// echo "<br>".$result->courseid."<br>";
	// 	foreach ($result as $res){
	// 		echo "<br> ================== singleprchasedata ======================<br>";
	// 		print_r($res);
	// 		echo "<br> ================== singleprchasedata ======================<br>";
	// 		unenrol_user($item->userid, $result->courseid, $item->groupid);
	// 	}
	// }else{
	// 	$query = "SELECT cu.* FROM {companylicense_users} cu 
	// 	INNER JOIN {companylicense_info} ci ON ci.companylicense_id = cu.licenseid 
	// 	WHERE ci.invoiceitem_id = $item->id";
	// 	$result = $DB->get_records_sql($query);
	// 	foreach ($result as $res){
	// 		echo "<br> ================== license block ======================<br>";
	// 		print_r($res);
	// 		echo "<br> ================== license block ======================<br>";
	// 		unenrol_user($res->userid, $res->licensecourseid, $item->groupid);
	// 	}
	// }
	
	$DB->delete_records('invoiceitem', ['id' => $item->id]);
	// echo "course id  ---- ".$item->courseid;
	// $query = "SELECT ue.* FROM {user_enrolments} ue INNER JOIN {enrol} e ON ue.enrolid = e.id WHERE ue.userid = $item->userid AND e.courseid = $item->courseid";
	// echo $query;
	// $result = $DB->get_records_sql($query);
	// echo "user enrolment data<pre>";
	// print_r($result);
}
$DB->delete_records('invoice', ['id' => $id]);
// die;
function unenrol_user($userid, $courseid, $groupid, $islicense) {
    global $DB;

	if(!empty($groupid)){
		$DB->delete_records('groups_members', ['userid' => $userid, 'groupid' => $groupid]);
	}
    

	$sql = "SELECT * FROM {groups} g INNER JOIN {groups_members} gm ON g.id = gm.groupid WHERE g.courseid = $courseid AND gm.userid = $userid";
	$data = $DB->get_records_sql($sql);
	if ($data) {
		return;
	}

	unenrol($userid, $courseid, $islicense);

}

function unenrol($userid, $courseid, $islicense) {
    // Determine the enrolment plugin to use.
    $enroltype = $islicense ? 'license' : 'manual';
    $plugin = enrol_get_plugin($enroltype);

    if ($plugin) {
        // Get all enrol instances for the course.
        $enrol_instances = enrol_get_instances($courseid, true);

        foreach ($enrol_instances as $instance) {
            if ($instance->enrol === $enroltype) {
                // Unenrol the user using the appropriate plugin.
                $plugin->unenrol_user($instance, $userid);
                break;
            }
        }
    }
}
// die('stop');

$redirecturl = new moodle_url('/blocks/learnpeek_shop/orderlist.php');
// // $sql = "DELETE FROM {tool_courserating_rating} WHERE " . $DB->sql_compare_text('id') . " = " . $DB->sql_compare_text(':id');
// if($data = $DB->execute($sql, ['id' => $id])) {
	redirect($redirecturl, 'Order Deleted Successfully', null, \core\output\notification::NOTIFY_SUCCESS);
// } else {
// 	redirect($redirecturl, "Something is went wrong!", null, \core\output\notification::NOTIFY_ERROR);
// }