<?php
require_once "../../config.php";
require_once('lib.php');
global $DB, $CFG, $PAGE, $OUTPUT, $USER;
require_login();

$id = required_param('id', PARAM_TEXT);
$companyid = iomad::get_my_companyid(context_system::instance());
$companyDetails = new stdClass();
if($companyid>0) {
    $company = $DB->get_record("company",array("id"=>$companyid));
    $companyDetails->id = $companyid; 
    $companyDetails->data = $company ? $company : null; 
}

$PAGE->requires->js(new moodle_url('https://code.jquery.com/jquery-3.6.0.min.js'));
$PAGE->requires->js(new moodle_url($CFG->wwwroot.'/local/scan_face_recognition/scanface-webcam.js'));
$PAGE->requires->js(new moodle_url($CFG->wwwroot.'/local/scan_face_recognition/scanface-upload-image.js'));
$PAGE->requires->js(new moodle_url($CFG->wwwroot.'/local/scan_face_recognition/scanface-alerts.js'));

$html = '
<style>

  body{
    overflow-x:hidden;
  }
    #video-wrapper>video{
    width:100%;
     margin-top:10px;
}

#canvas-wrapper #myCanvas {
    margin-top: 10px;
    aspect-ratio: unset !important;
    min-width: 100%;
}
</style> 
<div id="camera-wrapper" class="row">
    <div class="col-sm-12 align-center">
        <p class="title"><strong>Please use your camera to take a profile photo that will be used for facial recognition during the course duration</strong></p>
        <p>Please note that, once you confirm the photo to be used, you will not be able to change it and the same photo will be used on your Certificate of Completion</p>
        <hr class="divider my-4" style="width:120px; margin:0px auto; height:4px; background: #f8b944 ">
    </div>
    <div id="video-wrapper" class="col-sm-12 col-md-6 align-center"></div>
    <div id="canvas-wrapper" class="col-sm-12 col-md-6 align-center"></div>
    <div class="col-sm-12 align-center justify-content-center text-center">
        <div id="snap-button-wrapper" class="align-right shape-round icon_alignment-left d-inline-block" style="max-width:300px ">
            <a id="snapshotBtn" class="btn btn-primary bp-element-hover  btn-normal "  >
                <span class="inner-text">Take a photo</span>
            </a>
        </div>
        <div id="save-button-wrapper" class="shape-round shape-round icon_alignment-left d-inline-block mt-4 ml-4" style="max-width:300px">
            <a id="saveBtn" class="btn btn-primary bp-element-hover  btn-normal " >
                <span class="inner-text">Save</span>
            </a>
			<img style="display:none;" src="'.$CFG->wwwroot.'/local/scan_face_recognition/lp_loader_submit.gif" alt="lp_scansave_imgloader" class="lp_scansave_imgloader" />
        </div>
    </div>
</div>';


$html .= '<script>var CFG = '.json_encode($CFG).'; 
var aw = '.json_encode($USER).'; 
var courseid = '.$id.'; 
var server = window.location.hostname;
var companyDetails = '.json_encode($companyDetails).';
</script>';

echo $OUTPUT->header();
echo $html;
echo $OUTPUT->footer();
