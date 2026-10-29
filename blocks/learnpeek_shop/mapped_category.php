<?php
require('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
//require('gear.php');
require_login();
$PAGE->set_context(context_system::instance());
global $DB, $USER;
$delete          = optional_param('delete', 0, PARAM_INT);
// $confirm          = optional_param('confirm', "0", PARAM_RAW);
$suspend = optional_param('suspend', 0, PARAM_INT);
$unsuspend = optional_param('unsuspend', 0, PARAM_INT);
$showsuspended = optional_param('showsuspended', 0, PARAM_INT);
$confirm = optional_param('confirm', '', PARAM_ALPHANUM);   // Md5 confirmation hash.

$params = [
    'suspend' => $suspend ? $suspend : $unsuspend,
    'showsuspended' => $showsuspended,
    'confirm' => $confirm,
];

$returnurl = new moodle_url('/blocks/learnpeek_shop/mapped_category.php', array());
$PAGE->requires->jquery();
$PAGE->set_pagelayout('base');
$PAGE->set_title(get_string('managecompany','block_learnpeek_shop'));
$PAGE->set_heading(get_string('managecompany','block_learnpeek_shop'));

$userId = $USER->id;

if ($suspend and confirm_sesskey()) {

    // Suspend a company, after confirmation.
    $company = $DB->get_record('company', ['id' => $suspend], '*', MUST_EXIST);
    if ($confirm != md5($suspend)) {
        $fullname = $company->name;
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('suspendcompany', 'block_iomad_company_admin'). " " . $fullname);
        $optionsyes = array('suspend' => $suspend,
                            'confirm' => md5($suspend),
                            'showsuspended' => $showsuspended,
                            'sesskey' => sesskey());

        echo $OUTPUT->confirm(get_string('suspendcompanycheckfull', 'block_iomad_company_admin', "'$fullname'"),
                              new moodle_url('mapped_category.php', $optionsyes), 'mapped_category.php');
        echo $OUTPUT->footer();
        die;
    } else {

        // Suspend the company
        // Create an event for this.  This handles the actual lifting.
        $eventother = array('companyid' => $company->id);
        $event = \block_iomad_company_admin\event\company_suspended::create(array('context' => context_system::instance(),
                                                                                      'objectid' => $company->id,
                                                                                      'userid' => $USER->id,
                                                                                      'other' => $eventother));
        $event->trigger();
        $returnurl->param('suspend', 0);
        $returnurl->param('unsuspend', 0);
        $returnurl->param('showsuspended', $showsuspended);
        redirect($returnurl);
        die;
    }
} else if ($unsuspend and confirm_sesskey()) {

    // Unsuspends a selected company, after confirmation.
    $company = $DB->get_record('company', ['id' => $unsuspend], '*', MUST_EXIST);
    if (!empty($company->parentid) && $DB->get_record('company', array('id' => $company->parentid, 'suspended' => 1))) {
        throw new moodle_exception('parentcompanysuspended', 'block_iomad_company_admin');
    }

    if ($confirm != md5($unsuspend)) {
        $fullname = $company->name;
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('unsuspendcompany', 'block_iomad_company_admin'). " " . $fullname);
        $optionsno = array('unsuspend' => $unsuspend,
                            'confirm' => md5($unsuspend),
                            'showsuspended' => $showsuspended,
                            'sesskey' => sesskey());
        echo $OUTPUT->confirm(get_string('unsuspendcompanycheckfull', 'block_iomad_company_admin', "'$fullname'"),
                              new moodle_url('mapped_category.php', $optionsno), 'mapped_category.php');
        echo $OUTPUT->footer();
        die;
    } else {
        // Unsuspend the company
        // Create an event for this.  This handles the actual lifting.
        $eventother = array('companyid' => $company->id);
        $event = \block_iomad_company_admin\event\company_unsuspended::create(array('context' => context_system::instance(),
                                                                                      'objectid' => $company->id,
                                                                                      'userid' => $USER->id,
                                                                                      'other' => $eventother));
        $event->trigger();
        $returnurl->param('unsuspend', 0);
        $returnurl->param('suspend', 0);
        $returnurl->param('showsuspended', $showsuspended);
        redirect($returnurl);
        die;
    }

}

