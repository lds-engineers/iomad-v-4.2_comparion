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

 $html = '';
	foreach ($evts as $evt) {
		if($evt->modulename=="reservation"){
			$evtmodulename = "In person";

		}else{
			$evtmodulename =$evt->modulename;
		}

	    $max_time = strtotime('+30 days');

	    if($evt->timestart > time() && $evt->timestart < $max_time){
	    	$eventendtime = $evt->timestart + $evt->timeduration;
	 $html .= '<tr>
	            <td><img alt="certificate" src="'.$OUTPUT->image_url('icon', $evt->modulename).'" height="60" width="100"></td>
	            <td>'.$evtmodulename.'</td>
	            <td>'.date("M d, h:i A",$evt->timestart).'</td>
	            <td>'.date("M d, h:i A",$eventendtime).'</td>
	            <!--<td>'.$evt->activityname.'</td>-->
	            <td><a class="text-dark" href="'.$evt->url.'">'.$evt->url.'</a></td>
	          </tr>';
	    }

	}

echo json_encode($html);


?>