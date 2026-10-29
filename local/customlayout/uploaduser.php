<?php
require_once('../../config.php');
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/moodlelib.php');
require_once($CFG->dirroot.'/local/customlayout/locallib.php');
require_once($CFG->dirroot . '/local/learnpeek_email_template/locallib.php');

require_login();


// $companyid = iomad::get_my_companyid(context_system::instance());

$PAGE->set_url(new moodle_url('/local/customlayout/uploaduser.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('bulkuserupload', 'local_customlayout'));
$PAGE->set_heading(get_string('bulkuserupload', 'local_customlayout'));


$getcompnayadmin = $DB->get_record_sql("SELECT * FROM {company_users} WHERE userid =?",array($USER->id));


if (!is_siteadmin() && (!$getcompnayadmin ||  ($getcompnayadmin->managertype != 1 && $getcompnayadmin->managertype != 2))) {
    redirect(new moodle_url($CFG->wwwroot.'/my'),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
     die;
}
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
echo $OUTPUT->header();

class bulk_user_upload_form extends moodleform {
    public function definition() {
        $mform = $this->_form;
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
        $content = $_SESSION['custom_upload_csv'];
        unset($_SESSION['custom_upload_csv']);
        $lines = explode("\n", trim($content));

        $headerline = array_shift($lines);
        $headerline = preg_replace('/^\xEF\xBB\xBF/', '', $headerline);
        // $columns = str_getcsv($headerline);

        $columns = array_map('trim', str_getcsv($headerline));

        // Remove empty columns caused by trailing commas.
        $columns = array_filter($columns, function($col) {
            return $col !== '';
        });

        $columns = array_values($columns);

        $cleanlines = [];
        foreach ($lines as $line) {
            // Remove extra spaces/newlines.
            $line = trim($line);

            // Skip blank CSV rows
            if (trim(str_replace(',', '', $line)) === '') {
                continue;
            }

            $cleanlines[] = $line;
        }

        $lines = $cleanlines;

        $created = 0;
        $skipped = 0;
        $created_users = [];
        $skipped_users = [];
        // echo "<pre>";
        // print_r($lines);
        // print_r($columns);
        // echo "</pre>";
        // die();
        $hasbusinesscolumn = in_array('bussinessuser_email', $columns);
        foreach ($lines as $i => $line) {

            // Remove empty/blank rows.
            if (trim(str_replace(',', '', $line)) === '') {
                continue;
            }

            $data = array_map('trim', str_getcsv($line));

            // Match column count.
            $data = array_slice($data, 0, count($columns));

            while (count($data) < count($columns)) {
                $data[] = '';
            }

            $record = array_combine($columns, $data);

            // Required field validation.
            if (
                empty($record['username']) ||
                empty($record['firstname']) ||
                empty($record['lastname']) ||
                empty($record['email']) ||
                empty($record['password'])
            ) {

                $skipped++;
                $skipped_users[] = "Line " . ($i + 2) . ": Required fields missing.";
                continue;
            }

            // Optional business user validation.
            if ($hasbusinesscolumn) {

                $buseremail = strtolower(trim($record['bussinessuser_email']));

                // Empty business email.
                if (empty($buseremail)) {

                    $skipped++;
                    $skipped_users[] =
                        "Line " . ($i + 2) .
                        ": bussinessuser_email cannot be empty.";

                    continue;
                }


                // Check Moodle user exists.
                $businessuser = $DB->get_record(
                    'user',
                    ['email' => trim(strtolower($record['bussinessuser_email']))]
                );

                if (!$businessuser) {

                    echo $OUTPUT->notification(
                        "Line " . ($i + 2) . ": Business user '" .
                        s($record['bussinessuser_email']) .
                        "' does not exist in Moodle. Please create this user first.",
                        'notifyproblem'
                    );

                    continue;
                }
            }

            //==================================================
            if (is_siteadmin()) {
                // check business user are company id
                $buseremail = trim(strtolower($record['bussinessuser_email']));
                $companyusers = $DB->get_records_sql("SELECT cu.companyid 
                    FROM {user} u INNER JOIN {company_users} cu ON cu.userid = u.id 
                    WHERE u.email = ? AND cu.managertype = ?",[$buseremail, 2]
                );
                if(empty($companyusers)){
                    $skipped++;
                    $skipped_users[] =
                        "Line " . ($i + 2) .
                        ": Business user '" .
                        s($buseremail) .
                        "' is not mapped with Business User role .";

                    continue;
                }

                if (count($companyusers) == 1) {
                    $record1 = reset($companyusers);
                    $companyid = $record1->companyid;

                } else if (count($companyusers) > 1) {
                    $companyid = iomad::get_my_companyid(context_system::instance());
                    $userscompanyids = array_column($companyusers, "companyid");
                    if(!in_array($companyid, $userscompanyids)){
                        $skipped_users[] =
                            "Line " . ($i + 2) .
                            ": Business user '" .
                            s($buseremail) .
                            "' is associated with multiple companies. Please switch to the appropriate company in the Iomad Dashboard and try again.";
                        continue;
                    }
                }
            }else if($getcompnayadmin->managertype == 1){
                $companyid = $getcompnayadmin->companyid;
                $buseremail = trim(strtolower($record['bussinessuser_email']));
                $companyusers = $DB->get_record_sql("SELECT cu.companyid 
                    FROM {user} u INNER JOIN {company_users} cu ON cu.userid = u.id 
                    WHERE u.email = ? AND cu.managertype = ? AND companyid = ?",[$buseremail, 2 ,$companyid]
                );
                if(empty($companyusers)){
                    $skipped++;
                    $skipped_users[] =
                        "Line " . ($i + 2) .
                        ": Business user '" .
                        s($buseremail) .
                        "' is not mapped with Business User role .";

                    continue;
                }
            }else{
                $companyid = $getcompnayadmin->companyid;
            }
            // echo $companyid;
            // Username duplicate check (Global)
            // echo $record['username'];
            $usernameexists = $DB->record_exists(
                'user',
                [
                    'username' => strtolower(trim($record['username']))
                ]
            );
            // die();
            if ($usernameexists) {

                $skipped++;

                $skipped_users[] =
                    "Line " . ($i + 2) .
                    ": Username already exists (" .
                    s($record['username']) .
                    ")";

                continue;
            }

            // Email duplicate check (Company Wise)


            $validateuseremail = $DB->get_record_sql("SELECT u.id FROM {user} u 
                INNER JOIN {company_users} cu ON cu.userid = u.id 
                WHERE (u.email = :email OR u.username = :username) AND cu.companyid = :companyid",
                    [
                        'email' => strtolower(trim($record['email'])),
                        'username' => strtolower(trim($record['email'])),
                        'companyid' => $companyid
                    ]
                );

            if ($validateuseremail) {

                $skipped++;

                $skipped_users[] =
                    "Line " . ($i + 2) .
                    ": Email already exists for this company (" .
                    s(strtolower(trim($record['email']))) .
                    ")";

                continue;
            }

            $userdata = new stdClass();
            $userdata->username = strtolower(trim($record['username']));
            $userdata->firstname = trim($record['firstname']);
            $userdata->lastname = trim($record['lastname']);
            $userdata->email = strtolower(trim($record['email']));
            $userdata->password = trim($record['password']);

            
            $userdata->bussinessuser_email =
                !empty($record['bussinessuser_email'])
                ? trim($record['bussinessuser_email'])
                : '';
    
            $userdata->company_id = $companyid;

            $final = create_individual_user($userdata);
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
