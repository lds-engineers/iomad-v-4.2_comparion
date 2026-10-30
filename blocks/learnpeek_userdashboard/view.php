<?php
require_once(dirname(__FILE__) . '/../../config.php');
global $DB, $CFG, $USER;
require_once($CFG->dirroot . '/blocks/learnpeek_userdashboard/locallib.php');
echo $OUTPUT->header();

// function convert_pdf_to_png($pdfUrl, $outputPath) {
//     $tempPdf = sys_get_temp_dir() . '/certificate.pdf';

//     // Download PDF
//     $pdfContent = file_get_contents($pdfUrl);
//     if ($pdfContent === false) {
//         throw new Exception("Unable to download PDF");
//     }

//     file_put_contents($tempPdf, $pdfContent);

//     // Convert using Imagick
//     $imagick = new Imagick();
//     $imagick->setResolution(300, 300);
//     $imagick->readImage($tempPdf . '[0]');
//     $imagick->setImageFormat('png');

//     $imagick->writeImage($outputPath);

//     $imagick->clear();
//     $imagick->destroy();

//     // Cleanup
//     unlink($tempPdf);

//     return $outputPath;
// }

function convert_pdf_to_png($pdfUrl) {
    $tempPdf = sys_get_temp_dir() . '/certificate.pdf';

    $pdfContent = file_get_contents($pdfUrl);

    if ($pdfContent === false) {
        throw new Exception("Unable to download PDF");
    }

    file_put_contents($tempPdf, $pdfContent);

    $imagick = new Imagick();
    $imagick->setResolution(300, 300);
    $imagick->readImage($tempPdf . '[0]');
    $imagick->setImageFormat('png');

    $imageBlob = $imagick->getImageBlob();
    $dataUrl = 'data:image/png;base64,' . base64_encode($imageBlob);

    $imagick->clear();
    $imagick->destroy();

    unlink($tempPdf);

    return $dataUrl;
}


// $pdfUrl = $CFG->wwwroot . "/local/customlayout/file.php?id=0280960caffaf4c0b58ecdf3ed36101c45524f08";


///============================================
$crtuserid = 967;
$crtcourse = 481;
$certificate = '';

$backupcerts = $DB->get_record_sql(
    "SELECT ccb.* FROM {user_enrolments} ue 
        INNER JOIN {enrol} e ON e.id = ue.enrolid 
        INNER JOIN {customcert_backup} ccb ON ccb.current_enrollment = ue.id 
        WHERE ue.userid = ? 
        AND e.courseid = ?",
    [$crtuserid , $crtcourse]
);

if($backupcerts){
    $usercontext = \context_user::instance($crtuserid);
    $contextid   = $usercontext->id;

    $docfile = $DB->get_record_sql(
        "SELECT * FROM {files}
            WHERE contextid = :contextid 
            AND component = 'user'
            AND itemid = :itemid
            AND filesize > 0
            AND filearea = 'certificate'
            AND filename != '.'",
        [
            'contextid' => $contextid,
            'itemid' => $backupcerts->id
        ]
    );
    
    if($docfile){
        $certificate = $CFG->wwwroot.'/local/customlayout/file.php?id='.$docfile->pathnamehash;

    }
}

if($certificate){
   $certificateimage = convert_pdf_to_png($certificate);
    echo "<img src='".$certificateimage."' style='width:350px;'>"; 
}

 
echo $OUTPUT->footer();