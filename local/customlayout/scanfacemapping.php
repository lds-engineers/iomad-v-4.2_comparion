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
// redirect(new moodle_url($CFG->wwwroot . '/my'), 'This functinality is currently desabled', null, \core\output\notification::NOTIFY_ERROR);

function all_mycourse_get_all_category_ids($parentids, &$children = []) {
    global $DB;

    // Ensure input is always an array
    if (!is_array($parentids)) {
        $parentids = [$parentids];
    }

    foreach ($parentids as $parentid) {
        // Avoid duplicates
        if (!in_array($parentid, $children)) {
            $children[] = $parentid;
        }

        // Get immediate children
        $childcategories = $DB->get_records(
            'course_categories',
            ['parent' => $parentid],
            '',
            'id'
        );

        foreach ($childcategories as $child) {
            if (!in_array($child->id, $children)) {
                $children[] = $child->id;

                // Recursive call for each child
                all_mycourse_get_all_category_ids($child->id, $children);
            }
        }
    }

    return $children;
}

if(!is_siteadmin()){
    redirect(new moodle_url($CFG->wwwroot . '/my'), 'Access denied', null, \core\output\notification::NOTIFY_ERROR);
    die;
}
$scanfaceids = $_POST['scanface'];
if (isset($_POST['submit']) && !empty($scanfaceids)) {
    global $DB, $USER;
    $courseids = array_keys($scanfaceids);
    require_once($CFG->libdir . '/moodlelib.php');
    $customfield = $DB->get_record('customfield_field', ['shortname' => 'scanface_required'], '*', IGNORE_MISSING);
    if (!$customfield) {
        throw new moodle_exception('Custom field "scanface_required" not found');
    }

    $fieldid = $customfield->id;
    $now = time();

    $all_courses = $DB->get_records_select('course', 'visible = 1 AND category > 0 AND id IN('.implode(",", $courseids).')', null, 'fullname ASC');

    $transaction = $DB->start_delegated_transaction();
    $targetcategories = [40, 50, 51];
    $allcategories = all_mycourse_get_all_category_ids($targetcategories);
    $disabledcourses = [];
    foreach ($all_courses as $course) {
        $courseid = $course->id;
        $isenabled = $scanfaceids[$courseid];
        $contextid = context_course::instance($courseid)->id;
        $mapping = [
            'courseid' => $courseid
        ];
        if($exists = $DB->get_record('lcl_scan_face_mapping', $mapping)){
            $exists->modifiedby = $USER->id;
            $exists->isenabled = $isenabled;
            $exists->timemodified = $now;
            $DB->update_record('lcl_scan_face_mapping', $exists);
        } else {
            $mapping['createdby'] = $USER->id;
            $mapping['timecreated'] = $now;
            $mapping['isenabled'] = $isenabled;
            $DB->insert_record('lcl_scan_face_mapping', $mapping);
        }
        $existing_cf = $DB->get_record('customfield_data', [
            'instanceid' => $courseid,
            'fieldid' => $fieldid
        ]);
        if ($existing_cf) {
            $existing_cf->intvalue = $isenabled;
            $existing_cf->value = $isenabled;
            $existing_cf->contextid = $contextid;
            $existing_cf->timemodified = $now;
            $upd = $DB->update_record('customfield_data', $existing_cf);
        } else {
            $existing_cf = new stdClass();
            $existing_cf->instanceid = $courseid;
            $existing_cf->fieldid = $fieldid;
            $existing_cf->intvalue = $isenabled;
            $existing_cf->value = $isenabled;
            $existing_cf->valueformat = 1;
            $existing_cf->contextid = $contextid;
            $existing_cf->timecreated = $now;
            $existing_cf->timemodified = $now;
            $DB->insert_record('customfield_data', $existing_cf);
        }

        if (in_array($course->category, $allcategories) && $isenabled == 0) {
            $disabledcourses[] = $course->fullname;
        }
    }
    $transaction->allow_commit();
    if (!empty($disabledcourses)) {
        $subject = "Scanface Disabled Courses Alert";
        $messagehtml = "<p>Scan face disabled course details are mentioned below:</p>";
        $i = 1;
        foreach ($disabledcourses as $coursename) {
            $messagehtml .= $i . ". " . $coursename . "<br>";
            $i++;
        }
        $userfrom = \core_user::get_noreply_user();
        $emailuser = (object) [];
        $emailuser->email = "contact@learnpeek.com";
        $emailuser->firstname = 'Admin';
        $emailuser->lastname = '';
        $emailuser->maildisplay = true;
        $emailuser->mailformat = 1; // 0 (zero) text-only emails, 1 (one) for HTML/Text emails.
        $emailuser->id = 1;
        $emailuser->firstnamephonetic = false;
        $emailuser->lastnamephonetic = false;
        $emailuser->middlename = false;
        $emailuser->username = false;
        $emailuser->alternatename = false;
        email_learnpeek_to_user($emailuser, $userfrom, $subject, $message = '',$messagehtml);
        $emailuser->email = "suneet@lds-international.in";
        email_learnpeek_to_user($emailuser, $userfrom, $subject, $message = '',$messagehtml);
    }
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
                <input type="hidden" name="scanface[<?php echo $course->id; ?>]" value="0"/>
                <input type="checkbox" name="scanface[<?php echo $course->id; ?>]" value="1" <?php echo $is_enabled ? 'checked' : ''; ?> />
            </td>
        </tr>
        <?php
    }
}

        ?>
        </tbody>
    </table>
    <div style="padding:10px 15px; margin:10px 15px; background:#e7f3ff; color:#0c5460; border:1px solid #b8daff; border-radius:5px;">
        Only the records currently visible (based on filters/pagination) will been updated.
    </div>
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