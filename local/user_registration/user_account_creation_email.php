<?php
require_once('../../config.php');

// function trainee_registration_email_to_user($companyid, $userid, $password, $preview = false) {
//     global $DB, $CFG;

//     $gettemplate = $DB->get_record_sql(
//         "SELECT * FROM {custom_email_template} WHERE email_template = ?",
//         ['user-account-creation']
//     );
//     if (!$gettemplate) {
//         $gettemplate = $DB->get_record_sql(
//             "SELECT * FROM {custom_email_template} company_id = ? AND  WHERE email_template = ?",
//             [0, 'user-account-creation']
//         );
//     }
//     if (!$gettemplate) {
//         return false;
//     }

//     $emailtemplatedata_user = $gettemplate->email_message ?: '';
//     $rawsubject             = $gettemplate->email_subject ?: "Your {{company_name}} account has been created";

//     $company_logo = '';
//     if ($comlogodetails = $DB->get_record('company_logo', ['company_id' => $companyid])) {
//         $fs = get_file_storage();
//         $context = context_system::instance(); // use system context
//         $files = $fs->get_area_files($context->id, 'landingpage', 'logo_image', $comlogodetails->id, 'sortorder', false);
//         if ($files) {
//             $file = reset($files);
//             $logourl = moodle_url::make_pluginfile_url(
//                 $file->get_contextid(),
//                 $file->get_component(),
//                 $file->get_filearea(),
//                 $file->get_itemid(),
//                 $file->get_filepath(),
//                 $file->get_filename()
//             )->out(false);
//             $company_logo = "<img src='{$logourl}' style='width:125px;max-height:150px;object-fit:cover;'>";
//         }
//     }

//     $company = $DB->get_record('company', ['id' => $companyid], '*', MUST_EXIST);
//     $user    = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

//     $login_url = $CFG->wwwroot . '/login/index.php';
//     $login_url = '<a href="' . $login_url . '" target="_blank">Login here</a>';

//     $placeholders = ['{{username}}', '{{company_name}}', '{{password}}', '{{login_url}}', '{{company_logo}}'];
//     $replacements = [
//         $user->username,
//         $company->name,
//         $password,
//         $login_url,
//         $company_logo
//     ];

//     $email_body = str_replace($placeholders, $replacements, $emailtemplatedata_user);
//     $subject    = str_replace($placeholders, $replacements, $rawsubject);

//     $messagehtml = "<!DOCTYPE html>
//         <html>
//             <head><meta charset='utf-8'><title>{$subject}</title></head>
//             <body>$email_body</body>
//         </html>";
//     $messagetext = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $email_body));

//     if ($preview) {
//         echo "<h2>Email Preview</h2>";
//         echo "<strong>Subject:</strong> " . format_string($subject) . "<br><br>";
//         echo $messagehtml;
//         exit;
//     }

//     $fromUser = core_user::get_support_user();
//     if ($getcompany_wu = $DB->get_record_sql(
//         "SELECT * FROM {company_users} WHERE companyid = ? AND managertype = ?", 
//         [$companyid, 1]
//     )) {
//         if ($whitelabeluser = $DB->get_record('user', ['id' => $getcompany_wu->userid])) {
//             $fromUser = fullname($whitelabeluser);
//         }
//     }

//     $emailuser = (object)[
//         'email' => $user->email,
//         'firstname' => $user->firstname,
//         'lastname' => $user->lastname,
//         'maildisplay' => true,
//         'mailformat' => 1,
//         'id' => $user->id
//     ];

//     if(!empty($gettemplate->destination_email)){
//         $emailuser->email = $gettemplate->destination_email;
//     }
//     return email_learnpeek_to_user(
//         $emailuser,
//         $fromUser,
//         $subject,
//         $messagetext,
//         $messagehtml,
//         '', '', true, '', '', 79, $companyid
//     );
// }