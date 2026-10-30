<?php
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/../iomad_company_admin/lib.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/local/email/lib.php');

$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);
$companyid = optional_param('companyid', 0, PARAM_INTEGER);
$courseid = optional_param('courseid', 0, PARAM_INTEGER);
$departmentid = optional_param('deptid', 0, PARAM_INTEGER);
$licenseid = optional_param('licenseid', 0, PARAM_INTEGER);
$error = optional_param('error', 0, PARAM_INTEGER);
$selectedcourses = optional_param_array('courses', array(), PARAM_INT);
$chosenid = optional_param('chosenid', 0, PARAM_INT);

$context = context_system::instance();
require_login();
iomad::require_capability('block/iomad_company_admin:allocate_licenses', $context);

// Correct the navbar.
// Set the name for the page.
// $linktext = get_string('company_license_users_title', 'block_iomad_company_admin');
$linktext = "Manage Seats";
// Set the url.
$linkurl = new moodle_url('/blocks/learnpeek_shop/assign_license.php');

// Print the page header.
$PAGE->set_context($context);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);

// Set the page heading.
$PAGE->set_heading($linktext);

// get output renderer
$output = $PAGE->get_renderer('block_iomad_company_admin');

// Javascript for fancy select.
// Parameter is name of proper select form element.
$PAGE->requires->js_call_amd('block_iomad_company_admin/department_select', 'init', array('deptid', 'mform1', $departmentid));

// Set the companyid
$companyid = iomad::get_my_companyid($context);
$company = new company($companyid);

//  Check the license is valid for this company.
if (!empty($licenseid) && !company::check_valid_company_license($companyid, $licenseid)) {
    throw new moodle_exception('invalidcompanylicense', 'block_iomad_company_admin');
}

$urlparams = array('companyid' => $companyid);
if ($returnurl) {
    $urlparams['returnurl'] = $returnurl;
}
if ($courseid) {
    $urlparams['courseid'] = $courseid;
}

// Get the top level department.
$parentlevel = company::get_company_parentnode($companyid);

$availablewarning = '';
$licenselist = array();
if (iomad::has_capability('block/iomad_company_admin:edit_all_departments', context_system::instance())) {
    $userhierarchylevel = $parentlevel->id;
    // Get all the licenses.
    $licenses = $DB->get_records('companylicense', array('companyid' => $companyid), 'expirydate DESC', 'id,name,startdate,expirydate');
    foreach ($licenses as $license) {
        if ($license->expirydate < time()) {
            $licenselist[$license->id] = $license->name . " (" . get_string('licenseexpired', 'block_iomad_company_admin', date($CFG->iomad_date_format, $license->expirydate)) . ")";
        } else if ($license->startdate > time()) {
            $licenselist[$license->id] = $license->name . " (" . get_string('licensevalidfrom', 'block_iomad_company_admin', date($CFG->iomad_date_format, $license->startdate)) . ")";
            if ($licenseid == $license->id) {
                $availablewarning = get_string('licensevalidfromwarning', 'block_iomad_company_admin', date($CFG->iomad_date_format, $license->startdate));
            }
        } else {
            $licenselist[$license->id] = $license->name;
        }
    }

} else {
    $userlevel = $company->get_userlevel($USER);
    $userhierarchylevel = key($userlevel);
    if (iomad::has_capability('block/iomad_company_admin:edit_licenses', context_system::instance())) {
        $alllicenses = true;
    } else {
        $alllicenses = false;;
    }
    $licenses = $DB->get_records('companylicense', array('companyid' => $companyid), 'expirydate DESC', 'id,name,startdate,expirydate');

    if (!empty($licenses)) {
        foreach ($licenses as $license) {
            if ($alllicenses || $license->expirydate > time()) {
                if ($license->startdate > time()) {
                    $licenselist[$license->id] = $license->name . " (" . get_string('licensevalidfrom', 'block_iomad_company_admin', date($CFG->iomad_date_format, $license->startdate)) . ")";
                    if ($licenseid == $license->id) {
                        $availablewarning = get_string('licensevalidfromwarning', 'block_iomad_company_admin', date($CFG->iomad_date_format, $license->startdate));
                    }
                } else {
                    $licenselist[$license->id] = $license->name;
                }
            }
        }
    }
}

// If we haven't been passed a department level choose the users.
if (empty($departmentid)) {
    $departmentid = $userhierarchylevel;
}

$usersform = new block_iomad_company_admin\forms\company_license_users_form($PAGE->url, $context, $companyid, $licenseid, $departmentid, $selectedcourses, $error, $output);

echo $output->header();

// Check the department is valid.
if (!empty($departmentid) && !company::check_valid_department($companyid, $departmentid)) {
    throw new moodle_exception('invaliddepartment', 'block_iomad_company_admin');
}

//  Check the license is valid for this company.
if (!empty($licenseid) && !company::check_valid_company_license($companyid, $licenseid)) {
    throw new moodle_exception('invalidcompanylicense', 'block_iomad_company_admin');
}

// Display the license selector.
$select = new single_select($linkurl, 'licenseid', $licenselist, $licenseid);
$select->label = get_string('licenseselect', 'block_iomad_company_admin');
$select->formid = 'chooselicense';
echo html_writer::tag('div', $output->render($select), array('id' => 'iomad_license_selector'));
$fwselectoutput = html_writer::tag('div', $output->render($select), array('id' => 'iomad_license_selector'));

// Do we have any licenses?
if (empty($licenselist)) {
    echo get_string('nolicenses', 'block_iomad_company_admin');
    echo $output->footer();
    die;
}

if ($usersform->is_cancelled() || optional_param('cancel', false, PARAM_BOOL)) {
    if ($returnurl) {
        redirect($returnurl);
    } else {
        redirect(new moodle_url($CFG->wwwroot .'/blocks/iomad_company_admin/index.php'));
    }
} else {
    if ($licenseid > 0) {
        $usersform->process();

        if (!empty($availablewarning)) {
            echo html_writer::start_tag('div', array('class' => "alert alert-success"));
            echo $availablewarning;
            echo "</div>";
        }

        // Reload the form.
        $usersform = new block_iomad_company_admin\forms\company_license_users_form($PAGE->url, $context, $companyid, $licenseid, $departmentid, $selectedcourses, $error, $output);
        $usersform->get_data();
        echo $usersform->display();
    }
}
echo "<style>
form#chooselicense {
  display: none;
}
#fitem_courseselector {
  display: none;
}
#potentialcourseusers_wrapper #potentialcourseusers\[\]_custom_srch option{
  display: none !important;
}

#currentlyenrolledusers_wrapper #currentlyenrolledusers\[\]_custom_srch option{
  display: none !important;
}
style>";
echo $output->footer();