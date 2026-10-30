<?php
require('../../config.php');
// require('locallitest.php');
require('locallib.php'); 
//require('gear.php');
require_login();


        global $DB, $CFG, $USER;

        $userid = 2487;
        $courseid = 77;
        $licenseid = 1661;
        $preview = false;


        // --- Get company ID of the current logged-in user ---
        $companyid = $DB->get_field('company_users', 'companyid', ['userid' => $USER->id], MUST_EXIST);

        // --- Load template from DB ---
        $gettemplate = $DB->get_record_sql(
            "SELECT * FROM {custom_email_template} WHERE email_template = ?",
            ['notify-user-course-assign']
        );
        if (!$gettemplate) {
            // fallback to global template
            $gettemplate = $DB->get_record_sql(
                "SELECT * FROM {custom_email_template} WHERE company_id = ? AND email_template = ?",
                [0, 'notify-user-course-assign']
            );
        }
        if (!$gettemplate) {
            return false; // no template found
        }

          $userrole = $DB->get_record_sql("SELECT * FROM {company_users} WHERE userid = ? AND managertype = 1",array($USER->id));
          if($userrole){
            $regards_by = "";
                 //get compnay logo
                $comlogodetails = $DB->get_record_sql('SELECT * FROM {company_logo} WHERE company_id = ?', array($companyid));
                if ($comlogodetails) {
                    $companylogo = $DB->get_record_sql("SELECT * FROM {files} WHERE contextid= 1 AND component='landingpage' AND itemid=? AND filesize > 0 AND filearea = ?", array($comlogodetails->id, 'logo_image'));
                    $logourl = $CFG->wwwroot . '/blocks/custom_mycourse/file.php?id=' . $companylogo->pathnamehash;
                    $company_logo = "<img src='" . $logourl . "' style='width: 125px;max-height: 150px;object-fit: cover;'>";
                } else {
                    $company_logo = "";
                }
          }else{
            $regards_by = fullname($USER);
            $company_logo = "";
          }

        $email_template = $gettemplate->email_message ?: '';
        $subject_template = $gettemplate->email_subject ?: "You have been assigned to the course {{course_name}}.";

        // --- Fetch user and course ---
        $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

        // --- Build Assign Table (all courses under same license) ---
        $license_courses = $DB->get_records('companylicense_courses', ['licenseid' => $licenseid]);
        $table_html = '<table border="1" cellpadding="5" cellspacing="0"><tr><th>Course Name</th><th>Link</th></tr>';
        $coursenames = array();
        foreach ($license_courses as $lc) {
            $c = $DB->get_record('course', ['id' => $lc->courseid]);
            if ($c) {
                $coursenames[] = format_string($c->fullname);
                $courseurl = $CFG->wwwroot . '/course/view.php?id=' . $c->id;
                $table_html .= '
                    <tr>
                        <td>' . format_string($c->fullname) . '</td>
                        <td><a href="' . $courseurl . '">'.$courseurl.'</a></td>
                    </tr>';
            }
        }
        $table_html .= '</table>';

        // --- Placeholders and replacements ---
        $placeholders = ['{{user_name}}', '{{course_name}}', '{{assigned_by}}', '{{assign_table}}','{{regards_by}}','{{company_logo}}'];
        $replacements = [
            fullname($user),
            implode(", " , $coursenames),
            fullname($USER),
            $table_html,
            $regards_by,
            $company_logo
        ];

        $email_body = str_replace($placeholders, $replacements, $email_template);
        $subject = str_replace($placeholders, $replacements, $subject_template);

        // --- Build HTML + plain text ---
        $messagehtml = "<!DOCTYPE html>
            <html>
                <head><meta charset='utf-8'><title>{$subject}</title></head>
                <body>$email_body</body>
            </html>";
        $messagetext = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $email_body));
        // --- Preview mode ---
        if ($preview) {
            echo "<h2>Email Preview</h2>";
            echo "<strong>Subject:</strong> " . format_string($subject) . "<br><br>";
            echo $messagehtml;
            exit;
        }

        // --- From user ---
        $companyname = $DB->get_field('company', 'name', ['id' => $companyid]);
        $fromUser = fullname($USER);

        // --- Recipient ---
        $emailuser = (object)[
            'email' => $user->email,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'maildisplay' => true,
            'mailformat' => 1,
            'id' => $user->id
        ];

        if(!empty($gettemplate->destination_email)){
            $emailuser->email = $gettemplate->destination_email;
        }
        // --- Send email ---
        return email_learnpeek_to_user(
            $emailuser,
            $fromUser,
            $subject,
            $messagetext,
            $messagehtml,
            '', '', true, '', '', 79, $companyid
        );



die();


















































// --- Get company ID of the current logged-in user ---

$userid = 1107;
$courseid = 308;
$licenseid = 775;
$preview = true;



$companyid = $DB->get_field('company_users', 'companyid', ['userid' => $USER->id], MUST_EXIST);

// --- Load template from DB ---
$gettemplate = $DB->get_record_sql(
    "SELECT * FROM {custom_email_template} WHERE email_template = ?",
    ['notify-user-course-assign']
);
if (!$gettemplate) {
    // fallback to global template
    $gettemplate = $DB->get_record_sql(
        "SELECT * FROM {custom_email_template} WHERE company_id = ? AND email_template = ?",
        [0, 'notify-user-course-assign']
    );
}
if (!$gettemplate) {
    return false; // no template found
}

