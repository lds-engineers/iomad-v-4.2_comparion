<?php
use core_completion\progress;
require('../../config.php');
require_once($CFG->dirroot . '/lib/enrollib.php');
require_once("../../course/externallib.php");
global $DB, $USER, $PAGE;

$filterdat = $_GET['filterdat'];
$search = $_GET['search'];
$sort = $_GET['sort'];
$userid = $_GET['userid'];

if($sort == 'coursename'){
    $ord = 'c.fullname ASC';
}else{
    $ord = 'la.timeaccess DESC';
}

$srch='';
if(!empty($search)){
    $srch = "AND c.fullname LIKE '%$search%'";
}

$filt='';
if($filterdat != 'all' && $filterdat == 'inprogress'){
    $filt="AND c.startdate < ".time()." AND (c.enddate = 0 OR c.enddate > ".time()." )";
}elseif($filterdat != 'all' && $filterdat == 'future'){
    $filt="AND c.startdate > ".time()."";
}elseif($filterdat != 'all' && $filterdat == 'past'){
    $filt="AND (c.enddate < ".time()." AND c.enddate != 0)";
}else{
    $filt='';
}


function coursecolor($courseid) {
    $basecolors = ['#81ecec', '#74b9ff', '#a29bfe', '#dfe6e9', '#00b894', '#0984e3', '#b2bec3', '#fdcb6e', '#fd79a8', '#6c5ce7'];
    $color = $basecolors[$courseid % 10];
    return $color;
}
function getcourse_image($courseid) {
    global $DB, $CFG;
    require_once($CFG->dirroot. '/course/classes/list_element.php');
    $course = $DB->get_record('course', array('id' => $courseid));
    $course = new core_course_list_element($course);
    foreach ($course->get_course_overviewfiles() as $file) {
        $isimage = $file->is_valid_image();
        $imageurl = file_encode_url("$CFG->wwwroot/pluginfile.php", '/'. $file->get_contextid(). '/'. $file->get_component(). '/'. $file->get_filearea(). $file->get_filepath(). $file->get_filename(), !$isimage);
        return $imageurl;
    }
    if(empty($imageurl)){
        $color = coursecolor($course->id);
        $pattern = new \core_geopattern();
        $pattern->setColor($color);
        $pattern->patternbyid($courseid);
        $classes = 'coursepattern';
        $imageurl = $pattern->datauri();
    }
    return $imageurl;
}

$qsql = "SELECT DISTINCT c.id, c.*, la.timeaccess AS lastaccess
FROM mdl_user_enrolments ue 
JOIN mdl_enrol e ON ue.enrolid = e.id 
JOIN mdl_course c ON e.courseid = c.id 
JOIN mdl_user_lastaccess la ON la.userid = ue.userid AND la.courseid = c.id
WHERE ue.userid = :userid $srch $filt ORDER BY $ord";

// die;

$data = $DB->get_records_sql($qsql, array('userid' => $userid));


$msg='';
    foreach ($data as $course ) { 
        $percentage = progress::get_course_progress_percentage($course, $userid);
        $percentagedata=(!empty($percentage))? $percentage:0;
        
        $msg .= '
        <div class="col-12 col-lg-3 col-md-4 my-3 custom-course-frame">
            <div class="card">
                <div class="img-frame img-frame-1by1">
                     <a href="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'"><img src="'.getcourse_image($course->id).'" alt="img""></a>
                </div>
                <div class="card-body custom-border">
                    <a href="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'"><h4 class="custom-title head-lime-clamp">'.$course->fullname.'</h4></a>
                    <div class="h-progresss">
                        <div id="myProgresss">
                            <div id="myBarr" style="width : '.round($percentagedata).'%;"></div>
                        </div>
                                   <p>'.round($percentagedata).'%</p>
                    </div> 
                </div>
            </div>
        </div>';
    }


$returndata = new stdClass();
$returndata->html=$msg;
echo json_encode($returndata);