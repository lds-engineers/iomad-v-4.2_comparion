<?php
require_once("../../config.php");
global $DB, $CFG, $PAGE, $USER, $OUTPUT;
require_login();

echo $OUTPUT->header();
echo 'customlayout';
echo $OUTPUT->footer();

