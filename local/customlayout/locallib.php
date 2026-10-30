<?php

function create_user($userdata) {
    global $DB, $CFG;

    require_once($CFG->libdir . '/moodlelib.php');
    require_once($CFG->dirroot . '/user/lib.php');

    $userdata2 = new stdClass();
    $userdata2->username = $userdata->username;
    $userdata2->email = $userdata->email;
    $userdata2->confirmed = 1;
    $userdata2->mnethostid = 1;
    $userdata2->lastname = $userdata->lastname ?: "Lastname";
    $userdata2->firstname = $userdata->firstname ?: "Firstname";
    $userdata2->auth = 'manual';
    $userdata2->password = hash_internal_user_password($userdata->password);
    $userdata2->timecreated=time();
    $userdata2->timemodified=time();
    if ($userid = $DB->insert_record("user", $userdata2)) {
        $user = $DB->get_record('user', ['id' => $userid]);
        if ($user && !empty($user->email)) {
            $token = random_string(32);
            $reset = new stdClass();
            $reset->userid = $user->id;
            $reset->timerequested = time();
            $reset->token = $token;
            $DB->insert_record('user_password_resets', $reset);
            $resetlink = $CFG->wwwroot . '/login/forgot_password.php?token=' . $token;
            $subject = 'Set your password for ' . $CFG->sitename;
            $message = "Hi {$user->firstname},\n\n";
            $message .= "Your account has been created. Please use the link below to set your password:\n\n";
            $message .= $resetlink . "\n\n";
            $message .= "Thank you,\n{$CFG->sitename}";
            email_to_user($user, core_user::get_support_user(), $subject, $message);
            return $userid;
        }
    }
    return false;
}

function create_individual_user($userdata) {
    global $DB, $USER;
    $departmentid = $DB->get_record('department', array('company' => $userdata->company_id));
    $userid = create_user($userdata);
    if ($userid) {
        $cmpuserdata = new stdClass();
        $cmpuserdata->companyid = $userdata->company_id;
        $cmpuserdata->userid = $userid;
        $cmpuserdata->managertype = 0;
        $cmpuserdata->departmentid = $departmentid->id ?? 0;
        $cmpuserdata->suspended = 0;
        $cmpuserdata->educator = 0;

        $parent_id = 0;
        if($b_email = $userdata->bussinessuser_email) {
            $bussinessuser = $DB->get_record("user",array("email"=>$b_email));
            $parent_id = $bussinessuser->id;
        } else {
            $context = context_user::instance($USER->id);
            $current_user_roles = get_user_roles($context, $USER->id);
            $manag_role = array_column($current_user_roles, 'shortname');
            if($manag_role[0] == "companydepartmentmanager") {
                $parent_id = $USER->id;
            }
        }
        if($DB->insert_record('company_users', $cmpuserdata)) {
            $businesusrchild = new stdclass();
            $businesusrchild->company_id = $userdata->company_id;
            $businesusrchild->parent_id = $parent_id;
            $businesusrchild->child_id = $userid;
            $businesusrchild->created_date = time();
            $businesusrchildid = $DB->insert_record('business_user_child', $businesusrchild);
        }
        return $userid;
    } else {
        return false; 
    }
}
function get_all_subcategory_ids($parentids, &$children = []) {
    global $DB;

    // Ensure input is always an array
    if (!is_array($parentids)) {
        $parentids = [$parentids];
    }

    foreach ($parentids as $parentid) {
        // Avoid duplicates
        if (!in_array($parentid, $children)) {
            $children[] = $parentid;
        }

        // Get immediate children
        $childcategories = $DB->get_records(
            'course_categories',
            ['parent' => $parentid],
            '',
            'id'
        );

        foreach ($childcategories as $child) {
            if (!in_array($child->id, $children)) {
                $children[] = $child->id;

                // Recursive call for each child
                get_all_subcategory_ids($child->id, $children);
            }
        }
    }

    return $children;
}

// function enrol_user_as_editingteacher($userid, $courseid) {
//     global $DB;

//     // Get the role id for 'editingteacher'
//     $role = $DB->get_record('role', ['shortname' => 'editingteacher'], 'id', MUST_EXIST);
//     $roleid = $role->id;

//     // Get the enrolment instances for this course
//     $instances = enrol_get_instances($courseid, false);
//     $manualinstance = null;

//     // Find the manual enrol instance
//     foreach ($instances as $instance) {
//         if ($instance->enrol === 'manual') {
//             $manualinstance = $instance;
//             break;
//         }
//     }

//     if (!$manualinstance) {
//         debugging("No manual enrol instance found for course id $courseid");
//         return false;
//     }

//     // Get the manual enrol plugin
//     $enrolplugin = enrol_get_plugin('manual');

//     if (!$enrolplugin) {
//         debugging("Manual enrol plugin not available");
//         return false;
//     }

//     // Perform the enrolment
//     $enrolplugin->enrol_user($manualinstance, $userid, $roleid);
//     return true;
// }
