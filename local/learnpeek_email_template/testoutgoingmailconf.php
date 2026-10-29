<?php
require_once('../../config.php');
require_once("$CFG->libdir/formslib.php");
require_once("$CFG->libdir/filelib.php");
require_once("form_testoutgoingmailconf.php");
require_login();
global $DB, $PAGE, $CFG, $OUTPUT;
// $id = required_param('id', PARAM_INT);
$id = optional_param('id', 0, PARAM_INT);
$PAGE->requires->jquery();
$systemcontext = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_pagelayout('standard');
$PAGE->set_title('Test outgoing mail configuration');

// $com_id = iomad::get_my_companyid(context_system::instance());
$mapped_ctg = $DB->get_record("company_category_mapping", ["id" => $id]);
$com_id = $mapped_ctg->company_id;
$companydata = $DB->get_record("company",array("id"=>$com_id));
// echo "<pre>rrrr";
// print_r($companydata);
// print_r($mapped_ctg);
// echo "</pre>";

$getcompnayadmin = $DB->get_record_sql("SELECT * FROM {company_users} WHERE companyid = ? AND userid =?",array($com_id,$USER->id));
$pagetitle = 'Test outgoing mail configuration :'.$companydata->name;
$PAGE->set_heading($pagetitle);

if(is_siteadmin()){
	echo $OUTPUT->header();
	$mform = new testoutgoingmail();
	if ($mform->is_cancelled()) {
	    redirect($CFG->wwwroot.'/blocks/learnpeek_shop/mapped_category.php');
	} else if ($fromform = $mform->get_data()) {

		$mappedctg = $DB->get_record("company_category_mapping", ["id" => $fromform->mapped_id]);
		$companyid = $mappedctg->company_id;
		$company = $DB->get_record('company', ['id' => $companyid]);
		// echo "<pre>";
		// print_r($fromform);
		// print_r($company);
		// echo "</pre>";

		$getcompany_wu = $DB->get_record_sql("SELECT * FROM {company_users} WHERE companyid = ? AND managertype =?", array($companyid, 1));
		$whitelableuser = $DB->get_record("user", array("id" => $getcompany_wu->userid));
		$white_label_admin_name = fullname($whitelableuser);
		$fromUser =  $white_label_admin_name;

		// $fromUser = core_user::get_support_user();
		$message = "This is a test message forom ".$company->name." to confirm that you have successfully configured company outgoing mail";
		if($fromform->email_subject){
			$subject = $fromform->email_subject;
		}else{
			$subject = "Test Mail";
		}
		$emailuser = (object) [];
		$emailuser->email = $fromform->to_email_address;
		$emailuser->firstname = $USER->firstname;
		$emailuser->lastname = $USER->lastname;
		$emailuser->maildisplay = true;
		$emailuser->mailformat = 1; // 0 (zero) text-only emails, 1 (one) for HTML/Text emails.
		$emailuser->id = 1;
		$emailuser->firstnamephonetic = false;
		$emailuser->lastnamephonetic = false;
		$emailuser->middlename = false;
		$emailuser->username = false;
		$emailuser->alternatename = false;

		// echo $companyid;

		$emailstatus = email_learnpeek_to_user($emailuser, $fromUser, $subject, $message , $messagehtml ='', '', '', true, '', '', 79, $companyid);
		// $emailstatus = email_to_user($emailuser, $fromUser, $subject, $message , $messagehtml ='', '', '', true, '', '', 79, $companyid); 
		// $emailstatus = 1;
		if($emailstatus){
			echo '<div class="alert alert-success alert-block fade in  alert-dismissible">
				    This site has successfully sent a test message To: '.$fromform->to_email_address.'
				    
				 </div>';
				 exit();

		}else{
			echo '<div class="alert alert-danger alert-block fade in  alert-dismissible">Please checked mail configuration : Mail are not send</div>';
			exit();
		}

	}

	$mform->display();

}else{
	redirect(new moodle_url($CFG->wwwroot.'/my'),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
}
echo $OUTPUT->footer();