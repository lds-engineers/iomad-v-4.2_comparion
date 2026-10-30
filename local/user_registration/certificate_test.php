<?php
require_once(dirname(__FILE__) . '/../../config.php');
// require_once($CFG->dirroot . '/mod/customcert/classes/template.php');

global $USER,$DB,$CFG;

$courseid = 299;
$userid = $USER->id;

$certificatemodule = $DB->get_record("modules", array("name" => "customcert"));

$coursmodul = $DB->get_record("course_modules", array('module' => $certificatemodule->id, 'course' => $courseid));

$customcert = $DB->get_record('customcert', ['id' => $coursmodul->instance], '*', MUST_EXIST);

$template = $DB->get_record('customcert_templates', ['id' => $customcert->templateid], '*', MUST_EXIST);

$template = new \mod_customcert\template($template);



$pdf = $template->generate_pdf(false, $userid, true);

// $cm = get_coursemodule_from_instance('customcert', $customcert->id);

// echo "<pre>";
// print_r($pdf);
?>