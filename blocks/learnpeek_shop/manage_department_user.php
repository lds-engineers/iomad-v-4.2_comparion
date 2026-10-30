<?php
require('../../config.php');
require_once(dirname(__FILE__) . '/../iomad_company_admin/lib.php');
require_once($CFG->dirroot . '/lib/enrollib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/local/user_registration/locallib.php');
$is_hide = check_company_access_restriction();
require_login();
$PAGE->set_context(context_system::instance());
$context_user = context_user::instance($USER->id);
$current_user_roles = get_user_roles($context_user, $USER->id);

$current_user_roles = array_values($current_user_roles);
$hascompanyreporterrole = (isset($current_user_roles[0]) && $current_user_roles[0]->shortname == 'companyreporter');

$getcompnayadmin = $DB->get_record_sql("SELECT * FROM {company_users} WHERE companyid = ? AND userid =?",array($companyid,$USER->id));

if (!is_siteadmin() && (!$getcompnayadmin || ($getcompnayadmin->managertype != 1 && $getcompnayadmin->managertype != 2)) &&!$hascompanyreporterrole) {
    redirect(new moodle_url($CFG->wwwroot.'/my'),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
     die;
}
global $DB, $USER;
$buserid          = optional_param('buserid', 0, PARAM_INT);
// $confirm          = optional_param('confirm', "0", PARAM_RAW);
$returnurl = new moodle_url('/blocks/course_mapping/mapped_list.php', array());
$companyid = iomad::get_my_companyid(context_system::instance());
$PAGE->requires->jquery();
$PAGE->set_pagelayout('base');
$title = get_string('manageusers', 'local_customlayout');
$PAGE->set_title($title);
$PAGE->set_heading($title);
echo $OUTPUT->header();
if($buserid){
	$buser_id = $buserid;
	$addtrainee = "";
}else{
	$buser_id = $USER->id;
	if(!$is_hide){
		$addtrainee = '<a class="btn btn-primary mb-4" href="'.$CFG->wwwroot .'/blocks/learnpeek_shop/create_trainee.php" role="button">' . get_string('createnewtrainee', 'block_learnpeek_shop') . '</a>';
	}else{
		$addtrainee = "";
	}
}
?>
    <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" href="" data-toggle="tab" role="tab" aria-selected="true" tabindex="0"><i class="fa fa-users"></i> <?php echo get_string('traineelisting', 'block_learnpeek_shop'); ?></a>
        </li>

		<?php if(!$is_hide){ ?>
			<li class="nav-item">
				<a class="nav-link" href="<?php echo $CFG->wwwroot; ?>/local/customlayout/deptuploaduser.php"><i class="fa fa-action fa-upload"></i> <?php echo get_string('bulkupload', 'block_learnpeek_shop'); ?></a>
			</li>
		<?php } ?>
        
	    <li class="nav-item">
			<a class="nav-link" href="<?php echo $CFG->wwwroot; ?>/local/customlayout/sample/sample2.csv"><i class="fa fa-action fa-download"></i> <?php echo get_string('downloadsample', 'block_learnpeek_shop'); ?></a>
		</li>

		
		<li class="nav-item">
			<a class="nav-link" href="<?php echo $CFG->wwwroot; ?>/local/customlayout/assigneelisting.php"><i class="fa-solid fa-user-check"></i><?php echo get_string('enrolledusers', 'block_learnpeek_shop'); ?></a>
		</li>
    </ul>
	<div class="bundle_table">
		<?php echo $addtrainee; ?>
		<table id="usetTable" class="table">
			<thead> 
				<tr> 
					<th><?php echo get_string('avatar', 'block_learnpeek_shop'); ?></th>
                    <th><?php echo get_string('traineename', 'block_learnpeek_shop'); ?></th>
                    <th><?php echo get_string('email', 'block_learnpeek_shop'); ?></th>
                    <th><?php echo get_string('traineecourses', 'block_learnpeek_shop'); ?></th>
                    <th><?php echo get_string('department', 'block_learnpeek_shop'); ?></th>
                    <th><?php echo get_string('role', 'block_learnpeek_shop'); ?></th>
				</tr>
			</thead>
			<tbody>
			 <?php
			   $sql = "SELECT ca.id, ca.parent_id, ca.department_id 
			   		FROM {customdepartment_assign} ca 
                    INNER JOIN {customdepartment} AS c ON ca.department_id = c.id 
                    WHERE ca.child_id =:child_id AND ca.is_departmentmanager = 1 ORDER BY ca.id DESC";  
               $departments = $DB->get_records_sql($sql,['child_id'=>$USER->id]);


                $department_ids = [];
				foreach ($departments as $dept) {
				    $department_ids[] = $dept->department_id;
				}
				$businestrainer = [];
				if (!empty($department_ids)) {
					$insql = implode(',', $department_ids);

				    $sql = "SELECT 
				                cda.child_id,
				                GROUP_CONCAT(c.name SEPARATOR '<br>') AS departments,
				                MAX(cda.is_departmentmanager) AS is_departmentmanager
				            FROM {customdepartment_assign} cda
				            JOIN {customdepartment} c ON c.id = cda.department_id
				            JOIN {user} u ON u.id = cda.child_id
				            WHERE cda.department_id IN ($insql)
				            AND cda.child_id != :userid
				            AND u.deleted = 0
				            AND u.suspended = 0
				            GROUP BY cda.child_id
				            ORDER BY u.firstname ASC";

				   // $sql = "SELECT cda.* 
						   // FROM {customdepartment_assign} cda 
						   // INNER JOIN {user} u  ON u.id = cda.child_id 
						   // WHERE cda.department_id IN ($insql) 
						   // AND cda.child_id != :userid 
						   // AND u.deleted = 0 
						   // AND u.suspended = 0 
						   // ORDER BY u.firstname ASC";

				   $params['userid'] = $USER->id;
	               $businestrainer = $DB->get_records_sql($sql, $params);
				}

				// echo "<pre>";
		        // print_r($businestrainer);
		        // echo "</pre>";

			   $i = 1;
			   foreach($businestrainer as $trainee){

               	// $userdepartment = $DB->get_record("customdepartment", ['id'=>$trainee->department_id]);

			   	$user = $DB->get_record("user", array("id"=>$trainee->child_id));
			   	if(!$user) {
			   		continue;
			   	}

			   	if(!$trainee) {
			   		continue;
			   	}
			   	$ucontext = context_user::instance($trainee->child_id);
				$ucurrent_user_roles = get_user_roles($ucontext, $trainee->child_id);
				$ucurrent_user_roles = array_values($ucurrent_user_roles);
				if($ucurrent_user_roles) {
			   		$userrole1 = $ucurrent_user_roles[0]->shortname;
			   		if($userrole1 == "companyreporter") {
			   			$userrole = "Department Manager";
			   		}
			   	} else {
			   		$userrole = "Individual User";
			   	}

			   	$courses = [];
			   	$courses = enrol_get_users_courses($trainee->child_id);
			   	 foreach ($courses as $cid => $course) {
			        $completion = new completion_info($course);
			        if ($completion->is_enabled()) {
			            $iscomplete = $completion->is_course_complete($userid);
			            $courses[$cid]->completionstatus = $iscomplete ? 'Completed' : 'Enrolled';
			        } else {
			            $courses[$cid]->completionstatus = 'Completion disabled';
			        }
			    }
			   	require_once($CFG->dirroot."/course/externallib.php");
			   	$userpicture = new user_picture($user);
			    $userpicture->size = 1; // Size f1.
			    $userproimg = $userpicture->get_url($PAGE)->out(false);
				 echo '
					<tr>						
					  <td><img class="avatar"  fetchpriority="high" decoding="async" alt="User Avatar"
                     src="' . $userproimg . '" height="50"
                     width="50"></td>
					  <td><a target="_blank" href="'.$CFG->wwwroot.'/user/profile.php?id='.$trainee->child_id.'">'.$user->firstname.' '.$user->lastname.'</a></td>
					  <td>'.$user->email.'</td>
					  <td>
                       <table class="ucourselist" id="bs_trainee_list">
							<tbody>
								<tr>
									<th>Course</th>
									<th>Course Status</th>
								</tr>';
								foreach ($courses as $course) {
									echo '<tr>
									<td>
										<div class="csinfo_lf"><a href="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'">'.$course->fullname.'</a>
										</div>
									</td>
									<td>
										<p>'.$course->completionstatus.'</p>
									</td>
								</tr>';
									
								}

								echo '
							</tbody>
						</table>
					  </td>
					  <td>'. $trainee->departments .'</td>
					  <td>'. $userrole .'</td>
					  
					  
		                        						
		            </tr>';
			   $i++;
			   };
			   echo '

			</tbody>
	</div>';
	?>
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
		} );
	</script>
<?php
echo $OUTPUT->footer();