<?php
require('../config.php');
require_once('lib.php');
if (isloggedin() && !isguestuser()) {
    if(!empty($USER->realuser) && $USER->realuser != $USER->id){
        if($originaluser = $DB->get_record("user", array("id"=>$USER->realuser, "confirmed"=>1, "deleted"=>0))){
            complete_user_login($originaluser);
            \core\session\manager::apply_concurrent_login_limit($originaluser->id, session_id());
        }
    }
}
redirect(new moodle_url('/my'));
exit;