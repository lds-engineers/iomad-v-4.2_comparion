<?php
require('../../config.php');

require_login();

global $DB, $PAGE, $OUTPUT, $USER, $CFG;

$context = context_system::instance();
require_capability('block/shared_courses:addinstance', $context);

$PAGE->set_url('/blocks/shared_courses/manage.php');
$PAGE->set_context($context);
$pagetitle = get_string('pluginname', 'block_shared_courses');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);

require_once($CFG->dirroot.'/blocks/shared_courses/classes/form/shared_courses_form.php');

$editid = optional_param('edit', 0, PARAM_INT);
$add    = optional_param('add', 0, PARAM_INT);

$record = null;

// If Edit clicked → fetch record
if ($editid) {
    $record = $DB->get_record('shared_courses', ['id' => $editid], '*', MUST_EXIST);
}

$customdata = [
    'editmode' => $editid ? true : false
];

$mform = new \shared_courses\form\shared_courses_form(null, $customdata);

// Prefill form if editing
if ($record) {
    $mform->set_data($record);
}

// Form Submitted
if ($mform->is_cancelled()) {
    redirect($PAGE->url);

} else if ($data = $mform->get_data()) {

    // if (!empty($data->id)) {
    if ($DB->record_exists('shared_courses', ['id' => $data->id])){

        // UPDATE RECORD
        $update = new stdClass();
        $update->id = $data->id;
        $update->course_id = $data->course_id;
        $update->idps_entityid = $data->idps_entityid;
        $update->status = $data->status;
        $update->updated_at = time();

        // Regenerate access link
        $accessurl = new moodle_url('/blocks/shared_courses/access.php', [
            'id' => $data->id
        ]);

        $update->shared_link = $accessurl->out(false);

        $DB->update_record('shared_courses', $update);

    } else {

        // INSERT RECORD
        $insert = new stdClass();
        $insert->course_id = $data->course_id;
        $insert->status = $data->status;
        $insert->user_id = $USER->id;
        $insert->idps_entityid = $data->idps_entityid;
        $insert->created_at = time();
        $insert->updated_at = time();

        $recordid = $DB->insert_record('shared_courses', $insert);

        // Generate access link
        $accessurl = new moodle_url('/blocks/shared_courses/access.php', [
            'id' => $recordid
        ]);

        // Store link
        $DB->update_record('shared_courses', [
            'id' => $recordid,
            'shared_link' => $accessurl->out(false)
        ]);
    }

    redirect($PAGE->url);
}

echo $OUTPUT->header();

// Add Button

echo '<div class = "d-flex justify-content-between">';
echo html_writer::link(
    new moodle_url('/blocks/shared_courses/manage.php', ['add' => 1]),
    get_string('addsharedcourse', 'block_shared_courses'),
    ['class' => 'btn btn-primary mb-3']
);

echo html_writer::link(
    new moodle_url('/blocks/shared_courses/course_reports.php'),
    get_string('coursereports', 'block_shared_courses'),
    ['class' => 'btn btn-primary mb-3 mx-2']
);
echo '</div>';

// Show form if Add OR Edit
if ($add || $editid) {
    $mform->display();
}

// List Table
$records = $DB->get_records('shared_courses');

if ($records) {

    $table = new html_table();
    $table->head = [
        get_string('coursename', 'block_shared_courses'),
        get_string('identityprovider', 'block_shared_courses'),
        get_string('status', 'block_shared_courses'),
        get_string('sharedurl', 'block_shared_courses'),
        get_string('action', 'block_shared_courses')
    ];

    foreach ($records as $rec) {

        $course = $DB->get_record('course', ['id' => $rec->course_id]);
        $identity_provider = $DB->get_record('auth_iomadsaml2_idps', ['entityid' => $rec->idps_entityid]);

        $status = $rec->status ? 'Active' : 'Inactive';

        $editurl = new moodle_url('/blocks/shared_courses/manage.php', [
            'edit' => $rec->id
        ]);

        $sharedurl = s($rec->shared_link);

        // Click-to-copy element
        $copylink = '<span class="copy-url" 
                        data-url="'.$sharedurl.'" 
                        style="cursor:pointer; color:#0d6efd; text-decoration:underline;">
                        '.$sharedurl.'
                     </span>';

        $table->data[] = [
            $course ? $course->fullname : 'Course not found',
            $identity_provider ? $identity_provider->displayname : 'Identity provider not found',
            $status,
            $copylink,
            html_writer::link($editurl, get_string('edit','block_shared_courses'), ['class' => 'btn btn-secondary btn-sm'])
        ];
    }

    echo html_writer::table($table);
}

// 🔥 Clipboard JS
$PAGE->requires->js_init_code("
    document.querySelectorAll('.copy-url').forEach(function(el) {

        // Create tooltip element
        var tooltip = document.createElement('span');
        tooltip.innerText = 'Copied!';
        tooltip.style.position = 'absolute';
        tooltip.style.background = '#28a745';
        tooltip.style.color = '#fff';
        tooltip.style.padding = '4px 8px';
        tooltip.style.borderRadius = '4px';
        tooltip.style.fontSize = '12px';
        tooltip.style.display = 'none';
        tooltip.style.zIndex = '9999';

        document.body.appendChild(tooltip);

        el.addEventListener('click', function(e) {

            var url = this.getAttribute('data-url');

            navigator.clipboard.writeText(url).then(function() {

                // Position tooltip near clicked element
                var rect = el.getBoundingClientRect();
                tooltip.style.left = (rect.left + window.scrollX) + 'px';
                tooltip.style.top = (rect.top + window.scrollY - 30) + 'px';

                tooltip.style.display = 'block';

                // Hide after 2 seconds
                setTimeout(function() {
                    tooltip.style.display = 'none';
                }, 2000);
            });
        });
    });
");

echo $OUTPUT->footer();