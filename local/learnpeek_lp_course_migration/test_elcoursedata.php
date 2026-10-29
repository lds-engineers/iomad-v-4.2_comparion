<?php
require_once('../../config.php');
global $DB, $CFG, $PAGE, $USER;
require_once($CFG->dirroot . "/local/learnpeek_lp_course_migration/locallib.php");
$userid = $USER->id;
$coursedat = lms_getuser_enrolled_course_data($USER->id);
echo "<pre>";
print_r($coursedat);
echo "</pre>";