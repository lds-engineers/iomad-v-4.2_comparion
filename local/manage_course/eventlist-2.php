<?php
require('../../config.php');
//require('gear.php');
require_login();
$PAGE->set_context(context_system::instance());
global $DB, $USER;
$delete          = optional_param('delete', 0, PARAM_INT);
$confirm          = optional_param('confirm', "0", PARAM_RAW);
$returnurl = new moodle_url('/local/manage_course/eventlist.php', array());
$PAGE->requires->jquery();
$PAGE->set_pagelayout('base');
$PAGE->set_title('Manage Event');
$PAGE->set_heading('Manage Event');
echo $OUTPUT->header();

$companyid = iomad::get_my_companyid(context_system::instance());
?>
<div class="bundle_table">
<?php 
	 $allevents = array();
		// echo "SELECT cm.course FROM {course_modules} AS cm INNER JOIN {modules} as m ON cm.module = m.id INNER JOIN {company_course} as cc ON cc.courseid = cm.course WHERE cm.deletioninprogress =0 AND  m.name = 'zoom' AND cc.companyid = '".$companyid."' GROUP BY cm.course";
		$getevent =$DB->get_records_sql("SELECT cm.course FROM {course_modules} AS cm INNER JOIN {modules} as m ON cm.module = m.id INNER JOIN {company_course} as cc ON cc.courseid = cm.course WHERE cm.deletioninprogress =0 AND  m.name = 'zoom' AND cc.companyid = '".$companyid."' GROUP BY cm.course");
		foreach ($getevent as $getevents) {
		   array_push($allevents, $getevents->course);
		}
		foreach ($allevents as $courseid) {
			echo "course_id #" .$courseid;
			$coursesection =  $DB->get_records_sql("SELECT
			     cs.*, g.id as group_id, g.participation,g.member_allow 
			     FROM 
			     {course_sections} as cs 
			     LEFT JOIN 
			     {groups} as g 
			     ON JSON_UNQUOTE(JSON_EXTRACT(cs.availability, '$.c[0].id')) = g.id 
			     WHERE cs.course = ? AND visible = ? AND cs.name IS NOT NULL", 
			     array($courseid,1));
			if($coursesection){
				foreach ($coursesection as $csection) {
					echo '<div class="course_section">'.$csection->name.'<div>';

					$getgroupevents = $DB->get_records_sql("SELECT cm.*,m.name 
                                FROM {course_modules} as cm 
                                INNER JOIN {modules} as m 
                                ON cm.module = m.id AND m.name in ('zoom') 
                                WHERE cm.deletioninprogress !=1 AND section = ?",array($csection->id));
					foreach ($getgroupevents as $groupevent) {
                            if($groupevent){
                                $event_type         = $groupevent->name;
                                if($groupevent->name=="zoom"){
                                    $eventdata   = $DB->get_record_sql("SELECT * FROM {zoom} WHERE id = ? ORDER BY start_time ASC", array($groupevent->instance));
                                    $event_name         = $eventdata->name;
                                    $event_starttime    = $eventdata->start_time;
                                    $event_duration     = $eventdata->duration;
                                    $event_timezone     = $eventdata->timezone;
                                    $event_endtime      = $eventdata->end_date_time;

                                    $eventstarttime = $event_starttime;
                                    $eventendtime = $event_starttime + $event_duration;

                                    $eventhours = floor($event_duration / 3600);
                                    $eventminutes = floor(($eventseconds % 3600) / 60);
                                    $eventseconds = $eventseconds % 60;
                                    $eventduration = sprintf("%02d:%02d:%02d", $eventhours, $eventminutes, $eventseconds);
                                }

                                //===========================================================
                                echo '<p>'.ucfirst($event_type). ' ' . $event_name .'</p>

                                <p>Event Time '. date("l: h:i A", $eventstarttime).' - '.date("h:i A", $eventendtime).'</p>';
                                
                            }

                        }
                        echo "<hr>";


				}
				//end course
				echo "<hr>";
				

			}
			
		}

?>
	
</div>
	<style>
	.bundle_table table {
	    width: 100%;
	}
	.table_heading {
	    color: #fff;
	    font-size: 30px;
	    text-align: center;
	    margin: 10px 0;
	    background: #3376d2;
	}
	.bundle_table{
		margin-top:50px;
	}
	.bundle_table table tr th,.bundle_table table tr td{
	    padding:6px 8px; 
	}
	.bundle_table thead {
		background: #0f47ad;
		color: #fff;
		font-size: 16px;
		text-wrap: nowrap;
	}
	.bundle_table table{
		border:1px solid #ddd;
	}
	</style> 


<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/dt/dt-1.10.16/datatables.min.css"/>

<script type="text/javascript" src="https://cdn.datatables.net/v/dt/dt-1.10.16/datatables.min.js"></script>
<!-- <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script> -->

<script>
	$(document).ready(function() {
		$('#usetTable').DataTable();
	} );
</script>
<?php
echo $OUTPUT->footer();
