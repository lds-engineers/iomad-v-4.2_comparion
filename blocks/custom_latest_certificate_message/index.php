<?php 
require_once("../../config.php");
global $DB,$CFG,$USER;
require_once($CFG->dirroot . '/lib/enrollib.php');
require_once($CFG->dirroot . '/message/externallib.php');
use core_message_external;

$convt = $DB->get_records('message_conversation_members',array('userid' => $USER->id));
$convert = [];
foreach ($convt as $conv) {
    // $msg = core_message_external::get_conversation_messages($USER->id,$conv->conversationid);
    array_push($convert, $conv->conversationid);
}

$cvtid = implode(",",$convert);
$msg = $DB->get_records_sql("SELECT * FROM {messages} WHERE useridfrom != $USER->id AND conversationid IN ($cvtid) ORDER BY id DESC LIMIT 2");

// SELECT *  FROM `mdl_messages` WHERE `conversationid` IN (4,3,6);

echo "<pre>";
print_r($cvtid);
echo "<pre>----------------------------------";
print_r($msg);
echo "</pre>";

die;



    $data = enrol_get_users_courses($USER->id);
    $enrolcourses = $data;
    $customcert = $DB->get_record('modules', array("name"=>"customcert"));    
    $iomadcert = $DB->get_record('modules', array("name"=>"iomadcertificate"));
    foreach ($enrolcourses as $course) {
        $cm = $DB->get_records('course_modules', array("course"=>$course->id , "module"=>$customcert->id));    
        $icm = $DB->get_records('course_modules', array("course"=>$course->id , "module"=>$iomadcert->id));    
        if(!empty($cm)){
        	foreach($cm as $ccm){
		        echo '<pre>';
		        print_r($ccm);
		        echo '</pre>';

        	}
        }
        if(!empty($icm)){
        	foreach($icm as $ccm){
		        echo '<pre>==';
		        print_r($ccm);
		        echo '</pre>';
        	}
        }    
    }

?>