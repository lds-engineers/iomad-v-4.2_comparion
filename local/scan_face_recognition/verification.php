<?php
require_once "../../config.php";
global $DB, $CFG, $PAGE, $OUTPUT, $USER, $SESSION;
require_login();

$id = required_param('id', PARAM_TEXT);
$lessonData = $DB->get_record_sql("SELECT * FROM {lcl_scan_face_recognition} WHERE userid = $USER->id ORDER BY id DESC LIMIT 1");

$html = '<div id="alert-wrap" align="center"><img src="https://diddikicks.co.uk/assets/plugins/animation/processing.gif" /></div>';


$html .= '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script>var CFG = '.json_encode($CFG).'; 
var aw = '.json_encode($USER).'; 
var lessonData = '.json_encode($lessonData).'; 
var courseid = '.$id.'; 
</script>
<script src="'.$CFG->wwwroot.'/local/scan_face_recognition/scanface.js"></script>
<script src="'.$CFG->wwwroot.'/local/scan_face_recognition/scanface-webcam.js"></script>
<script src="'.$CFG->wwwroot.'/local/scan_face_recognition/scanface-alerts.js"></script>';

echo $OUTPUT->header();
echo $html;
echo $OUTPUT->footer();
