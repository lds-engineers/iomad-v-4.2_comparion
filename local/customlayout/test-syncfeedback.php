<?php
require_once('../../config.php');
global $DB, $USER, $CFG;
require_once("$CFG->libdir/formslib.php");
$id = optional_param('id', 101, PARAM_INT);
require_login();
echo $OUTPUT->header();
$alldata = $DB->get_records_sql("SELECT * FROM `mdl_tool_courserating_rating` WHERE `courseid` IN (337,339,77,317,343,307,344,350,364) AND `status` = '1' AND id > ? LIMIT 2",[$id]);
$lpcourse_fid = $DB->get_record("customfield_field",array("shortname"=>"learnpress_courseid"));
    echo "<pre>";
        print_r($alldata);
        echo "</pre>";
        $lastprocessedid = $id;
foreach ($alldata as $key => $data) {
    $user   = $DB->get_record('user', ['id' => $data->userid]);
    $lpcoursedata = $DB->get_record_sql("SELECT value FROM {customfield_data} WHERE instanceid = ? AND fieldid =?",[$data->courseid, $lpcourse_fid->id]);
    $companydata = $DB->get_record('company_users', ['userid' => $user->id]);

    $wordpresscourseid = $lpcoursedata->value;
    $companyid = $companydata->companyid;
    $ssourl = get_config(null, "main_site_url{$companyid}");



    if($ssourl){
        echo $ssovalidateurl = rtrim($ssourl, '/') . '/wp-json/learnpeek/v1/receive-review';
        $ssovalidateurl;
        $payload = [
            'rating'            => (int)$data->rating,
            'review'            => $data->review,
            'status'            => $data->status,
            'useremail'         => $user->email,             
            'firstname'         => $user->firstname,             
            'lastname'          => $user->lastname,             
            'lp_courseid'       => $wordpresscourseid,
            'lms_courseid'      => (int)$data->courseid,
            'timecreated'       => $data->timecreated,  
            'moodlewsrestformat'=> 'json'
        ];

        echo "<pre>";
        print_r($payload);
        echo "</pre>";

        $curl = new curl();
        $response = $curl->post($ssovalidateurl, $payload);
        print_r($response);

         $lastprocessedid = $data->id;
    }
}

if (!empty($alldata)) {
    ?>
    <script>
        setTimeout(function() {
            window.location.href = '<?php echo basename(__FILE__); ?>?id=<?php echo (int)$lastprocessedid; ?>';
        }, 1000);
    </script>
    <?php
} else {
    echo "<p>All reviews have been processed.</p>";
}
echo $OUTPUT->footer();