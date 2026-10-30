<?php
use core_completion\progress;
class block_custom_dmdashboard extends block_base {
    public function init() {
        $this->title = get_string('plugintitle', 'block_custom_dmdashboard');
    }

    // public function department_userenrolledcourses($departmentid) {
    //     global $DB;

    //     $totlaenrollemts = $DB->get_field_sql("SELECT COUNT(ue.id)
    //             FROM {customdepartment_assign} cda
    //             JOIN {user} u ON u.id = cda.child_id
    //             JOIN {user_enrolments} ue ON ue.userid = u.id
    //             JOIN {enrol} e ON e.id = ue.enrolid
    //             WHERE u.suspended = 0
    //               AND u.deleted = 0
    //               AND cda.department_id = ?",[$departmentid]);

    //     return $totlaenrollemts;
    // }
    // public function department_useroverduecourses($departmentid) {
    //     global $DB;
    //      $currenttime = time();
    //      $sql = "SELECT COUNT(cc.id) FROM {customdepartment_assign} cda 
    //                 INNER JOIN {user} u ON u.id = cda.child_id
    //                 INNER JOIN {user_enrolments} ue ON ue.userid = u.id
    //                 INNER JOIN {enrol} e ON e.id = ue.enrolid
    //                 INNER JOIN {course} c ON c.id = e.courseid
    //                 INNER JOIN {course_completions} cc ON cc.userid = u.id AND cc.course = c.id
    //                 WHERE u.suspended = 0 AND u.deleted = 0 
    //                 AND cda.department_id = ? 
    //                 AND cc.timecompleted IS NULL 
    //                 AND c.enddate != 0 AND c.enddate < ?";
    //     $overduecourses = $DB->get_field_sql($sql, [$departmentid, $currenttime]);
    //     return $overduecourses;
    // }
    // public function department_userinprogresscourses($departmentid) {
    //     global $DB;
    //     $sql = "SELECT COUNT(cc.userid) FROM {course_completions} cc 
    //                 INNER JOIN {customdepartment_assign} cda ON cda.child_id = cc.userid 
    //                 INNER JOIN mdl_user u ON u.id = cda.child_id 
    //                 WHERE u.suspended = 0 AND u.deleted = 0 AND cda.department_id = ? AND cc.timecompleted IS NULL";
    //     $count = $DB->count_records_sql($sql, [$departmentid]);

    //     return $count;
    // }
    // public function department_users($departmentid) {
    //     global $DB;
    //     // $companyid = iomad::get_my_companyid(context_system::instance());
    //     $sql = "SELECT COUNT(DISTINCT(u.id)) 
    //             FROM {customdepartment_assign} cda 
    //             INNER JOIN {user} u ON u.id = cda.child_id 
    //             WHERE u.suspended = 0 AND u.deleted = 0 AND cda.department_id = ?";
    //     $count = $DB->count_records_sql($sql, [$departmentid]);
    //     return $count;
    // }

