<?php
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/../iomad_company_admin/lib.php');
require_once("locallib.php");
\block_iomad_commerce\helper::require_commerce_enabled();

$itemid         = required_param('itemid', PARAM_INT);
$licenseformempty = optional_param('licenseformempty', 0, PARAM_INT);

// Correct the navbar.
// Set the name for the page.
$linktext = get_string('course_shop_title', 'block_learnpeek_shop');
// Set the url.
$linkurl = new moodle_url('/blocks/learnpeek_shop/shop.php');

// Page stuff.
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);
$PAGE->requires->jquery();
$PAGE->navbar->add($linktext, $linkurl);

$companyid = iomad::get_my_companyid(context_system::instance());

// if ($item = $DB->get_record('course_shopsettings', ['id' => $itemid, 'enabled' => 1, 'companyid' => $companyid])) {
//     $PAGE->navbar->add($item->name);
// }
//"SELECT css.*, c.fullname, c.summary FROM mdl_course_shopsettings as css INNER JOIN mdl_course_shopsettings_courses as csc ON css.id = csc.itemid INNER JOIN mdl_course as c ON c.id = csc.courseid WHERE css.id = 21 AND css.companyid = 3 AND css.enabled = 1"

$item = $DB->get_record_sql('SELECT css.*, c.id as course_id , c.fullname, c.summary 
	FROM {course_shopsettings} as css 
	INNER JOIN {course_shopsettings_courses} as csc ON css.id = csc.itemid 
	INNER JOIN {course} as c ON c.id = csc.courseid 
	WHERE css.id = ? AND css.companyid = ? AND css.enabled = ?',array($itemid,$companyid,1));

// echo "<pre>";
// print_r($item);
// echo "</pre>";
if ($item) {
    $PAGE->navbar->add($item->fullname);
}
echo $OUTPUT->header();

flush();

\block_iomad_commerce\helper::show_basket_info();

if ($item) {

	echo '
	<link rel="stylesheet" href="css/shopstyle.css">
	<div class="cst_column">
		<h3>' . format_string($item->name) . '</h3>
		<div class="cst_img_parent">';
	    if(course_image($item->course_id)){
	        echo course_image($item->course_id);
	    }else{
	        $dumyimage = $CFG->wwwroot.'/blocks/learnpeek_shop/pix/learning.jpeg';
	        echo '<img src="'.$dumyimage.'" alt="'.$item->name.'" class="courseimage">';
	    };
	    echo '</div>';

	    $strbuynow = get_string('buynow', 'block_learnpeek_shop');
	    $strmoreinfo = get_string('moreinfo', 'block_learnpeek_shop');
	    if ($item->long_description) {
	        $productdesc =  $item->long_description;
	    } else {
	        $productdesc = $item->summary;
	    }

	    if($productdesc){
		    echo '
		    <div class="cst_course_description">
		    '.$productdesc.'
	    	</div>
	    	';
	    }

    echo '
    </div>';

    // $coursesection =  $DB->get_records_sql("SELECT * FROM {course_sections} WHERE course = ? AND visible = ? AND name IS NOT NULL", array($item->course_id,1));

    $coursesection =  $DB->get_records_sql("SELECT
     cs.*, g.id as group_id, g.participation,g.member_allow 
     FROM 
     {course_sections} as cs 
     LEFT JOIN 
     {groups} as g 
     ON JSON_UNQUOTE(JSON_EXTRACT(cs.availability, '$.c[0].id')) = g.id 
     WHERE cs.course = ? AND visible = ? AND cs.name IS NOT NULL", 
     array($item->course_id,1));
   /* echo "<pre>";
    print_r($coursesection);
    echo "</pre>";*/

    foreach ($coursesection as $csection) {
        $groupmember = $DB->get_records_sql("SELECT * FROM {groups_members} WHERE groupid = ?",array($csection->group_id));
        $totalmember = count($groupmember);
        $groupmember = (array)$groupmember;
        $available_seats = $csection->member_allow - $totalmember;
        $buynow ='';
        if($available_seats <= 0){
            $available_seats = "<span class='not_avlble'>Not Available</span>";
            $buynow = 'notvisible';
        }


        if(array_search($USER->id, array_column($groupmember, 'userid')) !== false) {
            $purchasestatus =  '<span class="enrol">Enrolled</span>';
        }
        else {
            $purchasestatus = '<a href="'.$CFG->wwwroot.'/blocks/learnpeek_shop/buynow.php?itemid='.$itemid.'&groupid='.$csection->group_id.'" class="btn btn-primary '.$buynow.'">'.get_string('buynow', 'block_learnpeek_shop').'</a>';
        };








        echo '
        <div class="accordion" id="accordion'.$csection->id.'">
          <div class="card">
            <div class="card-header" id="heading'.$csection->id.'">
              <h2 class="mb-0">
                <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#collapse'.$csection->id.'" aria-expanded="true" aria-controls="collapse'.$csection->id.'">
                  '.$csection->name.'
                </button> <span>Available Seats : '.$available_seats.'</span>
                <span>'. $purchasestatus.'</span>
              </h2>
            </div>

            <div id="collapse'.$csection->id.'" class="collapse" aria-labelledby="heading'.$csection->id.'" data-parent="#accordion'.$csection->id.'">
                <div class="card-body">
                <h1 class="my-3 cst_title">Course Schedule</h1>
                <table class="pdh_eventlist_table">
                    <thead>
                        <tr>
                            <th>Location</th>
                            <th>Start Date</th>
                            <th>Event Schedule</th>
                            
                        </tr>
                    </thead>
                    <tbody>';
                        $getgroupevents = $DB->get_records_sql("SELECT cm.*,m.name 
                                FROM {course_modules} as cm 
                                INNER JOIN {modules} as m 
                                ON cm.module = m.id AND m.name in ('zoom') 
                                WHERE cm.deletioninprogress !=1 AND section = ?",array($csection->id));
                        // echo "<pre>";
                        // print_r($getgroupevents);
                        // echo "</pre>";
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
                                echo '
                                <tr>
                                    <td data-title="Location" class="location evttablecoldata">
                                    <h4>
                                        <span class="evtname">'.ucfirst($event_type). ' ' . $event_name .'</span><br>
                                        <span class="evtloctype"> <!--<i class="fa fa-map-marker"></i>--></span><br>
                                        <!--<span class="prop"><strong><a href="https://pdhstar.com" class="text-primary"> https://pdhstar.com </a></strong>
                                        </span>-->
                                    </h4>
                                    </td>
                                    <td data-title="Start Date" class="startdate evttablecoldata">
                                        '. date("M d, Y", $eventstarttime) .'
                                    </td>
                                    <td data-title="Event Schedule" class="eventschedule evttablecoldata">
                                        <div class="evtdayslistcont"><ul class="evtdatedayslist"><li><strong>'. date("l: h:i A", $eventstarttime).' - '.date("h:i A", $eventendtime).'</strong> | '.date("M d, Y", $eventendtime).'</li></ul></div>
                                    </td>
                                    
                                    
                                    <td data-title="Register" class="register evttablecoldata">';
                                     if(array_search($USER->id, array_column($groupmember, 'userid')) !== false) {
                                            echo '<span class="enrol">Enrolled</span>';
                                        }
                                        else {
                                            echo '<a href="'.$CFG->wwwroot.'/blocks/learnpeek_shop/buynow.php?itemid='.$itemid.'&groupid='.$goroup->id.'" class="btn btn-primary '.$buynow.'">'.get_string('buynow', 'block_learnpeek_shop').'</a>';
                                        };

                                        echo ' 
                                    </td> 
                                </tr>';
                                //===========================================================
                            }

                        }
              echo '
                    </tbody>
                    </table>

              </div>
            </div>
          </div>
        </div>
        ';

        
    }
} else {
    echo "<p>" . get_string('courseunavailable', 'block_learnpeek_shop') . "</p>";
}
echo '
<style>
.notvisible{
    display : none !important;
}
</style>
';
echo $OUTPUT->footer();
