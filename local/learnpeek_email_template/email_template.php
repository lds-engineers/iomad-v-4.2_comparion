<?php
require_once('../../config.php');
require_once("$CFG->libdir/formslib.php");
require_once("$CFG->libdir/filelib.php");
require_once("form_email_template.php");
require_login();
global $DB, $PAGE, $CFG, $OUTPUT;
$id   = optional_param('id', 0, PARAM_INT);
$PAGE->requires->jquery();
$systemcontext = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('emailtemplate','local_learnpeek_email_template'));

$pagetitle = get_string('emailtemplate','local_learnpeek_email_template');
$PAGE->set_heading($pagetitle);
$com_id = iomad::get_my_companyid(context_system::instance());
$companydata = $DB->get_record("company",array("id"=>$com_id));

$getcompnayadmin = $DB->get_record_sql("SELECT * FROM {company_users} WHERE companyid = ? AND userid =?",array($com_id,$USER->id));

if(is_siteadmin()){
echo $OUTPUT->header();
	$mform = new create_email_template();
	if ($mform->is_cancelled()) {
	    redirect($CFG->wwwroot.'/local/learnpeek_email_template/template_list.php');
	} else if ($fromform = $mform->get_data()) {
		// echo "<pre>";
		// print_r($fromform);
		// echo "</pre>";
		$checktemplate = $DB->get_record_sql("SELECT * FROM {custom_email_template} WHERE company_id =? AND email_template = ?",array($fromform->company_id,$fromform->email_template));
		if($checktemplate || $fromform->id){
			
			$updateemailtemplate = new stdClass();
			$updateemailtemplate->id    			= $fromform->id;
			$updateemailtemplate->email_subject   	= $fromform->email_subject;
			$updateemailtemplate->destination_email = strtolower(trim($fromform->destination_email));
			$updateemailtemplate->email_message    	= $fromform->email_message['text'];
			// $updateemailtemplate->email_logo    	= $fromform->email_logo;
			$updateemailtemplate->updated_date    	= time();
			$emailtemplate = $DB->update_record('custom_email_template', $updateemailtemplate);
			// file_save_draft_area_files($fromform->email_logo, $systemcontext->id, 'learnpeek_email_template', "footer_logo", $id);
			redirect(new moodle_url($CFG->wwwroot.'/local/learnpeek_email_template/template_list.php'),'template Updated', null, \core\output\notification::NOTIFY_SUCCESS);

		}else{
			
			$addemailtemplate = new stdClass();
			$addemailtemplate->company_id    	= $fromform->company_id;
			$addemailtemplate->email_template   = $fromform->email_template;
			$addemailtemplate->email_subject    = $fromform->email_subject;
			$addemailtemplate->destination_email = strtolower(trim($fromform->destination_email));
			$addemailtemplate->email_message    = $fromform->email_message['text'];
			// $addemailtemplate->email_logo    	= $fromform->email_logo;
			$addemailtemplate->create_date    	= time();
			$emailtemplate = $DB->insert_record('custom_email_template', $addemailtemplate);
			// file_save_draft_area_files($fromform->email_logo, $systemcontext->id, 'learnpeek_email_template', "footer_logo", $emailtemplate);
			redirect(new moodle_url($CFG->wwwroot.'/local/learnpeek_email_template/template_list.php'),'template Created', null, \core\output\notification::NOTIFY_SUCCESS);

		}
	}

	$templatedata = $DB->get_record_sql("SELECT * FROM {custom_email_template} WHERE id = ?",array($id));
	// $email_logo = file_get_submitted_draft_itemid('footer_logo');
	    // file_prepare_draft_area($email_logo, $systemcontext->id, 'learnpeek_email_template', 'footer_logo', $id);

	if($templatedata){
		$data = new stdClass(); 
		$data->id                  		= $templatedata->id;
		$data->company_id          		= $templatedata->company_id;
		$data->email_template  			= $templatedata->email_template;
		$data->email_subject        	= $templatedata->email_subject;
		$data->destination_email        = $templatedata->destination_email;
		$data->email_message['text']	= $templatedata->email_message;
		// $data->email_logo       		= $email_logo;
	 
	}
	$mform->set_data($data);
	$mform->display();

}else{
	redirect(new moodle_url($CFG->wwwroot.'/my'),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
}
echo $OUTPUT->footer();