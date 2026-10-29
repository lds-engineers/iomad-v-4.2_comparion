<?php
require_once(dirname(__FILE__) . '/../../config.php'); // Creates $PAGE.
require_once(dirname(__FILE__) . '/../iomad_company_admin/lib.php');

$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);
$departmentid = optional_param('deptid', 0, PARAM_INTEGER);
$departmentid = 0;
$roleid = optional_param('managertype', 2, PARAM_INTEGER);
$companyid = iomad::get_my_companyid(context_system::instance());
$showothermanagers = optional_param('showothermanagers', 0, PARAM_BOOL);

$getcompnayadmin = $DB->get_record_sql("SELECT * FROM {company_users} WHERE companyid = ? AND userid =?",array($companyid,$USER->id));
if (!is_siteadmin() && (!$getcompnayadmin || $getcompnayadmin->managertype != 1)) {
    redirect(new moodle_url($CFG->wwwroot.'/my'),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
     die;
}

// If we are not handling company manager role types we are not picking other company managers.
if ($roleid != 1) {
    $showothermanagers = false;
}

$context = context_system::instance();
require_login();
iomad::require_capability('block/iomad_company_admin:company_manager', $context);

// Correct the navbar.
// Set the name for the page.
$linktext = get_string('assignbusinessuserrole','block_learnpeek_shop');
// Set the url.
$linkurl = new moodle_url('/blocks/learnpeek_shop/company_managers_form.php');

$PAGE->set_context($context);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);

// Set the page heading.
$PAGE->set_heading($linktext);


// Set the companyid
if(empty($companyid)){
    $companyid = iomad::get_my_companyid(context_system::instance());

}
$company = new company($companyid);


// Set up the departments stuffs.
$parentlevel = company::get_company_parentnode($company->id);
if (iomad::has_capability('block/iomad_company_admin:edit_all_departments', context_system::instance())) {
    $userhierarchylevel = $parentlevel->id;
} else {
    $userlevel = $company->get_userlevel($USER);
    $userhierarchylevel = key($userlevel);
}
if ($departmentid == 0) {
    $departmentid = $userhierarchylevel;
}

$PAGE->set_context($context);

// get output renderer
$output = $PAGE->get_renderer('block_iomad_company_admin');

// Javascript for fancy select.
// Parameter is name of proper select form element followed by 1=submit its form
$PAGE->requires->js_call_amd('block_iomad_company_admin/department_select', 'init', array('deptid', 1, optional_param('deptid', 0, PARAM_INT)));

$urlparams = array(
                   'managertype' => $roleid,
                   'showothermanagers' => $showothermanagers);
if ($returnurl) {
    $urlparams['returnurl'] = $returnurl;
}

// Get the manager types.
// $managertypes = $company->get_managertypes();
$managertypes = array("2" => get_string('businessuser','block_learnpeek_shop'));

if (empty($departmentid)) {
    $departmentid = $parentlevel->id;
} else if ($departmentid != $parentlevel->id) {
    unset($managertypes[1]);
    if ($roleid ==1) {
        $urlparams['managertype'] = '';
        $urlparams['deptid'] = $departmentid;
        redirect(new moodle_url($linkurl, $urlparams));
    }
}
$managerselect = new single_select(
    new moodle_url($linkurl, $urlparams),
    'managertype',
    $managertypes,
    $roleid,
    array('' => 'choosedots'),
    null
);
$managerselect->set_label(get_string('managertype', 'block_iomad_company_admin'), ['style' => 'justify-content:left;width:100%;text-align: left;padding-top:5px;']);

$othersselect = new single_select(new moodle_url($linkurl, $urlparams), 'showothermanagers',
                array(get_string('no'), get_string('yes')), $showothermanagers);
$othersselect->label = get_string('showothermanagers', 'block_iomad_company_admin') .
                       $output->help_icon('showothermanagers', 'block_iomad_company_admin') . '&nbsp';

// Set up the allocation form.

$managersform = new block_iomad_company_admin\forms\company_managers_form($PAGE->url, $context, $companyid, $departmentid, $roleid, $showothermanagers);

// Change the department for the form.
$setdata = new stdClass();
$setdata->managertype = 2;
if ($departmentid != 0) {
	$setdata->deptid = $departmentid;
}
// Change the user type of the form.
if ($roleid != 0) {
	$setdata->managertype = $roleid;
}
$managersform->set_data($setdata);


if ($managersform->is_cancelled()) {
    if ($returnurl) {
        redirect($returnurl);
    } else {
        redirect(new moodle_url($CFG->wwwroot .'/blocks/iomad_company_admin/index.php'));
    }
} else {
    $managersform->process($departmentid, $roleid);
    echo $output->header();
    if (is_siteadmin())
    {
    $selectedcompanies = $DB->get_records_sql("SELECT * FROM {company} WHERE suspended !=1 ORDER BY name ASC");
    echo '
    <div class="row align-items-center p-2 w-100 my-2">
        <label for="compnay" class="mx-2">'.get_string('selectcompany','block_learnpeek_shop').'</label>
        <form id="companyChanged">
            <select name="companyid" id="company" class="p-2 rounded w-100">';
                foreach ($selectedcompanies as $companykey => $companyvalue) {
                    echo '<option value="' . $companyvalue->id . '" ' . (($companyid == $companyvalue->id) ? 'selected' : '') . '>' . $companyvalue->name . '</option>';
                };
            echo '
            </select>
        <form>
    </div>';
    }

    // Check the department is valid.
    if (!empty($departmentid) && !company::check_valid_department($companyid, $departmentid)) {
        throw new moodle_exception('invaliddepartment', 'block_iomad_company_admin');
    }

    // Display the department tree.
    echo $output->display_tree_selector($company, $parentlevel, $linkurl, $urlparams, $departmentid);

    echo html_writer::start_tag('div', array('class' => 'iomadclear'));
    echo html_writer::start_tag('div', array('class' => 'fitem'));
    echo $output->render($managerselect);
    echo html_writer::end_tag('div');
    echo html_writer::end_tag('div');

    if (iomad::has_capability('block/iomad_company_admin:company_add', context_system::instance()) &&
        $roleid == 1) {
        echo html_writer::start_tag('div', array('class' => 'iomadclear'));
        echo html_writer::start_tag('div', array('class' => 'fitem'));
        echo $output->render($othersselect);
        echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');
    }
    // echo "<pre>";
    // print_r($setdata);
    // print_r($managertypes);
    // var_dump($roleid);
    // echo "</pre>";

    echo $managersform->display();
echo '
<style>
#potentialmanagers_wrapper .custom_srch, #currentmanagers_wrapper .custom_srch{
	display: none !important;
}
#page-blocks-learnpeek_shop-company_managers_form h4 {
    display: none !important;
}
</style>
<script>
    $(document).on("change", "#company", function(){
        $("#companyChanged").submit();        
    });
</script>
';
    echo $output->footer();
}
