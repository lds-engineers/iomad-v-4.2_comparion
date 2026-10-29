<?php
require_once('../../config.php');
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/moodlelib.php');
require_once($CFG->dirroot.'/local/customlayout/locallib.php');
require_once($CFG->dirroot.'/blocks/learnpeek_shop/locallib.php');
require_once($CFG->dirroot . '/local/learnpeek_email_template/locallib.php');

require_login();

$companyid = iomad::get_my_companyid(context_system::instance());

$context_user = context_user::instance($USER->id);
$current_user_roles = get_user_roles($context_user, $USER->id);
$current_user_roles = array_values($current_user_roles);
$manag_role = array_column($current_user_roles, 'shortname');

$PAGE->set_url(new moodle_url('/local/customlayout/uploaduser.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('bulkuserupload', 'local_customlayout'));
$PAGE->set_heading(get_string('bulkuserupload', 'local_customlayout'));

// uday
// Default: show button.
$is_hide = false;

// Site admins can always see the button.
if (!is_siteadmin()) {

    // Get user's company.
    $usercompany = $DB->get_record(
        'company_users',
        ['userid' => $USER->id],
        'companyid'
    );

    if ($usercompany) {

        // Get company shortname.
        $company = $DB->get_record(
            'company',
            ['id' => $usercompany->companyid],
            'shortname'
        );

        if ($company) {

            // Get saved registration settings.
            $settings = $DB->get_record_sql("
                SELECT companies
                  FROM {registration_settings}
              ORDER BY id ASC
                 LIMIT 1
            ");

            if ($settings && !empty($settings->companies)) {

                $blockedcompanies = json_decode($settings->companies, true);

                if (
                    is_array($blockedcompanies) &&
                    in_array($company->shortname, $blockedcompanies, true)
                ) {
                    // Hide button for non-site-admin users whose company is blocked.
                    $is_hide = true;
                }
            }
        }
    }
}

if($is_hide){
    redirect(new moodle_url($CFG->wwwroot.'/my'),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
    die;
}
$getcompnayadmin = $DB->get_record_sql("SELECT * FROM {company_users} WHERE companyid = ? AND userid =?",array($companyid,$USER->id));

if ($manag_role[0] != 'companyreporter') {
     redirect(new moodle_url($CFG->wwwroot.'/my'),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
     die;
}
// if (!is_siteadmin() && (!$getcompnayadmin ||  ($getcompnayadmin->managertype != 1 && $getcompnayadmin->managertype != 2))) {
//     redirect(new moodle_url($CFG->wwwroot.'/my'),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
//      die;
// }

echo $OUTPUT->header();

class bulk_user_upload_form extends moodleform {
    public function definition() {
        global $CFG, $DB, $USER;
        $mform = $this->_form;

        $departmentoptions = [];

        $context = context_user::instance($USER->id);
        $current_user_roles = get_user_roles($context, $USER->id);

        if ($current_user_roles) {
            $managerrole = array_column($current_user_roles, 'shortname');

            if ($managerrole[0] == "companyreporter") {
                $department_ids = [];
                $userdepartments = $DB->get_records_sql("SELECT * FROM {customdepartment_assign} WHERE child_id = ? AND is_departmentmanager = 1",[$USER->id]);

                foreach ($userdepartments as $department) {
                    $department_ids[] = $department->department_id;
                }

                if (!empty($department_ids)) {
                    $insql = implode(',', $department_ids);
                    $departments = $DB->get_records_sql("SELECT * FROM {customdepartment} WHERE id IN ($insql)");

                    $departmentoptions[''] = get_string('selectdepartment','local_customlayout');
                    foreach ($departments as $dept) {
                        $departmentoptions[$dept->id] = $dept->name;
                    }
                    // echo "<pre>";
                    // print_r($departmentoptions);
                    // echo "</pre>";
                    // echo count($departmentoptions);
                    if (count($departmentoptions) > 2) {
                        $mform->addElement('select', 'departmentid', get_string('selectdepartment','local_customlayout'), $departmentoptions);
                        $mform->addRule('departmentid', get_string('pleaseselectdepartment', 'local_customlayout'), 'required', null, 'client');
                    }
                }
            }
        }


        $mform->addElement('filepicker', 'csvfile', get_string('uploadcsvfile', 'local_customlayout'), null, [
            'accepted_types' => ['.csv'],
            'maxbytes' => 5 * 1024 * 1024
        ]);
        $mform->addRule('csvfile', null, 'required', null, 'client');
        $mform->addElement('submit', 'submitbutton', get_string('upload', 'local_customlayout'));
    }
}

$form = new bulk_user_upload_form();
if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/customlayout/uploaduser.php'));
} else if ($data = $form->get_data()) {

    $departmentid = $data->departmentid;
    $fs = get_file_storage();
    $draftitemid = file_get_submitted_draft_itemid('csvfile');
    $usercontext = context_user::instance($USER->id);
    $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftitemid, 'id DESC', false);
    $file = reset($files);
    if ($file) {
        $content = $file->get_content();
        $lines = explode("\n", trim($content));
        $columns = array_filter(str_getcsv(array_shift($lines)), function($col) {
            return trim($col) !== '';
        });
        $previewdata = [];
        foreach ($lines as $i => $line) {
            if (trim($line) === '') continue;
            $data = str_getcsv($line);
            $data = array_slice($data, 0, count($columns));
            $record = array_combine($columns, $data);
            $previewdata[] = $record;
        }

        $_SESSION['custom_upload_csv'] = $content;

        echo "<style>
        .table-wrapper {
            max-height: 400px;
            overflow-y: auto;
        }
        .table-preview {
            width: 100%;
            table-layout: fixed;
        }
        .table-preview th, .table-preview td {
            white-space: nowrap;
            overflow: hidden;
            text-align: justify;
        }
        </style>";

        echo "<h3>CSV Preview</h3>";
        echo "<div class='row m-2'>";

        echo "<table class='table table-preview mb-0'>";
        echo "<thead><tr><th>" . implode("</th><th>", array_map('htmlspecialchars', $columns)) . "</th></tr></thead>";
        echo "</table>";

        echo "<div class='table-wrapper'>";
        echo "<table class='table table-preview'>";
        echo "<tbody>";
        foreach ($previewdata as $row) {
            echo "<tr><td>" . implode("</td><td>", array_map('htmlspecialchars', $row)) . "</td></tr>";
        }
        echo "</tbody>";
        echo "</table>";
        echo "</div>";
        echo "</div>";

        echo "<form method='post'>";
        echo "<input type='hidden' name='confirmupload' value='1' />";
        echo "<input type='hidden' name='departmentid' value='$departmentid' />";
        echo "<input type='submit' value='Confirm Upload' class='btn btn-primary' />";
        echo "<input type='submit' name='cancelupload' value='Cancel Upload' class='btn btn-secondary mx-2' />";
        echo "</form>";
        echo "<hr>";

    } else {
        echo $OUTPUT->notification("No file uploaded or unreadable.", 'notifyproblem');
    }
}

if (optional_param('confirmupload', 0, PARAM_BOOL)) {
    if (!isset($_SESSION['custom_upload_csv'])) {
        echo $OUTPUT->notification("No CSV data found.", 'notifyproblem');
    } else {
        $departmentid = $_POST['departmentid'];
        $content = $_SESSION['custom_upload_csv'];
        unset($_SESSION['custom_upload_csv']);
        $lines = explode("\n", trim($content));

        $headerline = array_shift($lines);
        $headerline = preg_replace('/^\xEF\xBB\xBF/', '', $headerline);
        $columns = str_getcsv($headerline);

        $created = 0;
        $skipped = 0;
        $created_users = [];
        $skipped_users = [];
        foreach ($lines as $i => $line) {
            if (trim($line) === '') continue;
            $data = str_getcsv($line);
            $record = array_combine($columns, $data);

            if (
                $DB->record_exists('user', ['username' => strtolower(trim($record['username']))]) ||
                $DB->record_exists('user', ['email' => strtolower(trim($record['email']))])
            ) {
                $skipped++;
                $skipped_users[] = "Line $i: Duplicate entry (Username: {$record['username']}, Email: {$record['email']})";
                continue;
            }

            $userdata = new stdClass();
            $userdata->username = strtolower(trim($record['username']));
            $userdata->firstname = trim($record['firstname'] ?? '');
            $userdata->assigdepartment  = $departmentid;
            $userdata->lastname = trim($record['lastname'] ?? '');
            $userdata->email = trim($record['email']);
            $userdata->company_id      = $companyid;
            $userdata->parent_id       = $USER->id;
            $userdata->password = trim($record['password']);
            $userdata->role      = $manag_role;
            $final = create_business_trainee($userdata);
            if ($final) {
                trainee_registration_email_to_user($companyid, $final, $userdata->password);
                
                $userobj = $DB->get_record('user', ['id' => $final]);
                $userobj->bussinessuser_email = $userdata->bussinessuser_email; // attach manually
                
                $created_users[] = $userobj;
                $created++;
            } else {
                $skipped++;
            }
        }
        if (!empty($created_users)) {
            bulk_trainee_registration_email_to_whitelable($companyid, $created_users);
        }
        if (!empty($skipped_users)) {
            echo "<h3>Skipped Users</h3>";
            foreach ($skipped_users as $user) {
                echo $OUTPUT->notification($user, 'notifyproblem');
            }
        }
        echo $OUTPUT->notification("$created users created | $skipped skipped", 'notifysuccess');
    }
} elseif (optional_param('cancelupload', 0, PARAM_TEXT)) {
    echo $OUTPUT->notification("Cancelled", 'notifyproblem');
}

$form->display();
echo $OUTPUT->footer();
