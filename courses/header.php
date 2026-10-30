<?php
require_once(__DIR__ . '/../config.php');
global $CFG, $DB, $OUTPUT,$USER, $PAGE, $SESSION;
if(isloggedin()){
    redirect($CFG->wwwroot."/blocks/learnpeek_shop/shop.php");
    die;
}
require_once($CFG->dirroot. '/courses/locallib.php');
require_once($CFG->dirroot. '/blocks/custom_mycourse/locallib.php');
require_once($CFG->dirroot. '/blocks/learnpeek_shop/locallib.php');
require_once($CFG->dirroot . '/theme/moove/classes/output/core_renderer.php');
$output = new \theme_moove\output\core_renderer($PAGE, theme_config::load('moove'));

$current_host = $_SERVER['HTTP_HOST'];
$getcompany = $DB->get_record_sql("SELECT * FROM {company} WHERE hostname LIKE ?", array($current_host));
$companyid = $getcompany->id;
$homeurl = get_config(null, "main_site_url{$companyid}");


$logo = $OUTPUT->get_logo();
$favicon = $OUTPUT->favicon();
$title = $OUTPUT->page_title();

$login_url = '/login/index.php';
$register_url = '/local/user_registration/index.php';
echo "
<link rel='stylesheet' href='css/coursesstyle.css'>
<link rel='icon' href='{$favicon}' type='image/x-icon'>
<style>


header.cst_coures_header ul li{
   list-style:none;
}
nav[aria-label='Site navigation'] {
    display: none;
}
header.cst_coures_header ul li a{
   text-decoration:none;
}

.custom-cart {
   position: absolute;
   top: 25px;
   right: 20px;
}

</style>";


echo '
    <header class="cst_coures_header fixed-top py-4 bg-white shadow">
       <div class="conatainer-fluid px-3 ">
          <ul class="d-flex align-items-center m-0 justify-content-between p-0" style="gap :0px;">
            <div class="d-flex align-items-center" style="gap:10px">
               <li><a href="'.$homeurl.'"><img src="' . $logo . '" alt="Logo" style="height:40px;"></a></li>
               <li><a href="'.$homeurl.'">Home</a></li>
            </div>
            <div>
              <a href="'.$login_url.'" class="btn btn-primary">Login</a>
              <a href="'.$register_url.'" class="btn btn-secondary">Register</a>
            </div>
          </ul>
       </div>
    </header>
';

?>