<?php
use core_completion\progress;
// include_once($CFG->dirroot . '/course/lib.php');
class block_custom_mycourse extends block_base {
    public function init() {
        // $this->title = get_string('pluginname', 'block_custom_mycourse');
        $this->title = '';
    }
    public function get_enrolled_courses() {
        global $DB,$CFG,$USER;
        require_once($CFG->dirroot . '/lib/enrollib.php');
        $data = enrol_get_users_courses($USER->id);
        return $data;
    }

    public function get_content() {
        global $PAGE, $USER, $DB, $CFG;
        require_once($CFG->dirroot . '/lib/enrollib.php');
        require_once($CFG->dirroot . '/blocks/custom_mycourse/locallib.php');
        $enrolledcoursedata = enrol_get_my_courses($fields = null, $sort = "sortorder asc", $limit = 0, $courseids = [], $allaccessible = false, $offset = 0, $excludecourses = []);
        $this->content = new stdClass();
        $companyid = iomad::get_my_companyid(context_system::instance());
        $getcompnayadmin = $DB->get_record_sql("SELECT * FROM {company_users} WHERE companyid = ? AND userid =?",array($companyid,$USER->id));
        $getcompanydat = $DB->get_record("company", array("id"=>$companyid));
        $categoryid = $getcompanydat->category;
        if($getcompnayadmin->managertype ==1){
            $enrolcourses = $DB->get_records("course",array("category"=>$categoryid));
        }else{
            $enrolcourses = $this->get_enrolled_courses();
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
                        $failedCount++;
                    } else if($getscormscor->status == "passed" && $rating){
                        array_push($completcourse,$course);
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


        $html = '
        <style>
        .d-flex.flex-sm-row.flex-column.boxes_parent {
            width: fit-content;
            overflow: auto;
        }
        </style>
        <h3>' . get_string('overview', 'block_custom_mycourse') .'</h3>
        <div class="row" id="cc_course">
            <div class="col-md-4 p-0">
                <a href="'.$CFG->wwwroot .'/blocks/custom_mycourse/mycourse.php?type=enrolled" style="text-decoration:none;">
                <div class="row box b1 bg_custom m-2">
                    <div class="col-md-2">
                       <i class="fa-solid fa-share-from-square"></i>
                    </div>
                    <div class="col-md-10 fs-5">
                       <span class="text_color_custom">' . get_string('enrolledcourses', 'block_custom_mycourse') .' </span> <span class="cst_number"><b class="fw-bold">' . count($enrolcourses) . '</b></span>
                    </div>
                </div>
                </a>  
            </div>
            <div class="col-md-4 p-0">
                <a href="'.$CFG->wwwroot .'/blocks/custom_mycourse/mycourse.php?type=progress" style="text-decoration:none;">
                <div class="row box b1 bg_custom m-2">
                    <div class="col-md-2">
                        <i class="fa-solid fa-spinner"></i>
                    </div>
                    <div class="col-md-10">
                        <span class="text_color_custom">' . get_string('inprogresscourses', 'block_custom_mycourse') .'</span> <span class="cst_number"><b>'.count($progesscourse). '</b></span>
                    </div>
                </div>
                </a>    
            </div>
            <div class="col-md-4 p-0">
                <a href="'.$CFG->wwwroot .'/blocks/custom_mycourse/mycourse.php?type=completed" style="text-decoration:none;">
                <div class="row box b2 bg_custom m-2">
                    <div class="col-md-2">
                       <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <div class="col-md-10">
                        <span class="text_color_custom">' . get_string('completedcourses', 'block_custom_mycourse') .'</span>  <span class="cst_number"><b>' . count($completcourse) . '</b></span>
                    </div>
                </div> 
                </a>   
            </div>
            <div class="col-md-4 p-0">
                <a href="'.$CFG->wwwroot .'/blocks/custom_mycourse/mycourse.php?type=passed" style="text-decoration:none;">
                <div class="row box b1 bg_custom m-2">
                    <div class="col-md-2">
                     <i class="fa-solid fa-check-double"></i>
                    </div>
                    <div class="col-md-10">
                        <span class="text_color_custom">' . get_string('passedcourses', 'block_custom_mycourse') .'</span>  <span class="cst_number"><b>' . $passedCount . '</b></span>
                    </div>
                </div> 
                </a>   
            </div>
            <div class="col-md-4 p-0">
                <a href="'.$CFG->wwwroot .'/blocks/custom_mycourse/mycourse.php?type=failed" style="text-decoration:none;">
                <div class="row box b3 bg_custom m-2">
                    <div class="col-md-2">
                       <i class="fa-solid fa-circle-xmark"></i>
                    </div>
                    <div class="col-md-10">
                        <span class="text_color_custom">' . get_string('failedcourses', 'block_custom_mycourse') .'</span>  <span class="cst_number"><b>' . $failedCount . '</b></span>
                    </div>
                </div> 
                </a>   
            </div>
            <div class="col-md-4 p-0">
                <a href="'.$CFG->wwwroot .'/blocks/custom_mycourse/mycourse.php?type=overdue" style="text-decoration:none;">
                <div class="row box b3 bg_custom m-2">
                    <div class="col-md-2">
                       <i class="fa-solid fa-circle-exclamation"></i>
                    </div>
                    <div class="col-md-10">
                       <span class="text_color_custom">' . get_string('overduecourses', 'block_custom_mycourse') .'</span>  <span class="cst_number"><b>' . count($overduecourse) . '</b></span>
                    </div>
                </div> 
                </a>   
            </div>
        </div>
        <div class="row cst_boxes_parent my-3">
          <div class="col-sm-12 col-md-12 d-flex boxes_parent" style="flex-wrap:wrap; padding: 9px;">';
          foreach ($enrolledcoursedata as $coursedata) {
            $percentage = progress::get_course_progress_percentage($coursedata, $USER->id);
            if($percentage > 0 && $percentage < 100){
            $html .= '
             <div class="col-md-4 col-sm-12 my-2">
               <div class="sec_box_1 p-xl-3 bg_custom p-2">
                 <div class="col-xxl-12 col-xl-9 title_boxes">'.$coursedata->fullname.'</div> 
                    <div class="col-xl-8 col-lg-12 my-2 d-flex flex-xl-row flex-column title_boxes_2" style="padding: 14px;">
                     <!--<span class="pr-2 text-nowrap">4 Assignment</span>
                     <span><li class="text-nowrap">20 Video</li></span>-->
                    </div>
                    <div class="parents_btn d-flex col-lg-12 mt-5 p-0 align-items-center relative">
                      <div class="mx-2">
                        <a href="'.$CFG->wwwroot .'/course/view.php?id='.$coursedata->id.'" class="btn">Continue Course</a>
                      </div>
                      <div class="progress_wheel_parent ">
                       <div class="progress-circle" style="background: conic-gradient(#030f3f 0% '.$percentage.'%, #f0f4ff '.$percentage.'%);">
                          <div class="progress-text">
                           <span>'. round($percentage) .'</span><br>Complete
                           </div>
                       </div>
                      </div>
                    </div> 
               </div>
             </div>';
            }
          }
             
        $html .= '
          </div>
        </div>
        ';
        $this->content->text = $html;

        return $this->content;
    }
}
