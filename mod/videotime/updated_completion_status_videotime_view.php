<?php
define('AJAX_SCRIPT', true);

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$id = optional_param('cmid', 0, PARAM_INT);

if (!$id) {
    throw new moodle_exception('missing cmid');
}

use mod_videotime\output\next_activity_button;
use mod_videotime\videotime_instance;

$cm = get_coursemodule_from_id('videotime', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$moduleinstance = $DB->get_record('videotime', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
require_sesskey();
$context = context_module::instance($cm->id);
require_capability('mod/videotime:view', $context);

$moduleinstance = \mod_videotime\videotime_instance::instance_by_id($moduleinstance->id);

// Mark the activity as complete.
completion_status_videotime_view($moduleinstance, $course, $cm, $context);

$buttonhtml = '';
$nextcm = false;
$nextcmid = null;

// Find the current activity's section.
$section = $DB->get_record_sql(
    "SELECT id, section, sequence
       FROM {course_sections}
      WHERE course = ?
        AND FIND_IN_SET(?, sequence) > 0",
    [$course->id, $cm->id]
);

if ($section) {
    $sequence = array_values(array_filter(
        array_map('intval', explode(',', $section->sequence))
    ));

    $index = array_search((int)$cm->id, $sequence, true);

    // Case 1: Find the next activity in the same section.
    if ($index !== false && isset($sequence[$index + 1])) {
        $nextcmid = (int)$sequence[$index + 1];

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

    // Case 2: Current activity is the last one in this section
    if (!$nextcm) {
        $nextsections = $DB->get_records_sql(
            "SELECT section, sequence
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

            foreach ($nextsequence as $candidatecmid) {
                $candidate = $DB->get_record_sql(
                    "SELECT cm.id, cm.instance, cm.module, m.name AS modname
                       FROM {course_modules} cm
                       JOIN {modules} m ON m.id = cm.module
                      WHERE cm.id = ?
                        AND cm.course = ?
                        AND cm.deletioninprogress = 0",
                    [$candidatecmid, $course->id]
                );

                if ($candidate) {
                    $nextcm = $candidate;
                    break 2;
                }
            }
        }
    }
}

if ($nextcm) {
    $nextcmid = (int)$nextcm->id;
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


header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'buttonhtml' => $buttonhtml,
    'nextcmid' => $nextcm ? $nextcm->id : null
]);