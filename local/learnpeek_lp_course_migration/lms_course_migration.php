<?php
require_once(dirname(__FILE__) . '/../../config.php');
global $DB, $PAGE, $CFG, $USER, $OUTPUT; 
require_once("$CFG->libdir/filelib.php");
require_once("locallib.php");
require_once("lms_course_migrationform.php");
require_login();
if (!is_siteadmin()) {
    redirect(new moodle_url($CFG->wwwroot.'/my'),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
     die;
}
$companyid = iomad::get_my_companyid(context_system::instance());
$PAGE->set_url($CFG->wwwroot.'/local/learnpeek_lp_course_migration/lms_course_migration.php');
$PAGE->set_pagelayout('standard');
$PAGE->set_heading('<div>'.get_string('migratecourse','local_learnpeek_lp_course_migration').'</div>');

$companyid = iomad::get_my_companyid(context_system::instance());
$wp_siteurl = "https://pdhstar.com";

$mform = new migrate_lp_courses();
if ($mform->is_cancelled()) {
      redirect($CFG->wwwroot.'/my');
}else if ($fromform = $mform->get_data()) {
    $categoryid = $fromform->categoryid;
    $courseid = $fromform->lp_courseid;
    $migartionurl = rtrim($wp_siteurl, '/') . '/wp-json/custom/v1/lpcoursemigration';
    $request_data = [
        'course_id'    => $courseid,
        'moodlewsrestformat'=> 'json'
    ];
    $curl = new curl();
    $response = $curl->post($migartionurl, $request_data);
    $result = json_decode($response);
    if (empty($response)) {
        $errormessage = 'No response from the WordPress migration endpoint.';
        redirect(new moodle_url('/my'), $errormessage, 3, \core\output\notification::NOTIFY_ERROR);
    }
    if (!isset($result->id) || empty($result->title)) {
        $errormessage = 'Invalid or missing data from the migration response.';
        redirect(new moodle_url('/my'), $errormessage, 3, \core\output\notification::NOTIFY_ERROR);
    }

    if ($courseid == $result->id) {
        $coursedata = new stdClass();
        $coursedata->fullname = $result->title;
        $coursedata->category = $categoryid;
        $coursedata->summary = $result->content ?? '';
        $course_id = create_lp_course($coursedata, $editoroptions = NULL);
        if($course_id){
            // add query for store lp course id in lms course
            $context = context_course::instance($course_id);
            $lpcoursefield = $DB->get_record('customfield_field',['shortname' => 'learnpress_courseid'],'*',MUST_EXIST);
            $addcoursedata = new stdClass();
            $addcoursedata->fieldid        = $lpcoursefield->id;
            $addcoursedata->instanceid     = $course_id;
            $addcoursedata->contextid      = $context->id;
            $addcoursedata->charvalue      = $result->id;
            $addcoursedata->value          = $result->id;
            $addcoursedata->valueformat    = 0;
            $addcoursedata->timecreated    = time();
            $addcoursedata->timemodified   = time();
            $DB->insert_record('customfield_data', $addcoursedata);
            //end code
            
            $migratedata = new stdClass();
            $migratedata->lp_courseid = $result->id;
            $migratedata->lp_coursedata = $response;
            $migratedata->courseid = $course_id;
            $migratedata->created_date = time();
            $migrationdata = $DB->insert_record('course_migration', $migratedata);
            if($migrationdata){
                redirect($CFG->wwwroot.'/local/learnpeek_lp_course_migration/migration.php?migrateid='.$migrationdata);
            }else{
                $errormessage = 'Failed to store LearnPress course migration data.';
            }
        }else{
           $errormessage = 'Failed to create the Moodle course.';
        }
    }else{
        $errormessage = 'Course verification failed. Please check the course ID or migration response.';
    }
    if (!empty($errormessage)) {
        redirect(new moodle_url('/my'), $errormessage, 3, \core\output\notification::NOTIFY_ERROR);
    }
}
echo $OUTPUT->header();
$mform->display();



// $learnpress_courses = [
//     'SST Courses' => [
//         ['id' => '', 'name' => 'Select LearnPress Course'],
//         ['id' => 15068, 'name' => '2-Hour Drug and Alcohol Awareness SST-302 - #15068'],
//         ['id' => 17342, 'name' => '2-Hour Drug and Alcohol Awareness SST-302 (Spanish)'],
//         ['id' => 15968, 'name' => '2-Hour Drug and Alcohol Awareness (Russian) SST-302'],
//         ['id' => 17418, 'name' => '8-Hour Fall Prevention SST-307 (Spanish)'],
//         ['id' => 16072, 'name' => '8-Hour Fall Prevention (Russian) SST-307'],
//         ['id' => 36714, 'name' => 'CE-102 Sight Distance Guidelines: 2 PDH'],
//         ['id' => 16634, 'name' => '4-Hour Fall Prevention SST-305'],
//     ],
//     'HAZWOPER' => [
//         ['id' => 19448, 'name' => '40-Hour HAZWOPER Training'],
//         ['id' => 19501, 'name' => '24-Hour HAZWOPER Training'],
//     ],
//     'COMPLIANCE CATEGORY' => [
//         ['id' => 17930, 'name' => 'Bloodborne Pathogens'],
//         ['id' => 17788, 'name' => 'Excavation Safety in Construction (Spanish)'],
//         ['id' => 17746, 'name' => 'Excavation Safety in Construction'],
//         ['id' => 17575, 'name' => 'OSHA Respiratory Protection'],
//         ['id' => 17133, 'name' => '4 Hour OSHA Scaffold Training'],
//         ['id' => 16956, 'name' => '4 Hour Fall Protection for Construction'],
//         ['id' => 16747, 'name' => 'Confined Space Entry for Construction'],
//         ['id' => 17959, 'name' => 'Radiofrequency (RF) Safety Training'],
//         ['id' => 17695, 'name' => 'MEWP: Aerial and Scissor Lifts (Spanish)'],
//         ['id' => 17643, 'name' => 'MEWP: Aerial and Scissor Lifts'],
//     ],
//     'PDF Courses' => [
//         ['id' => 37408, 'name' => 'CE-105 Reducing Wrong-Way Crashes on Freeways: 6PDH - #37408'],
//         ['id' => 36714, 'name' => 'CE-102 Sight Distance Guidelines: 2 PDH - #36714'],
//         ['id' => 14950, 'name' => 'LS-006 Data Acquisition Using LiDAR 1 PDH - #14950'],
//         ['id' => 14903, 'name' => 'OG-013 ExxonMobil Baton Rouge Refinery Disaster: 3 PDH - #14903'],
//         ['id' => 14826, 'name' => 'CE-090 Foundations in Coastal Regions: 3 PDH - #14826'],
//         // ['id' => , 'name' => ' - #'],
        
//     ]
// ];

// $lp_course_names = [];
// foreach ($learnpress_courses as $category => $courses) {
//     foreach ($courses as $course) {
//         if (!empty($course['id'])) {
//             $lp_course_names[$course['id']] = [
//                 'name' => $course['name'],
//                 'category' => $category
//             ];
//         }
//     }
// }

$ssourl = "https://pdhstar.com";

// Fetch grouped LearnPress courses from WordPress API.
$learnpress_courses = get_learnpress_courses_from_wordpress($ssourl);

// Create a lookup array: LearnPress course ID => name and category.
$lp_course_names = [];

foreach ($learnpress_courses as $category => $courses) {

    foreach ($courses as $course) {

        // Skip the default "Select LearnPress Course" option.
        if (empty($course['id'])) {
            continue;
        }

        $courseid = (int) $course['id'];

        $lp_course_names[$courseid] = [
            'name'     => $course['name'],
            'category' => $category,
        ];
    }
}

$migrationdata = $DB->get_records_sql("SELECT cm.id, cm.lp_courseid, cm.courseid, cm.status, c.fullname, c.category as coursecategory FROM {course_migration} as cm INNER JOIN {course} as c ON cm.courseid = c.id ORDER BY lp_courseid ASC");
echo '<div class="bundle_table table-responsive">
    <table id="usetTable" class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>'. get_string('learnpresscorse','local_learnpeek_lp_course_migration').'</th>
                <th>'. get_string('lmscourse','local_learnpeek_lp_course_migration').'</th>
                <th>'. get_string('lmscategory','local_learnpeek_lp_course_migration').'</th>
                <th>'. get_string('migratestatus','local_learnpeek_lp_course_migration').'</th>
            </tr>
        </thead>
        <tbody>';

$count = 1;
foreach ($migrationdata as $row) {
    $lpname = isset($lp_course_names[$row->lp_courseid]['name']) ? $lp_course_names[$row->lp_courseid]['name'] : 'Unknown (#'.$row->lp_courseid.')';
    $lpcategory = isset($lp_course_names[$row->lp_courseid]['category']) ? $lp_course_names[$row->lp_courseid]['category'] : '-';

    // Moodle LMS course name
    $coursename = '-';
    if (!empty($row->courseid)) {
        $courcategory = $DB->get_record('course_categories', ['id' => $row->coursecategory], 'name');

        if ($courcategory) {
            $categoryname = $courcategory->name;
        }
    }

    // Migration status
    $status = ($row->status == 1) ? '<span style="color:green;">'.get_string('completed','local_learnpeek_lp_course_migration').'</span> | <a href=" '.$CFG->wwwroot.'/course/view.php?id='.$row->courseid.'">'.get_string('view','local_learnpeek_lp_course_migration').'</a>' : '<span style="color:red;">'.get_string('pending','local_learnpeek_lp_course_migration').'</span>';

    echo '<tr>
        <td>'.$lpname.' ('.$lpcategory.') </td>
        <td>'.$row->fullname.'</td>
        <td>'.$categoryname.'</td>
        <td>'.$status.'</td>
        
    </tr>';
}

echo '</tbody></table></div>';


?>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/dt/dt-1.10.16/datatables.min.css" />
<script type="text/javascript" src="https://cdn.datatables.net/v/dt/dt-1.10.16/datatables.min.js"></script>
<script>
    $(document).ready(function() {
        $('#usetTable').DataTable();
    });
</script>
<?php
//custom tbale mdl_course_migration,mdl_sso_validation
echo $OUTPUT->footer(); 
?>



