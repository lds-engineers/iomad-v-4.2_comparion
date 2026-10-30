<?php
require_once "../../config.php";
global $DB, $CFG, $PAGE, $OUTPUT, $USER;

$referenceImage = isset($_POST['referenceImage']) ? $_POST['referenceImage'] : null;
$currentImage = isset($_POST['currentImage']) ? $_POST['currentImage'] : null;
$courseid = isset($_POST['courseid']) ? $_POST['courseid'] : null;


// $bin = base64_decode($currentImage);
// $im = imageCreateFromString($bin);
// $filename = time()."_filename.png";
// $img_file = 'temp/'.$filename;

// $width = imagesx($im);
// $height = imagesy($im);
// $background = imagecreatetruecolor($width, $height);
// $white = imagecolorallocate($background, 255, 255, 255);
// imagefill($background, 0, 0, $white);
// imagecopy($background, $im, 0, 0, 0, 0, $width, $height);
// imagepng($background, $img_file, 0);
// imagedestroy($background);
// imagedestroy($im);
// $img_file = $CFG->wwwroot . '/local/scan_face_recognition/' . $img_file;

if (isset($_FILES['file'])) {
    $uploadDirectory = $CFG->dirroot . '/local/scan_face_recognition/temp/';
    require_once("./locallib.php");

    $fileName = basename($_FILES['file']['name']);
    $timestamp = time();
    $newFileName = "scanfacerefimg_{$timestamp}_{$USER->id}.jpg";
    $filePath = $uploadDirectory . $newFileName;

    $array = new stdClass();
    $array->userid = $USER->id;
    $array->courseid = $courseid;
    $array->image = $newFileName;
    $array->timemodified = time();
    $imageurl = $CFG->wwwroot . '/local/scan_face_recognition/temp/' . $array->image;

    // Check if record exists for this user and course
    $existing = $DB->get_record('lcl_scan_face_recognition', [
        'userid' => $USER->id,
        'courseid' => $courseid
    ]);

    if ($existing) {
        $array->id = $existing->id; // Required for update
        $DB->update_record('lcl_scan_face_recognition', $array);
    } else {
        $array->timecreated = time();
        $DB->insert_record('lcl_scan_face_recognition', $array);
    }

    // Move file after DB insert/update
    if (move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {
        if (empty($USER->picture)) {
            setuserprofileimagedata($USER->id, $imageurl);
        }
    } else {
        echo "Error moving file.";
    }
}

// if (isset($_FILES['file'])) {
//     $uploadDirectory = $CFG->dirroot.'/local/scan_face_recognition/temp/';
//     require_once("./locallib.php");
//     $fileName = basename($_FILES['file']['name']);
//     $timestamp = time();
//     $newFileName = "scanfacerefimg_{$timestamp}_{$USER->id}.jpg";
//     $filePath = $uploadDirectory . $newFileName;
//     $array = new stdClass();
//     $array->userid = $USER->id;
//     $array->courseid = $courseid;
//     $array->image = $newFileName;
//     $array->timecreated = time();
//     $array->timemodified = time();
//     $imageurl = $CFG->wwwroot.'/local/scan_face_recognition/temp/'.$array->image;
//     $insertdata = $DB->insert_record('lcl_scan_face_recognition', $array);
//     if ($insertdata) {
//         if (move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {
//             if(empty($USER->picture)){
//                 $userprofileimage = setuserprofileimagedata($USER->id,$imageurl); 
//             }
//         } else {
//             echo "Error moving file.";
//         }
//     }
// }





function curl_get_contents($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

function imageToBase64($image) {
    $imageData = base64_encode(curl_get_contents($image));
    $mime_types = array(
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'odt' => 'application/vnd.oasis.opendocument.text ',
    'docx'	=> 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'gif' => 'image/gif',
    'jpg' => 'image/jpg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'bmp' => 'image/bmp'
    );
    $ext = pathinfo($image, PATHINFO_EXTENSION);
    if (array_key_exists($ext, $mime_types)) {
        $a = $mime_types[$ext];
    }
    return $imageData;
}

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => "https://facematch.p.rapidapi.com/API/verify/Facematch",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => json_encode([
        'method' => 'facevalidate',
        'txn_id' => 'test-f23a-4bed-88fa-270befab4407',
        'clientid' => '222',
        'image_url1' => $referenceImage,
        'image_url2' => $currentImage
    ]),
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "x-rapidapi-host: facematch.p.rapidapi.com",
        "x-rapidapi-key: 6705a4b80emshfdf0c05e7294147p193596jsna4936e46b7e2"
    ],
]);

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
    echo "cURL Error #:" . $err;
} else {
    echo $response;
}

