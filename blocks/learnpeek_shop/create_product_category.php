<?php
require_once(dirname(__FILE__) . '/../../config.php');
require_once("create_product_categoryform.php");
require_once("locallib.php");
global $DB, $PAGE, $CFG, $USER, $OUTPUT; 
// $userid = required_param('id',PARAM_INT); 
require_login();
$PAGE->set_url($CFG->wwwroot.'/blocks/learnpeek_shop/create_product_category.php');
$PAGE->set_pagelayout('standard');
$companydata = $DB->get_record('company_users', array('userid' => $userid));
$companyid = $companydata->companyid;
$getcompnayadmin = $DB->get_record_sql("SELECT * FROM {company_users} WHERE companyid = ? AND userid =?",array($companyid,$USER->id));
// echo "<pre>";
// print_r($getcompnayadmin);
// echo "</pre>";
if(is_siteadmin() || $getcompnayadmin->managertype ==1){
  $PAGE->set_heading(get_string('createproductcategory','block_learnpeek_shop'));
  // $PAGE->requires->css(new moodle_url($CFG->wwwroot.'/local/user_registration/style.css'));
  echo $OUTPUT->header();
  $mform = new create_product_category();

  if ($mform->is_cancelled()) {
      redirect($CFG->wwwroot.'/blocks/learnpeek_shop/productcategory.php');
  }else if ($fromform = $mform->get_data()) {
      $categorydata = new stdclass();
      $categorydata->category_name    = $fromform->category_name;
      $categorydata->company_id      = $fromform->company_id;
      $categorydata->created_date  = time();
      $insertcategory = $DB->insert_record('product_category', $categorydata);
      if($insertcategory){
        redirect(new moodle_url($CFG->wwwroot.'/blocks/learnpeek_shop/productcategory.php'),'Records Store', null, \core\output\notification::NOTIFY_SUCCESS);
      }
  }
  $mform->display();

}else{
  redirect(new moodle_url($CFG->wwwroot.'/my'),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
}



echo "<style>

</style>";
echo $OUTPUT->footer(); 
?>
