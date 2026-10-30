<?php

function local_scan_face_recognition_after_require_login() {
    global $DB, $COURSE, $USER, $CFG;

    $context = context_user::instance($USER->id);
    $current_user_roles = get_user_roles($context, $USER->id);
    $manag_role = array_column($current_user_roles, 'shortname');
    $whitelevel = 0;
    if ($manag_role[0] == 'companymanager') {
        $whitelevel = 1;
    }
    
    if (!$COURSE->id || is_siteadmin() || $whitelevel == 1) {
        return;
    }

    $customfield = get_course_custom_field($COURSE->id, "scanface_required");
    $scanface_required = false;

    if ($customfield && isset($customfield->value) && $customfield->value == 1) {
        $scanface_required = true;
    } else {
        $scanface_required = $DB->record_exists_sql(
            "SELECT 1 FROM {lcl_scan_face_mapping} WHERE courseid = :courseid AND isenabled = 1",
            ['courseid' => $COURSE->id]
        );
    }

    if (!$scanface_required || $COURSE->id <= 1) {
        return;
    }
    $timeCompleted = $DB->get_field_sql("SELECT timecompleted FROM {course_completions} where course=:course and userid=:userid", array("userid"=>$USER->id, 'course'=>$COURSE->id));
    // if(!empty($timeCompleted)){
    //     return;
    // }

    $params = ['userid' => $USER->id];

    $hasRecord = $DB->record_exists('lcl_scan_face_recognition', ['userid' => $USER->id]);
    if (!$hasRecord) {
        redirect("{$CFG->wwwroot}/local/scan_face_recognition/?id={$COURSE->id}");
    }

    $bothNull = $DB->record_exists_sql(
        "SELECT 1 FROM {lcl_scan_face_recognition}
         WHERE userid = :userid AND image IS NULL AND document IS NULL
         ORDER BY id DESC", $params);

    $onlyImage = $DB->record_exists_sql(
        "SELECT 1 FROM {lcl_scan_face_recognition}
         WHERE userid = :userid AND image IS NOT NULL AND document IS NULL
         ORDER BY id DESC", $params);

    $bothExist = $DB->record_exists_sql(
        "SELECT 1 FROM {lcl_scan_face_recognition}
         WHERE userid = :userid AND image IS NOT NULL AND document IS NOT NULL
         ORDER BY id DESC", $params);

    if ($bothNull) {
        redirect("{$CFG->wwwroot}/local/scan_face_recognition/?id={$COURSE->id}");
    } elseif ($onlyImage) {
        redirect("{$CFG->wwwroot}/local/scan_face_recognition/uploaddocument.php?id={$COURSE->id}");
    } elseif ($bothExist) {
        return;
    }

    redirect("{$CFG->wwwroot}/local/scan_face_recognition/?id={$COURSE->id}");
}




function get_course_custom_field($courseid, $fieldname) {
    global $DB;
    $sql = "SELECT cfd.value
            FROM {customfield_data} cfd
            JOIN {customfield_field} cf ON cf.id = cfd.fieldid
            WHERE cfd.instanceid = :courseid 
            AND cf.shortname = :fieldname";
    return $DB->get_record_sql($sql, ['courseid' => $courseid, 'fieldname' => $fieldname]);
}


