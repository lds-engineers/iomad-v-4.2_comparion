<?php
function learnpeek_user_courses_status($userid) {
    global $DB, $CFG;

    require_once($CFG->dirroot . '/lib/enrollib.php');
    require_once($CFG->dirroot . '/blocks/custom_mycourse/locallib.php');

    $companyid = iomad::get_my_companyid(context_system::instance());

    $enrolledcoursedata = enrol_get_my_courses($fields = null, $sort = "sortorder asc", $limit = 0, $courseids = [], $allaccessible = false, $offset = 0, $excludecourses = []);

    $getcompanydat = $DB->get_record('company', ['id' => $companyid]);

    $getcompnayadmin = $DB->get_record_sql("
        SELECT * FROM {company_users} 
        WHERE companyid = ? AND userid =?",[$companyid,$userid]
    );

    $categoryid = $getcompanydat->category;

    if($getcompnayadmin->managertype ==1){
        $enrolcourses = $DB->get_records("course",array("category"=>$categoryid));
    }else{
        $enrolcourses = enrol_get_users_courses($userid);
    }


    $progesscourse  = [];
    $completcourse  = [];
    $overduecourse  = [];
    $passCount      = [];
    $failCount      = [];
    $panding        = [];
    $passedCount    = 0;
    $failedCount    = 0;
    foreach ($enrolcourses as $course) {
        $categoryids = blocks_custom_mycourse_get_all_category_ids(64);
        $isosha = in_array($course->category, $categoryids);
        $percentage = \core_completion\progress::get_course_progress_percentage($course, $userid);
        if($isosha){
            $getscormscor = $DB->get_record_sql("SELECT u.id as userid, u.firstname, u.lastname, st.value as status, st.attempt, st.scormid FROM {scorm_scoes_track} st INNER JOIN {scorm} s ON s.id = st.scormid INNER JOIN {user} u ON u.id = st.userid WHERE st.element = 'cmi.core.lesson_status' AND s.course = ? AND u.id = ?",array($course->id,$userid));
            $rating = $DB->get_record_sql("SELECT r.id AS rid, u.id AS userid, u.firstname, u.lastname, r.rating AS rating, r.review AS review, r.reply AS reply, r.timemodified AS timemodified, r.courseid AS courseid, c.fullname AS coursename FROM {tool_courserating_rating} r INNER JOIN {user} u ON u.id = r.userid INNER JOIN {course} c ON c.id = r.courseid WHERE c.id=? AND u.id = ?",array($course->id,$userid));
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
            if($checkscorm){
                $getscormscor = $DB->get_record_sql("SELECT u.id as userid, u.firstname, u.lastname, s.value as status,  s.attempt, s.scormid
                                                FROM {scorm_scoes_track} s
                                                JOIN {user} u ON u.id = s.userid
                                                WHERE s.element = 'cmi.core.lesson_status' AND s.scormid = ? AND u.id = ?",array($checkscorm->id,$userid));

                if($getscormscor->status=="passed" ){
                    array_push($passCount, $course);
                    $passedCount++;
                }else if($getscormscor->status=="failed" ){
                    array_push($failCount, $course);
                    $failedCount++;
                }else{
                    $gradeitem = $DB->get_record("grade_items", array("itemtype"=>"mod", "itemmodule"=>"scorm", "iteminstance"=>$checkscorm->id));
                    if($gradeitem){
                        $getgrade = $DB->get_record("grade_grades", array("itemid"=>$gradeitem->id,'userid'=>$userid));
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
                $scormattempt = $DB->get_record_sql("SELECT value FROM {scorm_scoes_track} WHERE userid = ?  AND scormid = ? AND element = 'cmi.core.lesson_status' ORDER BY attempt DESC", [$userid, $scorm->id]);  
                if($scormattempt){
                     array_push($progesscourse,$course);
                }else{
                   array_push($panding,$course);  
                }
            }else{
                array_push($panding,$course);

            }
        }
        if($DB->get_records_sql('SELECT * FROM {course} WHERE id = '.$course->id.' AND enddate != 0 AND enddate < '. time()) && $percentage < 100){
            array_push($overduecourse,$course);
        }
    }

    return [
        'progesscourse' => $progesscourse,
        'completcourse' => $completcourse,
        'overduecourse' => $overduecourse,
        'passCount'     => $passCount,
        'failCount'     => $failCount,
        'panding'       => $panding,
        'passedCount'   => $passedCount,
        'failedCount'   => $failedCount,
    ];

}

// User Overview
function learnpeek_user_overview($userid) {
    global $DB,$CFG;

    $allenrolcourses = enrol_get_users_courses($userid);
    $enrolcourses = learnpeek_user_courses_status($userid);

    $completcourses     = $enrolcourses['completcourse'];

    // Total complated course hourse
    $totalhourslearned  = 0;
    foreach ($completcourses as $course) {
        $coursedata = get_course_customfield_data($course->id , 'course_duration');
        $duration = (float)$coursedata->value;
        $totalhourslearned += $duration;
        
    }

    // Total Enrolled course hourse
    $totalcoursehours = 0;
    foreach ($allenrolcourses as $coursehours) {
        $coursedata = get_course_customfield_data($coursehours->id , 'course_duration');
        $duration = (float)$coursedata->value;
        $totalcoursehours += $duration;
    }

    if($totalhourslearned > 0){
        $hourslearnpercent = round(($totalhourslearned / $totalcoursehours) * 100);
    }else{
       $hourslearnpercent = 0; 
    }


    $earnedcertificates = $DB->get_records("customcert_backup",["userid"=>$userid]);
    $totalearnedcertificates = count($earnedcertificates);



    $totalenrolled = count($allenrolcourses);
    $totalprogesscourse = count($enrolcourses['progesscourse']);
    $totalcompletcourse = count($enrolcourses['completcourse']);

    // Avoid division by zero.
    if ($totalenrolled > 0) {
        $inprogresspercent = round(($totalprogesscourse / $totalenrolled) * 100);
        $completedpercent  = round(($totalcompletcourse / $totalenrolled) * 100);
    } else {
        $inprogresspercent = 0;
        $completedpercent = 0;
        $certificatepercent = 0;
    }

    $html = '
        <div class="row g-3 mb-4">

            <!-- In Progress -->
            <div class="col-12 col-sm-6 col-lg-3 my-2">
                <div class="dashboard-card active-card h-100">
                    <div class="card-content">
                        <div class="icon-box">
                            <i class="fa fa-book-open"></i>
                        </div>
                        <div class="card-text">
                            <h3>' .$totalprogesscourse . '</h3>
                            <h6>In Progress</h6>
                            <p>Keep going!</p>
                            <div class="progress-line blue" style="width: '.$inprogresspercent.'%;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Completed -->
            <div class="col-12 col-sm-6 col-lg-3 my-2">
                <div class="dashboard-card h-100">
                    <div class="card-content">
                        <div class="icon-box green-bg">
                            <i class="fa fa-check-circle"></i>
                        </div>
                        <div class="card-text">
                            <h3 class="text-success">' . $totalcompletcourse . '</h3>
                            <h6>Completed</h6>
                            <p>Great job!</p>
                            <div class="progress-line green" style="width: '.$completedpercent.'%;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Certificates -->
            <div class="col-12 col-sm-6 col-lg-3 my-2">
                <div class="dashboard-card h-100">
                    <div class="card-content">
                        <div class="icon-box purple-bg">
                            <i class="fa fa-award"></i>
                        </div>
                        <div class="card-text">
                            <h3 class="text-purple">' . $totalearnedcertificates . '</h3>
                            <h6>Certificates</h6>
                            <p>Earned</p>
                            <div class="progress-line purple" style="width: '.$completedpercent.'%;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hours Learned -->
            <div class="col-12 col-sm-6 col-lg-3 my-2">
                <div class="dashboard-card h-100">
                    <div class="card-content">
                        <div class="icon-box orange-bg">
                            <i class="fa fa-clock"></i>
                        </div>
                        <div class="card-text">
                            <h3 class="text-warning">' . $totalhourslearned . '</h3>
                            <h6>Hours Learned</h6>
                            <p>Keep it up!</p>
                            <div class="progress-line orange" style="width: '.$hourslearnpercent.'%;"></div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        ';
    return $html;
}


// user in progress course list (Continue Learning)
function user_continue_learning($userid) {
	global $DB,$CFG;

    require_once($CFG->dirroot . '/blocks/custom_mycourse/locallib.php');

    $enrolcourses   = learnpeek_user_courses_status($userid);
    $progesscourse  = $enrolcourses['progesscourse'];
    $panding        = $enrolcourses['panding'];

    // Maximum courses to display.
    $maxcourses = 4;

    $displaycourses = [];

    // Add in-progress courses first.
    foreach ($progesscourse as $course) {
        if (count($displaycourses) >= $maxcourses) {
            break;
        }

        $displaycourses[] = [
            'course' => $course,
            'type'   => 'progress'
        ];
    }

    // Fill remaining slots with pending/not-started courses.
    if (count($displaycourses) < $maxcourses) {
        foreach ($panding as $course) {
            if (count($displaycourses) >= $maxcourses) {
                break;
            }

            $displaycourses[] = [
                'course' => $course,
                'type'   => 'pending'
            ];
        }
    }

	$html = '
    <div class="card border-0 p-4 shadow-sm mb-4" style="border-radius: 16px;">
	    <div class="d-flex justify-content-between align-items-center mb-3">
	        <h5 class="font-weight-bold m-0">Continue Learning </h5>
	        <a href="'.$CFG->wwwroot.'/my/courses.php" class="btn btn-link btn-sm text-primary p-0">View all</a>
	    </div>
		';

           if (!empty($displaycourses)) {

        foreach ($displaycourses as $item) {

            $course = $item['course'];
            $type   = $item['type'];

            // Only calculate percentage for courses that have started.
            $percentage = 0;

            if ($type === 'progress') {
                $percentage = \core_completion\progress::get_course_progress_percentage(
                    $course,
                    $userid
                );

                // Handle null just in case.
                if ($percentage === null) {
                    $percentage = 0;
                }
            }

            // Button depends on course status.
            if ($type === 'progress') {

                $buttontext = 'Continue';

            } else {

                $buttontext = 'Start';
            }

            $html .= '
                <div class="row align-items-center mb-3 pb-3">

                    <!-- Course Image -->
                    <div class="col-12 col-md-3 mb-3 mb-md-0 text-center text-md-start">
                        <div class="cst_width"
                             style="
                                max-width: 200px;
                                width: 100%;
                                height: 100px;
                                background-color: #e5e7eb;
                                border-radius: 8px;
                             ">
                            ' . getcourse_image($course->id) . '
                        </div>
                    </div>

                    <!-- Course Content -->
                    <div class="col-12 col-md-9">

                        <div class="d-flex flex-row flex-wrap justify-content-between align-items-center gap-3 cst-flex">

                            <!-- Course Details -->
                            <div class="col-xl-9">

                                <h6 class="mb-1 font-weight-bold">
                                    ' . format_string($course->fullname) . '
                                </h6>

                                <p class="text-muted small mb-2">
                                    ' . get_course_description_by_id($course->id, 30) . '
                                </p>';

            // Show progress bar only for courses already started.
            if ($type === 'progress') {

                $html .= '
                                <div class="d-flex align-items-center flex-wrap gap-2"
                                     style="gap:4px;">

                                    <div class="progress flex-grow-1"
                                         style="
                                            height: 6px;
                                            min-width: 180px;
                                            max-width: 300px;
                                         ">

                                        <div class="progress-bar"
                                             role="progressbar"
                                             style="width: ' . round($percentage) . '%;">
                                        </div>

                                    </div>

                                    <small class="text-muted">
                                        ' . round($percentage) . '%
                                    </small>

                                </div>';

            } else {

                // Optional message for courses not started yet.
                $html .= '
                                <small class="text-muted">
                                    Not started yet
                                </small>';
            }

            $html .= '
                            </div>

                            <!-- Button -->
                            <div class="col-md-12 col-xl-3 col-xxl-3 w-100 w-md-auto text-end pr-0">

                                <a href="' . $CFG->wwwroot . '/course/view.php?id=' . $course->id . '"
                                   class="btn btn-sm px-4 py-2 cst_brn_clr"
                                   style="border-radius: 8px;">
                                    ' . $buttontext . '
                                </a>

                            </div>

                        </div>

                    </div>

                    <div class="col-12 col-md-9" style="margin-left: auto;">
                        <div class="cst_border mt-3"></div>
                    </div>

                </div>
            ';
        }

    } else {

        $html .= '
            <div class="text-center norecord_info alert">
                No courses available.
            </div>
        ';
    }
    $html .= '
    </div>';    

    return $html;
}

// My Certificates
function learnpeek_user_certificates($userid) {
    global $DB, $CFG, $PAGE;

    $PAGE->requires->jquery();
    $usercontext = \context_user::instance($userid);
    $contextid   = $usercontext->id;

    $earnedcertificates = $DB->get_records('customcert_backup', ['userid' => $userid]);

    $html = '
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css">
        <div class="card border-0 p-4 shadow-sm mb-4" style="border-radius:16px;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="font-weight-bold m-0">My Certificates</h5>
            <a href="'.$CFG->wwwroot.'/local/customlayout/certificates.php" class="btn btn-link btn-sm text-primary p-0">View all</a>
        </div>

        <div class="owl-carousel certificate-slider">';

            $hascertificate = false;

            if (!empty($earnedcertificates)) {

                foreach ($earnedcertificates as $cert) {

                    $docfile = $DB->get_record_sql("
                        SELECT * FROM {files}
                         WHERE contextid = :ctxid
                           AND component = 'user'
                           AND itemid = :itemid
                           AND filesize > 0
                           AND filearea = 'certificate'
                           AND filename != '.'",
                        ['ctxid' => $contextid, 'itemid' => $cert->id]);

                    if ($docfile) {
                        $hascertificate = true;



                        $course = $DB->get_record("course",array("id"=>$cert->courseid));

                        $license_url = $CFG->wwwroot.'/local/customlayout/file.php?id='.$docfile->pathnamehash;

                        $html .= '
                        <div class="item">
                            <div style="border:1px solid #ddd;padding:10px;border-radius:8px; min-height:230px">
                                <div class="rounded text-center bg-light">
                                    <a target="_blank" href="'.$license_url.'">
                                        <img
                                        src="'.$CFG->wwwroot.'/blocks/learnpeek_userdashboard/pix/certificate.png"
                                        style="width:100%;"
                                        alt="Certificate">
                                    </a>
                                </div>

                                <small class="d-block font-weight-bold mt-2">'
                                    . format_string($course->fullname) .
                                '</small>

                                <small class="text-muted">
                                    Earned on '. $dateearned = date('F j, Y', $cert->course_completion_date) .'
                                </small>
                            </div>
                        </div>';
                    }
                }

            }

            $html .= '
        </div>';
        if(!$hascertificate){
            $html .= '
                <div class="text-center norecord_info alert">
                    No Certificates Earned Yet.
                </div>';
        }
       $html .= ' 
    </div>

    <script>

        $(document).ready(function() {

            if (!$(".certificate-slider").hasClass("owl-loaded")) {

                $(".certificate-slider").owlCarousel({
                    loop: '.(sizeof($earnedcertificates) >= 4?'true':'false').',
                    margin: 20,
                    nav: true,
                    dots: false,
                    autoplay: true,
                    autoplayTimeout: 3000,
                    autoplayHoverPause: true,

                    navText: [
                        "<span style=\'font-size:24px;\'>‹</span>",
                        "<span style=\'font-size:24px;\'>›</span>"
                    ],

                    responsive: {
                        0: {
                            items: 1
                        },
                        576: {
                            items: 2
                        },
                        992: {
                            items: 3
                        },
                        1200: {
                            items: 4
                        }
                    }
                });

            }

        });
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>
    ';
    return $html;
}

// Explore New Courses 
function explore_new_courses() {
    global $CFG;
    $html = '
    <div class="card border-0 shadow-sm mb-4 overflow-hidden" style="border-radius:16px; background:linear-gradient(90deg,#4F6BFF 0%,#7B61FF 100%); position:relative;">

        <div class="row align-items-center p-4">

            <!-- Left Content -->
            <div class="col-md-5">
                <h4 class="font-weight-bold text-white mb-2">
                    Explore New Courses
                </h4>

                <p class="text-white mb-0"
                style="opacity:.9;font-size:16px;">
                    Expand your knowledge with our latest courses
                </p>
            </div>

            <!-- Center Button -->
            <div class="col-md-3 text-center my-2">
                <a href="'.$CFG->wwwroot.'/blocks/learnpeek_shop/shop.php" class="btn font-weight-bold px-4 py-2"
                        style="background:#fff;
                            color:#4F6BFF;
                            border-radius:8px;
                            min-width:170px;
                            box-shadow:0 4px 12px rgba(0,0,0,.12);">
                    Browse Courses
                </a>
            </div>

            <!-- Right Image -->
            <div class="col-xl-4  text-xl-right text-left">
                <img src="https://cdn-icons-png.flaticon.com/512/3145/3145765.png"
                    alt="Books"
                    style="height:80px;">
            </div>

        </div>

        <!-- Decorative Circle -->
        <div style="position:absolute; right:180px; top:-40px; width:180px; height:180px; border-radius:50%; background:rgba(255,255,255,.08);">
        </div>

    </div>
    ';
    return $html;
}

// Learning Streak
function render_learning_streak_card($userid) {
    global $DB;

    $sql = "SELECT DISTINCT FROM_UNIXTIME(timecreated, '%Y-%m-%d') as logdate
            FROM {logstore_standard_log}
            WHERE userid = :userid";

    $records = $DB->get_records_sql($sql, ['userid' => $userid]);

    $activitydays = [];
    foreach ($records as $r) {
        $activitydays[] = $r->logdate;
    }

    // ---------------------------
    // 2. CALCULATE STREAK
    // ---------------------------
    $streak = 0;
    $today = strtotime('today');

    while (in_array(date('Y-m-d', $today), $activitydays)) {
        $streak++;
        $today = strtotime('-1 day', $today);
    }

    // ---------------------------
    // 3. WEEK RANGE (MON-SUN)
    // ---------------------------
    $startofweek = strtotime('monday this week');

    $daysShort = ['M','T','W','T','F','S','S'];
    $weekHTML = '';

    for ($i = 0; $i < 7; $i++) {

        $currentDay = date('Y-m-d', strtotime("+$i day", $startofweek));
        $label = $daysShort[$i];

        // Default styles
        $bg = '#fff';
        $color = '#000';
        $border = 'border';

        // If activity exists → Blue Tick
        if (in_array($currentDay, $activitydays)) {
            $bg = '#A5B4FC';
            $color = 'white';
            $content = '✓';
            $border = '';
        } 
        // If today and no activity → Orange
        else if ($currentDay == date('Y-m-d')) {
            $bg = '#F59E0B';
            $color = 'white';
            $content = $label;
            $border = '';
        } 
        // Future day → empty circle
        else if (strtotime($currentDay) > strtotime(date('Y-m-d'))) {
            $content = '';
        } 
        // Past no activity → empty
        else {
            $bg = '#F59E0B';
            $color = 'white';
            $content = $label;
            $border = '';
        }

        $weekHTML .= '
        <div>
            <div class="rounded-circle d-flex align-items-center justify-content-center '.$border.'"
                style="width:36px;height:36px;background:'.$bg.';color:'.$color.';font-size:14px;border-color:#D1D5DB;">
                '.$content.'
            </div>
            <small class="d-block mt-2 text-muted">'.$label.'</small>
        </div>';
    }

    // ---------------------------
    // 4. FINAL HTML
    // ---------------------------
    $html = '
    <div class="card border-0 p-4 shadow-sm mb-4" style="border-radius: 16px;">
        
        <h6 class="font-weight-bold mb-4" style="font-size:18px;">
            🔥 Learning Streak
        </h6>

        <div style="font-size:60px; font-weight:700; line-height:1;">
            '.$streak.'
        </div>

        <div class="font-weight-bold mt-3" style="font-size:24px;">
            Days in a row!
        </div>

        <p class="text-muted mt-2 mb-4" style="font-size:15px;">
            You\'re on fire! Keep it up.
        </p>

        <div class="d-flex  text-center flex-wrap" style="gap:15px">
            '.$weekHTML.'
        </div>

    </div>';

    return $html;
}

// Recent Achievements
function learnpeek_user_recent_achievements($userid){
    global $DB, $CFG, $PAGE;

    $usercontext = \context_user::instance($userid);
    $contextid   = $usercontext->id;

    $enrolcourses = learnpeek_user_courses_status($userid);
    $completcourses     = $enrolcourses['completcourse'];
    // echo "<pre>";
    // print_r($completcourses);
    // die();
    $customcert = $DB->get_record('modules', array("name"=>"customcert"));

    $html = '
        <div class="card border-0 p-4 shadow-sm mb-4" style="border-radius:16px;">

            <h5 class="font-weight-bold mb-4" style="font-size:20px;">
                Recent Achievements
            </h5>';

            if($completcourses){
                $allcompletcourses = implode(',', array_column($completcourses, "id"));

                $recentactive = $DB->get_records_sql("
                    SELECT * FROM {course_completions} 
                    WHERE userid = ? AND course IN ($allcompletcourses) AND timecompleted IS NOT NULL
                    ORDER BY timecompleted DESC LIMIT 2",
                    [$userid]
                );

                foreach ($recentactive as $course) {
                    $cm = $DB->get_records('course_modules', array("course"=>$course->course , "module"=>$customcert->id, "deletioninprogress"=>0));

                    $scorm_cm = $DB->get_record_sql('SELECT * FROM {course_modules} WHERE course =? AND module = ? AND deletioninprogress = 0  ORDER BY id ASC', array($course->course ,$scorm_md->id));

                    $courseinfo =  $DB->get_record('course', array("id"=>$course->course));
                    $fullname = strip_tags($courseinfo->fullname);

                    if(!empty($cm)){
                        foreach($cm as $ccm){
                            $modinfo = get_fast_modinfo($course->course);
                            $cminfo = $modinfo->get_cm($ccm->id);
                            if ($cminfo->uservisible) {
                                $getcertificateissue = $DB->get_record_sql("SELECT * FROM {customcert_issues} WHERE customcertid = ? AND userid = ?",array($ccm->instance, $userid));
                                if($getcertificateissue){
                                    $certificateurl = $CFG->wwwroot.'/mod/customcert/view.php?id='.$ccm->id.'&downloadown=1';
                                    $html .= '
                                        <div class="d-flex justify-content-between align-items-xl-center align-items-start mb-4 flex-xl-row flex-lg-column flex-md-row flex-column">
                                            
                                            <div class="d-flex align-items-center">
                                                
                                                <div class="d-flex align-items-center justify-content-center mr-3"
                                                    style="width:42px;height:42px;background:#EEFDF3;border-radius:12px;">
                                                    <span style="font-size:20px;color:#22C55E;"><i class="fa-regular fa-circle-check"></i></span>
                                                </div>

                                                <div>
                                                    <div style="font-weight:600;font-size:16px;color:#111827;">
                                                        Course Completed
                                                    </div>
                                                    <div style="font-size:15px;color:#6B7280;">
                                                       '. $fullname .'
                                                    </div>
                                                </div>

                                            </div>

                                            <div style="font-size:14px;color:#6B7280;white-space:nowrap;">
                                                '. learnpeek_time_ago($course->timecompleted).'
                                            </div>

                                        </div>
                                    ';
                                }
                            }  
                        } 
                    }

                    if($scorm_cm){
                      $aicc_scorm = $DB->get_record("scorm", array('id' => $scorm_cm->instance));
                      if($aicc_scorm->version == "AICC"){
                        $certificateurl = $CFG->wwwroot.'/mod/scorm/view.php?id='.$scorm_cm->id;
                        $html .= '
                            <div class="d-flex justify-content-between align-items-center mb-4 flex-xl-row flex-column">
                                
                                <div class="d-flex align-items-center">
                                    
                                    <div class="d-flex align-items-center justify-content-center mr-3"
                                        style="width:42px;height:42px;background:#EEFDF3;border-radius:12px;">
                                        <span style="font-size:20px;color:#22C55E;">✓\</span>
                                    </div>

                                    <div>
                                        <div style="font-weight:600;font-size:16px;color:#111827;">
                                            Course Completed
                                        </div>
                                        <div style="font-size:15px;color:#6B7280;">
                                            '. $fullname .'
                                        </div>
                                    </div>

                                </div>

                                <div style="font-size:14px;color:#6B7280;white-space:nowrap;">
                                    '. learnpeek_time_ago($course->timecompleted).'
                                </div>

                            </div>
                        ';
                      }
                    }
                }
                $html .= '

                    <a href="'.$CFG->wwwroot.'/blocks/custom_mycourse/mycourse.php?type=completed"
                    style="font-size:15px;font-weight:600;color:#6366F1;text-decoration:none;">
                        View all achievements
                    </a>';

            }else{
                $html .='
                <div class="text-center norecord_info alert">
                    No Recent Achievements Yet.
                </div>
                ';
            }
            $html .= '
        </div>
    ';

    return $html;
}

// comapny allow course list
function get_company_allowed_courses($companyid) {
    global $DB;

    $courses = $DB->get_records_sql("
        SELECT DISTINCT c.id
        FROM {course} c
        INNER JOIN {company_course} cc ON cc.courseid = c.id
        INNER JOIN {course_shopsettings_courses} cssc ON cssc.courseid = c.id
        INNER JOIN {course_shopsettings} css ON css.id = cssc.itemid
        WHERE cc.companyid = ?
        AND css.companyid = ?
        AND css.enabled = 1
    ", [$companyid, $companyid]);

    return array_keys($courses);
}

// Recent Achievements
function learnpeek_user_upcoming_events($userid, $limit = 2, $companyid) {
    global $DB, $USER;

    $events = [];

    //  Step 1: Get allowed courses
    $allowedcourses = get_company_allowed_courses($companyid);


    if (empty($allowedcourses)) {
        return '<div class="text-center norecord_info alert">
                    No events available.
                </div>';
    }

    list($insql, $params) = $DB->get_in_or_equal($allowedcourses);

    //=========== trainingevent ============
    
    $trainingevents = $DB->get_records_sql("
        SELECT t.id, t.name, t.startdatetime, t.enddatetime, t.classroomid, cm.course, cm.id as moduleid
        FROM {trainingevent} t
        JOIN {course_modules} cm ON cm.instance = t.id
        JOIN {modules} m ON m.id = cm.module AND m.name = 'trainingevent'
        WHERE cm.course $insql
        AND cm.deletioninprogress = 0
    ", $params);

    foreach ($trainingevents as $te) {
        if ($te->startdatetime < time()) continue;

        $events[] = [
            'type'  => 'In Person',
            'title' => $te->name,
            'start' => $te->startdatetime,
            'end'   => $te->enddatetime,
            'pageview'      => 'tevent',
            'course'        => $te->course,
            'moduleid'    => $te->moduleid,

        ];
    }

    //=========== reservation ============

    $reservations = $DB->get_records_sql("
        SELECT r.id, r.location, r.timestart, r.timeend, cm.course, cm.id as moduleid
        FROM {reservation} r
        JOIN {course_modules} cm ON cm.instance = r.id
        JOIN {modules} m ON m.id = cm.module AND m.name = 'reservation'
        WHERE cm.course $insql
        AND cm.deletioninprogress = 0
    ", $params);

    foreach ($reservations as $re) {
        if ($re->timestart < time()) continue;

        $events[] = [
            'type'          => 'In Person',
            'title'         => 'In Person',
            'start'         => $re->timestart,
            'end'           => $re->timeend,
            'pageview'      => 'event',
            'course'        => $re->course,
            'moduleid'    => $re->moduleid,
        ];
    }

    //=========== ZOOM ============

    $zooms = $DB->get_records_sql("
        SELECT z.id, z.start_time, z.duration, cm.course , cm.id as moduleid
        FROM {zoom} z
        JOIN {course_modules} cm ON cm.instance = z.id
        JOIN {modules} m ON m.id = cm.module AND m.name = 'zoom'
        WHERE cm.course $insql
        AND cm.deletioninprogress = 0
    ", $params);

    foreach ($zooms as $ze) {
        if ($ze->start_time < time()) continue;

        $events[] = [
            'type'  => 'Virtual',
            'title' => 'Zoom Meeting',
            'start' => $ze->start_time,
            'end'   => $ze->start_time + ($ze->duration * 60),
            'pageview'      => 'event',
            'course'        => $ze->course,
            'moduleid'    => $ze->moduleid,
        ];
    }


    usort($events, function($a, $b) {
        return $a['start'] <=> $b['start'];
    });

    $events = array_slice($events, 0, $limit);



    $html = '
    <div class="card border-0 p-4 shadow-sm mb-4" style="border-radius:16px;">

        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
            <h5 class="font-weight-bold mb-0" style="font-size:20px;">
                Upcoming Events
            </h5>

            <a href="'.$CFG->wwwroot.'/blocks/learnpeek_shop/shop.php"
            style="color:#6366F1;font-weight:600;text-decoration:none; text-wrap:nowrap;">
                View all
            </a>
        </div>
    ';

    if (!empty($events)) {
        // echo "<pre>";
        // print_r($events);
        // echo "</pre>";
        // die();
        $count = 0;

        foreach ($events as $event) {
            $eventproduct = $DB->get_record_sql("
                SELECT cssc.* FROM {course_shopsettings_courses} cssc 
                INNER JOIN {course_shopsettings} css ON css.id = cssc.itemid 
                WHERE cssc.courseid = ? 
                AND css.companyid = ?",
                [$event['course'],$companyid]
            );

            if ($count >= $limit) break;

            $month = strtoupper(date("M", $event['start']));
            $day   = date("d", $event['start']);
            $date  = date("M d, Y", $event['start']);
            $time  = date("h:i A", $event['start']);

            $border = ($count == 0) 
                ? 'border-bottom:1px solid #F1F5F9; padding-bottom:20px; margin-bottom:20px;' 
                : '';

            $html .= '
            <div class="d-flex justify-content-between align-items-center cst-flex flex-wrap" style="'.$border.'">

                <div class="d-flex align-items-center">

                    <div class="text-center mr-3 d-flex flex-column justify-content-center"
                        style="width:48px;height:48px;border:1px solid #FBCFE8;border-radius:12px;background:#FFF;">
                        <span style="font-size:11px;font-weight:700;color:#EF4444;line-height:1;">
                            '.$month.'
                        </span>
                        <span style="font-size:16px;font-weight:700;color:#374151;">
                            '.$day.'
                        </span>
                    </div>

                    <div>
                        <div style="font-weight:600;font-size:16px;color:#111827;">
                            '.$event['title'].'
                        </div>

                        <div style="font-size:15px;color:#6B7280;">
                            '.$date.' • '.$time.'
                        </div>
                    </div>

                </div>

                <a href="'.$CFG->wwwroot .'/blocks/learnpeek_shop/'.$event['pageview'].'.php?itemid='.$eventproduct->itemid.'" class="btn"
                        style="border:1px solid #D1D5DB;color:#6366F1;font-weight:600;background:#fff;border-radius:10px;padding:8px 18px; margin: 5px 0;">
                    Register
                </a>

            </div>';
            
            $count++;
        }

    } else {

        $html .= '<p style="color:#6B7280;">No upcoming events</p>';
    }

    $html .= '</div>';

    return $html;

}


// get course descrption according to set lenght
function get_course_description_by_id($courseid, $length) {
    global $DB;

    $course = $DB->get_record('course', ['id' => $courseid]);

    if (!$course) {
        return '';
    }

    $summary = html_to_text($course->summary, 0);

    if (core_text::strlen($summary) > $length) {
        $summary = core_text::substr($summary, 0, $length) . '...';
    }

    return trim($summary);
}

// course custom filed data by course id or shortname
function get_course_customfield_data($courseid, $shortname) {
    global $DB;

    $sql = "
        SELECT cd.*
        FROM {customfield_data} cd
        JOIN {customfield_field} cf
            ON cf.id = cd.fieldid
        WHERE cd.instanceid = :courseid
          AND cf.shortname = :shortname
    ";

    return $DB->get_record_sql($sql, [
        'courseid'  => $courseid,
        'shortname' => $shortname
    ]);
}


// display time like day, week, month , months , year , years
function learnpeek_time_ago($timestamp) {
    $diff = time() - $timestamp;

    $units = [
        31536000 => 'year',   // 365 days
        2592000  => 'month',  // 30 days
        604800   => 'week',   // 7 days
        86400    => 'day',
        3600     => 'hour',
        60       => 'minute'
    ];

    foreach ($units as $secs => $label) {
        if ($diff >= $secs) {
            $value = floor($diff / $secs);
            return $value . ' ' . $label . ($value > 1 ? 's' : '') . ' ago';
        }
    }

    return 'just now';
}