<?php
require('../../config.php');
require_once(dirname(__FILE__) . '/../iomad_company_admin/lib.php');
\block_iomad_commerce\helper::require_commerce_enabled();
require_login();
$PAGE->set_context(context_system::instance());
global $DB, $USER;
$companyid = iomad::get_my_companyid(context_system::instance());


$ucontext = context_user::instance($USER->id);
$ucurrent_user_roles = get_user_roles($ucontext, $USER->id);
$ucurrent_user_roles = array_values($ucurrent_user_roles);
$hascompanyreporterrole = (isset($ucurrent_user_roles[0]) && $ucurrent_user_roles[0]->shortname == 'companyreporter');


$getcompnayadmin = $DB->get_record_sql("SELECT * FROM {company_users} WHERE companyid = ? AND userid =?", array($companyid, $USER->id));

if (!is_siteadmin() && (!$getcompnayadmin || ($getcompnayadmin->managertype != 1 && $getcompnayadmin->managertype != 2)) && !$hascompanyreporterrole) {
	redirect(new moodle_url($CFG->wwwroot . '/my'), 'Access denied', null, \core\output\notification::NOTIFY_ERROR);
	die;
}
$buserid  = optional_param('buserid', 0, PARAM_INT);
$returnurl = new moodle_url('/blocks/course_mapping/mapped_list.php', array());
$PAGE->requires->jquery();
$PAGE->set_pagelayout('base');
$PAGE->set_title("Manage Seats");
$PAGE->set_heading('Manage Seats');
echo $OUTPUT->header();
if ($buserid) {
	$buser_id = $buserid;
} else {
	$buser_id = $USER->id;
}

$companyid = iomad::get_my_companyid(context_system::instance());

?>
<div class="bundle_table table-responsive">
	<table id="usetTable" class="table" border="1">
		<thead>
			<tr>
				<th>Sl. No.</th>
				<th>Seat identifier</th>
				<!-- <th>Allocated courses</th> -->
				<th>Seat expires after</th>
				<th>Seat is valid for (days)</th>
				<th>Max</th>
				<th>Used</th>
				<th>Action</th>
			</tr>
		</thead>
		<tbody>
			<?php
			$lexpirydate = time();
			$purchaselicense = $DB->get_records_sql("SELECT cl.*, clf.user_id,clf.invoiceitem_id,clc.courseid,c.fullname FROM {companylicense_info} as clf INNER JOIN {companylicense} as cl ON cl.id = clf.companylicense_id INNER JOIN {companylicense_courses} as clc ON clc.licenseid = cl.id INNER JOIN {course} as c ON clc.courseid = c.id WHERE clf.user_id =? AND cl.companyid=? AND cl.expirydate > $lexpirydate", array($buser_id, $companyid));
			$i = 1;
			// echo "<pre>";
			// print_r($purchaselicense);
			// echo "</pre>";
			foreach ($purchaselicense as $license) {
				echo '
					<tr>						
					  <td>' . $i . '</td>
					  <td>' . $license->name . '</td>
					  <!--td>' . $license->fullname . '</td-->
					  <td>' . date("F j, Y", $license->expirydate) . '</td>
					  <td>' . $license->validlength . '</td>
					  <td>' . $license->humanallocation . '</td>
					  <td>' . $license->used . '</td>
		            <td style="text-wrap: nowrap;"><a class="btn btn-primary" href="' . $CFG->wwwroot . '/blocks/learnpeek_shop/assign_license.php?licenseid=' . $license->id . '">Allocate Seats</a>
		            </td>             						
		            </tr>';
				$i++;
			};
			echo '

			</tbody>
		</table>
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
