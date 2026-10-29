<?php
require('../../config.php');
require_once($CFG->dirroot . '/lib/enrollib.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/blocks/custom_mycourse/locallib.php');
use core_completion\progress;
require_login();
$PAGE->set_context(context_system::instance());
global $PAGE, $USER, $DB, $CFG;
$PAGE->requires->jquery();
$PAGE->set_pagelayout('base');
$type = required_param('type', PARAM_RAW);
if(isset($_SESSION['paymentinvoice'])){
    unset($_SESSION['paymentinvoice']);
}
if($type=="enrolled"){
    $companyid = iomad::get_my_companyid(context_system::instance());
    $getcompnayadmin = $DB->get_record_sql("SELECT * FROM {company_users} WHERE companyid = ? AND userid =?",array($companyid,$USER->id));
    $getcompanydat = $DB->get_record("company", array("id"=>$companyid));
    $categoryid = $getcompanydat->category;
    if($getcompnayadmin->managertype ==1){
        $courses = $DB->get_records("course",array("category"=>$categoryid));
        $pageheading = get_string('mycourses', 'block_custom_mycourse');
    }else{
        $pageheading = get_string('myoverview', 'block_custom_mycourse');
    }
}


function course_image1($courseid) {
    global $DB, $CFG;
    require_once($CFG->dirroot. '/course/classes/list_element.php');
    $courserecord = $DB->get_record('course', array('id' => $courseid));
    $course = new core_course_list_element($courserecord);
    foreach ($course->get_course_overviewfiles() as $file) {
        $isimage = $file->is_valid_image();
        $url = file_encode_url("$CFG->wwwroot/pluginfile.php", '/' . $file->get_contextid() . '/' . $file->get_component() . '/' .
        $file->get_filearea() . $file->get_filepath() . $file->get_filename(), !$isimage);
        if ($isimage) {
            return html_writer::empty_tag('img', array('src' => $url, 'alt' => $course->fullname, 'class' => 'courseimage'));
        } else {
            
            return 0;
        }
    }
}




$enrolledcoursedata = enrol_get_my_courses($fields = null, $sort = "sortorder asc", $limit = 0, $courseids = [], $allaccessible = false, $offset = 0, $excludecourses = []);
// $this->content = new stdClass();
$companyid = iomad::get_my_companyid(context_system::instance());
$getcompnayadmin = $DB->get_record_sql("SELECT * FROM {company_users} WHERE companyid = ? AND userid =?",array($companyid,$USER->id));
$getcompanydat = $DB->get_record("company", array("id"=>$companyid));
$categoryid = $getcompanydat->category;
if($getcompnayadmin->managertype ==1){
    $enrolcourses = $DB->get_records("course",array("category"=>$categoryid));

    // uday code block start
    $licensedcourses = $DB->get_records('companylicense_users', [
        'userid' => $USER->id
    ]);

    if ($licensedcourses) {
        foreach ($licensedcourses as $record) {

            $courseid = $record->licensecourseid;

            if (!$DB->record_exists('course', ['id' => $courseid])) {
                continue;
            }

            $coursecontext = context_course::instance($courseid);

            if (is_enrolled($coursecontext, $USER->id, '', true)) {

                $course = get_course($courseid);
                // Prevent duplicates
                if (!isset($enrolcourses[$course->id])) {
                    $enrolcourses[$course->id] = $course;
                }
            }
        }
    }
    // uday code block end
}else{
    $enrolcourses = enrol_get_users_courses($USER->id);
}


