<?php
require_once('../../config.php');
global $DB, $CFG, $USER, $PAGE;
require_once($CFG->dirroot . '/local/user_registration/locallib.php');
require_login();
$is_hide = check_company_access_restriction();

$linktext = get_string('manageusers', 'local_customlayout');

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->requires->jquery();
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);
$PAGE->navbar->add($linktext);
$PAGE->set_heading($linktext);
$companyid = iomad::get_my_companyid(context_system::instance());
$companydata = $DB->get_record("company",array("id"=>$companyid));
$getcompnayadmin = $DB->get_record_sql("SELECT * FROM {company_users} WHERE companyid = ? AND userid =?",array($companyid,$USER->id));
if (!is_siteadmin() && (!$getcompnayadmin || $getcompnayadmin->managertype != 1)) {
    redirect(new moodle_url($CFG->wwwroot.'/my'),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
     die;
}
$context = context_user::instance($USER->id);
$current_user_roles = get_user_roles($context, $USER->id);
$manag_role = array_column($current_user_roles, 'shortname');
$iscompletedcourse = (int)$companyid === 48 ? true : false;

$html = '
<div class="m-t-2 iomad_company_admin">
    <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" href="#UserAdmin" data-toggle="tab" role="tab" aria-selected="true" tabindex="0"><i class="fa fa-user"></i>' . get_string('users', 'local_customlayout') .'</a>
        </li>';
    if(!$is_hide){
        $html.= '<li class="nav-item">
            <a class="nav-link" href="#BulkUpload" data-toggle="tab" role="tab" aria-selected="true" tabindex="1"><i class="fa fa-users"></i>'. get_string('bulkupload', 'local_customlayout') .'</a>
        </li>';
    }
        
    $html.= '</ul>
    <div class="tab-content mt-3" id="iomadDashboardTabContent">
        <div class="tab-pane iomadlink_container active" id="UserAdmin" role="tabpanel">';

            if (!$is_hide) {
                $html .= '
                    <a href="'.$CFG->wwwroot.'/local/user_registration/create_individual.php">
                        <div class="iomadlink mr-2">
                            <div class="iomadicon user">
                                <div class="fa fa-topic fa-user"></div>
                                <div class="fa fa-action fa-plus-square"></div>
                            </div>
                            <div class="actiondescription">'.get_string('createuser', 'local_customlayout').'</div>
                        </div>
                    </a>';
            }        

        $html .= '<a href="'.$CFG->wwwroot.'/local/customlayout/studentslisting.php">
                    <div class="iomadlink mr-2">
                        <div class="iomadicon user">
                            <div class="fa fa-topic fa-user"></div>
                            <div class="fa fa-action fa-book"></div>
                        </div>
                        <div class="actiondescription">'. get_string('enrolledusers', 'local_customlayout') . '</div>
                    </div>
                </a>
                <a href="'.$CFG->wwwroot.'/local/customlayout/userslisting.php">
                    <div class="iomadlink mr-2">
                        <div class="iomadicon user">
                            <div class="fa fa-topic fa-user"></div>
                            <div class="fa fa-action fa-pen"></div>
                        </div>
                        <div class="actiondescription">'. get_string('editusers', 'local_customlayout') . '</div>
                    </div>
                </a>';
            if($iscompletedcourse){
                $html.='
                <a href="'.$CFG->wwwroot.'/local/customlayout/completedstudentslisting.php">
                    <div class="iomadlink mr-2">
                        <div class="iomadicon user">
                            <div class="fa fa-topic fa-user"></div>
                            <div class="fa fa-action fa-pen"></div>
                        </div>
                        <div class="actiondescription">Completed users</div>
                    </div>
                </a>';
            }
    $html.= '<div class="clearfix"></div>
        </div>
        <div class="tab-pane iomadlink_container" id="BulkUpload" role="tabpanel">
            <a href="'.$CFG->wwwroot.'/local/customlayout/uploaduser.php">
                <div class="iomadlink mr-2">
                    <div class="iomadicon user">
                        <div class="fa fa-topic fa-user"></div>
                        <div class="fa fa-action fa-upload"></div>
                    </div>
                    <div class="actiondescription">'. get_string('uploadusers', 'local_customlayout') . '</div>
                </div>
            </a>';
            if(is_siteadmin() || $manag_role[0] == 'companymanager') {
                $html .= '<a href="'.$CFG->wwwroot.'/local/customlayout/sample/sample.csv">
                    <div class="iomadlink mr-2">
                        <div class="iomadicon user">
                            <div class="fa fa-topic fa-user"></div>
                            <div class="fa fa-action fa-download"></div>
                        </div>
                        <div class="actiondescription">'. get_string('downloadsample', 'local_customlayout') . '</div>
                    </div>
                </a>';
            } elseif($manag_role[0] == "companydepartmentmanager") {
                $html .= '<a href="'.$CFG->wwwroot.'/local/customlayout/sample/sample2.csv">
                    <div class="iomadlink mr-2">
                        <div class="iomadicon user">
                            <div class="fa fa-topic fa-user"></div>
                            <div class="fa fa-action fa-download"></div>
                        </div>
                        <div class="actiondescription">Download sample</div>
                    </div>
                </a>';
            }
            $html .= '<div class="clearfix"></div>
        </div>
    </div>
</div>
';

echo $OUTPUT->header();
echo $html;
echo $OUTPUT->footer();
?>