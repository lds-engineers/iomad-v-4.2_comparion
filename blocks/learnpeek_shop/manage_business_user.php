<?php
require('../../config.php');
require_once(dirname(__FILE__) . '/../iomad_company_admin/lib.php');
require_once($CFG->dirroot . '/lib/enrollib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/local/user_registration/locallib.php');
//require('gear.php');
require_login();
$PAGE->set_context(context_system::instance());
global $DB, $USER, $CFG;
$companyid = iomad::get_my_companyid(context_system::instance());
$getcompnayadmin = $DB->get_record_sql("SELECT * FROM {company_users} WHERE companyid = ? AND userid =?", array($companyid, $USER->id));
if (!is_siteadmin() && (!$getcompnayadmin || $getcompnayadmin->managertype != 1 && $getcompnayadmin->managertype != 2)) {
	redirect(new moodle_url($CFG->wwwroot . '/my'), 'Access denied', null, \core\output\notification::NOTIFY_ERROR);
	die;
}

$is_hide = check_company_access_restriction();

$buserid          = optional_param('buserid', 0, PARAM_INT);
// $confirm          = optional_param('confirm', "0", PARAM_RAW);
$returnurl = new moodle_url('/blocks/course_mapping/mapped_list.php', array());

$PAGE->requires->jquery();
$PAGE->set_pagelayout('base');
$PAGE->set_title(get_string('manageusers', 'block_learnpeek_shop'));
$PAGE->set_heading(get_string('manageusers', 'block_learnpeek_shop'));

echo $OUTPUT->header();
if ($buserid) {
	$buser_id = $buserid;
	$addtrainee = "";
} else {
	$buser_id = $USER->id;
	if(!$is_hide){
		$addtrainee = '<a class="btn btn-primary mb-4" href="' . $CFG->wwwroot . '/blocks/learnpeek_shop/create_trainee.php" role="button">'
		. get_string('createnewtrainee', 'block_learnpeek_shop') .
		'</a>';
	}else{
		$addtrainee = "";
	}
}
?>
<ul class="nav nav-tabs" role="tablist">
	<li class="nav-item">
		<a class="nav-link active" href="" data-toggle="tab" role="tab" aria-selected="true" tabindex="0"><i class="fa fa-users"></i><?php echo get_string('traineelisting', 'block_learnpeek_shop'); ?></a>
	</li>
	<?php if(!$is_hide){ ?>
		<li class="nav-item">
			<a class="nav-link" href="<?php echo $CFG->wwwroot; ?>/local/customlayout/uploaduser.php"><i class="fa fa-action fa-upload"></i><?php echo get_string('bulkupload', 'block_learnpeek_shop'); ?></a>
		</li>
	<?php } ?>
	<li class="nav-item">
		<?php if ($buserid) { ?>
			<a class="nav-link" href="<?php echo $CFG->wwwroot; ?>/local/customlayout/sample/sample.csv"><i class="fa fa-action fa-download"></i> <?php echo get_string('downloadsample', 'block_learnpeek_shop'); ?></a>
		<?php } else { ?>
			<a class="nav-link" href="<?php echo $CFG->wwwroot; ?>/local/customlayout/sample/sample2.csv"><i class="fa fa-action fa-download"></i> <?php echo get_string('downloadsample', 'block_learnpeek_shop'); ?></a>
		<?php } ?>
	</li>
	<li class="nav-item">
		<a class="nav-link" href="<?php echo $CFG->wwwroot; ?>/local/customlayout/departmentassign.php"><i class="fa fa-action fa-home"></i><?php echo get_string('managedepartments', 'block_learnpeek_shop'); ?></a>
	</li>
	<li class="nav-item">
		<a class="nav-link" href="<?php echo $CFG->wwwroot; ?>/local/customlayout/assigneelisting.php"><i class="fa-solid fa-user-check"></i><?php echo get_string('enrolledusers', 'block_learnpeek_shop'); ?></a>
	</li>
