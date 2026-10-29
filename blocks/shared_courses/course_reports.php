<?php
require('../../config.php');
require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->dirroot . '/blocks/shared_courses/course_report_table.php');
use core_completion\progress;

require_login();

global $DB, $PAGE, $OUTPUT, $USER;

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/blocks/shared_courses/course_reports.php');
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('sharedcoursereport','block_shared_courses'));
$PAGE->set_heading(get_string('sharedcoursereport','block_shared_courses'));


//  FILTER VALUES
$entityid = optional_param('entityid', '', PARAM_TEXT);
$startdate = optional_param('startdate', '', PARAM_TEXT);
$enddate   = optional_param('enddate', '', PARAM_TEXT);
$download  = optional_param('download', '', PARAM_ALPHA);


//  ENTITY DROPDOWN DATA
$entities = $DB->get_records('auth_iomadsaml2_idps'); // adjust table if needed


$table = new course_report_table('shared_course_report');
$table->is_downloading($download, 'shared_course_report', 'shared_course_report');


//  PAGE HEADER + FILTER UI
if (!$table->is_downloading()) {
    echo $OUTPUT->header();

    echo html_writer::link(
        new moodle_url('/blocks/shared_courses/manage.php'),
        get_string('back','block_shared_courses'),
        ['class' => 'btn btn-primary mb-3']
    );

    echo '<form method="post" class="mb-3">';

    echo '<label><b>'.get_string('entity','block_shared_courses').'</b></label> ';
    echo '<select name="entityid" required>';
    echo '<option value="">'.get_string('selectentity','block_shared_courses').'</option>';

    foreach ($entities as $e) {
        $selected = ($entityid == $e->entityid) ? 'selected' : '';
        echo "<option value='$e->entityid' $selected>$e->displayname</option>";
    }

    echo '</select> ';

    echo '<label><b>'.get_string('startdate','block_shared_courses').'</b></label> ';
    echo "<input type='date' name='startdate' value='$startdate'> ";

    echo '<label><b>'.get_string('enddate','block_shared_courses').'</b></label> ';
    echo "<input type='date' name='enddate' value='$enddate'> ";

    echo '<button type="submit">'.get_string('filter','block_shared_courses').'</button>';

    echo '</form>';
}


//  DATE LOGIC
$currenttime = time();

if (!empty($startdate) && !empty($enddate)) {
    $starttime = strtotime($startdate);
    $endtime   = strtotime($enddate . ' 23:59:59');

} else if (!empty($startdate)) {
    $starttime = strtotime($startdate);
    $endtime   = $currenttime;

} else if (!empty($enddate)) {
    $endtime   = strtotime($enddate);
    $starttime = $endtime - 86400;

} else {
    $starttime = $currenttime - 86400;
    $endtime   = $currenttime;
}


//  SQL
$select = "
scel.id,
u.id as userid,
u.firstname,
u.lastname,
u.email,
c.id as courseid,
c.fullname as coursename,
cc.timecompleted,
gg.finalgrade as score
";

$from = "
{shared_courses_enrol_logs} scel
JOIN {user} u ON u.id = scel.userid
JOIN {course} c ON c.id = scel.courseid
LEFT JOIN {course_completions} cc 
    ON cc.course = c.id AND cc.userid = u.id
LEFT JOIN {grade_items} gi 
    ON gi.courseid = c.id AND gi.itemtype = 'course'
LEFT JOIN {grade_grades} gg 
    ON gg.itemid = gi.id AND gg.userid = u.id
";

$where = "u.deleted = 0";
$params = [];


//  FILTERS
if (!empty($entityid)) {
    $where .= " AND scel.idp_entityid = :entityid";
    $params['entityid'] = $entityid;
}

$where .= " AND (cc.timecompleted BETWEEN :start AND :end OR cc.timecompleted IS NULL)";
$params['start'] = $starttime;
$params['end']   = $endtime;


//  SET TABLE SQL
$table->set_sql($select, $from, $where, $params);


//  BASE URL (KEEP FILTERS)
$baseurl = new moodle_url('/blocks/shared_courses/course_reports.php', [
    'entityid' => $entityid,
    'startdate' => $startdate,
    'enddate' => $enddate
]);

$table->define_baseurl($baseurl);


//  OUTPUT
$table->out(20, true);


if (!$table->is_downloading()) {
    echo "<style>
      select[name='entityid'] {
        padding: 7px;
        margin: 0 5px;
        border-radius: 5px;
      }
      input[type='date'] {
        padding: 5px;
        margin: 0 5px;
        border-radius: 5px;
      }

      button[type='submit'] {
        padding: 7px 33px;
        border-radius: 5px;
        border: 0;
    }
    </style>";
    
    echo $OUTPUT->footer();
}