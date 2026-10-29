<?php
require_once(dirname(__FILE__) . '/../../config.php');
global $DB,$USER;
echo $USER->id;
 $data = enrol_get_users_courses($USER->id);
$enrolledroleteacher = enrol_user_as_editingteacher($USER->id, $courseid);
echo "<pre>";
var_dump($enrolledroleteacher);
echo "</pre>";
die();