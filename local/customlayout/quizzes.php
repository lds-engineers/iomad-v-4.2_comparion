<?php
require_once('../../config.php');
global $DB, $CFG, $USER, $PAGE;
require_login();

$userid = optional_param('userid', $USER->id, PARAM_INT);
if(!is_siteadmin()){
    $userid = $USER->id;  // Owner of the page
}

$strmymoodle = get_string('quizes', 'local_customlayout');
$PAGE->set_title($strmymoodle);
$PAGE->set_heading($strmymoodle);



$enrolled_courses = $DB->get_records_sql("SELECT DISTINCT c.id, c.fullname FROM {user_enrolments} ue JOIN {enrol} e ON ue.enrolid = e.id JOIN {course} c ON e.courseid = c.id WHERE ue.userid = :userid", array('userid' => $userid));

$html = '<style>
    .main-box{
     width:31.333%;
     margin:5px;
     max-height:155px
    }

@media(max-width:1366px){

        .main-box{
        max-height:100%;
        }

    .left-umn {
        flex-direction: column;
    }
    .left-umn>span {
        width: 100%;
        margin-bottom: 10px;
    }
}


@media(max-width:574px){
.main-box {
    width: 47.333%;
}
}

@media(max-width:443px){
.main-box {
    width: 100%
}
}

</style> 
<div class="row align-items-start -lg-12 p-0">';

foreach ($enrolled_courses as $course) {
	$quizzes = get_all_instances_in_courses('quiz', array($course->id => $course), $userid, $includeinvisible);
	if(!empty($quizzes)){
        $i = 01;
        foreach ($quizzes as $quiz) {
            $attempt = $DB->get_record_sql('SELECT * from {quiz_attempts} WHERE quiz ='.$quiz->id.'  AND userid= '.$userid.' order by id desc limit 1');
            
            $html .= '<div class="main-box  rounded  p-2 Larger shadow-lg">
                <div class="left-umn d-flex  align-items-center  justify-content-evenly">
                    <span class="d-flex align-items-center px-4 bg-primary rounded text-white display-4 mx-2 fw-semibold" style="font-weight:bolder; height:138px">'.sprintf("%02d", $i).'</span>
                    <div class="w-100">
                        <a href="'.$CFG->wwwroot.'/mod/quiz/view.php?id='.$quiz->coursemodule. '"><p class="m-0 border-primary fw-semibold" style="border-bottom:2px solid; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; width: 62%;" >'.$quiz->name.'</p></a>';
                        if(!empty($attempt->timefinish) && ($attempt->state == "finished")){
                            $firstDate = new DateTime("@$attempt->timestart");
                            $secondDate = new DateTime("@$attempt->timefinish");
                            $difference = $secondDate->diff($firstDate);
                            $differenceString = $difference->format("%h:%i:%s");
                            list($hours, $minutes, $seconds) = explode(':', $differenceString);
                            $formattedDifference = sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
                            $html .= '<p class="fw-semibold my-2 d-inline" style="font-weight: 600;" >Status &nbsp&nbsp&nbsp 100% Completed</p>
                            <p class="fw-semibold my-2" style="font-weight: 600;" >Spend Time &nbsp&nbsp&nbsp '.$formattedDifference.'</p>
                            <p class="fw-semibold my-2" style="font-weight: 600;" >Date &nbsp&nbsp&nbsp '.date('M d, Y',$attempt->timestart).'</p>';
                        }else{
                            $html .= '<p class="fw-semibold my-2" style="font-weight: 600;" >Status &nbsp&nbsp&nbsp 0% In Progress</p>
                            <p class="fw-semibold my-2" style="font-weight: 600;" >Spend Time &nbsp&nbsp&nbsp --:-- </p>
                            <p class="fw-semibold my-2" style="font-weight: 600;" >Date &nbsp&nbsp&nbsp '.date('M d, Y',$attempt->timestart).'</p>';
                        }
          $html .= '</div>
                </div>
            </div>';
            $i++;  
        }	
    }
}
$html .= '</div>';
echo $OUTPUT->header();
echo $html;
echo $OUTPUT->footer();
