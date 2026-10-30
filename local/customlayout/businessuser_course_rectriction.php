<?php
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/blocks/iomad_company_admin/lib.php');
require_once($CFG->dirroot . '/' . $CFG->admin . '/tool/courserating/lib.php');
require_once("$CFG->libdir/filelib.php");
require_once("businessuser_course_rectrictionform.php");
require_once($CFG->dirroot . '/blocks/learnpeek_shop/locallib.php');
global $DB, $PAGE, $CFG, $OUTPUT; 
require_login();

$buserid   = optional_param('buserid', 0, PARAM_INT);
$companyuser = $DB->get_record('company_users', ['userid' => $buserid]);
$companyid = $companyuser->companyid;
$getcompnayadmin = $DB->get_record_sql("SELECT * FROM {company_users} WHERE companyid = ? AND userid =?",array($companyid,$USER->id));
if (!is_siteadmin() && (!$getcompnayadmin || $getcompnayadmin->managertype != 1)) {
    redirect(new moodle_url($CFG->wwwroot.'/my/gggggg'),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
     die;
}

$pageurl = $CFG->wwwroot."/local/customlayout/businessuser_course_rectriction.php";
$PAGE->requires->jquery();
$PAGE->set_url($CFG->wwwroot.'/local/customlayout/businessuser_course_rectriction.php');
// $PAGE->set_pagelayout('standard');
$PAGE->set_pagelayout('base');
$PAGE->set_title(get_string('managecategory','local_customlayout'));
$PAGE->set_heading(get_string('managecategory','local_customlayout'));

// Page stuff.
$context = context_system::instance();
$PAGE->set_context($context);
echo $OUTPUT->header();
$mform = new business_users_course_rectriction();
if ($mform->is_cancelled()) {
    redirect($CFG->wwwroot.'/blocks/learnpeek_shop/mapped_category.php');
}else if ($fromform = $mform->get_data()) {
	$allowcategs = $DB->get_record('businessuser_courserestriction', ['user_id' => $fromform->buserid]);
	if($allowcategs){	
		$category = $_POST['category'];
		if($_POST['category']){
			$category = implode(",",$_POST['category']);
		}
		$updatedata = new stdclass();
	    $updatedata->id = $allowcategs->id;
	    $updatedata->course_category = $category;
	    $updatedata->updated_date = time();
		$updatedatacompanymapping = $DB->update_record('businessuser_courserestriction', $updatedata);
		redirect(new moodle_url($CFG->wwwroot.'/local/customlayout/businessuser_course_rectriction.php?buserid='.$buserid),'Records Updated', null, \core\output\notification::NOTIFY_SUCCESS);

	}else{
		if($_POST['category']){
			$category = implode(",",$_POST['category']);
		}
		$categorydata = new stdclass();
	    $categorydata->company_id = $companyid;
	    $categorydata->course_category = $category;
	    $categorydata->user_id = $fromform->buserid;
	    $categorydata->created_date = time();
	    // echo "<pre>";
	    // print_r($categorydata);
	    // echo "</pre>";
	    // die();
		$companymapping = $DB->insert_record('businessuser_courserestriction', $categorydata);
		redirect(new moodle_url($CFG->wwwroot.'/local/customlayout/businessuser_course_rectriction.php?buserid='.$buserid),'Records Updated', null, \core\output\notification::NOTIFY_SUCCESS);
	}
}
$olddata = new stdClass();
$olddata->course_category = array();
$olddata->company_id = $companyid;
$olddata->buserid = $buserid;
if($data = $DB->get_record('businessuser_courserestriction', array('user_id'=>$buserid))){ 
	$olddata->course_category = explode(",", $data->course_category);

}
$mform->set_data($olddata);
$mform->display();
$ajaxurl= $CFG->wwwroot."/local/customlayout/courserectriction_ajax.php";
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
				   $("#fitem_id_allcategoryele .col-form-label .form-label-addon").html(`<label  class="d-inline word-break course-categ ml-0">'.get_string('courseassignement','local_customlayout').'</label>`);
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

echo $OUTPUT->footer();