<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/local/user_registration/locallib.php');
global $DB, $PAGE, $CFG, $OUTPUT, $USER;
$PAGE->requires->jquery();
$PAGE->set_url($CFG->wwwroot.'/local/user_registration/whitelabeladminlist.php');
$PAGE->set_pagelayout('standard');
$PAGE->set_title('White Label Admin List');

$companyid = iomad::get_my_companyid(context_system::instance());
$companyname = $DB->get_record("company", array("id"=>$companyid));
$departmentid = $DB->get_record('department', array('company' => $companyid));

$getcompnayadmin = $DB->get_record_sql("SELECT * FROM {company_users} WHERE companyid = ? AND userid =?",array($companyid,$USER->id));
if (!is_siteadmin() && (!$getcompnayadmin || $getcompnayadmin->managertype != 1)) {
    redirect(new moodle_url($CFG->wwwroot.'/my'),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
     die;
}

$pagetitle = get_string('managewhitelabeladmin', 'local_user_registration');
$PAGE->set_heading($pagetitle);
echo $OUTPUT->header();
$is_hide = check_company_access_restriction();
?>
	<div class="bundle_table table-responsive">
		<?php 
			if(!$is_hide){
				echo '<a class="btn btn-primary my-2" href="' . $CFG->wwwroot . '/local/user_registration/create_whitelabel.php" role="button">' 
				. get_string('createwhitelabeladmin', 'local_user_registration') . '</a>';
			}
		?>
		 <table id="usetTable" class="table" border="1">
				<thead> 
					<tr> 
						<th><?php echo get_string('fullname', 'local_user_registration'); ?></th>
                        <th><?php echo get_string('email', 'local_user_registration'); ?></th>
					</tr>
				</thead>
				<tbody>
				 <?php
			       $whitelbadmins = $DB->get_records_sql("SELECT u.* FROM {company_users} cu INNER JOIN {user} u ON u.id =cu.userid WHERE cu.companyid = ? AND cu.managertype = 1 AND u.deleted = 0 AND u.suspended = 0 ",array($companyid));
				   $i = 1;
				   foreach($whitelbadmins as $whitelbadmin ){
				 	?>
				
					<tr>						
					  <td><?php echo fullname($whitelbadmin);?></td>
					  <td><?php echo $whitelbadmin->email;?></td>  
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
	<script>
		$(document).ready(function() {
			$('#usetTable').DataTable();
		} );
	</script>
<?php
echo $OUTPUT->footer();
