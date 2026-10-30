<?php
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/lib/enrollib.php');
require_once($CFG->dirroot . '/blocks/iomad_company_admin/lib.php');

global $DB, $USER;
require_login();
$PAGE->set_context(context_system::instance());
$context = context_system::instance();
$PAGE->set_context($context);
$companyid = iomad::get_my_companyid(context_system::instance());

$pagetitle = get_string('scanfacecourse','local_customlayout');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);
$PAGE->requires->jquery();
$PAGE->requires->css(new moodle_url('https://cdn.datatables.net/1.10.16/css/jquery.dataTables.min.css'));
redirect(new moodle_url($CFG->wwwroot . '/my'), 'This functinality is currently desabled', null, \core\output\notification::NOTIFY_ERROR);
if(!is_siteadmin()){
    redirect(new moodle_url($CFG->wwwroot . '/my'), 'Access denied', null, \core\output\notification::NOTIFY_ERROR);
    die;
}
if (isset($_POST['submit'])) {
    global $DB, $USER;
    require_once($CFG->libdir . '/moodlelib.php');
    $customfield = $DB->get_record('customfield_field', ['shortname' => 'scanface_required'], '*', IGNORE_MISSING);
    if (!$customfield) {
        throw new moodle_exception('Custom field "scanface_required" not found');
    }

    $fieldid = $customfield->id;
    $now = time();
    $all_courses = $DB->get_records_select('course', 'visible = 1 AND category > 0', null, 'fullname ASC');
    $checked_ids = isset($_POST['scanface']) ? array_map('intval', array_keys($_POST['scanface'])) : [];

    $transaction = $DB->start_delegated_transaction();
    $DB->delete_records('lcl_scan_face_mapping');

    foreach ($all_courses as $course) {
        $courseid = $course->id;
        $isenabled = in_array($courseid, $checked_ids) ? 1 : 0;
        $contextid = context_course::instance($courseid)->id;
        $mapping = (object)[
            'courseid' => $courseid,
            'isenabled' => $isenabled,
            'createdby' => $USER->id,
            'modifiedby' => $USER->id,
            'timecreated' => $now,
            'timemodified' => $now
        ];
        $DB->insert_record('lcl_scan_face_mapping', $mapping);

        $existing_cf = $DB->get_record('customfield_data', [
            'instanceid' => $courseid,
            'fieldid' => $fieldid
        ]);

        if ($existing_cf) {
            $existing_cf->id = $existing_cf->id;
            $existing_cf->intvalue = $isenabled;
            $existing_cf->value = $isenabled;
            $existing_cf->contextid = $contextid;
            $existing_cf->timemodified = $now;
            $upd = $DB->update_record('customfield_data', $existing_cf);
        }
    }

    $transaction->allow_commit();
    redirect(
        new moodle_url('/local/customlayout/scanfacemapping.php'),
        'Scanface mappings saved successfully.',
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
$courses = $DB->get_records_select('course', 'visible = 1 AND category > 0', null, 'fullname ASC');
?>
<div class="bundle_table table-responsive">
	<!-- <div align="left"><h3><?php echo $pagetitle; ?></h3></div> -->
	<form action="" method="post">
	<table id="usetTable" class="table" border="1">
		<thead> 
			<tr> 
				<th><?php echo get_string('slno','local_customlayout')?></th>
				<th><?php echo get_string('category','local_customlayout')?></th>
				<th><?php echo get_string('coursename','local_customlayout')?> </th>
                <th><?php echo get_string('enabledisable','local_customlayout')?></th>
			</tr>
		</thead>
		<tbody>
		<?php  
if (sizeof($courses) > 0) {
    $i = 1;
    foreach ($courses as $key => $course) {
        $is_enabled = false;

        // Check lcl_scan_face_mapping
        if ($DB->record_exists("lcl_scan_face_mapping", ["courseid" => $course->id])) {
            $mapping = $DB->get_record("lcl_scan_face_mapping", ["courseid" => $course->id]);
            if (!empty($mapping->isenabled)) {
                $is_enabled = true;
            }
        }

        // Check custom field value if not enabled via mapping
        if (!$is_enabled) {
            $sql = "SELECT cfd.value
                    FROM {customfield_data} cfd
                    JOIN {customfield_field} cf ON cf.id = cfd.fieldid
                    WHERE cfd.instanceid = :courseid AND cf.shortname = :fieldname";
            $params = ['courseid' => $course->id, 'fieldname' => 'scanface_required'];
            $customfield = $DB->get_record_sql($sql, $params);
            if (!empty($customfield->value) && $customfield->value == 1) {
                $is_enabled = true;
            }
        }

        // Get category name
        $category = null;
        if ($course->category) {
            $category = $DB->get_record("course_categories", ["id" => $course->category]);
        }
        ?>
        <tr>
            <td><?php echo $i++; ?></td>
            <td><?php echo $category ? $category->name : ''; ?></td>
            <td><?php echo $course->fullname; ?></td>
            <td>
                <input type="checkbox" name="scanface[<?php echo $course->id; ?>]" value="1" <?php echo $is_enabled ? 'checked' : ''; ?> />
            </td>
        </tr>
        <?php
    }
}

		?>
		</tbody>
	</table>
	<input type="submit" value=<?php echo get_string('save', 'local_customlayout')?> name="submit" class="btn btn-primary">
	</form>
</div>
<style>
.bundle_table table {
    width: 100%;
}
.table_heading {
    color: #fff;
    font-size: 30px;
    text-align: center;
    margin: 10px 0;
    background: #3376d2;
}
.bundle_table{
	margin-top:50px;
}
.bundle_table table tr th,.bundle_table table tr td{
    padding:6px 8px; 
}
.bundle_table table{
	border:1px solid #ddd;
}
</style> 
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
            setTimeout(initDataTable, 300);
        }
    }
    initDataTable();
});
</script>
<?php  echo $OUTPUT->footer(); ?>