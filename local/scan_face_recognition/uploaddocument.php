<?php
require_once(dirname(__FILE__) . '/../../config.php');
require_once('uploaddocumentform.php');
global $DB, $USER;
require_login();
$PAGE->set_context(context_system::instance());
$context = context_system::instance();
$PAGE->set_context($context);
$companyid = iomad::get_my_companyid(context_system::instance());
$id = required_param('id', PARAM_INT);

$pageurl = $CFG->wwwroot."/local/scan_face_recognition/uploaddocument.php?id=".$id;
$args = array("id"=>$id);

$pagetitle = 'Upload ID (Driver License)';
$PAGE->set_title($pagetitle);
$PAGE->set_url($pageurl);

echo $OUTPUT->header();

?>
<style type="text/css">
</style>
<div class="dash-heading"align="left"><h3 class="text-left"><?php echo $pagetitle; ?></h3></div>
<?php
$addformaaa = new uploaddocumentform($pageurl, $args);
if ($addformaaa->is_cancelled()) {
	redirect($CFG->wwwroot). "/scan_face_recognition/uploaddocument.php?id=".$id; 
}else if($addform = $addformaaa->get_data()) {
    $document = $DB->get_record_sql('SELECT * FROM {lcl_scan_face_recognition} WHERE userid = ?', array($USER->id));
    $documentData = new stdClass();
    $documentData->id           = $document->id;
    $documentData->document     = $addform->document;
    $documentData->modifiedtime = time();
    $documentData->modifiedby = $USER->id;
    $DB->update_record('lcl_scan_face_recognition', $documentData);
    file_save_draft_area_files($addform->document, $systemcontext->id, 'userdocument',"document",$documentData->id);
    $url = $CFG->wwwroot."/course/view.php?id=$id";
    redirect(new moodle_url($url),'Upload successful. Please wait while your document is being processed. You will be redirected to the course page shortly </strong> to start the course', null, \core\output\notification::NOTIFY_SUCCESS);
}
$documentdetails = $DB->get_record_sql('SELECT * FROM {lcl_scan_face_recognition} WHERE userid = ?', array($USER->id));
if($documentdetails) {
    $document = file_get_submitted_draft_itemid('document');
    file_prepare_draft_area($document, $systemcontext->id, 'userdocument', 'document', $documentdetails->id);
    $data = new stdClass(); 
    $data->document = $document;
}
$addformaaa->set_data($data);
$addformaaa->display();
echo $OUTPUT->footer();
