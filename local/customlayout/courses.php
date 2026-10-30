<?php
require_once('../../config.php');

use core_completion\progress;

global $DB, $CFG, $USER, $PAGE;
require_login();
$userid = optional_param('userid', $USER->id, PARAM_INT);
if (!is_siteadmin()) {
    $userid = $USER->id;  // Owner of the page
}

// $context = context_user::instance($USER->id);
$context = context_system::instance();
// $PAGE->set_blocks_editing_capability('moodle/my:manageblocks');
$strmymoodle = get_string('courses','local_customlayout');
$pagetitle = $strmymoodle;
$params = array();
$PAGE->set_context($context);
$PAGE->set_url('/local/customlayout/courses.php', $params);
$PAGE->set_pagelayout('mycourses');
$PAGE->add_body_class('limitedwidth');
$PAGE->set_pagetype('my-customcourses');
$PAGE->blocks->add_region('content');
// $PAGE->set_subpage($currentpage->id);
$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);

function coursecolor($courseid)
{
    $basecolors = ['#81ecec', '#74b9ff', '#a29bfe', '#dfe6e9', '#00b894', '#0984e3', '#b2bec3', '#fdcb6e', '#fd79a8', '#6c5ce7'];
    $color = $basecolors[$courseid % 10];
    return $color;
}
function getcourse_image($courseid)
{
    global $DB, $CFG;
    require_once($CFG->dirroot . '/course/classes/list_element.php');
    $course = $DB->get_record('course', array('id' => $courseid));
    $course = new core_course_list_element($course);
    foreach ($course->get_course_overviewfiles() as $file) {
        $isimage = $file->is_valid_image();
        $imageurl = file_encode_url("$CFG->wwwroot/pluginfile.php", '/' . $file->get_contextid() . '/' . $file->get_component() . '/' . $file->get_filearea() . $file->get_filepath() . $file->get_filename(), !$isimage);
        return $imageurl;
    }
    if (empty($imageurl)) {
        $color = coursecolor($course->id);
        $pattern = new \core_geopattern();
        $pattern->setColor($color);
        $pattern->patternbyid($courseid);
        $classes = 'coursepattern';
        $imageurl = $pattern->datauri();
    }
    return $imageurl;
}

$data = $DB->get_records_sql("SELECT DISTINCT c.id, c.* FROM {user_enrolments} ue JOIN {enrol} e ON ue.enrolid = e.id JOIN {course} c ON e.courseid = c.id WHERE ue.userid = :userid", array('userid' => $userid));

$html = '
                <style>
                
                div#myenrolledcourse select, div#myenrolledcourse input {
                        padding: 10px;
                        border-radius:5px;
                    }
                    div#myenrolledcourse input#search {
                        height: 42px;
                    }

                    .card {
                        min-height: 400px;
                        max-height: 400px;
                    }
                                    
                .img-frame {
                    position: relative;
                    display: block;
                    width: 100%;
                    padding: 0;
                    overflow: hidden;
                    min-height: 170px;
                }
                .img-frame::before {
                    display: block;
                    content: "";
                }
                .img-frame-21by9::before {
                    padding-top: 42.8571428571%;
                }
                .img-frame-16by9::before {
                    padding-top: 56.25%;
                }
                .img-frame-4by3::before {
                    padding-top: 75%;
                }
                
                .img-frame img {
                    position: absolute;
                    top: 0;
                    bottom: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    border: 0;
                    -o-object-fit: cover;
                    object-fit: cover;
                    border-top-left-radius: 8px;
                    border-top-right-radius: 8px;
                }

                .head-lime-clamp{
                    display: -webkit-box;
                    -webkit-box-orient: vertical;
                    -webkit-line-clamp: 2;
                    overflow: hidden;
                }

                .txt-lime-clamp{
                    display: -webkit-box;
                    -webkit-box-orient: vertical;
                    -webkit-line-clamp: 3;
                    overflow: hidden;
                }

                .h-progresss p {
                        margin: 10px 0;
                        font-size: 18px;
                        font-weight: bold;
                    }

               #myProgresss {
                    background: #f2f2f2;
                    width: 100%;
                    height: 10px;
                    border-radius: 10px;
                    margin: 2px 0;
                }

                #myBarr{
                    background : #3986b6;
                    height : 100%;
                    border-radius: 10px;
                }

                .theme-btn,
                .theme-btn:hover,
                .theme-btn:focus{
                    background: #3986b6;
                    border: 2px solid #3986b6;
                    color: #fff;
                    padding: 3px 10px;
                    border-radius: 3px;
                }
                .h-progresss{
                    min-height: 50px;
                    display: flex;
                    flex-direction: column;
                    justify-content: center;
                }
                .custom-border{
                    border: 1px solid #e3e3e3;
                    border-bottom-left-radius: 8px;
                    border-bottom-right-radius: 8px;
                }
                .course-name {
                    display: -webkit-box;
                    -webkit-box-orient: vertical;
                    -webkit-line-clamp: 2;
                    overflow: hidden;
                    max-height: 50px;
                    min-height: 50px;
                    color : #0a0a0a;
                    font-family : arial !important;
                    font-weight : 700 !important;
                }

                @media (min-width: 992px){
                    .custom-course-frame{
                        flex: 0 0 25% !important;
                        max-width: 25% !important; 
                    }
                }
                </style> 
                <div class="containers">
                <div class="row" id="myenrolledcourse" >
                <div class="col-12">

                	<input type="hidden" class="filters" name="userid" id="userid" value="' . $userid . '">
                
                	<select name="filtdata" class="filters" id="filtdata">
					  <option value="all" class="border-bottom">All</option>
					  <option value="inprogress">In progress</option>
					  <option value="future">Future</option>
					  <option value="past" class="border-bottom">Past</option>
					</select>

                	<input type="text" class="filters" name="search" id="search">

                	<select name="sort" class="filters" id="sort">
					  <option value="coursename">sort by course name</option>
					  <option value="lastaccess">sort by last accessed</option>
					</select>

                </div>
                <div class="col-12">
                <div class="row" id="loaddata">';
