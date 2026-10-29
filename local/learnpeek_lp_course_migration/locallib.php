<?php
function generatelpcourseshortname($coursename, $randamno){
   global$DB, $CFG;
    $coursename=str_replace(" ","",trim($coursename));
    $courseshortname=strtolower($coursename).'-'.$randamno;
    $error = false;
    if ( !$error && $DB->record_exists('course', array('shortname' => $courseshortname))){
        $randamno = rand(100,1000);
        $courseshortname = generatecourseshortname($coursename,$randamno);
    }
    return $courseshortname;
}
function create_lp_course($courseinfo,$editoroptions){
	global$DB, $CFG;
	// $CFG->dirroot.'/course/lib.php';
	require_once($CFG->dirroot.'/course/lib.php');
	require_once($CFG->libdir."/moodlelib.php");
	$coursedata = new stdClass();
	$coursedata->fullname = $courseinfo->fullname;
	$coursedata->category = $courseinfo->category;
	$coursedata->summary = $courseinfo->summary;
	$randamn = rand(100,1000);
	$coursedata->shortname = generatelpcourseshortname($courseinfo->fullname,$randamn);
	$coursedata->idnumber = '';
	$coursedata->idnumber = '';
	$coursedata->visible = 1;
	$coursedata->summaryformat = 1;
	$coursedata->overviewfiles_filemanager =0;
	$coursedata->format = 'topics';
	$coursedata->showgrades = 0;
	$coursedata->newsitems = 0;
	$coursedata->maxbytes = 2122317824;
	$coursedata->enablecompletion = 1;
	$coursedata->showactivitydates = 1;
	$coursedata->startdate = time();
	$coursedata->enddate = 0;
	// echo "<pre>";
	// print_r($coursedata);
	// echo "</pre>";
	// die();
	$course = create_course($coursedata, NULL);
	if($course){
		purge_all_caches();
	}
	return $course->id;
}
use core_completion\progress;
function lms_getuser_enrolled_course_data($userid){
	global$DB, $CFG;
	require_once($CFG->dirroot . '/lib/enrollib.php');
   $enrolcourses = enrol_get_users_courses($userid);
   $enrolledcoursedata = [];
   foreach ($enrolcourses as $course) {
		$percentage = \core_completion\progress::get_course_progress_percentage($course,$userid);
		$percentage = round($percentage, 2);
		$course_image_url = lmscourse_image($course->id);
		$item = new stdClass();
		$item->course_id        = $course->id;
		$item->course_name      = $course->fullname;
		$item->progress         = $percentage;
		$item->course_image_url = $course_image_url;
		$enrolledcoursedata[] = $item;
   }
   return $enrolledcoursedata;
}
function lmscourse_image($courseid) {
	global $DB, $CFG;
	require_once($CFG->dirroot. '/course/classes/list_element.php');
	$course = $DB->get_record('course', array('id' => $courseid));
	$course = new core_course_list_element($course);
	foreach ($course->get_course_overviewfiles() as $file) {
	$isimage = $file->is_valid_image();
	$imageurl = file_encode_url("$CFG->wwwroot/pluginfile.php", '/'. $file->get_contextid(). '/'. $file->get_component(). '/'. $file->get_filearea(). $file->get_filepath(). $file->get_filename(), !$isimage);
		// return html_writer::empty_tag('img', array('src' => $imageurl, 'alt' => $course->fullname, 'class' => 'courseimage'));
		// return $imageurl;
	}
	if(empty($imageurl)){
	$color = customcoursecolor($course->id);
	$pattern = new \core_geopattern();
	$pattern->setColor($color);
	$pattern->patternbyid($courseid);
	$classes = 'coursepattern';
	$imageurl = $pattern->datauri();

	
	$svg_collection = array('Untitled_blue.jpg', 'Untitled_gray.jpg', 'Untitled_purple.jpg', 'Untitled_skyblue.jpg');
	$image = array_rand($svg_collection, 1);
	$imageurl  = $CFG->wwwroot."/blocks/custom_mycourse/src/images/".$svg_collection[$image];

	}
	// return html_writer::empty_tag('img', array('src' => $imageurl, 'alt' => $course->fullname, 'class' => 'courseimage'));
	return $imageurl;
}
function customcoursecolor($courseid) {
	$basecolors = ['#81ecec', '#74b9ff', '#a29bfe', '#dfe6e9', '#00b894', '#0984e3', '#b2bec3', '#fdcb6e', '#fd79a8', '#6c5ce7'];
	$color = $basecolors[$courseid % 10];
	return $color;
}
?>
