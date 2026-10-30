<?php
require_once('../../config.php');
global $DB;
require_login(); 
$id = required_param('id', PARAM_TEXT);

 $sql = "
    SELECT 
        csc.id as cscid, ii.*, 
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

foreach($data as $item){
	if($item->invoiceableitemtype == 'licenseblock'){
		$query = "SELECT cu.*,ci.companylicense_id FROM {companylicense_info} AS ci LEFT JOIN {companylicense_users} AS cu ON ci.companylicense_id = cu.licenseid
				  WHERE ci.invoiceitem_id = $item->id";
		$result = $DB->get_records_sql($query);
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
	
	$DB->delete_records('invoiceitem', ['id' => $item->id]);
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


$redirecturl = new moodle_url('/blocks/learnpeek_shop/orderlist.php');
redirect($redirecturl, 'Order Deleted Successfully', null, \core\output\notification::NOTIFY_SUCCESS);
