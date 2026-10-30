<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/local/user_registration/locallib.php');
global $DB, $CFG, $USER, $PAGE;
require_login();
// Page stuff.

$linktext = get_string('managebusinessusers', 'local_customlayout');
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->requires->jquery();
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);
$PAGE->navbar->add($linktext);
$companyid = iomad::get_my_companyid(context_system::instance());
$companydata = $DB->get_record("company",array("id"=>$companyid));
$getcompnayadmin = $DB->get_record_sql("SELECT * FROM {company_users} WHERE companyid = ? AND userid =?",array($companyid,$USER->id));
if (!is_siteadmin() && (!$getcompnayadmin || $getcompnayadmin->managertype != 1)) {
    redirect(new moodle_url($CFG->wwwroot.'/my'),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
    die;
}
$is_hide = check_company_access_restriction();
$PAGE->set_heading($linktext);
$html = '
  <div class="m-t-2 iomad_company_admin">
    <ul class="nav nav-tabs" role="tablist">
           <!-- <li class="nav-item">
                <a class="nav-link active" href="#BusinessUser" data-toggle="tab" role="tab" aria-selected="true" tabindex="0"><i class="fa fa-user"></i> Business Users </a>
            </li>-->
    </ul>
    <div class="tab-content mt-3" id="iomadDashboardTabContent">
            <div class="tab-pane iomadlink_container active" id="BusinessUser" role="tabpanel">';

    if(!$is_hide){
        $html .='<a href="' . $CFG->wwwroot . '/local/user_registration/create_business.php">
                    <div class="iomadlink mr-2">
                        <div class="iomadicon user">
                            <div class="fa fa-topic fa-user"></div>
                            <div class="fa fa-action fa-plus-square"></div>
                        </div>
                        <div class="actiondescription">' . get_string('createuser', 'local_customlayout') . '</div>
                    </div>
                </a>';
    }       
        $html .='   <a href="' . $CFG->wwwroot . '/local/customlayout/businessusers.php">
                        <div class="iomadlink mr-2">
                            <div class="iomadicon user">
                                <div class="fa fa-topic fa-user"></div>
                                <div class="fa fa-action fa-gear"></div>
                            </div>
                            <div class="actiondescription">' . get_string('businessusers', 'local_customlayout') . '</div>
                        </div>
                    </a>
                    <a href="' . $CFG->wwwroot . '/blocks/learnpeek_shop/company_managers_form.php">
                        <div class="iomadlink mr-2">
                            <div class="iomadicon department">
                                <div class="fa fa-topic fa-group"></div>
                                <div class="fa fa-action fa-chevron-circle-right"></div>
                            </div>
                            <div class="actiondescription">' . get_string('assignbusinessrole', 'local_customlayout') . '</div>
                        </div>
                    </a>
                    <a href="' . $CFG->wwwroot . '/local/customlayout/departmentassign.php">
                        <div class="iomadlink mr-2">
                            <div class="iomadicon department">
                                <div class="fa fa-topic fa-home"></div>
                                <div class="fa fa-action fa-chevron-circle-right"></div>
                            </div>
                            <div class="actiondescription">' . get_string('managedepartments', 'local_customlayout') . '</div>
                        </div>
                    </a>
                <div class="clearfix"></div>
            </div>
    </div>
</div>
';

echo $OUTPUT->header();
echo $html;
echo $OUTPUT->footer();
?>