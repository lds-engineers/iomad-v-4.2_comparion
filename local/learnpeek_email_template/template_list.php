<?php
require('../../config.php');
//require('gear.php');
require_login();
$PAGE->set_context(context_system::instance());
global $DB, $USER,$CFG;
require_once($CFG->dirroot.'/lib/moodlelib.php');
$delete          = optional_param('delete', 0, PARAM_INT);
$PAGE->requires->jquery();
$PAGE->set_pagelayout('base');
$PAGE->set_title(get_string('emailtemplates','local_learnpeek_email_template'));
$PAGE->set_heading(get_string('emailtemplates','local_learnpeek_email_template'));
$companyid = iomad::get_my_companyid(context_system::instance());

$companydata = $DB->get_record("company",array("id"=>$companyid));

$getcompnayadmin = $DB->get_record_sql("SELECT * FROM {company_users} WHERE companyid = ? AND userid =?",array($companyid,$USER->id));

// || $getcompnayadmin->managertype ==1
if(is_siteadmin()){
	if(isset($delete) && !empty($delete )){
		global $DB, $USER;
		$emailtemplatedata = $DB->get_records_sql("SELECT * FROM {custom_email_template} WHERE id=?",array($delete));
		if($emailtemplatedata->company_id == $companyid || is_siteadmin()){
			$DB->delete_records('custom_email_template', array('id'=>$delete));
		}else{
			redirect(new moodle_url($CFG->wwwroot.'/local/learnpeek_email_template/template_list.php'),'Permission denied', null, \core\output\notification::NOTIFY_ERROR);
		}
	}
	echo $OUTPUT->header();
?>

	<div class="bundle_table table-responsive">
	<a class="btn btn-primary mb-2" href="<?php  echo $CFG->wwwroot . '/local/learnpeek_email_template/email_template.php'; ?>" role="button"><?php echo get_string('createemailtemplate','local_learnpeek_email_template')?></a>
		<table id="usetTable" class="table" border="1">
			<thead> 
				<tr> 
					<th><?php echo get_string('slno','local_learnpeek_email_template')?></th>
					<th><?php echo get_string('companyname','local_learnpeek_email_template')?>  </th>
					<th><?php echo get_string('emailtemplate','local_learnpeek_email_template')?> </th>                
					<th><?php echo get_string('destinationemail','local_learnpeek_email_template')?> </th>                
	               <th><?php echo get_string('subject','local_learnpeek_email_template')?></th>
	               <th><?php echo get_string('message','local_learnpeek_email_template')?></th>
	               <!-- <th>Footer Logo</th> -->
	               <th><?php echo get_string('action','local_learnpeek_email_template')?></th>
				</tr>
			</thead>
			<tbody>
			 <?php
			 if(is_siteadmin()){
			 	$emailtemplates = $DB->get_records_sql("SELECT * FROM {custom_email_template} ORDER BY company_id ASC");
			 }else{
			 	$emailtemplates = $DB->get_records_sql("SELECT * FROM {custom_email_template} WHERE company_id = ?",array($companyid));

			 }

			   $i = 1;
			   $email_template = array(
				    'order-email-user' => 'Order email to User',
				    'order-email-white-label' => 'Order email to White Label Admin',
				    'course-completion' => 'Course completion to User',
				    'cumulative-order-white-label' => 'Cumulative Order Email',
				    'order-email-admin' => 'Forward Order Email to Admin',
				    'register-email-user' => 'User Registration Email', // Uday
					'user-account-creation' => 'New Account Created Notification', // Uday
					'notify-user-course-assign' => 'Course Assignment Notification', // Uday
					'user-account-creation-whitelabeladmin' => 'Created user Email to White Label Admin', // Uday
					'bulk-user-account-creation-whitelabeladmin' => 'Bulk user created Email to White Label Admin', // Uday
					'course-enrol-whitelabeladmin' => 'Course Enrol Email to White Label Admin' // Uday
				);

			   foreach($emailtemplates as $emailtemplate ){
			   		if (isset($emailtemplate->company_id)) {
				        if ($emailtemplate->company_id == 0) {
				            $cname = "Default Template";
				        } else {
				            $company = $DB->get_record("company", array("id" => $emailtemplate->company_id));
				            $cname = $company ? $company->name : "";
				        }
				    } else {
				        $cname = "";
				    }
				  
				   	$key = $emailtemplate->email_template;
				    if (isset($email_template[$key])) {
				        $templatename = $email_template[$key];
				    } else {
				         $templatename =  'Unknown template';
				    }

				  $footer_logo = $DB->get_record_sql("SELECT * FROM {files} WHERE contextid= 1 AND component='learnpeek_email_template' AND itemid=? AND filesize > 0 AND filearea = ?", array($emailtemplate->id,'footer_logo'));
				 $logourl = $CFG->wwwroot.'/blocks/custom_mycourse/file.php?id='.$footer_logo->pathnamehash;

			   	echo '
				<tr>						
				  <td>'. $i.'</td>
				  <td>'.$cname.'</td>
				  <td>'.$templatename.'</td>
				  <td style="text-align: center;">'. (!empty($emailtemplate->destination_email) ? $emailtemplate->destination_email : '-') . '</td>
				  <td>'.$emailtemplate->email_subject.'</td>
				  <td>'.$emailtemplate->email_message.'</td>
				  <!--td><img src="'.$logourl.'" style="width: 100px;height: 100px;object-fit: contain;"></td-->';
				?> 
	            <td><a href="<?php echo $CFG->wwwroot . '/local/learnpeek_email_template/email_template.php?id='.$emailtemplate->id;?>"><?php echo get_string('edit','local_learnpeek_email_template')?></a> | | <a href="javascript::void(0);" class="deleteitem" data-href="<?php echo $CFG->wwwroot . '/local/learnpeek_email_template/template_list.php?delete='.$emailtemplate->id;?>" style="color: #dc3545;"><?php echo get_string('delete','local_learnpeek_email_template')?></a>

	            </td> 
				  
				                        						
				                        						
	            </tr>
			   <?php 
			   $i++;
			   } ?>
			</tbody>
		</table>
	</div>
		<style>
		.bundle_table table {
		    width: 100%;
		}
		.table_heading {
		    color: #fff;
		    font-size: 30px;
		    text-align: center;
		    margin: 10px 0;
		    background: #3376d2;
		}
		.bundle_table{
			margin-top:50px;
		}
		.bundle_table table tr th,.bundle_table table tr td{
		    padding:6px 8px; 
		}
		.bundle_table thead {
			background: #0f47ad;
			color: #fff;
			font-size: 16px;
			text-wrap: nowrap;
		}
		.bundle_table table{
			border:1px solid #ddd;
		}
		</style> 

	<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/dt/dt-1.10.16/datatables.min.css"/>
	<script type="text/javascript" src="https://cdn.datatables.net/v/dt/dt-1.10.16/datatables.min.js"></script>
	<!-- <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script> -->
	<script>
		$(document).ready(function() {
			$('#usetTable').DataTable();
			$(".deleteitem").click(function(e){
				var link = $(this).data("href");
				if(link && confirm("are you sure?")){
					window.location.href=link;
				} else {
					e.preventDefault();
				}
			})
		} );
	</script>

<?php
}else{
	redirect(new moodle_url($CFG->wwwroot.'/my'),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
}
echo $OUTPUT->footer();
