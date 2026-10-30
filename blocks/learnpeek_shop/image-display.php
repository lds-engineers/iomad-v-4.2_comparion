<?php
require('../../config.php');
//require('gear.php');
require_login();
$PAGE->set_context(context_system::instance());
global $DB, $USER,$CFG;

$comlogodetails = $DB->get_record_sql('SELECT * FROM {company_logo} WHERE company_id = ?',array(32));

$sliderimage = $DB->get_record_sql("SELECT * FROM {files} WHERE contextid= 1 AND component='landingpage' AND itemid=? AND filesize > 0 AND filearea = ?", array($comlogodetails->id,'logo_image'));
echo "<pre>";
print_r($sliderimage);
echo "</pre>";
echo $CFG->wwwroot;
// echo $CFG->wwwroot.'/blocks/custom_mycourse/file.php?id='.$sliderimage->pathnamehash;
// echo "</pre>";
// echo '<figure class="image><a href="#" class="lightbox-image" data-fancybox="images"><img src="'.$CFG->wwwroot.'/blocks/custom_mycourse/file.php?id='.$sliderimage->pathnamehash.'" alt=""></a></figure>';


