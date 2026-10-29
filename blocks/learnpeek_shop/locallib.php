<?php
include_once("../../config.php");
global $CFG,$USER,$DB,$PAGE;
function course_image($courseid){
    global $DB, $CFG;
    require_once($CFG->dirroot. '/course/classes/list_element.php');
    $courserecord = $DB->get_record('course', array('id' => $courseid));
    $course = new core_course_list_element($courserecord);
    foreach ($course->get_course_overviewfiles() as $file) {
        $isimage = $file->is_valid_image();
        $url = file_encode_url("$CFG->wwwroot/pluginfile.php", '/' . $file->get_contextid() . '/' . $file->get_component() . '/' .
        $file->get_filearea() . $file->get_filepath() . $file->get_filename(), !$isimage);
        if ($isimage) {
            return html_writer::empty_tag('img', array('src' => $url, 'alt' => $course->fullname, 'class' => 'courseimage'));
        } else {
            
            return "";
        }
    }
}

function total_enrolled_users_bycourse($courseid){
    global $DB, $USER;

    $companyid = iomad::get_my_companyid(context_system::instance());
    $getcompnayadmin = $DB->get_record_sql("SELECT * FROM {company_users} WHERE companyid = ? ",array($companyid));
    $getcompanydat = $DB->get_record("company", array("id"=>$companyid));
    $categoryid = $getcompanydat->category;
    $courses = $DB->get_records("course",array("category"=>$categoryid, "id"=>$courseid));

    return count($courses);
}

function total_enrolled_compnayusers_bycourse($courseid){
    global $DB, $USER;
    $companyid = iomad::get_my_companyid(context_system::instance());
    // Get the course context
    $context = context_course::instance($courseid);

    // Get enrolled users
    $enrolled_users = get_enrolled_users($context);
    $allenrolledusers = array_column($enrolled_users, 'id');
     if($allenrolledusers){
        $allenrolleduser = implode(",", $allenrolledusers);
     }else{
        $allenrolleduser = 0;
     }
     // echo "SELECT * FROM {company_users} WHERE companyid =".$companyid." AND userid IN (".$allenrolleduser.")";
    $companyuser = $DB->get_records_sql("SELECT * FROM {company_users} WHERE companyid =".$companyid." AND userid IN (".$allenrolleduser.") AND managertype != 1");
    if($companyuser){
        // Return the count of enrolled users
       return count($companyuser);
    }else{
         return 0;
    }

}
function convertTimestampToDate($timestamp, $timezone) {
    // Create a new DateTime object from the timestamp
    $dateTime = new DateTime();
    
    // Set the timezone to the given one
    $dateTime->setTimestamp($timestamp);
    
    // Set the timezone to the provided timezone
    $dateTimeZone = new DateTimeZone($timezone);
    $dateTime->setTimezone($dateTimeZone);
    
    // Format the date into a readable format (e.g., Y-m-d H:i:s)
    return $dateTime->format('Y-m-d H:i:s');
}

