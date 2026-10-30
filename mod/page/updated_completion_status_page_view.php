<?php
define('AJAX_SCRIPT', true);

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

require_login();

$cmid = required_param('cmid', PARAM_INT);

$cm = get_coursemodule_from_id(
    'page',
    $cmid,
    0,
    false,
    MUST_EXIST
);

$course = $DB->get_record(
    'course',
    ['id' => $cm->course],
    '*',
    MUST_EXIST
);

$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_capability('mod/page:view', $context);
require_sesskey();

// Load the Page activity record.
$page = $DB->get_record(
    'page',
    ['id' => $cm->instance],
    '*',
    MUST_EXIST
);

// Call function to complete activity
page_view(
    $page,
    $course,
    $cm,
    $context
);


$buttonhtml = '';
$id = (int) $cmid;

// Find the section containing the current activity.
$section = $DB->get_record_sql(
    "SELECT id, section, sequence
       FROM {course_sections}
      WHERE course = ?
        AND FIND_IN_SET(?, sequence) > 0",
    [$course->id, $id]
);

$nextcm = false;

if ($section) {
    $sequence = array_values(array_filter(
        array_map('intval', explode(',', $section->sequence))
    ));

    $index = array_search($id, $sequence, true);

    // First, check for the next activity in the same section.
    if ($index !== false && isset($sequence[$index + 1])) {
        $nextcmid = $sequence[$index + 1];

        $nextcm = $DB->get_record_sql(
            "SELECT cm.id, cm.instance, cm.module, m.name AS modname
               FROM {course_modules} cm
               JOIN {modules} m ON m.id = cm.module
              WHERE cm.id = ?
                AND cm.course = ?
                AND cm.deletioninprogress = 0",
            [$nextcmid, $course->id]
        );
    }

    // If there is no next activity in this section,
    // search subsequent sections.
    if (!$nextcm) {
        $nextsections = $DB->get_records_sql(
            "SELECT id, section, sequence
               FROM {course_sections}
              WHERE course = ?
                AND section > ?
              ORDER BY section ASC",
            [$course->id, $section->section]
        );

        foreach ($nextsections as $nextsection) {
            $nextsequence = array_values(array_filter(
                array_map('intval', explode(',', $nextsection->sequence))
            ));

            foreach ($nextsequence as $nextcmid) {
                $candidate = $DB->get_record_sql(
                    "SELECT cm.id, cm.instance, cm.module, m.name AS modname
                       FROM {course_modules} cm
                       JOIN {modules} m ON m.id = cm.module
                      WHERE cm.id = ?
                        AND cm.course = ?
                        AND cm.deletioninprogress = 0",
                    [$nextcmid, $course->id]
                );

                if ($candidate) {
                    $nextcm = $candidate;
                    break 2;
                }
            }
        }
    }
}

// Build the next activity button.
if ($nextcm) {
    $nextinstancename = $DB->get_field(
        $nextcm->modname,
        'name',
        ['id' => $nextcm->instance]
    );

    if ($nextinstancename !== false) {
        $nexturl = new moodle_url(
            "/mod/{$nextcm->modname}/view.php",
            ['id' => $nextcm->id]
        );

        $buttonhtml = '
            <div class="text">
                <span class="nav-guide">Next activity</span>
                <a href="' . s($nexturl->out(false)) . '"
                   id="next-activity-link"
                   class="btn btn-link">' .
                   format_string($nextinstancename) .
                '</a>
            </div>
            <div class="rarrow">
                <i class="icon-arrow-right"></i>
            </div>';
    }
}

echo json_encode([
    'status' => 'success',
    'buttonhtml' => $buttonhtml,
    'nextcmid' => $nextcm ? $nextcm->id : null
]);