////////////////////////////

  $userrole = $DB->get_record_sql("SELECT * FROM {company_users} WHERE userid = ? AND managertype = 1",array($USER->id));
  if($userrole){
    $regards_by = "";
         //get compnay logo
        $comlogodetails = $DB->get_record_sql('SELECT * FROM {company_logo} WHERE company_id = ?', array($companyid));
        if ($comlogodetails) {
            $companylogo = $DB->get_record_sql("SELECT * FROM {files} WHERE contextid= 1 AND component='landingpage' AND itemid=? AND filesize > 0 AND filearea = ?", array($comlogodetails->id, 'logo_image'));
            $logourl = $CFG->wwwroot . '/blocks/custom_mycourse/file.php?id=' . $companylogo->pathnamehash;
            $company_logo = "<img src='" . $logourl . "' style='width: 125px;max-height: 150px;object-fit: cover;'>";
        } else {
            $company_logo = "";
        }
  }else{
    $regards_by = fullname($USER);
    $company_logo = "";
  }

  echo 

$email_template = $gettemplate->email_message ?: '';
$subject_template = $gettemplate->email_subject ?: "You have been assigned to the course {{course_name}}.";

// --- Fetch user and course ---
$user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

// --- Build Assign Table (all courses under same license) ---
$license_courses = $DB->get_records('companylicense_courses', ['licenseid' => $licenseid]);
$table_html = '<table border="1" cellpadding="5" cellspacing="0"><tr><th>Course Name</th></tr>';
$coursenames = array();
foreach ($license_courses as $lc) {
    $c = $DB->get_record('course', ['id' => $lc->courseid]);
    if ($c) {
        $coursenames[] = format_string($c->fullname);
        $table_html .= '<tr><td>' . format_string($c->fullname) . '</td></tr>';
    }
}
$table_html .= '</table>';

// --- Placeholders and replacements ---
$placeholders = ['{{user_name}}', '{{course_name}}', '{{assigned_by}}', '{{assign_table}}','{{regards_by}}','{{company_logo}}'];
$replacements = [
    fullname($user),
    implode(", " , $coursenames),
    fullname($USER),
    $table_html,
    $regards_by,
    $company_logo
];

$email_body = str_replace($placeholders, $replacements, $email_template);
$subject = str_replace($placeholders, $replacements, $subject_template);

// --- Build HTML + plain text ---
$messagehtml = "<!DOCTYPE html>
    <html>
        <head><meta charset='utf-8'><title>{$subject}</title></head>
        <body>$email_body</body>
    </html>";
$messagetext = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $email_body));
// --- Preview mode ---
if ($preview) {
    echo "<h2>Email Preview</h2>";
    echo "<strong>Subject:</strong> " . format_string($subject) . "<br><br>";
    echo $messagehtml;
    exit;
}

// --- From user ---
$companyname = $DB->get_field('company', 'name', ['id' => $companyid]);
$fromUser = fullname($USER);

// --- Recipient ---
$emailuser = (object)[
    'email' => $user->email,
    'firstname' => $user->firstname,
    'lastname' => $user->lastname,
    'maildisplay' => true,
    'mailformat' => 1,
    'id' => $user->id
];

if(!empty($gettemplate->destination_email)){
    $emailuser->email = $gettemplate->destination_email;
}
// --- Send email ---
return email_learnpeek_to_user(
    $emailuser,
    $fromUser,
    $subject,
    $messagetext,
    $messagehtml,
    '', '', true, '', '', 79, $companyid
);
    









die();
global $DB, $USER,$CFG; 

// $user_display_name = fullname($USER);
// $course_name = "LDS Course"; 

// $placeholders = ['{{user_display_name}}', '{{course_name}}'];
// $replacements = [$user_display_name, $course_name];







// $emailtemplatedata = $DB->get_record_sql("SELECT * FROM {custom_email_template} WHERE id = ?", array(1));

// $email_body = str_replace($placeholders, $replacements, $emailtemplatedata->email_message);

// $html = "";
// $html .= $email_body;

// echo $html;
$companyid = 36;
$oderuserid = 643;
$oderinvoiceid = 1097; 
// $courseid = 235;
// $courseid = 77;
// echo $email = order_complete_email_to_superadmin($companyid, $oderuserid, $oderinvoiceid);
echo $email = order_complete_email_to_whitelable($companyid, $oderuserid, $oderinvoiceid);
echo $email = order_complete_email_to_user($companyid, $oderinvoiceid); 
// echo $email = course_complte_email_to_user($courseid);

  //  $whitelableuser = $DB->get_record("user", array("id"=>$USER->id));
  //  echo core_user::get_support_user();
  //  exit()
  //  $fromUser = core_user::get_support_user();
  //           $subject = $gettemplate->email_subject;
  //           $emailuser = (object) [];
  //           // $emailuser->email = $whitelableuser->email;
  //           $emailuser->email = "zaidk4076@gmail.com";
  //           $emailuser->firstname = $whitelableuser->firstname;
  //           $emailuser->lastname= $whitelableuser->lastname;
  //           $emailuser->maildisplay = true;
  //           $emailuser->mailformat = 1; // 0 (zero) text-only emails, 1 (one) for HTML/Text emails.
  //           $emailuser->id = 1;
  //           $emailuser->firstnamephonetic = false;
  //           $emailuser->lastnamephonetic = false;
  //           $emailuser->middlename = false;
  //           $emailuser->username = false;
  //           $emailuser->alternatename = false;
  //           if($emailtemplatedata_wl){
  //             email_to_user($emailuser,$fromUser, $subject, $message = '', $messagehtml);
  //             // echo "email send to ".$whitelableuser->email;
  //             // return = 1;
  //             // echo "mail send to white lavel";
  //           }

