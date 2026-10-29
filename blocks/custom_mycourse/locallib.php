<?php
function getcourse_image($courseid) {
	global $DB, $CFG;
	require_once($CFG->dirroot. '/course/classes/list_element.php');
	$course = $DB->get_record('course', array('id' => $courseid));
	$course = new core_course_list_element($course);
	foreach ($course->get_course_overviewfiles() as $file) {
	$isimage = $file->is_valid_image();
	$imageurl = file_encode_url("$CFG->wwwroot/pluginfile.php", '/'. $file->get_contextid(). '/'. $file->get_component(). '/'. $file->get_filearea(). $file->get_filepath(). $file->get_filename(), !$isimage);
		return html_writer::empty_tag('img', array('src' => $imageurl, 'alt' => $course->fullname, 'class' => 'courseimage'));
	}
	if(empty($imageurl)){
	$color = coursecolor($course->id);
	$pattern = new \core_geopattern();
	$pattern->setColor($color);
	$pattern->patternbyid($courseid);
	$classes = 'coursepattern';
	$imageurl = $pattern->datauri();

	
	$svg_collection = array('Untitled_blue.jpg', 'Untitled_gray.jpg', 'Untitled_purple.jpg', 'Untitled_skyblue.jpg');
	$image = array_rand($svg_collection, 1);
	$imageurl  = $CFG->wwwroot."/blocks/custom_mycourse/src/images/".$svg_collection[$image];

	}
	return html_writer::empty_tag('img', array('src' => $imageurl, 'alt' => $course->fullname, 'class' => 'courseimage'));
}
function coursecolor($courseid) {
	$basecolors = ['#81ecec', '#74b9ff', '#a29bfe', '#dfe6e9', '#00b894', '#0984e3', '#b2bec3', '#fdcb6e', '#fd79a8', '#6c5ce7'];
	$color = $basecolors[$courseid % 10];
	return $color;
}
function blocks_custom_mycourse_get_all_category_ids($parentid, &$children = []) {
    global $DB;

    // Only add the parent once at the top level
    if (empty($children)) {
        $children[] = $parentid;
    }

    // Get immediate children of the given parent category
    $childcategories = $DB->get_records('course_categories', ['parent' => $parentid], '', 'id');

    foreach ($childcategories as $child) {
        $children[] = $child->id;
        // Recursive call to get children of this child
        blocks_custom_mycourse_get_all_category_ids($child->id, $children);
    }

    return $children;
}
function block_custom_mycourse_get_modulesequence($modulename, $courseid, $userid){
    global $DB;
    $outputarray = [];
    $query = "SELECT * FROM {course_sections} WHERE `course` = ".$courseid;
    $sections = $DB->get_records_sql($query);
    $allseqs = array_column($sections, 'sequence');

    if(!empty($allseqs)){
        $seq = implode(",", $allseqs);
        $seqarray = implode(",", array_filter(explode(",", $seq)));
        if(!empty($seqarray)){
            $finalquery = "SELECT cm.* FROM {course_modules} cm INNER JOIN {modules} m on m.id = cm.module WHERE cm.id IN ($seqarray) AND m.name ='{$modulename}'  ORDER BY FIELD( cm.id, $seqarray )";
            $cms = $DB->get_records_sql($finalquery);
            foreach($cms as $cm){
                array_push($outputarray, $cm);
            }
        }
    }
    return $outputarray;
}
