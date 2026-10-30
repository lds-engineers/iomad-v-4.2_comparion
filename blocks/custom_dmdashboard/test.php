<?php
require_once(dirname(__FILE__) . '/../../config.php');
global $DB, $CFG;
 require_once($CFG->dirroot . '/lib/enrollib.php');
 require_once($CFG->dirroot . '/blocks/custom_mycourse/locallib.php');
use core_completion\progress;
echo $user = 1278;
$courses = enrol_get_users_courses($user);
foreach ($courses as $course) {
 $percentage = \core_completion\progress::get_course_progress_percentage($course, $user);

echo "<pre>";
print_r($course);
echo " $user # $percentage";
echo "</pre>"; 
}
