<?php
require_once('../../config.php');
global $DB, $CFG, $USER, $PAGE;
require_login();

$companyid = iomad::get_my_companyid(context_system::instance());
$companydata = $DB->get_record("company",array("id"=>$companyid));
$getcompnayadmin = $DB->get_record_sql("SELECT * FROM {company_users} WHERE companyid = ? AND userid =?",array($companyid,$USER->id));
if (!is_siteadmin() && (!$getcompnayadmin || $getcompnayadmin->managertype != 1)) {
    redirect(new moodle_url($CFG->wwwroot.'/my'),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
     die;
}

// Page stuff.
// $linktext = "Manage Event Courses";
// $context = context_system::instance();
// $PAGE->set_context($context);
// $PAGE->requires->jquery();
// $PAGE->set_pagelayout('base');
// $PAGE->set_title($linktext);
// $PAGE->navbar->add($linktext);
// $PAGE->set_heading($linktext);
$linktext = get_string('manageeventcourses', 'local_customlayout');

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->requires->jquery();
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);
$PAGE->navbar->add($linktext);
$PAGE->set_heading($linktext);
$html = '
  <div class="m-t-2 iomad_company_admin">
    <ul class="nav nav-tabs" role="tablist">
            <!--<li class="nav-item">
                <a class="nav-link active" href="#UserAdmin" data-toggle="tab" role="tab" aria-selected="true" tabindex="0"><i class="fa fa-file-text"></i> Events</a>
            </li> -->
            <!--<li class="nav-item" id="yui_3_18_1_1_1745560839813_33">
                <a class="nav-link" href="#ECommerceAdmin" data-toggle="tab" role="tab" aria-selected="false" tabindex="-1"><i class="fa fa-truck"></i>E-Commerce</a>
            </li>-->
    </ul>
    <div class="tab-content mt-3" id="iomadDashboardTabContent">
            <div class="tab-pane iomadlink_container active" id="UserAdmin" role="tabpanel">
                    <a href="'.$CFG->wwwroot.'/local/manage_course/eventlist.php">
                        <div class="iomadlink mr-2">
                            <div class="iomadicon course">
                                <div class="fa fa-topic fa-file-text"></div>
                                <div class="fa fa-action fa-plus-square"></div>
                            </div>
                            <div class="actiondescription">'. get_string('eventcourselist', 'local_customlayout') . '</div>
                        </div>
                    </a>
                    <!--a href="'.$CFG->wwwroot.'/blocks/iomad_company_admin/company_course_users_form.php">
                        <div class="iomadlink mr-2">
                            <div class="iomadicon course">
                                <div class="fa fa-topic fa-file-text"></div>
                                <div class="fa fa-action fa-user"></div>
                            </div>
                            <div class="actiondescription">User enrolments</div>
                        </div>
                    </a>-->

                    <a href="'.$CFG->wwwroot.'/blocks/learnpeek_shop/license_management.php">
                        <div class="iomadlink mr-2">
                            <div class="iomadicon course">
                                <div class="fa fa-topic fa-file-text"></div>
                                <div class="fa fa-action fa-user"></div>
                            </div>
                            <div class="actiondescription">'. get_string('manageseat', 'local_customlayout') . '</div>
                        </div>
                    </a>
                    
                <div class="clearfix"></div>
            </div>
            <div class="tab-pane iomadlink_container" id="ECommerceAdmin" role="tabpanel">
                <!--<a href="'.$CFG->wwwroot.'/blocks/iomad_commerce/courselist.php">
                    <div class="iomadlink mr-2">
                        <div class="iomadicon ecomm">
                            <div class="fa fa-topic fa-file-text"></div>
                            <div class="fa fa-action fa-money"></div>
                        </div>
                        <div class="actiondescription">Products</div>
                    </div>
                </a>-->
                <a href="'.$CFG->wwwroot.'/blocks/learnpeek_shop/orderlist.php">
                    <div class="iomadlink mr-2">
                        <div class="iomadicon ecomm">
                            <div class="fa fa-topic fa-truck"></div>
                            <div class="fa fa-action fa-eye"></div>
                        </div>
                        <div class="actiondescription">Orders</div>
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