    public function get_department_course_stats($departmentid) {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/lib/enrollib.php');
        require_once($CFG->dirroot . '/blocks/custom_mycourse/locallib.php');

        // Get OSHA category IDs (only once for performance)
        $categoryids = blocks_custom_mycourse_get_all_category_ids(64);

        // Get department users
        $users = $DB->get_records_sql("
            SELECT u.id
            FROM {customdepartment_assign} cda
            JOIN {user} u ON u.id = cda.child_id
            WHERE u.suspended = 0 
              AND u.deleted = 0 
              AND cda.department_id = ?", [$departmentid]);

        // Initialize counters
        $totalusers = [];
        $enrolled = [];
        $progesscourse = [];
        $completcourse = [];
        $overduecourse = [];
        $passCount = [];
        $failCount = [];

        foreach ($users as $user) {
            $courses = enrol_get_users_courses($user->id);
            array_push($totalusers,$user);

            foreach ($courses as $course) {
                array_push($enrolled,$course);
                // OSHA CHECK
                $isosha = in_array($course->category, $categoryids);
                $percentage = \core_completion\progress::get_course_progress_percentage($course, $user->id); 
                if($isosha){
                    $getscormscor = $DB->get_record_sql("SELECT u.id as userid, u.firstname, u.lastname, st.value as status, st.attempt, st.scormid FROM {scorm_scoes_track} st INNER JOIN {scorm} s ON s.id = st.scormid INNER JOIN {user} u ON u.id = st.userid WHERE st.element = 'cmi.core.lesson_status' AND s.course = ? AND u.id = ?",array($course->id,$user->id));
                    $rating = $DB->get_record_sql("SELECT r.id AS rid, u.id AS userid, u.firstname, u.lastname, r.rating AS rating, r.review AS review, r.reply AS reply, r.timemodified AS timemodified, r.courseid AS courseid, c.fullname AS coursename FROM {tool_courserating_rating} r INNER JOIN {user} u ON u.id = r.userid INNER JOIN {course} c ON c.id = r.courseid WHERE c.id=? AND u.id = ?",array($course->id,$user->id));
                    if($getscormscor){
                        if($getscormscor->status == "failed"){
                            array_push($completcourse,$course);
                            array_push($failCount, $course);
                        } else if($getscormscor->status == "passed" && $rating){
                            array_push($completcourse,$course);
                            array_push($passCount, $course);
                        } else {
                            array_push($progesscourse,$course);
                        }
                    }
                } else if($percentage == 100){
                    array_push($completcourse,$course);  
                    $checkscorm = $DB->get_record_sql("SELECT id, name FROM {scorm} WHERE course = ?",array($course->id));
                    $finalscorm = null;
                    $scorms = block_custom_mycourse_get_modulesequence("scorm", $course->id, $user->id);
                    if($scorms){
                        $finalscorm = array_pop($scorms);
                        $checkscorm = $DB->get_record_sql("SELECT s.id, s.name FROM {scorm} s INNER JOIN {course_modules} cm on cm.instance = s.id WHERE cm.id = ?",array($finalscorm->id));
                    }
                    if($checkscorm){
                        $getscormscor = $DB->get_record_sql("SELECT u.id as userid, u.firstname, u.lastname, s.value as status,  s.attempt, s.scormid
                                                        FROM {scorm_scoes_track} s
                                                        JOIN {user} u ON u.id = s.userid
                                                        WHERE s.element = 'cmi.core.lesson_status' AND s.scormid = ? AND u.id = ?",array($checkscorm->id,$user->id));

                        if($getscormscor->status=="passed" ){
                            array_push($passCount, $course);
                        }else if($getscormscor->status=="failed" ){
                            array_push($failCount, $course);
                        }else{
                            $gradeitem = $DB->get_record("grade_items", array("itemtype"=>"mod", "itemmodule"=>"scorm", "iteminstance"=>$checkscorm->id));
                            if($gradeitem){
                                $getgrade = $DB->get_record("grade_grades", array("itemid"=>$gradeitem->id,'userid'=>$user->id));
                                if ($getgrade && $getgrade->rawgrade !== null) {
                                    if($getgrade->rawgrade >= $gradeitem->gradepass){
                                        array_push($passCount, $course);
                                    }else{
                                        array_push($failCount, $course);
                                    }
                                } else {
                                    array_push($failCount, $course);
                                }
                            } else {
                                array_push($failCount, $course);
                            }

                        }

                    }else{
                        array_push($passCount, $course);
                    }
                } else if($percentage > 0 && $percentage < 100){
                    array_push($progesscourse,$course);
                }else{
                    $scorm = $DB->get_record('scorm', ['course' => $course->id]);
                    if ($scorm) {
                        $scormattempt = $DB->get_record_sql("SELECT value FROM {scorm_scoes_track} WHERE userid = ?  AND scormid = ? AND element = 'cmi.core.lesson_status' ORDER BY attempt DESC", [$user->id, $scorm->id]);  
                        if($scormattempt){
                             array_push($progesscourse,$course);
                        }
                    }
                }
                if($DB->get_records_sql('SELECT * FROM {course} WHERE id = '.$course->id.' AND enddate != 0 AND enddate < '. time()) && $percentage < 100){
                    array_push($overduecourse,$course);
                }
            }
        }
        $stats = [
            'totalusers' => sizeof($totalusers),
            'enrolled' => sizeof($enrolled),
            'inprogress' => sizeof($progesscourse),
            'completed' => sizeof($completcourse),
            'passed' => sizeof($passCount),
            'failed' => sizeof($failCount),
            'overdue' => sizeof($overduecourse),
        ];

        return $stats;
    }

    public function get_content() {
        global $PAGE, $USER, $DB, $CFG;

        if (!has_capability('block/custom_dmdashboard:view', context_block::instance($this->instance->id)) || is_siteadmin()) {
            return null; // completely hide block
        }
        
        $this->content = new stdClass();
        $companyid = iomad::get_my_companyid(context_system::instance());


        $html = '
        <style>
            .dashboard-card {
                background: #f0f4fa;
                border: none;
                border-radius: 8px;
                padding: 10px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                display: flex;
                align-items: center;
                height: 90px;
                margin-bottom: 10px;
            }
            .dashboard-card .icon {
                font-size: 24px;
                color: #5c8ff9;
                margin-right: 15px;
            }
            .dashboard-card .text-content {
                flex: 1;
            }
            .dashboard-card .card-title {
                font-size: 14px;
                color: #757575;
                margin-bottom: 2px;
            }
            .dashboard-card .card-value {
                font-size: 24px;
                font-weight: 600;
                color: #212121;
                margin-bottom: 2px;
            }
            .dashboard-card .card-subtitle {
                font-size: 12px;
                color: #a0a0a0;
            }
            .kpi-section .col-md-4 {
                padding: 5px;
            }
            .progress {
                height: 8px;
                background-color: #e0e0e0;
                width: 150px;
                display: inline-block;
                vertical-align: middle;
                margin: 0 10px;
            }
            .progress-bar-custom {
                height: 8px;
                background-color:rgb(86, 85, 85);
            }
            .course-item {
                display: flex;
                align-items: center;
                margin-bottom: 10px;
            }
            .review-item {
                display: flex;
                align-items: center;
                margin-bottom: 10px;
            }
            .review-item .fa-user-circle {
                font-size: 24px;
                color: #757575;
                margin-right: 10px;
            }
            .review-text {
                font-size: 12px;
                color: #757575;
                margin-right: 10px;
            }
            .rating {
                color: #ffca28;
                font-size: 14px;
            }
            h3, h5 {
                color: #212121;
            }
            .chart-card.cst_columns_area span {
                width: 70%;
            }

            .dm-dashborad {
                padding: 20px;
                border-radius: 10px;
                box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;
                box-shadow: rgba(17, 12, 46, 0.15) 0px 48px 100px 0px;
            }
        </style>
        ';


    $assigndepartments = $DB->get_records_sql("SELECT cd.* FROM {customdepartment_assign} cda INNER JOIN {customdepartment} AS cd ON cda.department_id = cd.id WHERE cda.child_id = ? AND cda.is_departmentmanager = 1 ORDER BY cd.name ASC", [$USER->id]);
    if($assigndepartments){
        foreach ($assigndepartments as $department) {
            $coursestatus = $this->get_department_course_stats($department->id);

            $html .='<div class="mt-4 m-3">
                        <div class="kpi-section dm-dashborad bg_custom" style="margin: initial;">
                         <h3 class="mb-3 text-white">'.$department->name.'</h3>
                           <div class="row">
                               <div class="col-md-4">
                                <div class="dashboard-card">
                                    <i class="fas fa-users icon"></i>
                                    <div class="text-content">
                                        <div class="card-title">'. get_string('totaluser', 'block_custom_dmdashboard') .'</div>
                                        <div class="card-value">'.$coursestatus['totalusers'].'</div>
                                        <div class="card-subtitle"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="dashboard-card">
                                    <i class="fas fa-book icon"></i>
                                    <div class="text-content">
                                        <div class="card-title">'. get_string('enrolledcourses', 'block_custom_dmdashboard') .'</div>
                                        <div class="card-value">'.$coursestatus['enrolled'].'</div>
                                        <div class="card-subtitle"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="dashboard-card">
                                    <i class="fas fa-check-circle icon"></i>
                                    <div class="text-content">
                                        <div class="card-title">'. get_string('completedcourses', 'block_custom_dmdashboard') .'</div>
                                        <div class="card-value"> '.$coursestatus['completed'].' </div>
                                        <div class="card-subtitle"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="dashboard-card">
                                    <i class="fas fa-clock icon"></i>
                                    <div class="text-content">
                                        <div class="card-title">'. get_string('inprogress', 'block_custom_dmdashboard') .'</div>
                                        <div class="card-value"> '.$coursestatus['inprogress'].' </div>
                                        <div class="card-subtitle"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="dashboard-card">
                                    <i class="fas fa-award icon"></i>
                                    <div class="text-content">
                                        <div class="card-title">'. get_string('passedcourses', 'block_custom_dmdashboard') .'</div>
                                        <div class="card-value"> '.$coursestatus['passed'].' </div>
                                        <div class="card-subtitle"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="dashboard-card">
                                    <i class="fas fa-times-circle icon"></i>
                                    <div class="text-content">
                                        <div class="card-title">'. get_string('failedcourses', 'block_custom_dmdashboard') .'</div>
                                        <div class="card-value"> '.$coursestatus['failed'].' </div>
                                        <div class="card-subtitle"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="dashboard-card">
                                    <i class="fas fa-exclamation-triangle icon"></i>
                                    <div class="text-content">
                                        <div class="card-title">'. get_string('Overduecourses', 'block_custom_dmdashboard') .'</div>
                                        <div class="card-value"> '.$coursestatus['overdue'].' </div>
                                        <div class="card-subtitle"></div>
                                    </div>
                                </div>
                            </div>
                           </div>
                        </div>
                    </div>
                        ';
        }

    }else{
       $html .= '<h3>Not Mapped Any department</h3>'; 
    }
        $this->content->text = $html;
        return $this->content;
    }
}
