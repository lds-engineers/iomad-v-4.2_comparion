<?php
require_once('../../config.php');

function create_user($userdata){
	global $DB;

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

	if($userid=$DB->insert_record("user",$userdata2)){
        $sso = new stdClass();
        $sso->userid       = $userid;
        $sso->password     = $userdata2->password;
        $sso->updated_date = time();
        $DB->insert_record("sso_user_password", $sso);
		return $userid;
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
function corporate_create_company($user_details) {
    global $DB;
    $company = $user_details->companyname;
    $userid = create_user($user_details);
    if($userid){
        // create course category
        $categorydata = new stdclass();
        $categorydata->name = $company;
        $categorydata->parent = 0;
        $categorydata->sortorder = 999;
        $categorydata->visible = 1;
        $coursecat_id = $DB->insert_record('course_categories', $categorydata);

        if($coursecat_id){
            // create company 
            $companydata = new stdClass();
            $companydata->name = $company;
            $companydata->shortname = strtolower($company);
            $companydata->city = 'city_name';
            $companydata->country = 'AU'; 
            $companydata->address = ''; 
            $companydata->maildisplay = 2;
            $companydata->mailformat = 1;
            $companydata->maildigest = 0;
            $companydata->maxusers = 0;
            $companydata->autosubscribe = 1;
            $companydata->trackforums = 0;
            $companydata->htmleditor = 1;
            $companydata->screenreader = 0;
            $companydata->timezone = 99;
            $companydata->lang = "en";
            $companydata->theme = 'moove';
            $companydata->category = $coursecat_id;
            $companydata->profileid = '';
            $companydata->suspended = '';
            $companydata->supervisorprofileid = 0;
            $companydata->managernotify = 0;
            $companydata->parentid  = 0;
            $companydata->ecommerce  = 0;
            $companydata->managerdigestday = 0;
            $companydata->previousroletemplateid = 0;
            $companydata->previousemailtemplateid = 0;
            $companydata->companyterminated = 0;
            $companydata->departmentprofileid = 0;
            $companyid = $DB->insert_record('company', $companydata);
            
            if($companyid){
                // create department
                $coursedepart = new stdclass();
                $coursedepart->name = $company; 
                $coursedepart->shortname = strtolower($company); 
                $coursedepart->company = $companyid; 
                $coursedepart->parent = 0;
                $coursedep_id = $DB->insert_record('department', $coursedepart);

                if($coursedep_id){
                    // insert records company_users
                    $insdata = new stdclass();
                    $insdata->companyid = $companyid;
                    $insdata->userid = $userid;
                    $insdata->managertype = 1;
                    $insdata->departmentid = $coursedep_id;
                    $insdata->suspended = 0;
                    $insdata->educator = 1;
                    $insertdata = $DB->insert_record('company_users', $insdata);

                    if($insertdata){
                        // Manager role
                        $roledata = $DB->get_record('role', array('name' => 'Company Manager'));
                        $role_id = $roledata->id;
                        if(!$DB->record_exists('role_assignments', array('roleid'=>$role_id,'userid'=>$userid))){
                            $role_assignments_ins = new stdClass();
                            $role_assignments_ins->roleid = $role_id;
                            $role_assignments_ins->contextid = 1;
                            $role_assignments_ins->userid = $userid;
                            $role_assignments_ins->timemodified = time();
                            $role_assignments_ins->modifierid = $userid;
                            $role_assignments_ins->component = ''; 
                            $role_assignments_ins->itemid = 0;
                            $role_assignments_ins->sortorder= 0;
                            $DB->insert_record('role_assignments', $role_assignments_ins);
                            // return array("companyid"=>$companyid, "departmentid"=>$coursedep_id, "userdata"=>$userdata);
                            return $userid;
                        }
                    }
                }
            } 
        }
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

function generateusername($firstname, $randamno){
    global $DB; 
    $firstname=str_replace(" ","",trim($firstname));
    $username=strtolower($firstname).'-'.$randamno;
    $exists = $DB->record_exists('user', ['username' => $username]);
    if ($exists) {
        $randamno = rand(100,1000);
        $username = generateusername($firstname,$randamno);
    }
    return $username;
}

//functions for create user by sso

function create_user_bysso($userdata){
    global $DB;

    $userdata2=new stdClass();
    $userdata2->username=$userdata->username;
    $userdata2->email=$userdata->email;
    $userdata2->confirmed=1;
    $userdata2->mnethostid=1;
    $userdata2->lastname=($userdata->lastname)? $userdata->lastname:"Lastname";
    $userdata2->firstname=($userdata->firstname)? $userdata->firstname:"Firstname";
    $userdata2->password=$userdata->password;
    $userdata2->timecreated=time();
    $userdata2->timemodified=time();

    if($userid=$DB->insert_record("user",$userdata2)){
        return $userid;
    }
    return false;
}

function create_individual_user_bysso($userdata){
    global $DB;
    $departmentid = $DB->get_record('department', array('company' => $userdata->company_id));
    $userid = create_user_bysso($userdata);
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

function create_compnay_business_user_bysso($userdata){
    global $DB;
    $departmentid = $DB->get_record('department', array('company' => $userdata->company_id));
    $userid = create_user_bysso($userdata);

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



function convert_individual_to_business_user_bysso($userdata) {
    global $DB;

    $userid = $userdata->userid;
    $companyid = $userdata->company_id;
    $checkuserrole = $DB->get_record(
        'company_users',
        array(
            'companyid' => $companyid,
            'userid'   => $userid,
        ),
        '*',
        IGNORE_MULTIPLE
    );
    if($checkuserrole->managertype == 2){
        return $userid;
    }


    $department = $DB->get_record(
        'department',
        array('company' => $companyid),
        '*',
        IGNORE_MULTIPLE
    );


    $companyuser = $DB->get_record(
        'company_users',
        array(
            'companyid' => $companyid,
            'userid'   => $userid
        ),
        '*',
        IGNORE_MULTIPLE
    );

    if ($companyuser) {
        $companyuser->managertype  = 2;
        $companyuser->departmentid = $department->id;
        $companyuser->suspended    = 0;
        $companyuser->educator     = 1;
        $updated = $DB->update_record('company_users', $companyuser);

    }

    if (!empty($userdata->user_company_name)) {

        $userfield = $DB->get_record(
            'user_info_field',
            array('shortname' => 'user_company_name')
        );

        if ($userfield) {

            update_user_field(
                $userid,
                $userfield->id,
                $userdata->user_company_name
            );

        }
    }

    $systemcontext = context_system::instance();

    $role = $DB->get_record(
        'role',
        array('shortname' => 'companydepartmentmanager')
    );

    if ($role) {

        role_assign(
            $role->id,
            $userid,
            $systemcontext->id
        );

    }
    return $userid;
}




function check_company_access_restriction() {
    global $DB, $USER;
    // uday
    // Default: show button.
    // $is_hide = false;

    // // Site admins can always see the button.
    // if (!is_siteadmin()) {

    //     // Get current host.
    //     $currenthost = $_SERVER['HTTP_HOST'];

    //     // Get company by hostname.
    //     $company = $DB->get_record_sql(
    //         "SELECT id, shortname
    //         FROM {company}
    //         WHERE hostname LIKE ?",
    //         [$currenthost]
    //     );

    //     if ($company) {

    //         // Get saved registration settings.
    //         $settings = $DB->get_record_sql(
    //             "SELECT companies
    //             FROM {registration_settings}
    //         ORDER BY id ASC
    //             LIMIT 1"
    //         );

    //         if ($settings && !empty($settings->companies)) {

    //             $blockedcompanies = json_decode($settings->companies, true);

    //             if (
    //                 is_array($blockedcompanies) &&
    //                 in_array($company->shortname, $blockedcompanies, true)
    //             ) {
    //                 $is_hide = true;
    //             }
    //         }
    //     }
    // }
    // return $is_hide;

    $is_hide = false;

    //  admin allowed
    if (is_siteadmin()) {
        return false;
    }

    // Case 1: Logged-in user (non-admin)
    if (isloggedin() && !isguestuser()) {

        $company = $DB->get_record_sql(
            "SELECT c.id, c.shortname
             FROM {company} c
             JOIN {company_users} cu ON cu.companyid = c.id
             WHERE cu.userid = ?",
            [$USER->id]
        );

    }else{
      // Case 2: Not logged-in → detect by hostname
      $currenthost = $_SERVER['HTTP_HOST'];

        // Get company by hostname.
        $company = $DB->get_record_sql(
            "SELECT id, shortname
            FROM {company}
            WHERE hostname LIKE ?",
            [$currenthost]
        );
    }

    // Site admins can always see the button.
    if ($company) {

        // Get saved registration settings.
        $settings = $DB->get_record_sql(
            "SELECT companies
            FROM {registration_settings}
        ORDER BY id ASC
            LIMIT 1"
        );

        if ($settings && !empty($settings->companies)) {

            $blockedcompanies = json_decode($settings->companies, true);

            if (
                is_array($blockedcompanies) &&
                in_array($company->shortname, $blockedcompanies, true)
            ) {
                $is_hide = true;
            }
        }
    }
    return $is_hide;
}