foreach ($data as $course) {
    $percentage = progress::get_course_progress_percentage($course, $userid);
    $percentagedata = (!empty($percentage)) ? $percentage : 0;

    $html .= '
					<div class="col-12 col-lg-3 col-md-4 my-3 custom-course-frame">
                        <div class="card">
                            <div class="img-frame img-frame-1by1">
                                 <a href="' . $CFG->wwwroot . '/course/view.php?id=' . $course->id . '"><img src="' . getcourse_image($course->id) . '" alt="img""></a>
                            </div>
                            <div class="card-body custom-border">
                                <a href="' . $CFG->wwwroot . '/course/view.php?id=' . $course->id . '"><h6 class="custom-title head-lime-clamp">' . $course->fullname . '</h6></a>
                                <div class="h-progresss">
                                    <div id="myProgresss">
                                        <div id="myBarr" style="width : ' . round($percentagedata) . '%;"></div>
                                    </div>
                                               <p>' . round($percentagedata) . '%</p>
                                </div> 
                            </div>
                        </div>
            	    </div>';
}
$html .= '</div>
            </div>
            </div>
            </div>
	<script type="text/javascript" src="js/jQuery.js"></script>
	<script type="text/javascript" src="css/slick.js"></script>
	<script type="text/javascript">

	$(function(){

		$(".filters").keyup(function(){
			var selectedFiltdata = $("#filtdata").val();
	        var searchText = $("#search").val();
	        var selectedSort = $("#sort").val();
	        var userid = $("#userid").val();
		    var data = {
		    	"filterdat" :  selectedFiltdata,
		    	"search" : searchText,
		    	"sort" : selectedSort,
		    	"userid" : userid
		    }
		    myajax(data);
			console.log("ddddddddd11",data);

		});
		$(".filters").change(function(){
			var selectedFiltdata = $("#filtdata").val();
	        var searchText = $("#search").val();
	        var selectedSort = $("#sort").val();
	        var userid = $("#userid").val();
		    var data = {
		    	"filterdat" :  selectedFiltdata,
		    	"search" : searchText,
		    	"sort" : selectedSort,
		    	"userid" : userid
		    }
		    myajax(data);
			console.log("ddddddddd",data);
		});

		function myajax(data) {
		    $.ajax({
		        type: "GET",
		        url: "' . $CFG->wwwroot . '/local/customlayout/ajax.php",
		        data: data,
		        success: function(response) {
		            response = JSON.parse(response);
		            $("#loaddata").html(response.html);
		            console.log("response- ", response); 
		        }
		    });
		}

	});
	</script>';
echo $OUTPUT->header();
// echo $OUTPUT->addblockbutton('content');
echo $OUTPUT->custom_block_region('content');
// var_dump($userid);
echo $html;
echo $OUTPUT->footer();
