<?php
require_once('../../config.php');
global $USER,$CFG;
redirect(new moodle_url($CFG->wwwroot.'/my'),$_REQUEST['message'], null, \core\output\notification::NOTIFY_ERROR);
