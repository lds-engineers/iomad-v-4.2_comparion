<?php
require_once("$CFG->dirroot/config.php");
function user_registration_email_to_user($companyid, $userid, $preview = false) {
    global $DB, $CFG;

    $company = $DB->get_record('company', ['id' => $companyid]);

    $gettemplate = $DB->get_record_sql(
        "SELECT * FROM {custom_email_template} WHERE email_template = ? AND company_id = ?",
        ['register-email-user',$companyid]
    );
    if (!$gettemplate) {
        $gettemplate = $DB->get_record_sql(
            "SELECT * FROM {custom_email_template} WHERE company_id = ? AND email_template = ?",
            [0, 'register-email-user']
        );
    }

    if (!$gettemplate) {
        return false;
    }

    $emailtemplatedata_user = $gettemplate->email_message ?: '';

    $company_logo = '';
    $comlogodetails = $DB->get_record('company_logo', ['company_id' => $companyid]);
    if ($comlogodetails) {
        $companylogo = $DB->get_record_sql(
            "SELECT * FROM {files} WHERE component = ? AND itemid = ? AND filearea = ? AND filesize > 0 ORDER BY timecreated DESC LIMIT 1",
            ['landingpage', $comlogodetails->id, 'logo_image']
        );
        if ($companylogo && !empty($companylogo->pathnamehash)) {
            $logourl = $CFG->wwwroot . '/blocks/custom_mycourse/file.php?id=' . $companylogo->pathnamehash;
            $company_logo = "<img src='" . $logourl . "' style='width:125px;max-height:150px;object-fit:cover;'>";
        }
    }

    $user = $DB->get_record('user', ['id' => $userid]);
    if (!$user) {
        return false; 
    }

    $login_url = $CFG->wwwroot . '/login/index.php';
    $login_url = '<a href="' . $login_url . '" target="_blank">'.$login_url.'</a>';
    
    $placeholders = ['{{username}}', '{{company_name}}', '{{login_url}}', '{{company_logo}}'];
    $replacements = [
        fullname($user),
        ($company && !empty($company->name)) ? $company->name : '',
        $login_url,
        $company_logo
    ];

    $email_body = str_replace($placeholders, $replacements, $emailtemplatedata_user);

    $rawsubject = !empty($gettemplate->email_subject)
        ? $gettemplate->email_subject
        : "Welcome to {{company_name}} – Your Account is Ready!";
    $subject = str_replace($placeholders, $replacements, $rawsubject);

    $messagehtml = "<!DOCTYPE html>
        <html>
            <head><meta charset='utf-8'><title>" . htmlspecialchars($subject) . "</title></head>
            <body>$email_body</body>
        </html>";

    if ($preview) {
        echo "<h2>Email Preview</h2>";
        echo "<strong>Subject:</strong> " . format_string($subject) . "<br><br>";
        echo $messagehtml;
        exit;
    }

    $getcompany_wu = $DB->get_record_sql(
        "SELECT * FROM {company_users} WHERE companyid = ? AND managertype = ?",
        [$companyid, 1]
    );
    if ($getcompany_wu) {
        $whitelabeluser = $DB->get_record('user', ['id' => $getcompany_wu->userid]);
        $fromUser = $whitelabeluser ? fullname($whitelabeluser) : core_user::get_support_user();
    } else {
        $fromUser = core_user::get_support_user();
    }

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
    
    $messagetext = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $email_body));

    return email_learnpeek_to_user(
        $emailuser,
        $fromUser,
        $subject,
        $messagetext,
        $messagehtml,
        '', '', true, '', '', 79, $companyid
    );
}