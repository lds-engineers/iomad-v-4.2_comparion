<?php
// use core_completion\progress;

// require_once("../../config.php");
// global $DB,$CFG,$USER;
// require_once($CFG->dirroot . '/lib/enrollib.php');
// $enrolcourses = enrol_get_users_courses($USER->id);

// $passCount = [];
// $failCount = [];
// $panding = [];

// foreach ($enrolcourses as $course) {
//     $coursestatus = array();
//     $cm = $DB->get_records_sql('SELECT cm.*,q.name as qname,q.attempts FROM {modules} m JOIN {course_modules} cm ON m.id = cm.module JOIN {quiz} q on cm.instance = q.id WHERE m.name = "quiz" AND cm.course ='.$course->id);
//     if($cm){
//         foreach ($cm as $cmm) {
//             $gradeitem = $DB->get_record("grade_items", array("itemtype"=>"mod", "itemmodule"=>"quiz", "iteminstance"=>$cmm->instance));
//             $status = 0;
//             $getgrade = $DB->get_record("grade_grades", array("itemid"=>$gradeitem->id,'userid'=>$USER->id));
//             if($getgrade->rawgrade >= $gradeitem->gradepass){
//                 $status = 1;
//             }else{
//                 $attmpt = $DB->count_records_sql("select count(qa.id) from {quiz_attempts} qa where qa.quiz=? and qa.userid=?", array($cmm->instance, $USER->id));
//                 if($attmpt == $cmm->attempts){
//                     $status = 0;
//                 }elseif ($attmpt < $cmm->attempts) {
//                     $status = 2;
//                 }
//             }
//             array_push($coursestatus, $status);
//         }
//     }
//     print_r($coursestatus);




//     if(empty($coursestatus)){
//         array_push($panding, $course->id);
//     } else if(in_array("0", $coursestatus)){
//         array_push($failCount, $course->id);
//     } else if(in_array("2", $coursestatus)){
//         array_push($panding, $course->id);
//     } else {
//         array_push($passCount, $course->id);
//     }
 
// }


// echo '<pre>';
// print_r($passCount);
// print_r($failCount);
// print_r($panding);
// echo '</pre>';

// die;


