<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * print the single entries
 *
 * @author Andreas Grabs
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_courserating
 */

require_once("../../config.php");
require_once("lib.php");

////////////////////////////////////////////////////////
//get the params
////////////////////////////////////////////////////////
$id = required_param('id', PARAM_INT);
$userid = optional_param('userid', false, PARAM_INT);
$showcompleted = optional_param('showcompleted', false, PARAM_INT);
$deleteid = optional_param('delete', null, PARAM_INT);
$courseid = optional_param('courseid', null, PARAM_INT);

////////////////////////////////////////////////////////
//get the objects
////////////////////////////////////////////////////////

list($course, $cm) = get_course_and_cm_from_cmid($id, 'courserating');

$baseurl = new moodle_url('/mod/courserating/show_entries.php', array('id' => $cm->id));
$PAGE->set_url(new moodle_url($baseurl, array('userid' => $userid, 'showcompleted' => $showcompleted,
        'delete' => $deleteid)));
$context = context_module::instance($cm->id);

require_login($course, true, $cm);
$courserating = $PAGE->activityrecord;

require_capability('mod/courserating:viewreports', $context);

$actionbar = new \mod_courserating\output\responses_action_bar($cm->id, $baseurl);

if ($deleteid) {
    // This is a request to delete a reponse.
    require_capability('mod/courserating:deletesubmissions', $context);
    require_sesskey();
    $courseratingstructure = new mod_courserating_completion($courserating, $cm, 0, true, $deleteid);
    courserating_delete_completed($courseratingstructure->get_completed(), $courserating, $cm);
    redirect($baseurl);
} else if ($showcompleted || $userid) {
    // Viewing individual response.
    $courseratingstructure = new mod_courserating_completion($courserating, $cm, 0, true, $showcompleted, $userid);
} else {
    // Viewing list of reponses.
    $courseratingstructure = new mod_courserating_structure($courserating, $cm, $courseid);
}

$responsestable = new mod_courserating_responses_table($courseratingstructure);
$anonresponsestable = new mod_courserating_responses_anon_table($courseratingstructure);

if ($responsestable->is_downloading()) {
    $responsestable->download();
}
if ($anonresponsestable->is_downloading()) {
    $anonresponsestable->download();
}

// Process course select form.
$courseselectform = new mod_courserating_course_select_form($baseurl, $courseratingstructure, $courserating->course == SITEID);
if ($data = $courseselectform->get_data()) {
    redirect(new moodle_url($baseurl, ['courseid' => $data->courseid]));
}
// Print the page header.
navigation_node::override_active_url($baseurl);
$PAGE->set_heading($course->fullname);
$PAGE->set_title($courserating->name);
$PAGE->activityheader->set_attrs([
    'hidecompletion' => true,
    'description' => ''
]);
$PAGE->add_body_class('limitedwidth');
echo $OUTPUT->header();

/** @var \mod_courserating\output\renderer $renderer */
$renderer = $PAGE->get_renderer('mod_courserating');
echo $renderer->main_action_bar($actionbar);
echo $OUTPUT->heading(get_string('show_entries', 'mod_courserating'), 3);

$current_tab = 'showentries';

/// Print the main part of the page
///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////

if ($userid || $showcompleted) {
    // Print the response of the given user.
    $completedrecord = $courseratingstructure->get_completed();

    if ($userid) {
        $usr = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);
        $responsetitle = userdate($completedrecord->timemodified) . ' (' . fullname($usr) . ')';
    } else {
        $responsetitle = get_string('response_nr', 'courserating') . ': ' .
                $completedrecord->random_response . ' (' . get_string('anonymous', 'courserating') . ')';
    }

    echo $OUTPUT->heading($responsetitle, 4);

    $form = new mod_courserating_complete_form(mod_courserating_complete_form::MODE_VIEW_RESPONSE,
            $courseratingstructure, 'courserating_viewresponse_form');
    $form->display();

    list($prevresponseurl, $returnurl, $nextresponseurl) = $userid ?
            $responsestable->get_reponse_navigation_links($completedrecord) :
            $anonresponsestable->get_reponse_navigation_links($completedrecord);

    echo html_writer::start_div('response_navigation');

    $responsenavigation = [
        'col1content' => '',
        'col2content' => html_writer::link($returnurl, get_string('back'), ['class' => 'back_to_list']),
        'col3content' => '',
    ];

    if ($prevresponseurl) {
        $responsenavigation['col1content'] = html_writer::link($prevresponseurl, get_string('prev'), ['class' => 'prev_response']);
    }

    if ($nextresponseurl) {
        $responsenavigation['col3content'] = html_writer::link($nextresponseurl, get_string('next'), ['class' => 'next_response']);
    }

    echo $OUTPUT->render_from_template('core/columns-1to1to1', $responsenavigation);
    echo html_writer::end_div();

} else {
    // Print the list of responses.
    $courseselectform->display();

    // Show non-anonymous responses (always retrieve them even if current courserating is anonymous).
    $totalrows = $responsestable->get_total_responses_count();
    if (!$courseratingstructure->is_anonymous() || $totalrows) {
        echo $OUTPUT->heading(get_string('non_anonymous_entries', 'courserating', $totalrows), 4);
        $responsestable->display();
    }

    // Show anonymous responses (always retrieve them even if current courserating is not anonymous).
    $courseratingstructure->shuffle_anonym_responses();
    $totalrows = $anonresponsestable->get_total_responses_count();
    if ($courseratingstructure->is_anonymous() || $totalrows) {
        echo $OUTPUT->heading(get_string('anonymous_entries', 'courserating', $totalrows), 4);
        $anonresponsestable->display();
    }

}

// Finish the page.
echo $OUTPUT->footer();