// Function to generate star rating
function displayStarRating($rating) {
    // Ensure rating is between 0 and 5
    $rating = max(0, min(5, $rating)); // Clamp rating to range 0-5
    
    $output = '';
    
    // Loop through 5 stars
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= floor($rating)) {
            // Full star
            $output .= '<span class="star filled">&#9733;</span>'; // Filled star (★)
        } elseif ($i - $rating > 0 && $i - $rating < 1) {
            // Half star
            $output .= '<span class="star half">&#9733;</span>'; // Half star (★)
        } else {
            // Empty star
            $output .= '<span class="star empty">&#9734;</span>'; // Empty star (☆)
        }
    }

    // Add CSS for color change based on class
    echo '<style>
            .star { font-size: 24px; }
            .filled { color: #FFD700; }
            .half { color: #FFA500; }
            .empty { color: #D3D3D3; }
         </style>';
    
    return $output;
}


function enrolCourse($courseid, $userid, $roleid,$endtime,$startime) { 
    global $DB, $CFG;
    $query = 'SELECT * FROM {enrol} WHERE enrol = "manual" AND courseid = '.$courseid;
    $enrollmentID = $DB->get_record_sql($query);
    if(!empty($enrollmentID->id)) {
        if (!$DB->record_exists('user_enrolments', array('enrolid'=>$enrollmentID->id, 'userid'=>$userid))) {
            $userenrol = new stdClass();
            $userenrol->status = 0;
            $userenrol->userid = $userid;
            $userenrol->enrolid = $enrollmentID->id;
            $userenrol->timestart  = $startime;
            $userenrol->timeend = $endtime;
            $userenrol->modifierid  = 2;
            $userenrol->timecreated  = time();
            $userenrol->timemodified  = time();
            $enrol_manual = enrol_get_plugin('manual');
            $enrol_manual->enrol_user($enrollmentID, $userid, $roleid, $userenrol->timestart, $userenrol->timeend);
        } else {
            $context = context_course::instance($courseid);
            $contextid= $context->id;
            role_assign($roleid, $userid, $contextid);
        }
    }
}

// Function to unenroll a user from a course
function unenroll_user_from_course($user_id, $course_id) {
    require_once($CFG->libdir . '/externallib.php');
    global $DB, $CFG;

    // Check if the user is enrolled in the course
    $context = context_course::instance($course_id);
    $enrolled = enrol_get_users_courses($user_id);

    // If user is enrolled in the course, unenroll them
    if ($enrolled) {
        // Enrolment plugin instance (e.g., manual enrolment plugin)
        $plugin = enrol_get_plugin('manual');
        
        // Unenroll the user
        $plugin->unenrol_user($context, $user_id);
        echo "User with ID $user_id has been unenrolled from the course with ID $course_id.";
    } else {
        echo "User with ID $user_id is not enrolled in the course with ID $course_id.";
    }
}
function create_trainee($userdata){
    global $DB;

    $userdata2=new stdClass();
    $userdata2->username=$userdata->username;
    $userdata2->email=$userdata->email;
    $userdata2->confirmed=1;
    $userdata2->mnethostid=1;
    $userdata2->lastname=($userdata->lastname)? $userdata->lastname:"Lastname";
    $userdata2->firstname=($userdata->firstname)? $userdata->firstname:"Firstname";
    $userdata2->password=md5($userdata->password);
    $userdata2->timecreated = time();
    $userdata2->timemodified = time();

    if($userid=$DB->insert_record("user",$userdata2)){
        return $userid;
    }
    return false;
}

function create_business_trainee($userdata){
    global $DB, $USER, $CFG;
    $departmentid = $DB->get_record('department', array('company' => $userdata->company_id));
    $userid = create_trainee($userdata);
    if($userid){
        $cmpuserdata = new stdclass();
        $cmpuserdata->companyid = $userdata->company_id;
        $cmpuserdata->userid = $userid;
        $cmpuserdata->managertype = 0;
        $cmpuserdata->departmentid = $departmentid->id;
        $cmpuserdata->suspended = 0;
        $cmpuserdata->educator = 0;
        $insercmpuserdata = $DB->insert_record('company_users', $cmpuserdata);
        if($insercmpuserdata){
            $businesusrchild = new stdclass();
            $businesusrchild->company_id = $userdata->company_id;
            $businesusrchild->child_id = $userid;
            $businesusrchild->department_id = 0;
            $businesusrchild->parent_id = $userdata->parent_id;
            $manag_role = $userdata->role;
            if($manag_role && in_array('companyreporter', $manag_role)) {
                if($userdata->assigdepartment){
                    $department = $DB->get_record_sql("SELECT * FROM {customdepartment_assign} 
                                                        WHERE department_id = ? AND child_id = ? AND is_departmentmanager = 1",
                                                        [$userdata->assigdepartment,$USER->id]);
                }else{
                    $sql = "SELECT ca.parent_id, ca.department_id FROM {customdepartment_assign} ca 
                        INNER JOIN {customdepartment} AS c ON ca.department_id = c.id 
                        WHERE ca.child_id =:child_id AND ca.is_departmentmanager = 1 ORDER BY ca.id DESC";
                    $department = $DB->get_record_sql($sql,['child_id'=>$USER->id]);
                }
                if($department) {
                    $businesusrchild->parent_id = $department->parent_id;
                    $businesusrchild->department_id = $department->department_id;
                    $customdepartment_assign = new stdclass();
                    $customdepartment_assign->department_id = $department->department_id;
                    $customdepartment_assign->parent_id = $department->parent_id;
                    $customdepartment_assign->child_id = $userid;
                    $customdepartment_assign->moddifiedby = $USER->id;
                    $customdepartment_assign->is_departmentmanager = 0;
                    $DB->insert_record('customdepartment_assign', $customdepartment_assign);
                }
            }
            $businesusrchild->created_date = time();
            $businesusrchild->created_by = $USER->id;

            $DB->insert_record('business_user_child', $businesusrchild);
            return $userid;
        }
    }else{
        return false; 
    }

}
function product_mapped_category($companyid){
    global $DB;
    // SELECT DISTINCT(pc.id), pc.category_name FROM mdl_product_category as pc INNER JOIN mdl_product_category_mapping as pcm on pc.id = pcm.product_category_id INNER JOIN mdl_course_shopsettings as css ON pcm.product_id = css.id WHERE css.companyid=32
    $cmappedctg = $DB->get_records_sql("SELECT DISTINCT(cc.id), cc.name FROM {course_categories} as cc INNER JOIN {product_category_mapping} as pcm on cc.id = pcm.product_category_id INNER JOIN {course_shopsettings} as css ON pcm.product_id = css.id WHERE css.companyid= ?", array($companyid));
    $result = array();
    foreach ($cmappedctg as $ccategory) {
        // if ($ccategory->id == $companycat->category) {
        //     continue;
        // }
        $result[] = array(
            'id' => $ccategory->id,
            'name' => $ccategory->name
        );
    }
return $result;
}
function company_mapped_category($companyid) {
    global $DB;
    $cmappedctg = $DB->get_record_sql("SELECT * FROM {company_category_mapping} WHERE company_id = ?", array($companyid));

    $result = array();
    
    if ($cmappedctg && !empty($cmappedctg->course_category)) {
        $companymappedctg = $cmappedctg->course_category;
        $companycat = $DB->get_record_sql("SELECT * FROM {company} WHERE id = ?", array($companyid));
        $allcategory = $DB->get_records_sql("SELECT * FROM {course_categories} WHERE id IN (" . $companymappedctg . ")");

        foreach ($allcategory as $ccategory) {
            // if ($ccategory->id == $companycat->category) {
            //     continue;
            // }

            $result[] = array(
                'id' => $ccategory->id,
                'name' => $ccategory->name
            );
        }
    }

    return $result;
}
function buser_company_mapped_category($companyid,$buserid) {
    global $DB;
    $cmappedctg = $DB->get_record_sql("SELECT * FROM {businessuser_courserestriction} WHERE company_id = ? AND user_id = ?", array($companyid,$buserid));
    if(empty($cmappedctg->course_category)){
        $cmappedctg = $DB->get_record_sql("SELECT * FROM {company_category_mapping} WHERE company_id = ?", array($companyid));
    }

    $result = array();
    
    if ($cmappedctg && !empty($cmappedctg->course_category)) {
        $companymappedctg = $cmappedctg->course_category;
        $companycat = $DB->get_record_sql("SELECT * FROM {company} WHERE id = ?", array($companyid));
        $allcategory = $DB->get_records_sql("SELECT * FROM {course_categories} WHERE id IN (" . $companymappedctg . ")");

        foreach ($allcategory as $ccategory) {
            // if ($ccategory->id == $companycat->category) {
            //     continue;
            // }

            $result[] = array(
                'id' => $ccategory->id,
                'name' => $ccategory->name
            );
        }
    }

    return $result;
}

function save_user_profile_data($user){  
    global $DB; 
    if(is_object($user)){
        foreach ($user as $key => $value) {
            if( strpos($key, "profile_field_") !== false){
                $field = str_replace("profile_field_", "", $key);
                if($fielddata = $DB->get_record('user_info_field',array('shortname'=>$field))){
                    if($DB->record_exists('user_info_data',array('userid'=>$user->id,'fieldid'=>$fielddata->id))){
                        $sql = "UPDATE {user_info_data} SET `data`= '".$value."' WHERE userid=$user->id AND fieldid=$fielddata->id";
                        $res =$DB->execute($sql);
                        return true;
                    }else{
                        $stdobj = new stdClass();
                        $stdobj->userid=$user->id;
                        $stdobj->fieldid = $fielddata->id;
                        $stdobj->data = $value;
                        $DB->insert_record('user_info_data',$stdobj);
                     }
                 }
            }
        }
        return true;
    } 
}
function lp_get_all_subcategories($parentcategoryid) {
    $subcategories = [];

    // Get the parent category object
    $parentcat = \core_course_category::get($parentcategoryid, IGNORE_MISSING);

    if (!$parentcat) {
        return []; // No such category
    }

    // Recursively get all child categories
    $descendants = $parentcat->get_children(); // Direct children

    foreach ($descendants as $subcategory) {
        $subcategories[] = $subcategory->id;

        // Recursive call to get deeper children
        $childsubs = lp_get_all_subcategories($subcategory->id);
        if (!empty($childsubs)) {
            $subcategories = array_merge($subcategories, explode(',', $childsubs));
        }
    }

    return implode(',', $subcategories);;
}

function lp_get_all_course_subcategories($parentcategoryid, &$subcategories = []) {
    global $DB;

    // Get direct children
    $children = $DB->get_records('course_categories', ['parent' => $parentcategoryid]);

    foreach ($children as $child) {
        $subcategories[] = $child->id;
        lp_get_all_course_subcategories($child->id, $subcategories); // Recursive call
    }

    return implode(',', $subcategories);
}



function custom_set_config($key, $value){
    if(!empty($value)){
        set_config($key, $value);
    } else {
        unset_config($key);
    }
}