<?php
require_once('../../config.php');
global $DB, $CFG, $USER, $PAGE;
require_login();
$userid = optional_param('userid', $USER->id, PARAM_INT);
if(!is_siteadmin()){
	$userid = $USER->id;  // Owner of the page
}

$strmymoodle = get_string('events', 'local_customlayout');
$PAGE->set_title($strmymoodle);
$PAGE->set_heading($strmymoodle);


echo $OUTPUT->header();
echo 'events';
echo $OUTPUT->footer();

?>