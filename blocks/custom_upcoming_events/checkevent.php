<?php
require('../../config.php');

global $DB, $CFG, $USER;

require_once($CFG->dirroot . '/lib/enrollib.php');
require_once($CFG->dirroot . '/calendar/lib.php');
require_once($CFG->dirroot . '/calendar/externallib.php');

use core_calendar_external;

// Set up the page
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/blocks/custom_upcoming_events/checkevent.php');
$PAGE->set_title('Upcoming Events');
$PAGE->set_heading('Upcoming Events');

// Get data before sending output
$data = enrol_get_users_courses($USER->id);
$evts = [];

foreach ($data as $course) {
    // This call indirectly uses moodle_page functions — must be done before output starts
    $events = core_calendar_external::get_calendar_upcoming_view($course->id, $course->category);
    foreach ($events->events as $event) {
        $evts[] = $event;
    }
}

// Now it's safe to start output
echo $OUTPUT->header();

echo "<pre>";
print_r($evts);
echo "</pre>";

echo $OUTPUT->footer();
