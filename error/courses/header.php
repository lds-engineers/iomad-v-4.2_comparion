<?php
$current_host = $_SERVER['HTTP_HOST'];
$getcompany = $DB->get_record_sql("SELECT * FROM {company} WHERE hostname LIKE ?", array($current_host));
$companyid = $getcompany->id;
$homeurl = get_config(null, "main_site_url{$companyid}");

$logoimage = '';
$logodetails = $DB->get_record('company_logo', ['company_id' => $companyid]);

if ($logodetails) {
    $systemcontext = context_system::instance();
    $fs = get_file_storage();
    $files = $fs->get_area_files(
        $systemcontext->id,
        'landingpage',
        'logo_image',
        $logodetails->id,
        'timemodified DESC',
        false
    );

    if (!empty($files)) {
        $logofile = reset($files);
        $pathnamehash = $logofile->get_pathnamehash();
        $logoimage = $CFG->wwwroot . '/blocks/custom_mycourse/file.php?id=' . $pathnamehash . '&v=' . time();
    }
}

// echo $logoimage;
// die('stop');
echo "
<link rel='stylesheet' href='css/coursesstyle.css'>

<style>


header.cst_coures_header ul li{
   list-style:none;
}

header.cst_coures_header ul li a{
   text-decoration:none;
}



</style>";


echo '
    <header class="cst_coures_header fixed-top py-4  shadow">
       <div class="conatainer-fluid px-3 ">
          <ul class="d-flex align-items-center m-0" style="gap : 40px;">
            <li><a href="'.$homeurl.'"><img src="' . $logoimage . '" alt="Logo" style="height:40px;"></a></li>
            <li><a href="'.$homeurl.'">Home</a></li>
          </ul>
       </div>
    </header>
';

?>