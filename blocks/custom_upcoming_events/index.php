<?php 
require_once("../../config.php");
global $DB,$CFG,$USER;
require_once($CFG->dirroot . '/lib/enrollib.php');
require_once($CFG->dirroot . '/calendar/externallib.php');
use core_calendar_external;

$data = enrol_get_users_courses($USER->id);
$evts = [];
foreach ($data as $course) {
	$events = core_calendar_external::get_calendar_upcoming_view($course->id,$course->category);
	foreach ($events->events as $event) {	
		array_push($evts,$event);
	}
}


foreach ($evts as $evt) {

	$max_time = strtotime('+30 days');

	if($evt->timestart > time() && $evt->timestart < $max_time){
	    // echo '<pre>----';
	    // print_r($evt);
	    // echo '</pre>';


	}

}

    die;

?>