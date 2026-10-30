<?php
require('../../config.php');

global $DB, $USER, $SESSION;
// Get shared record ID
$id = required_param('id', PARAM_INT);
$pageurl = new moodle_url('/blocks/shared_courses/access.php', [
    'id' => $id
]);

// Fetch shared course record
$record = $DB->get_record('shared_courses', ['id' => $id], '*', MUST_EXIST);
if (!$record || !$record->status) {
    throw new moodle_exception('This shared course link is inactive.');
}

// Fetch IdP record
$idp = $DB->get_record('auth_iomadsaml2_idps', [
    'entityid' => $record->idps_entityid
]);
if (!$idp) {
    throw new moodle_exception('IdP not found');
}

// Set company session
if ($company = $DB->get_record('company', ['id' => $idp->companyid])) {
    $SESSION->currenteditingcompany = $company->id;
    $SESSION->theme = $company->theme;
    $SESSION->company = $company;
}

// Return URL → second file
$returnurl = new moodle_url('/blocks/shared_courses/course_access.php', [
    'id' => $id
]);

/* =========================================================
   FORCE LOGOUT + SAML LOGIN (ALWAYS)
   Even if user has an active SAML session
========================================================= */

// Always logout Moodle user first
if (isloggedin() && !isguestuser()) {
    // session_start();
    // session_destroy();
    // unset($USER);
    // unset($SESSION);
    require_logout();

    $SESSION->currenteditingcompany = $company->id;
    $SESSION->theme = $company->theme;
    $SESSION->company = $company;
    $SESSION->accesscourse = $pageurl;
    $SESSION->iomadsaml2idp = md5($idp->entityid);
    
    $logouturl = new moodle_url('/auth/iomadsaml2/logout.php', [
        'return' => $pageurl->out(false)
    ]);

    redirect($logouturl);
    exit;
}

// Store return URL in session
$SESSION->wantsurl = $returnurl->out(false);

// Redirect to SAML login with forceAuthn
$samllogin = new moodle_url('/auth/iomadsaml2/login.php', [
    'idp'        => md5($idp->entityid),
    'passive'    => 'off',
    // 'forceauthn' => 1,            // 🔥 Forces fresh login at IdP
    'wants'      => $returnurl,
]);

redirect($samllogin);
exit;


// old file
// require('../../config.php');

// global $DB, $USER, $SESSION;

// Get shared record ID
// $id = required_param('id', PARAM_INT);

// Fetch shared course record
// $record = $DB->get_record('shared_courses', ['id' => $id], '*', MUST_EXIST);

// if (!$record || !$record->status) {
//     throw new moodle_exception('This shared course link is inactive.');
// }

// Fetch IdP record
// $idp = $DB->get_record('auth_iomadsaml2_idps', [
//     'entityid' => $record->idps_entityid
// ]);

// if (!$idp) {
//     throw new moodle_exception('IdP not found');
// }

// $departmentid = $DB->get_record('department', array('company' => $idp->companyid));

// if($company = $DB->get_record('company', array('id' => $idp->companyid))){
//     $SESSION->currenteditingcompany = $company->id;
//     $SESSION->theme = $company->theme;
//     $SESSION->company = $company;
// }

// This page URL (used for redirect after login)
// $returnurl = new moodle_url('/blocks/shared_courses/access.php', [
//     'id' => $id
// ]);

/* =========================================================
   STEP 1: FORCE SAML LOGIN IF NOT AUTHENTICATED PROPERLY
========================================================= */

// If:
// - Not logged in
// - OR guest user
// - OR logged in but not via iomadsaml2
// if (!isloggedin() || isguestuser() || $USER->auth !== 'iomadsaml2') {
    
//     // If logged in via manual auth, logout first
//     if (isloggedin() && !isguestuser()) {
//         require_logout();
//     }

//     // 🔥 Store return URL in session
//     $SESSION->wantsurl = $returnurl->out(false);

//     // Redirect to SAML login
//     $samllogin = new moodle_url('/auth/iomadsaml2/login.php', [
//         'idp'     => md5($idp->entityid),
//         'passive' => 'off',
//         'wants'     => $returnurl,
//     ]);
//     redirect($samllogin);
//     exit;
// }

/* =========================================================
   STEP 2: USER IS NOW AUTHENTICATED VIA SAML
========================================================= */

// Fetch course
// $course = $DB->get_record('course', [
//     'id' => $record->course_id
// ], '*', MUST_EXIST);

// $context = context_course::instance($course->id);

// Enrol user if not enrolled
// if (!is_enrolled($context, $USER->id)) {

//     $manual = enrol_get_plugin('manual');
//     $instances = enrol_get_instances($course->id, true);

//     foreach ($instances as $instance) {
//         if ($instance->enrol === 'manual') {
//             $manual->enrol_user(
//                 $instance,
//                 $USER->id,
//                 $instance->roleid,
//                 time()
//             );

            /* ====================================
               LOG AUTO ENROLMENT
            ==================================== */

            // $enrollog = new stdClass();
            // $enrollog->sharedcourseid = $record->id;
            // $enrollog->courseid = $course->id;
            // $enrollog->userid = $USER->id;
            // $enrollog->idp_entityid = $record->idps_entityid;
            // $enrollog->enrolmethod = 'auto';
            // $enrollog->timecreated = time();

//             $DB->insert_record('shared_courses_enrol_logs', $enrollog);

//             $exists = $DB->record_exists('company_users', [
//                 'companyid' => $idp->companyid,
//                 'userid' => $USER->id
//             ]);
//             if (!$exists) {
//                 $cmpuserdata = new stdclass();
//                 $cmpuserdata->companyid = $idp->companyid;
//                 $cmpuserdata->userid = $USER->id;
//                 $cmpuserdata->managertype = 0;
//                 $cmpuserdata->departmentid = $departmentid->id;
//                 $cmpuserdata->suspended = 0;
//                 $cmpuserdata->educator = 0;
//                 $insercmpuserdata = $DB->insert_record('company_users', $cmpuserdata);
//             }
//             break;
//         }
//     }
// }

/* ====================================
   LOG COURSE ACCESS
==================================== */

// $accesslog = new stdClass();
// $accesslog->sharedcourseid = $record->id;
// $accesslog->courseid = $course->id;
// $accesslog->userid = $USER->id;
// $accesslog->idp_entityid = $record->idps_entityid;
// $accesslog->timecreated = time();

// $DB->insert_record('shared_courses_access_logs', $accesslog);

// Clear wantsurl so it doesn't interfere later
// unset($SESSION->wantsurl);

// Redirect to course
// redirect(new moodle_url('/course/view.php', [
//     'id' => $course->id
// ]));