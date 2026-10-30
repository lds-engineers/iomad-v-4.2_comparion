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
				<th>Product</th>
				<th>Seat identifier</th>
				<!-- <th>Allocated courses</th> -->
				<!-- <th>Seat expires after</th> -->
				<!-- <th>Seat is valid for (days)</th> -->
				<th>Total Seats</th>
				<th>Used Seats</th>
				<th>Action</th>
			</tr>
		</thead>
		<tbody>
			<?php
			$lexpirydate = time();
			// $purchaselicense1 = $DB->get_records_sql("SELECT cl.*, clf.user_id,clf.invoiceitem_id,clc.courseid,c.fullname, ii.invoiceableitemid FROM {companylicense_info} as clf 
			// 	INNER JOIN {companylicense} as cl ON cl.id = clf.companylicense_id 
			// 	INNER JOIN {companylicense_courses} as clc ON clc.licenseid = cl.id 
			// 	INNER JOIN {course} as c ON clc.courseid = c.id 
			// 	INNER JOIN {invoiceitem} as ii ON ii.id = clf.invoiceitem_id
			// 	WHERE clf.user_id =? AND cl.companyid=? AND cl.expirydate > $lexpirydate", array($buser_id, $companyid));

			

			// $sqlpurchaselicense = "SELECT cl.*,clf.user_id,clf.invoiceitem_id, ii.invoiceableitemid, cl.id AS licenseid, COUNT(DISTINCT clu.userid) AS usedseats
			//     FROM {companylicense_info} clf
			//     INNER JOIN {companylicense} cl ON cl.id = clf.companylicense_id
			//     INNER JOIN {invoiceitem} ii ON ii.id = clf.invoiceitem_id
			//     LEFT JOIN {companylicense_users} clu ON clu.licenseid = cl.id
			//     WHERE clf.user_id = ? AND cl.companyid = ? AND cl.expirydate > ?
			//     GROUP BY ii.invoiceableitemid, cl.id, cl.name, cl.humanallocation";

			$sqlpurchaselicense = "SELECT cl.*,clf.user_id,clf.invoiceitem_id, ii.invoiceableitemid, cl.id AS licenseid
			    FROM {companylicense_info} clf
			    INNER JOIN {companylicense} cl ON cl.id = clf.companylicense_id
			    INNER JOIN {invoiceitem} ii ON ii.id = clf.invoiceitem_id
			    WHERE clf.user_id = ? AND cl.companyid = ? AND cl.expirydate > ?
			    GROUP BY ii.invoiceableitemid, cl.id, cl.name, cl.humanallocation";

			    

			$purchaselicense = $DB->get_records_sql($sqlpurchaselicense,[$buser_id, $companyid, $lexpirydate]);
			$mergedlicenses = [];

			foreach ($purchaselicense as $license) {
			    $key = $license->invoiceableitemid;
			    if (!isset($mergedlicenses[$key])) {
			        $mergedlicenses[$key] = (object)[
			            'invoiceitem_id' => $license->invoiceitem_id,
			            'invoiceableitemid' => $license->invoiceableitemid,
			            'name'              => $license->name,
			            'humanallocation'   => (int)$license->humanallocation,
			            'used'              => (int)$license->used,
			            'allocation'        => (int)$license->allocation
			        ];
			    } else {
			        $mergedlicenses[$key]->humanallocation += (int)$license->humanallocation;
			        $mergedlicenses[$key]->used += (int)$license->used;
			        $mergedlicenses[$key]->allocation += (int)$license->allocation;
			    }
			}

			$i = 1;
			foreach ($mergedlicenses as $license) {
				$perlicense = $license->allocation / $license->humanallocation;
        		$license->used = (int) ceil($license->used / $perlicense);
				$productinfo = $DB->get_record("course_shopsettings",["id"=>$license->invoiceableitemid]);
			    echo '
			        <tr>
			            <td>' . $i . '</td>
			            <td>' . $productinfo->name . '</td>
			            <td>' . s($license->name) . '</td>
			            <td>' . $license->humanallocation . '</td>
			            <td>' . $license->used . '</td>
			            <td>
			                <a class="btn btn-primary"
			                   href="' . $CFG->wwwroot . '/blocks/learnpeek_shop/assign_trainee.php?itemid=' . $license->invoiceableitemid . '">
			                   Assign To Trainee
			                </a>
			            </td>
			        </tr>';
			    $i++;
			}

/////==============================================================================
			// $mergedlicenses = [];

			// foreach ($purchaselicense as $license) {

			//     $key = $license->invoiceableitemid;

			//     if (!isset($mergedlicenses[$key])) {
			//         // Initialize
			//         $mergedlicenses[$key] = clone $license;
			//         $mergedlicenses[$key]->humanallocation = (int)$license->humanallocation;
			//         $mergedlicenses[$key]->used = (int)$license->used;
			//     } else {
			//         // Merge
			//         $mergedlicenses[$key]->humanallocation += (int)$license->humanallocation;
			//         $mergedlicenses[$key]->used += (int)$license->used;
			//     }
			// }



			// // echo "<pre>";
			// // print_r($mergedlicenses);
			// // echo "</pre>";
			// $i = 1;
			// foreach ($mergedlicenses as $license) {
			// 	echo '
			// 		<tr>						
			// 		  <td>' . $i . '</td>
			// 		  <td>' . $license->name . '</td>
			// 		  <!--td>' . $license->fullname . '</td-->
			// 		  <!--<td>' . date("F j, Y", $license->expirydate) . '</td>-->
			// 		  <!--<td>' . $license->validlength . '</td>-->
			// 		  <td>' . $license->humanallocation . '</td>
			// 		  <td>' . $license->used . '</td>
		    //         <td style="text-wrap: nowrap;"><a class="btn btn-primary" href="' . $CFG->wwwroot . '/blocks/learnpeek_shop/assign_trainee.php?itemid=' . $license->invoiceableitemid . '">Assign To Trainee </a>
		    //         </td>             						
		    //         </tr>';
			// 	$i++;
			// };
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
<script>
	$(document).ready(function() {
		$('#usetTable').DataTable();
	});
</script>
<?php
echo $OUTPUT->footer();
