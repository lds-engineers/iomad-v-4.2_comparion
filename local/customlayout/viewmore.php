<?php
require_once('../../config.php');
global $DB,$CFG,$PAGE;

$userid = required_param("id",PARAM_INT);
$courses = [];

if ($userid) {
    $udata = $DB->get_record("user", array("id" => $userid));
	// $courseids = explode(",", $courseids);
    // list($sqlin, $params) = $DB->get_in_or_equal($courseids, SQL_PARAMS_QM);
    // $courses = $DB->get_records_sql("SELECT * FROM {course} WHERE id $sqlin", $params);
    require_once($CFG->dirroot . '/lib/enrollib.php');
    $courses = enrol_get_users_courses($userid);
    require_once($CFG->libdir . '/completionlib.php');
    foreach ($courses as $cid => $course) {
        $completion = new completion_info($course);
        if ($completion->is_enabled()) {
            $iscomplete = $completion->is_course_complete($userid);
            $courses[$cid]->completionstatus = $iscomplete ? 'Completed' : 'Enrolled';
        } else {
            $courses[$cid]->completionstatus = 'Completion disabled';
        }
    }
}

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url($CFG->wwwroot.'/local/customlayout/viewmore.php');
$PAGE->set_title('Course Info');
$PAGE->requires->css(new moodle_url('https://cdn.datatables.net/1.10.16/css/jquery.dataTables.min.css'));

$heading = $udata->firstname." ".$udata->lastname." - Course Info";
$PAGE->set_heading($heading);

echo $OUTPUT->header();
$html = '<table class="table" id="usetTable" border="1">
			<thead>
				<tr>
					<th>Course Name</th>
					<th>Course Status</th>
				</tr>
			</thead>
			<tbody>';
			foreach ($courses as $course) {
				$html .= "<tr>
					<td>$course->fullname</td>
					<td>$course->completionstatus</td>
				</tr>";
			}
	$html .= '</tbody>
		</table>';
echo $html;

?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    var tries = 0;
    function initDataTable() {
        if (typeof $.fn.DataTable !== "undefined") {
            $("#usetTable").DataTable();
        } else if (tries < 10) {
            tries++;
            setTimeout(initDataTable, 300); // Try again in 300ms
        }
    }
    initDataTable();
});
</script>

<?php echo $OUTPUT->footer(); ?>