<?php
function create_user($userdata){
	global $DB,$CFG;
    require_once($CFG->libdir.'/adminlib.php');
    require_once("{$CFG->dirroot}/user/lib.php");
	$userdata2=new stdClass();
	$userdata2->username=$userdata->username;
	$userdata2->email=$userdata->email;
	$userdata2->confirmed=1;
	$userdata2->mnethostid=1;
	$userdata2->lastname=($userdata->lastname)? $userdata->lastname:"Lastname";
	$userdata2->firstname=($userdata->firstname)? $userdata->firstname:"Firstname";
	$userdata2->password=md5($userdata->password);
    $userdata2->timecreated=time();
    $userdata2->timemodified=time();

	if($newuserid=$DB->insert_record("user",$userdata2)){

        // store password
        $sso = new stdClass();
        $sso->userid       = $newuserid;
        $sso->password     = $userdata2->password;
        $sso->updated_date = time();
        $DB->insert_record("sso_user_password", $sso);
        
        // Create USER context for this user.
        $usercontext = context_user::instance($newuserid);

        // Trigger event If required.
        if ($triggerevent) {
            \core\event\user_created::create_from_userid($newuserid)->trigger();
        }

        // Purge the associated caches for the current user only.
        $presignupcache = \cache::make('core', 'presignup');
        $presignupcache->purge_current_user();

		return $newuserid;
	}
	return false;
}
function create_individual_user($userdata){
    global $DB;
    $departmentid = $DB->get_record('department', array('company' => $userdata->company_id));
    $userid = create_user($userdata);
    if($userid){
        // insert records company_users
        $cmpuserdata = new stdclass();
        $cmpuserdata->companyid = $userdata->company_id;
        $cmpuserdata->userid = $userid;
        $cmpuserdata->managertype = 0;
        $cmpuserdata->departmentid = $departmentid->id;
        $cmpuserdata->suspended = 0;
        $cmpuserdata->educator = 0;
        $insercmpuserdata = $DB->insert_record('company_users', $cmpuserdata);
        return $userid;
    }else{
        return false; 
    }

}
function create_compnay_business_user($userdata){
    global $DB;
    // echo "<pre>";
    // print_r($userdata);
    // echo "</pre>";
    // die;
    $departmentid = $DB->get_record('department', array('company' => $userdata->company_id));
    $userid = create_user($userdata);

     // Update custom profile field: user_company_name
    if (!empty($userdata->user_company_name)) {
        $userfield = $DB->get_record('user_info_field', array('shortname' => 'user_company_name'));
        if ($userfield) {
            update_user_field($userid, $userfield->id, $userdata->user_company_name);
        } else {
            debugging('Custom profile field "user_company_name" not found.', DEBUG_DEVELOPER);
        }
    }
    if($userid){
        // insert records company_users
        $cmpuserdata = new stdclass();
        $cmpuserdata->companyid = $userdata->company_id;
        $cmpuserdata->userid = $userid;
        $cmpuserdata->managertype = 2;
        $cmpuserdata->departmentid = $departmentid->id;
        $cmpuserdata->suspended = 0;
        $cmpuserdata->educator = 1;
        $insercmpuserdata = $DB->insert_record('company_users', $cmpuserdata);
        if($insercmpuserdata){
            $systemcontext = context_system::instance();
            $systemcontextid = $systemcontext->id;
            $role = $DB->get_record('role', array('shortname'=>'companydepartmentmanager'));
            if($role){
                
             role_assign($role->id, $userid, $systemcontextid);
            }

        }
        return $userid;
    }else{
        return false; 
    }

}


function update_user_field($userid,$key,$data){ 
    global $DB; 
    if($DB->record_exists('user_info_data',array('userid'=>$userid,'fieldid'=>$key))){
             $sql = "UPDATE {user_info_data} SET `data`= '".$data."' WHERE userid=$userid AND fieldid=$key";
           $res =$DB->execute($sql);
     }else{
        $stdobj = new stdClass();
        $stdobj->userid=$userid;
        $stdobj->fieldid = $key;
        $stdobj->data = $data;
       // print_r($stdobj);
        $DB->insert_record('user_info_data',$stdobj);
     } 
  }
  function guest_user_basket_info() {
    global $DB,$CFG;
    $newsessionid = session_id();
    $get_cart = $DB->get_record_sql("SELECT * FROM {guest_cart} WHERE sessionid = ?",[$newsessionid]);
    if ($get_cart) {
        $cart_items = $DB->get_record_sql("SELECT COUNT(guestcartid) as total_items FROM {guest_cart_items}  WHERE guestcartid = ?",[$get_cart->id]);
        $totalitem = $cart_items->total_items;
        $html = '<p><a href="'.$CFG->wwwroot.'/courses/guestcartitems.php"><i class="fa fa-shopping-cart"></i> '.$totalitem.' </a></p>';
        return $html; 
    } else {
        $html = '<p><a href="'.$CFG->wwwroot.'/courses/guestcartitems.php"><i class="fa fa-shopping-cart"></i> 0 </a></p>';
        return $html; 
    }
}
function company_mapped_category_list($companyid) {
    global $DB;

    $cmappedctg = $DB->get_record_sql("SELECT * FROM {company_category_mapping} WHERE company_id = ?", [$companyid]);

    if (!$cmappedctg || empty($cmappedctg->course_category)) {
        return [];
    }

    // Get mapped categories

    $categories = $DB->get_records_sql(
        "SELECT *
         FROM {course_categories}
         WHERE id IN ({$cmappedctg->course_category})
         ORDER BY parent, sortorder"
    );

    // Build category tree
    $tree = [];
    foreach ($categories as $cat) {
        $tree[$cat->parent][] = $cat;
    }

    return $tree;
}