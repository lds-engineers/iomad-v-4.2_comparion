<?php
require_once('../../config.php');
global $USER,$CFG;
if (isloggedin() && !isguestuser()) {
    $userid = $USER->id;
    $previous_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    // echo $previous_url;
    // die();
    require_logout();
    redirect($previous_url);
} else {
    redirect('/my');
}
