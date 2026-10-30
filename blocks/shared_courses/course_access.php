<?php
require('../../config.php');

global $DB, $USER, $SESSION;

// Get shared record ID
$id = required_param('id', PARAM_INT);

// सुरक्षा check (must be logged in)
if (!isloggedin() || isguestuser()) {
    redirect(new moodle_url('/blocks/shared_courses/access.php', ['id' => $id]));
    exit;
}

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

// Fetch department
$departmentid = $DB->get_record('department', [
    'company' => $idp->companyid
]);

/* =========================================================
   STEP 2: USER IS NOW AUTHENTICATED VIA SAML
========================================================= */

// Fetch course
$course = $DB->get_record('course', [
    'id' => $record->course_id
], '*', MUST_EXIST);

$context = context_course::instance($course->id);

// Enrol user if not enrolled
if (!is_enrolled($context, $USER->id)) {

    $manual = enrol_get_plugin('manual');
    $instances = enrol_get_instances($course->id, true);

    foreach ($instances as $instance) {
        if ($instance->enrol === 'manual') {

            $manual->enrol_user(
                $instance,
                $USER->id,
                $instance->roleid,
                time()
            );

            // Log enrolment
            $enrollog = new stdClass();
            $enrollog->sharedcourseid = $record->id;
            $enrollog->courseid = $course->id;
            $enrollog->userid = $USER->id;
            $enrollog->idp_entityid = $record->idps_entityid;
            $enrollog->enrolmethod = 'auto';
            $enrollog->timecreated = time();

            $DB->insert_record('shared_courses_enrol_logs', $enrollog);

            // Add to company if not exists
            $exists = $DB->record_exists('company_users', [
                'companyid' => $idp->companyid,
                'userid' => $USER->id
            ]);

            if (!$exists) {
                $cmpuserdata = new stdClass();
                $cmpuserdata->companyid = $idp->companyid;
                $cmpuserdata->userid = $USER->id;
                $cmpuserdata->managertype = 0;
                $cmpuserdata->departmentid = $departmentid->id;
                $cmpuserdata->suspended = 0;
                $cmpuserdata->educator = 0;

                $DB->insert_record('company_users', $cmpuserdata);
            }

            break;
        }
    }
}

/* ====================================
   LOG COURSE ACCESS
==================================== */

$accesslog = new stdClass();
$accesslog->sharedcourseid = $record->id;
$accesslog->courseid = $course->id;
$accesslog->userid = $USER->id;
$accesslog->idp_entityid = $record->idps_entityid;
$accesslog->timecreated = time();

$DB->insert_record('shared_courses_access_logs', $accesslog);

// Clear wantsurl
unset($SESSION->wantsurl);

// Redirect to course
redirect(new moodle_url('/course/view.php', [
    'id' => $course->id
]));