$progesscourse = [];
$completcourse = [];
$overduecourse = [];
$passCount = [];
$failCount = [];
$panding = [];
$passedCount = 0;
$failedCount = 0;
foreach ($enrolcourses as $course) {
    $categoryids = blocks_custom_mycourse_get_all_category_ids(64);
    $isosha = in_array($course->category, $categoryids);
    $percentage = \core_completion\progress::get_course_progress_percentage($course, $USER->id);
    if($isosha){
        $getscormscor = $DB->get_record_sql("SELECT u.id as userid, u.firstname, u.lastname, st.value as status, st.attempt, st.scormid FROM {scorm_scoes_track} st INNER JOIN {scorm} s ON s.id = st.scormid INNER JOIN {user} u ON u.id = st.userid WHERE st.element = 'cmi.core.lesson_status' AND s.course = ? AND u.id = ?",array($course->id,$USER->id));
        $rating = $DB->get_record_sql("SELECT r.id AS rid, u.id AS userid, u.firstname, u.lastname, r.rating AS rating, r.review AS review, r.reply AS reply, r.timemodified AS timemodified, r.courseid AS courseid, c.fullname AS coursename FROM {tool_courserating_rating} r INNER JOIN {user} u ON u.id = r.userid INNER JOIN {course} c ON c.id = r.courseid WHERE c.id=? AND u.id = ?",array($course->id,$USER->id));
        if($getscormscor){
            if($getscormscor->status == "failed"){
                array_push($completcourse,$course);
                array_push($failCount,$course);
                $failedCount++;
            } else if($getscormscor->status == "passed" && $rating){
                array_push($completcourse,$course);
                array_push($passCount,$course);
                $passedCount++;
            } else {
                array_push($progesscourse,$course);
            }
        }
    } else if($percentage == 100){
        array_push($completcourse,$course);  
        $checkscorm = $DB->get_record_sql("SELECT id, name FROM {scorm} WHERE course = ?",array($course->id));
        $finalscorm = null;
        $scorms = block_custom_mycourse_get_modulesequence("scorm", $course->id, $USER->id);
        if($scorms){
            $finalscorm = array_pop($scorms);
            $checkscorm = $DB->get_record_sql("SELECT s.id, s.name FROM {scorm} s INNER JOIN {course_modules} cm on cm.instance = s.id WHERE cm.id = ?",array($finalscorm->id));
        }
        if($checkscorm){
            $getscormscor = $DB->get_record_sql("SELECT u.id as userid, u.firstname, u.lastname, s.value as status,  s.attempt, s.scormid
                                            FROM {scorm_scoes_track} s
                                            JOIN {user} u ON u.id = s.userid
                                            WHERE s.element = 'cmi.core.lesson_status' AND s.scormid = ? AND u.id = ?",array($checkscorm->id,$USER->id));

            if($getscormscor->status=="passed" ){
                array_push($passCount, $course);
                $passedCount++;
            }else if($getscormscor->status=="failed" ){
                array_push($failCount, $course);
                $failedCount++;
            }else{
                $gradeitem = $DB->get_record("grade_items", array("itemtype"=>"mod", "itemmodule"=>"scorm", "iteminstance"=>$checkscorm->id));
                if($gradeitem){
                    $getgrade = $DB->get_record("grade_grades", array("itemid"=>$gradeitem->id,'userid'=>$USER->id));
                    if ($getgrade && $getgrade->rawgrade !== null) {
                        if($getgrade->rawgrade >= $gradeitem->gradepass){
                            array_push($passCount, $course);
                            $passedCount++;
                        }else{
                            array_push($failCount, $course);
                            $failedCount++;
                        }
                    } else {
                        array_push($failCount, $course);
                        $failedCount++;
                    }
                } else {
                    array_push($failCount, $course);
                    $failedCount++;
                }

            }

        }else{
            array_push($passCount, $course);
            $passedCount++;
        }
    } else if($percentage > 0 && $percentage < 100){
        array_push($progesscourse,$course);
    }else{
        $scorm = $DB->get_record('scorm', ['course' => $course->id]);
        if ($scorm) {
            $scormattempt = $DB->get_record_sql("SELECT value FROM {scorm_scoes_track} WHERE userid = ?  AND scormid = ? AND element = 'cmi.core.lesson_status' ORDER BY attempt DESC", [$USER->id, $scorm->id]);  
            if($scormattempt){
                 array_push($progesscourse,$course);
            }
        }
    }
    if($DB->get_records_sql('SELECT * FROM {course} WHERE id = '.$course->id.' AND enddate != 0 AND enddate < '. time()) && $percentage < 100){
        array_push($overduecourse,$course);
    }
}


// echo "<pre>";
// print_r($completcourse);
// echo "</pre>";


$title = get_string('enrolledcourses', 'block_custom_mycourse');     
if ($type == "progress") {
    $title = get_string('inprogresscourses', 'block_custom_mycourse');
}
if ($type == "completed") {
    $title = get_string('completedcourses', 'block_custom_mycourse');
}
if ($type == "passed") {
    $title = get_string('passedcourses', 'block_custom_mycourse');
}
if ($type == "failed") {
    $title = get_string('failedcourses', 'block_custom_mycourse');
}
if ($type == "overdue") {
    $title = get_string('overduecourses', 'block_custom_mycourse');
}
$PAGE->set_title($title);
$PAGE->set_heading($pageheading);
echo $OUTPUT->header(); 

echo '
    <div class="row">
        <div class="col-md-12" align="left"><h3>'.$title.'</h3></div>';
        if($type=="enrolled"){
            if($enrolcourses){
                foreach ($enrolcourses as $course) {
                    $percentage = \core_completion\progress::get_course_progress_percentage($course, $USER->id);
                    echo '
                    <a href="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'">
                        <div class="col-12 col-lg-4 col-md-4 my-3">
                            <div class="card">  
                                <div class="img-frame img-frame-1by1">
                                    '.getcourse_image($course->id).'
                                </div>
                                <div class="card-body custom-border p-3">
                                    <a href="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'"><h6 class="card-title head-lime-clamp">'.$course->fullname.'</h6></a>
                                    <div class="h-progress">
                                        <div id="myProgress">
                                            <div id="myBar" style="width: '.round($percentage,2).'%;"></div>
                                        </div>
                                        <p>'.round($percentage,2).'% complete</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>';
                }
            }else{
                echo '<div class="alert alert-warning text-center mt-4 width_100pr">'. get_string('nocoursesfound', 'block_custom_mycourse') .'</div>';
            }
        }

        if($type=="passed"){
            if($passCount){
                foreach ($passCount as $course) {
                    $percentage = \core_completion\progress::get_course_progress_percentage($course, $USER->id);
                    echo ' 
                    <a href="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'">
                        <div class="col-12 col-lg-4 col-md-4 my-3">
                            <div class="card">  
                                <div class="img-frame img-frame-1by1">
                                    '.getcourse_image($course->id).'
                                </div>
                                <div class="card-body custom-border p-3">
                                    <a href="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'"><h6 class="card-title head-lime-clamp">'.$course->fullname.'</h6></a>
                                    <div class="h-progress">
                                        <div id="myProgress">
                                            <div id="myBar" style="width: '.round($percentage,2).'%;"></div>
                                        </div>
                                        <p>'.round($percentage,2).'% complete</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>';
                }
            }else{
                echo '<div class="alert alert-warning text-center mt-4 width_100pr">'. get_string('nocoursesfound', 'block_custom_mycourse') .'</div>';
            }
        }

        if($type=="failed"){
            if($failCount){
                foreach ($failCount as $course) {
                    $percentage = \core_completion\progress::get_course_progress_percentage($course, $USER->id);
                    echo ' 
                    <a href="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'">
                        <div class="col-12 col-lg-4 col-md-4 my-3">
                            <div class="card">  
                                <div class="img-frame img-frame-1by1">
                                    '.getcourse_image($course->id).'
                                </div>
                                <div class="card-body custom-border p-3">
                                    <a href="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'"><h6 class="card-title head-lime-clamp">'.$course->fullname.'</h6></a>
                                    <div class="h-progress">
                                        <div id="myProgress">
                                            <div id="myBar" style="width: '.round($percentage,2).'%;"></div>
                                        </div>
                                        <p>'.round($percentage,2).'% complete</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>';
                }
            }else{
                echo '<div class="alert alert-warning text-center mt-4 width_100pr">'. get_string('nocoursesfound', 'block_custom_mycourse') .'</div>';
            }
        }

        if($type=="overdue"){
            if($overduecourse){
                foreach ($overduecourse as $course) {
                    $percentage = \core_completion\progress::get_course_progress_percentage($course, $USER->id);
                    echo ' 
                    <a href="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'">
                        <div class="col-12 col-lg-4 col-md-4 my-3">
                            <div class="card">  
                                <div class="img-frame img-frame-1by1">
                                    '.getcourse_image($course->id).'
                                </div>
                                <div class="card-body custom-border p-3">
                                    <a href="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'"><h6 class="card-title head-lime-clamp">'.$course->fullname.'</h6></a>
                                    <div class="h-progress">
                                        <div id="myProgress">
                                            <div id="myBar" style="width: '.round($percentage,2).'%;"></div>
                                        </div>
                                        <p>'.round($percentage,2).'% complete</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>';
                }
            }else{
                echo '<div class="alert alert-warning text-center mt-4 width_100pr">'. get_string('nocoursesfound', 'block_custom_mycourse') .'</div>';
            }
        }

        if($type=="progress"){
            if($progesscourse){
                foreach ($progesscourse as $course) {
                    $percentage = \core_completion\progress::get_course_progress_percentage($course, $USER->id);
                    echo ' 
                    <a href="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'">
                        <div class="col-12 col-lg-4 col-md-4 my-3">
                            <div class="card">  
                                <div class="img-frame img-frame-1by1">
                                    '.getcourse_image($course->id).'
                                </div>
                                <div class="card-body custom-border p-3">
                                    <a href="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'"><h6 class="card-title head-lime-clamp">'.$course->fullname.'</h6></a>
                                    <div class="h-progress">
                                        <div id="myProgress">
                                            <div id="myBar" style="width: '.round($percentage,2).'%;"></div>
                                        </div>
                                        <p>'.round($percentage,2).'% complete</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>';
                }
            }else{
                echo '<div class="alert alert-warning text-center mt-4 width_100pr">'. get_string('nocoursesfound', 'block_custom_mycourse') .'</div>';
            }
        }
        if($type=="completed"){
            if($completcourse){
                foreach ($completcourse as $course) {
                    $percentage = \core_completion\progress::get_course_progress_percentage($course, $USER->id);
                    echo ' 
                    <a href="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'">
                        <div class="col-12 col-lg-4 col-md-4 my-3">
                            <div class="card">  
                                <div class="img-frame img-frame-1by1">
                                    '.getcourse_image($course->id).'
                                </div>
                                <div class="card-body custom-border p-3">
                                    <a href="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'"><h6 class="card-title head-lime-clamp">'.$course->fullname.'</h6></a>
                                    <div class="h-progress">
                                        <div id="myProgress">
                                            <div id="myBar" style="width: '.round($percentage,2).'%;"></div>
                                        </div>
                                        <p>'.round($percentage,2).'% complete</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>';
                }
            }else{
                echo '<div class="alert alert-warning text-center mt-4 width_100pr">'. get_string('nocoursesfound', 'block_custom_mycourse') .'</div>';
            }
        }









echo '</div>';


echo '
 <style>
 .width_100pr{
    width:100%;
 }
    .img-frame {
    position: relative;
    display: block;
    width: 100%;
    padding: 0;
    overflow: hidden;
    height: 10rem !important;
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
.img-frame-1by1::before {
    padding-top: 100%;
}
.img-frame img {
    position: absolute;
    top: 0;
    bottom: 0;
    left: 0;
    width: 100%;
    border: 0;
    -o-object-fit: cover;
    object-fit: cover;
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
    height:100%;
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

#myProgress { 
    background : #f2f2f2;
    width : 100%;
    height : 3px;
}

#myBar{
    background : #3986b6;
    height : 100%;
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
.h-progress{
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
</style>';
echo $OUTPUT->footer(); 
?>