</ul>
<div class="bundle_table table-responsive">
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
			$businestrainer = $DB->get_records_sql("SELECT u.*,buc.parent_id,buc.created_date,buc.company_id 
				FROM {business_user_child} as buc 
				INNER JOIN {user} as u ON buc.child_id = u.id 
				WHERE parent_id= ? AND company_id = ? AND u.suspended = 0 AND u.deleted=0", array($buser_id, $companyid));
			$i = 1;
			foreach ($businestrainer as $trainee) {
				if (!$trainee) {
					continue;
				}
				$ucontext = context_user::instance($trainee->id);
				$ucurrent_user_roles = get_user_roles($ucontext, $trainee->id);
				$ucurrent_user_roles = array_values($ucurrent_user_roles);

				$customdepartment_assign = $DB->get_record("customdepartment_assign", ['child_id' => $trainee->id]);
				$userdepartment = $DB->get_record("customdepartment", ['id' => $customdepartment_assign->department_id]);
				$userdepartmentname = $userdepartment->name;

				if ($ucurrent_user_roles) {
					$userrole1 = $ucurrent_user_roles[0]->shortname;
					if ($userrole1 == "companyreporter") {
						$userrole = "Department Manager";

						// Company reporter can have multiple departments
        				$departments = [];
						$customdepartment_assign = $DB->get_records("customdepartment_assign", ['child_id' => $trainee->id]);

						if ($customdepartment_assign) {
				            foreach ($customdepartment_assign as $assign) {
				                $dept = $DB->get_record("customdepartment", ['id' => $assign->department_id]);
				                if ($dept) {
				                    $departments[] = $dept->name;
				                }
				            }
				        }
				        $userdepartmentname = implode(' <br> ', $departments);

						// $userdepartment = $DB->get_record("customdepartment", ['id' => $customdepartment_assign->department_id]);
					}
				} else {
					$userrole = "Individual User";
				}

				$courses = [];
				$courses = enrol_get_users_courses($trainee->id);
				foreach ($courses as $cid => $course) {
					$completion = new completion_info($course);
					if ($completion->is_enabled()) {
						$iscomplete = $completion->is_course_complete($userid);
						$courses[$cid]->completionstatus = $iscomplete ? 'Completed' : 'Enrolled';
					} else {
						$courses[$cid]->completionstatus = 'Completion disabled';
					}
				}

				$trainee_id = $trainee->id;
				$scanfacedata = $DB->get_record_sql("SELECT * FROM {lcl_scan_face_recognition} WHERE userid = $trainee_id");
				$id_image_element = "";
		        if($scanfacedata){
		            $documnetfile = $DB->get_record_sql("
		                SELECT * FROM {files}
		                WHERE contextid = 1 AND component = 'userdocument' AND itemid = ? AND filesize > 0 AND filearea = ? AND filename != '.'",
		                array($scanfacedata->id, 'document'));

		            $license_url = $documnetfile ? "$CFG->wwwroot/local/customlayout/file.php?id=$documnetfile->pathnamehash" : '' ;
		            $id_image_element = $license_url ?
		            	'<img class="avatar"  fetchpriority="high" decoding="async" alt="User Avatar" src="' . $license_url . '" height="50" width="50">'
		            	: '';
		        }

				require_once($CFG->dirroot . "/course/externallib.php");
				$userpicture = new user_picture($trainee);
				$userpicture->size = 1; // Size f1.
				$userproimg = $userpicture->get_url($PAGE)->out(false);
				echo '
					<tr>
	  			  		<td>
					  		<div style="display: flex;gap: 10px; flex-direction: column;align-items: center;">
					  			<img class="avatar"  fetchpriority="high" decoding="async" alt="User Avatar" src="' . $userproimg . '" height="50" width="50">
					 			'. $id_image_element .'
					  		</div>
	                 	</td>
					  <td><a target="_blank" href="'.$CFG->wwwroot.'/user/profile.php?id='.$trainee->id.'">' . $trainee->firstname . ' ' . $trainee->lastname . '</a></td>
					  <td>' . $trainee->email . '</td>
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
												<div class="csinfo_lf"><a href="' . $CFG->wwwroot . '/course/view.php?id=' . $course->id . '">' . $course->fullname . '</a>
												</div>
											</td>
											<td>
												<p>' . $course->completionstatus . '</p>
											</td>
										</tr>';
								}

								echo '
								</tbody>
						</table>
					  </td>
					  <td>' . $userdepartmentname . '</td>
					  <td>' . $userrole . '</td>       						
		            </tr>';
				$i++;
			};
			echo '

			</tbody>
	</table></div>';
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

	.bundle_table {
		margin-top: 50px;
	}

	.bundle_table table tr th,
	.bundle_table table tr td {
		padding: 6px 8px;
	}

	.bundle_table thead {
		background: #0f47ad;
		color: #fff;
		font-size: 16px;
		text-wrap: nowrap;
	}

	.bundle_table table {
		border: 1px solid #ddd;

	}
</style>


<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/dt/dt-1.10.16/datatables.min.css" />

<script type="text/javascript" src="https://cdn.datatables.net/v/dt/dt-1.10.16/datatables.min.js"></script>
<!-- <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script> -->

<script>
	$(document).ready(function() {
		$('#usetTable').DataTable();
	});
</script>
<?php
echo $OUTPUT->footer();
