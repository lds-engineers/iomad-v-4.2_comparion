<?php
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/../iomad_company_admin/lib.php');
require_once($CFG->dirroot . '/' . $CFG->admin . '/tool/courserating/lib.php');
require_once("$CFG->libdir/filelib.php");
require_once("manage_companyform.php");
require_once("locallib.php");
global $DB, $PAGE, $CFG, $OUTPUT; 
require_login();

if (is_siteadmin())
{

	$id   = optional_param('id', 0, PARAM_INT);
	$pageurl = $CFG->wwwroot."/blocks/learnpeek_shop/manage_company.php";
	$PAGE->requires->jquery();
	$PAGE->set_url($CFG->wwwroot.'/blocks/learnpeek_shop/manage_company.php');
	// $PAGE->set_pagelayout('standard');
	$PAGE->set_pagelayout('base');
	$PAGE->set_title(get_string('managecompany','block_learnpeek_shop'));
	$PAGE->set_heading(get_string('managecompany','block_learnpeek_shop'));



	// Page stuff.
	$context = context_system::instance();
	$PAGE->set_context($context);


	$companyid = iomad::get_my_companyid(context_system::instance());
	echo $OUTPUT->header();
	$mform = new manage_company_course();
	if ($mform->is_cancelled()) {
	    redirect($CFG->wwwroot.'/blocks/learnpeek_shop/mapped_category.php');
	}else if ($fromform = $mform->get_data()) {

		// 	echo "<pre>";
		// 	print_r($fromform);
		// 	print_r($_POST);
		// 	echo "</pre>";
		// die;

		if($id){	
			$category = $_POST['category'];
			if($_POST['category']){
				$category = implode(",",$_POST['category']);
			}
			$updatedata = new stdclass();
		    $updatedata->id = $id;
		    $updatedata->course_category = $category;
		    $updatedata->updated_date = time();
			$updatedatacompanymapping = $DB->update_record('company_category_mapping', $updatedata);
			if($updatedatacompanymapping){

				// ****** update records in Iomad {company_course} table ******

				$companydata = $DB->get_record('company_category_mapping', array('id'=>$id));
				$company_id = $companydata->company_id;
				$companycat = $DB->get_record_sql("SELECT cmp.*,d.id as departmentid FROM {company} as cmp INNER JOIN {department} as d ON cmp.id=d.company WHERE cmp.id = $company_id");

				// echo "<pre>";
				$company_catid = $companycat->category;
				$company_depid = $companycat->departmentid;
				// echo "\ncompany_id ". $company_id;
			    $mapped_cat = $_POST['category'];
			    require_once($CFG->dirroot . '/blocks/custom_mycourse/locallib.php');
        		$categoryids = blocks_custom_mycourse_get_all_category_ids(64);
			    $selectedcategory = array($company_catid);
			    foreach ($mapped_cat as $m_category) {
			    	// if (in_array($m_category, $categoryids)) {
                    //     continue;
                    // }
			    	if($m_category == $company_catid){continue;}
			    	$tobeaded = $DB->get_records_sql("SELECT c.* FROM {course} as c LEFT JOIN {company_course} as cc on c.id = cc.courseid and cc.companyid=:companyid WHERE c.category = :category AND c.visible = 1 AND cc.courseid IS NULL", array("category"=>$m_category, "companyid"=>$company_id));
			    	array_push($selectedcategory, $m_category);
			    	foreach ($tobeaded as $key => $course) {
			    		$newrec = new stdClass();
			    		$newrec->companyid = $company_id;
			    		$newrec->courseid = $course->id;
			    		$newrec->departmentid = $company_depid ?? 0;
			    		$insertrecord = $DB->insert_record("company_course", $newrec);
			    		if($insertrecord){
			    			 $role = $DB->get_record('role', array('shortname'=>'companyrestriction'));
			    			 $allcompanymanageruser = $DB->get_records_sql("SELECT cu.* FROM {company_users} as cu INNER JOIN {user} as u ON u.id = cu.userid WHERE cu.companyid = ? AND cu.managertype = 1 AND u.deleted = 0",array($company_id));


			    			 foreach ($allcompanymanageruser as $manageruser) {
			    			 	if (in_array($course->category, $categoryids)) {
			                        continue;
			                    }
			    			 	// enrolCourse($course->id, $manageruser->userid,$role->id,0,time());
			    			 }
			    		}
			    	}
			    }
			    if(sizeof($selectedcategory) > 0){
		    		$companycourses = $DB->get_records_sql("SELECT c.*,cc.id as companycourseid FROM {course} as c INNER JOIN {company_course} as cc on c.id = cc.courseid and cc.companyid=:companyid", array("category"=>$m_category, "companyid"=>$company_id));
		    		foreach ($companycourses as $companycourse) {
		    			if(!in_array($companycourse->category, $selectedcategory)){
			    			  $DB->delete_records("company_course",array("id"=>$companycourse->companycourseid));

		    		// 	    $cshopsett = $DB->get_record_sql("SELECT csc.*,cs.name FROM {course_shopsettings_courses} as csc INNER JOIN {course_shopsettings} as cs ON csc.itemid = cs.id WHERE cs.companyid= ? AND csc.courseid=?",array($company_id,$companycourse->id));
								// if($cshopsett){
								// 	$DB->delete_records("course_shopsettings_courses",array("id"=>$cshopsett->id));
								// }





		    			}
		    		}
			    }
			    //********* end *******

			    // updated compnay SMTP details
			    $smtpinfo = $DB->get_record("company_mail_configuration",array("company_id"=>$company_id));
			    if($smtpinfo){
					$updatedsmtp = new stdClass();
					$updatedsmtp->id = $smtpinfo->id;
					$updatedsmtp->smtphosts = $fromform->smtphosts;
					$updatedsmtp->smtpuser = $fromform->smtpuser;
					$updatedsmtp->smtppass = $fromform->smtppass;
					$updatedsmtp->smtpsecure = $fromform->smtpsecure;
					$updatedsmtp->smtpauthtype = $fromform->smtpauthtype;
					$updatedsmtp->noreplyaddress = $fromform->noreplyaddress;
					$updatedsmtp->cumulative_email = $fromform->cumulative_email;
					$updatedsmtp->updated_date = time();
					$updatedsmtpinfo = $DB->update_record('company_mail_configuration', $updatedsmtp);
			    }else{
			    	$insertsmtp = new stdClass();
					$insertsmtp->company_id = $company_id;
					$insertsmtp->smtphosts = $fromform->smtphosts;
					$insertsmtp->smtpuser = $fromform->smtpuser;
					$insertsmtp->smtppass = $fromform->smtppass;
					$insertsmtp->smtpsecure = $fromform->smtpsecure;
					$insertsmtp->smtpauthtype = $fromform->smtpauthtype;
					$insertsmtp->noreplyaddress = $fromform->noreplyaddress;
					$insertsmtp->cumulative_email = $fromform->cumulative_email;
					$insertsmtp->ceated_date = time();
					$insertsmtpinfo = $DB->insert_record('company_mail_configuration', $insertsmtp);
			    }
			    custom_set_config("smtphosts{$company_id}", $fromform->smtphosts);
				custom_set_config("smtpuser{$company_id}", $fromform->smtpuser);
				custom_set_config("smtppass{$company_id}", $fromform->smtppass);
				custom_set_config("smtpsecure{$company_id}", $fromform->smtpsecure);
				custom_set_config("smtpauthtype{$company_id}", $fromform->smtpauthtype);
				custom_set_config("noreplyaddress{$company_id}", $fromform->noreplyaddress);
				custom_set_config("main_site_url{$company_id}", $fromform->main_site_url);
				custom_set_config("connected_shop_url{$company_id}", $fromform->connected_shop_url);
				custom_set_config("privacy_policy{$company_id}", $fromform->privacy_policy);
				custom_set_config("terms_conditions{$company_id}", $fromform->terms_conditions);
				custom_set_config("googletagmanager{$company_id}", $fromform->container_id);
				custom_set_config("rm_business_registration{$company_id}", $fromform->rm_business_registration);
			    //end SMTP Code
				redirect(new moodle_url($CFG->wwwroot.'/blocks/learnpeek_shop/mapped_category.php'),'Records Updated', null, \core\output\notification::NOTIFY_SUCCESS);
			}

		}else{
		// echo "new";
			if($_POST['category']){
				// $category = implode(",",$_POST['category']);
				$category = implode(",",$_POST['category']);
			}
			$categorydata = new stdclass();
		    $categorydata->company_id = $fromform->company_id;
		    $categorydata->course_category = $category;
		    $categorydata->created_date = time();
			$companymapping = $DB->insert_record('company_category_mapping', $categorydata);

			if( $companymapping){

				// ****** update records in Iomad {company_course} table ******

				// $companydata = $DB->get_record('company_category_mapping', array('id'=>$id));
				$company_id = $fromform->company_id;
				$companycat = $DB->get_record_sql("SELECT cmp.*,d.id as departmentid FROM {company} as cmp INNER JOIN {department} as d ON cmp.id=d.company WHERE cmp.id = $company_id");

				// echo "<pre>";
				$company_catid = $companycat->category;
				$company_depid = $companycat->departmentid;
				// echo "\ncompany_id ". $company_id;
			    $mapped_cat = $_POST['category'];
			    require_once($CFG->dirroot . '/blocks/custom_mycourse/locallib.php');
        		$categoryids = blocks_custom_mycourse_get_all_category_ids(64);
			    $selectedcategory = array($company_catid);
			    foreach ($mapped_cat as $m_category) {
			    	if($m_category == $company_catid){continue;} 
			    	$tobeaded = $DB->get_records_sql("SELECT c.* FROM {course} as c LEFT JOIN {company_course} as cc on c.id = cc.courseid and cc.companyid=:companyid WHERE c.category = :category AND c.visible = 1 AND cc.courseid IS NULL", array("category"=>$m_category, "companyid"=>$company_id));
			    	array_push($selectedcategory, $m_category);
			    	foreach ($tobeaded as $key => $course) {
			    		$newrec = new stdClass();
			    		$newrec->companyid = $company_id;
			    		$newrec->courseid = $course->id;
			    		$newrec->departmentid = $company_depid ?? 0;
			    		$insertrecord = $DB->insert_record("company_course", $newrec);

			    		if($insertrecord){
			    			 $role = $DB->get_record('role', array('shortname'=>'companyrestriction'));
			    			 $allcompanymanageruser = $DB->get_records_sql("SELECT * FROM {company_users} WHERE companyid = ? AND managertype =1",array($company_id));
			    			 foreach ($allcompanymanageruser as $manageruser) {
			    			 	if (in_array($course->category, $categoryids)) {
			                        continue;
			                    }
			    			 	// enrolCourse($course->id, $manageruser->userid,$role->id,0,time());
			    			 }
			    		}
			    	}
			    }
			    //********* end *******

			    // updated compnay SMTP details
			    $insertsmtp = new stdClass();
				$insertsmtp->company_id = $company_id;
				$insertsmtp->smtphosts = $fromform->smtphosts;
				$insertsmtp->smtpuser = $fromform->smtpuser;
				$insertsmtp->smtppass = $fromform->smtppass;
				$insertsmtp->smtpsecure = $fromform->smtpsecure;
				$insertsmtp->smtpauthtype = $fromform->smtpauthtype;
				$insertsmtp->noreplyaddress = $fromform->noreplyaddress;
				$insertsmtp->cumulative_email = $fromform->cumulative_email;
				$insertsmtp->ceated_date = time();
				$insertsmtpinfo = $DB->insert_record('company_mail_configuration', $insertsmtp);
				custom_set_config("smtphosts{$company_id}", $fromform->smtphosts);
				custom_set_config("smtpuser{$company_id}", $fromform->smtpuser);
				custom_set_config("smtppass{$company_id}", $fromform->smtppass);
				custom_set_config("smtpsecure{$company_id}", $fromform->smtpsecure);
				custom_set_config("smtpauthtype{$company_id}", $fromform->smtpauthtype);
				custom_set_config("noreplyaddress{$company_id}", $fromform->noreplyaddress);
				custom_set_config("main_site_url{$company_id}", $fromform->main_site_url);
				custom_set_config("connected_shop_url{$company_id}", $fromform->connected_shop_url);
				custom_set_config("privacy_policy{$company_id}", $fromform->privacy_policy);
				custom_set_config("terms_conditions{$company_id}", $fromform->terms_conditions);
				custom_set_config("googletagmanager{$company_id}", $fromform->container_id);
				custom_set_config("rm_business_registration{$company_id}", $fromform->rm_business_registration);

			   redirect(new moodle_url($CFG->wwwroot.'/blocks/learnpeek_shop/mapped_category.php'),'Records Created', null, \core\output\notification::NOTIFY_SUCCESS);
			}

		}
	}
	$olddata = new stdClass();
	$olddata->course_category = array();
	if($id && $data = $DB->get_record('company_category_mapping', array('id'=>$id))){ 
		$olddata = $data;
		$olddata->course_category = explode(",", $data->course_category);
		$getsmtpdata = $DB->get_record("company_mail_configuration",array('company_id'=>$data->company_id));

		$olddata->smtphosts = $getsmtpdata->smtphosts;
		$olddata->smtpuser = $getsmtpdata->smtpuser;
		$olddata->smtppass = $getsmtpdata->smtppass;
		$olddata->smtpsecure = $getsmtpdata->smtpsecure;
		$olddata->smtpauthtype = $getsmtpdata->smtpauthtype;
		$olddata->noreplyaddress = $getsmtpdata->noreplyaddress;
		$olddata->cumulative_email = $getsmtpdata->cumulative_email;
		$olddata->main_site_url = get_config(null, "main_site_url{$data->company_id}");
		$olddata->connected_shop_url = get_config(null, "connected_shop_url{$data->company_id}");
		$olddata->privacy_policy = get_config(null, "privacy_policy{$data->company_id}");
		$olddata->terms_conditions = get_config(null, "terms_conditions{$data->company_id}");
		$olddata->container_id = get_config(null, "googletagmanager{$data->company_id}");
		$olddata->rm_business_registration = get_config(null, "rm_business_registration{$data->company_id}");

	}
	$mform->set_data($olddata);
	$mform->display();
	$ajaxurl= $CFG->wwwroot."/blocks/learnpeek_shop/manageajax.php";
	echo '<script>
	var olddata = '.json_encode($olddata).';
	$(document).ready(function(){
		if(olddata.company_id){
			// $("#id_company_id").attr("readonly", "readonly");
			// $("#id_company_id").attr("desabeled", "desabeled");
			$("#id_company_id").prop("readonly", true);
			companychanged(olddata.company_id);
		}
	});
	$("#id_company_id").on("change",function(){
		var company_id    =   document.getElementById("id_company_id").value; 
		companychanged(company_id);
	});
	function companychanged(company_id){
		var ajxurl    =   "'.$ajaxurl.'";

		jQuery.ajax({
	            type    :    "POST",
	            url      :  ajxurl,
	            data   :{
	                action : "manage_company",
	                company_id :  company_id            
	            },            
	                 success: function (data) {
	                 	var company = JSON.parse(data);
	                 	var allcheckboxex = ``;
	                 	if(Array.isArray(company?.allcategory)){
		                 	company.allcategory.forEach(function(currentValue, index, array) {
		                 		var label = `${currentValue.name}`;
		                 		var sel = ``;
		                 		if(olddata.company_id){
		                 			if(olddata.course_category.includes(currentValue.id)){
			                 			sel = `checked`;
		                 			}
			                 		if(currentValue.id == company.category){
			                 			label = `In-person course (${currentValue.name})`;
			                 		}
	                 			} else {
			                 		if(currentValue.id == company.category){
			                 			label = `In-person course (${currentValue.name})`;
			                 			sel = `checked`;
			                 		}
			                 	}
		                 		allcheckboxex += `<label  for="category_${currentValue.id}"><input type="checkbox" ${sel} name="category[]" value="${currentValue.id}" id="category_${currentValue.id}"/>${label}</label>`;
							});
	                 	}
	                 	console.log("company- ", company);
					   $("#allcategory").html(allcheckboxex);
					   $("#fitem_id_allcategoryele .col-form-label .form-label-addon").html(`<label  class="d-inline word-break course-categ ml-0">'. get_string('courseassignement','block_learnpeek_shop').'</label>`);
	            }
	            
	        });
	}

	</script>

	<style>
	.form-control-static div#allcategory {
	    display: flex;
	    gap: 13px;
	    flex-wrap: wrap;
	    align-items: center;
	}

	.form-control-static div#allcategory>label input[type="checkbox"] {
	    width: 20px;
	    margin: 0 6px;
	    height: 20px;
	    accent-color: cadetblue;
	}

	.form-control-static div#allcategory label {
	    padding: 9px 20px;
	    background-color: #839af0;
	    border-radius: 11px;
	    color: #fff;
	}
	#fitem_id_allcategoryele .form-label-addon{
	    margin-left: 0;
	}
	.mail_heading {
    margin-top: 50px;
    margin-bottom: 50px;
    font-size: 20px;
    font-weight: 600;
	}
	.mail_config {
	    font-size: 15px;
	    font-weight: 400;
	}
	</style>

	';
}else{
	redirect(new moodle_url($CFG->wwwroot.'/my'),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
}
echo $OUTPUT->footer();