<?php
require('../../config.php');
//require('gear.php');
require_login();
$PAGE->set_context(context_system::instance());
global $DB, $USER,$CFG;
require_once($CFG->dirroot.'/lib/moodlelib.php');
$delete          = optional_param('delete', 0, PARAM_INT);
$returnurl = new moodle_url('/blocks/course_mapping/mapped_list.php', array());
$PAGE->requires->jquery();
$PAGE->set_pagelayout('base');
$PAGE->set_title(get_string('eventcourselist','local_manage_course'));
$PAGE->set_heading(get_string('eventcourselist','local_manage_course'));
$companyid = iomad::get_my_companyid(context_system::instance());

$companydata = $DB->get_record("company",array("id"=>$companyid));

$getcompnayadmin = $DB->get_record_sql("SELECT * FROM {company_users} WHERE companyid = ? AND userid =?",array($companyid,$USER->id));


if(is_siteadmin() || $getcompnayadmin->managertype ==1){
	if(isset($delete) && !empty($delete )){
		global $DB, $USER;
		$courseid = $delete;
		$coursecate = $DB->get_record("course",array("id"=>$courseid));
		$courscat_id = $coursecate->category;

		$companycat = $DB->get_record("company",array("id"=>$companyid));
		$companycat_id = $companycat->category;
		if($companycat_id == $courscat_id || is_siteadmin()){
			
	 		$eventinfo = $DB->get_record("custom_eventinfo", array("course_id"=>$courseid));
	 		//detele product infor 
	 		$DB->delete_records('course_shopsettings', array('id'=>$eventinfo->product_id));

	 		$DB->delete_records('course_shopblockprice', ['itemid' => $eventinfo->product_id]);

	 		// $DB->delete_records('course_shopsettings_courses', ['itemid' => $eventinfo->product_id]);

	 		$DB->delete_records('custom_eventinfo', array('id'=>$eventinfo->id));

			$coursedeleted = delete_course($courseid, $showfeedback = false);
			if($coursedeleted){
	 			redirect(new moodle_url($CFG->wwwroot.'/local/manage_course/eventlist.php'),'Event Deleted', null, \core\output\notification::NOTIFY_SUCCESS);
		 	}
		}else{
			redirect(new moodle_url($CFG->wwwroot.'/local/manage_course/eventlist.php'),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
		} 
	}

	echo $OUTPUT->header();
?>

	<div class="bundle_table table-responsive">
	<a class="btn btn-primary my-2" href="<?php  echo $CFG->wwwroot . '/local/manage_course/create-event.php'; ?>" role="button"><?php echo get_string('createevent', 'local_manage_course'); ?></a>
		
	 <table id="usetTable" class="table" border="1">
			<thead> 
				<tr> 
					<th><?php echo get_string('slno', 'local_manage_course'); ?></th>
                    <th><?php echo get_string('eventname', 'local_manage_course'); ?></th>
                    <th><?php echo get_string('companyname', 'local_manage_course'); ?></th>
                    <th><?php echo get_string('action', 'local_manage_course'); ?></th>
				</tr>
			</thead>
			<tbody>
			 <?php
		       $companyevents = $DB->get_records_sql("SELECT * FROM {custom_eventinfo} WHERE company_id = ? AND status = ?",array($companyid,1));

			   $i = 1;
			   foreach($companyevents as $cevent ){
			   	
			   		$coursestatus = $DB->get_record("course",array("id"=>$cevent->course_id,"visible"=>1));
			   		if(empty($coursestatus)){
			   			continue;
			   		}
		       		$companyinfo = $DB->get_record("company",array("id"=>$cevent->company_id));
			 	?>
			
				<tr>						
				  <td><?php echo $i . '.'; ?></td>
				  <td><?php echo $cevent->fullname;?></td>
				  <td><?php echo $companyinfo->name;?></td>  
	            <td><a href="<?php echo $CFG->wwwroot . '/local/manage_course/create-event.php?id='.$cevent->course_id;?>"><?php echo get_string('edit', 'local_manage_course'); ?></a> | <a href="javascript::void(0);" class="deleteitem" data-href="<?php echo $CFG->wwwroot . '/local/manage_course/eventlist.php?delete='.$cevent->course_id;?>" style="color: #dc3545;"><?php echo get_string('delete', 'local_manage_course'); ?></a>



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
