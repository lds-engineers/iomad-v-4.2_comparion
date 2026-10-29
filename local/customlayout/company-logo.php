<?php
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/lib/enrollib.php');
require_once($CFG->dirroot . '/blocks/iomad_company_admin/lib.php');

\block_iomad_commerce\helper::require_commerce_enabled();
require_once('compnaylogoform.php');
global $DB, $USER;
require_login();
$PAGE->set_context(context_system::instance());
$context = context_system::instance();
$PAGE->set_context($context);
$companyid = iomad::get_my_companyid(context_system::instance());

$pagetitle = 'My Logo';
$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);
echo $OUTPUT->header();
$userId = $USER->id;
$context = context_user::instance($USER->id); // User context
$current_user_roles = get_user_roles($context, $USER->id);
$manag_role = array_column($current_user_roles, 'shortname');

$company_users = $DB->get_record_sql('SELECT cu.*,rs.roleid FROM {role_assignments} as rs INNER JOIN {role} as r ON r.id = rs.roleid INNER JOIN {company_users} as cu ON cu.userid = rs.userid WHERE r.shortname Like ? AND rs.userid= ?',array('companymanager',$USER->id));
if(!empty($company_users)){
?>
<!--     <div class="dash-heading"align="left">
        <h3  class="text-left">Updated Logo</h3> 
    </div> -->
    
    <?php if ($manag_role[0] == 'companymanager') { ?>
    <a href="<?php echo $CFG->wwwroot . '/user/profile.php?id='.$USER->id; ?>" class="btn btn-primary" style="margin-bottom: 10px;">Back to Profile</a>
    <?php } ?>

    <?php
    $addformaaa = new course_mappingform();
    if ($addformaaa->is_cancelled()) {
    	 redirect($CFG->wwwroot). "/my"; 
    }
    elseif($addform = $addformaaa->get_data())
    {
        // echo "<pre>";
        // print_r($addform);
        // die();
        $companylogo = $DB->get_record_sql('SELECT * FROM {company_logo} WHERE company_id = ?',array($companyid));
        if(empty($companylogo)){
            $fromform = new stdClass();
            $fromform->company_id           = $companyid;
            $fromform->user_id              = $USER->id;
            $fromform->logo_image           = $addform->logo_image;
            $fromform->logo_mobile_topbar   = $addform->logo_mobile_topbar;
            $fromform->favicon_image        = $addform->favicon_image; // Uday
            $fromform->created              = time();
            // $table = 'company_logo';
            $companylogoid = $DB->insert_record('company_logo', $fromform);
        }else{
            $companylogoid =  $companylogo->id;
            $up_companylogo = new stdClass();
            $up_companylogo->id                     = $companylogo->id;
            $up_companylogo->user_id                = $company_users->userid;
            $up_companylogo->logo_image             = $addform->logo_image;
            $up_companylogo->logo_mobile_topbar     = $addform->logo_mobile_topbar;
            $up_companylogo->favicon_image          = $addform->favicon_image; // Uday
            $up_companylogo->updated                = time();
            $updateRecords=$DB->update_record('company_logo', $up_companylogo);
        }
        file_save_draft_area_files($addform->logo_image, $systemcontext->id, 'landingpage', "logo_image", $companylogoid);
        file_save_draft_area_files($addform->logo_mobile_topbar, $systemcontext->id, 'landingpage', "logo_mobile_topbar", $companylogoid);

        // Uday
        file_save_draft_area_files($addform->favicon_image, $systemcontext->id, 'landingpage', 'favicon_image', $companylogoid);
       
        
        redirect(new moodle_url($CFG->wwwroot.'/local/customlayout/company-logo.php'),'Data Saved Successfully.', null, \core\output\notification::NOTIFY_SUCCESS);

    }
    $comlogodetails = $DB->get_record_sql('SELECT * FROM {company_logo} WHERE company_id = ?',array($companyid));
    if($comlogodetails){
        $logo_image = file_get_submitted_draft_itemid('logo_image');
        file_prepare_draft_area($logo_image, $systemcontext->id, 'landingpage', 'logo_image', $comlogodetails->id);

        $logo_mobile_topbar = file_get_submitted_draft_itemid('logo_mobile_topbar');
        file_prepare_draft_area($logo_mobile_topbar, $systemcontext->id, 'landingpage', 'logo_mobile_topbar', $comlogodetails->id);
        
        // Uday
        $favicon_image = file_get_submitted_draft_itemid('favicon_image');
        file_prepare_draft_area($favicon_image, $systemcontext->id, 'landingpage', 'favicon_image', $comlogodetails->id);

        $data = new stdClass(); 
        $data->logo_image = $logo_image;
        $data->logo_mobile_topbar = $logo_mobile_topbar;
        $data->favicon_image = $favicon_image; // Uday

    }


    $addformaaa->set_data($data);
    $addformaaa->display();
}else{ 
    redirect(new moodle_url($CFG->wwwroot.'/my'),'Not access this page.', null, \core\output\notification::NOTIFY_ERROR);
}

?>

<?php  echo $OUTPUT->footer(); ?>