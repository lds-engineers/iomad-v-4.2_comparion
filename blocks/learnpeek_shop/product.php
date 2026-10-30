<?php
require_once('../../config.php');
global $CFG,$USER,$DB,$PAGE;
$productid = required_param('itemid', PARAM_INT);
$return = optional_param('return', '', PARAM_TEXT);

// Store in session
$SESSION->lp_return_url = $return;

$producttype = $DB->get_record_sql("SELECT  COUNT(*) AS itemcount FROM {course_shopsettings_courses} WHERE itemid=?",[$productid]);
$itemcount = $producttype->itemcount;
if ($itemcount == 1) {
	$mappedcourse = $DB->get_record("course_shopsettings_courses",array("itemid"=>$productid));
	$courseid = $mappedcourse->courseid;

	$courseinfo = "SELECT cm.id, cm.course, cm.module, m.name
	        FROM {course_modules} cm
	        INNER JOIN {modules} m ON cm.module = m.id
	        WHERE cm.deletioninprogress = 0
	          AND (m.name = 'reservation' OR m.name = 'zoom' OR m.name = 'trainingevent')
	          AND cm.course = :courseid";
	$params = ['courseid' => $courseid];
	$coursetype = $DB->get_record_sql($courseinfo, $params);
	if ($coursetype) {
	    if ($coursetype->name == 'zoom' || $coursetype->name == 'reservation') {
	       $lunchurl = $CFG->wwwroot.'/blocks/learnpeek_shop/event.php?itemid='.$productid;
	    } else if ($coursetype->name == 'trainingevent') {
	       $lunchurl = $CFG->wwwroot.'/blocks/learnpeek_shop/tevent.php?itemid='.$productid;
	    }
	} else {
	   $lunchurl = $CFG->wwwroot.'/blocks/learnpeek_shop/item.php?itemid='.$productid;
	}
} else if ($itemcount > 1) {
    $lunchurl = $CFG->wwwroot.'/blocks/learnpeek_shop/item.php?itemid='.$productid;
} else {
	redirect(new moodle_url($CFG->wwwroot.'/blocks/learnpeek_shop/shop.php'),'no product found', null, \core\output\notification::NOTIFY_ERROR); 
    die();
}

if (!isloggedin() || isguestuser()) {
    // Replace the path only if the user is NOT logged in
    $lunchurl = str_replace('/blocks/learnpeek_shop/', '/courses/', $lunchurl);
    if (strpos($lunchurl, '/courses/item.php') !== false) {
        $lunchurl = str_replace('/courses/item.php', '/courses/guestcart.php', $lunchurl);
    }
    // $lunchurl = "https://staging31.pdhstar.com/my-account/?redirect_to=".$lunchurl;
    // $lunchurl = "https://staging31.pdhstar.com/my-account/";
}else{
	// Logged-in users: only change item.php -> buynow.php
	if (strpos($lunchurl, '/blocks/learnpeek_shop/item.php') !== false) {
        $lunchurl = str_replace(
            '/blocks/learnpeek_shop/item.php',
            '/blocks/learnpeek_shop/buynow.php',
            $lunchurl
        );
    }
}

redirect($lunchurl);