echo $OUTPUT->header();
if (is_siteadmin())
{
	?>
	<div class="bundle_table table-responsive">
	<a class="btn btn-primary my-2" href="<?php  echo $CFG->wwwroot . '/blocks/learnpeek_shop/manage_company.php'; ?>" role="button"><?php echo get_string('assigncategories','block_learnpeek_shop')?></a>
	<a class="btn btn-primary my-2" href="<?php  echo $CFG->wwwroot . '/blocks/learnpeek_shop/stop_registration.php'; ?>" role="button"><?php echo get_string('stopregistration','block_learnpeek_shop')?></a>
		<table id="usetTable" class="table" border="1">
			<thead> 
				<tr> 
					<!-- <th>S. No.</th> -->
					<th><?php echo get_string('companyname','block_learnpeek_shop') ?></th>                
					<th><?php echo get_string('assignedcategories','block_learnpeek_shop') ?></th>				
					<th><?php echo get_string('inpersoncourse','block_learnpeek_shop') ?></th>
	               <th><?php echo get_string('action','block_learnpeek_shop') ?></th>
				</tr>
			</thead>
			<tbody>
			 <?php
		       $company_mappedcat = $DB->get_records_sql("SELECT ccm.*,c.name,c.category,c.suspended FROM {company_category_mapping} as ccm inner JOIN {company} as c on ccm.company_id = c.id");		 
			   $i = 1;
			   foreach($company_mappedcat as $compnaydata ){
			   	$allcatname='';
			   	if(empty($compnaydata->course_category)){
					$compnaydata->course_category = 0;
			   	}
			   	$categoryname = $DB->get_records_sql("SELECT id,name FROM {course_categories} WHERE id in(".$compnaydata->course_category.")");
			   	$perstionstatus = "Not Allow";
			   	if($categoryname){
				 	$allcategory = array();
				   	foreach ($categoryname as $category) {
				   		// echo "<pre>";
				   		// print_r($compnaydata);
				   		// echo "</pre>";
				   		if($category->id==$compnaydata->category){
				   			$categoryname = 'In-person course ('.$category->name.')';
				   			array_push($allcategory, $categoryname);
				   			$perstionstatus = "Allow";
				   		}else{
				   			array_push($allcategory, $category->name);

				   		}
				   	}
			   		$allcatname = implode(" , ", $allcategory);
			   	}

			   	if($compnaydata->suspended == 1){
			   		$suspendstatus = "companususpend";

			   	}else{
			   		$suspendstatus = "";
			   	}
			echo '
			
				<tr class="'.$suspendstatus.'">						
				  <td>'.$compnaydata->name.'</td>
				  <td>'.$allcatname.'</td>  
	              <td>'.$perstionstatus.'</td> 
				<td>';
				if($compnaydata->suspended == 1){ ?>
					<a class="btn btn-sm btn-secondary" href="<?php echo $CFG->wwwroot . '/blocks/learnpeek_shop/mapped_category.php?suspend=' . $suspend . '&showsuspended=' . $showsuspended .'&confirm='.$confirm .'&sesskey='.$USER->sesskey . '&unsuspend=' .$compnaydata->company_id; ?>"><?php echo get_string('unsuspend','block_learnpeek_shop')?></a>

					<!-- https://lms.learnpeek.com/blocks/iomad_company_admin/editcompanies.php?delete=0&suspend=0&showsuspended=1&confirm=d645920e395fedad7bbbed0eca3fe2e0&confirmcompany=0&sort=country&dir=ASC&page=0&perpage=30&search&name&city&region&postcode&code&country&showchild=0&companyid=0&custom1&custom2&custom3&sesskey=VYB0cvaMom&unsuspend=40 -->

				<?php
				}else{ ?>
					<a class="btn btn-sm btn-primary" href="<?php echo $CFG->wwwroot . '/blocks/learnpeek_shop/manage_company.php?id=' . $compnaydata->id; ?>"><?php echo get_string('edit','block_learnpeek_shop')?></a> |
				  <a class="btn btn-sm btn-warning" href="<?php echo $CFG->wwwroot . '/blocks/learnpeek_shop/mapped_category.php?suspend=' . $compnaydata->company_id . '&showsuspended=' . $showsuspended .'&confirm='.$confirm .'&sesskey='.$USER->sesskey; ?>"><?php echo get_string('suspend','block_learnpeek_shop')?></a>

				<?php

				}


				?>
				  
				</td>
			<!-- 	https://lms.learnpeek.com/blocks/iomad_company_admin/editcompanies.php?delete=0&suspend=40&showsuspended=0&confirm&confirmcompany=0&sort=country&dir=ASC&page=0&perpage=30&search&name&city&region&postcode&code&country&showchild=0&companyid=0&custom1&custom2&custom3&sesskey=VYB0cvaMom -->

	            
				                        						
				                        						
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
		tr.companususpend.even {
		    background-color: #c5c5c54d;
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