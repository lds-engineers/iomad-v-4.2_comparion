<?php
require('../../config.php');
//require('gear.php');
require_login();
$PAGE->set_context(context_system::instance());
global $DB, $USER;
$delete          = optional_param('delete', 0, PARAM_INT);
$confirm          = optional_param('confirm', "0", PARAM_RAW);
$returnurl = new moodle_url('/blocks/course_mapping/mapped_list.php', array());
$PAGE->requires->jquery();
$PAGE->set_pagelayout('base');
$PAGE->set_title(get_string('productcategory','block_learnpeek_shop'));
$PAGE->set_heading(get_string('productcategory','block_learnpeek_shop'));
echo $OUTPUT->header();
$userId = $USER->id;
redirect(new moodle_url($CFG->wwwroot . '/my'), 'This functinality is currently desabled', null, \core\output\notification::NOTIFY_ERROR);
die();
if (is_siteadmin())
{
	?>
	<div class="bundle_table table-responsive">
	<a class="btn btn-primary mb-2" href="<?php  echo $CFG->wwwroot . '/blocks/learnpeek_shop/create_product_category.php'; ?>" role="button"><?php echo get_string('createcategory','block_learnpeek_shop')?></a>
		<table id="usetTable" class="table" border="1">
			<thead> 
				<tr> 
					<th><?php echo get_string('slno','block_learnpeek_shop')?></th>
					<th><?php echo get_string('categoryname','block_learnpeek_shop')?></th>                
					<th><?php echo get_string('assigncompany','block_learnpeek_shop')?></th>						
					<th><?php echo get_string('action','block_learnpeek_shop')?></th>						
				</tr>
			</thead>
			<tbody>
			 <?php
		       $categorydata = $DB->get_records_sql("SELECT * FROM {product_category}  ORDER BY category_name ASC");		 
			   $i = 1;
			   foreach($categorydata as $categorydata){
			   	$product_category_mapping = $DB->get_record('product_category_mapping',array('product_category_id'=>$categorydata->id));
			   	$btn = '<buttun onclick="alert(\'Category is already mapped\')" class="btn btn-secondary btn-sm" disabled>Delete</buttun>';
			   	if(!$product_category_mapping) {
			   	$btn = '<a onclick="return confirm(\'Are you sure, want to delete?\')" href="'.$CFG->wwwroot.'/blocks/learnpeek_shop/removecategory.php?id='.$categorydata->id.'" class="btn btn-danger btn-sm">'. get_string('delete','block_learnpeek_shop').'</a>';
			   	}
			   	$getcomay = $DB->get_record('company',array('id'=>$categorydata->company_id));
			   	if($getcomay){
			   		$companyname = $getcomay->name;
			   	}else{
			   		$companyname ="";
			   	}
			   	echo '
				<tr>						
				  <td>'.$i.'</td>
				  <td>'.$categorydata->category_name.'</td>                   						
				  <td>'.$companyname.'</td>                 						
				  <td>'.$btn.'</td>                 						
	            </tr>';
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
		} );
	</script>
<?php
echo $OUTPUT->footer();
}else{
	redirect(new moodle_url($CFG->wwwroot.'/my'),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
}