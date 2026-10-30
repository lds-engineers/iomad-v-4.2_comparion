<?php
require_once(dirname(__FILE__) . '/../../config.php');
require_once("createdepartementform.php");
require_once("locallib.php");
global $DB, $PAGE, $CFG, $USER, $OUTPUT; 
require_login();
$PAGE->requires->jquery();
$PAGE->set_url($CFG->wwwroot.'/blocks/learnpeek_shop/createdepartement.php');
$PAGE->set_pagelayout('standard');
// $companyid = iomad::get_my_companyid(context_system::instance());
// $PAGE->set_title('Registration Form - Individual');
$companydata = $DB->get_record('company_users', array('userid' => $userid));
$companyid = $companydata->companyid;
$getcompnayadmin = $DB->get_record_sql("SELECT * FROM {company_users} WHERE companyid = ? AND userid =?",array($companyid,$USER->id));
// echo "<pre>";
// print_r($getcompnayadmin);
// echo "</pre>";
if(is_siteadmin() || $getcompnayadmin->managertype ==1){
  $PAGE->set_heading('Create Departement');
  echo $OUTPUT->header();
  $mform = new create_departement();

  if ($mform->is_cancelled()) {
      redirect($CFG->wwwroot.'/blocks/learnpeek_shop/createdepartement.php');
  }else if ($fromform = $mform->get_data()) {
      

  } 

  
  // $mform->set_data($data);
  $mform->display();

}else{
  redirect(new moodle_url($CFG->wwwroot.'/my'),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
}

$ajaxurl= $CFG->wwwroot."/blocks/learnpeek_shop/manageajax.php";

echo '
<script>
  $("#id_company_id").on("change", function() {
    var company_id = document.getElementById("id_company_id").value; 
    alert(company_id);
    var ajxurl = "'.$ajaxurl.'";

    jQuery.ajax({
      type: "POST",
      url: ajxurl,
      data: {
        action: "create_department",
        company_id: company_id            
      },
      success: function(data) {
        console.log(data);

        // Clear existing options
        $("#id_buser_id").empty();

        // Optional: Add default placeholder
        $("#id_buser_id").append("<option value=\"\">Select User</option>");

        // Loop through response and append options
        $.each(data, function(key, user) {
          $("#id_buser_id").append(
            $("<option>", {
              value: user.id,
              text: user.firstname + " " + user.lastname
            })
          );
        });
      }      
    });
  });
</script>';

echo $OUTPUT->footer(); 
?>
