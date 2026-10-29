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
 * print a printview of courserating-items
 *
 * @author Andreas Grabs
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_courserating
 */

require_once("../../config.php");
require_once("lib.php");

$id = required_param('id', PARAM_INT);
$courseid = optional_param('courseid', false, PARAM_INT); // Course where this courserating is mapped to - used for return link.

$PAGE->set_url('/mod/courserating/print.php', array('id'=>$id));

list($course, $cm) = get_course_and_cm_from_cmid($id, 'courserating');
require_course_login($course, true, $cm);

// This page should be only displayed to users with capability to edit or view reports (to include non-editing teachers too).
$context = context_module::instance($cm->id);
$capabilities = [
    'mod/courserating:edititems',
    'mod/courserating:viewreports',
];
if (!has_any_capability($capabilities, $context)) {
    $capability = 'mod/courserating:edititems';
    if (has_capability($capability, $context)) {
        $capability = 'mod/courserating:viewreports';
    }
    throw new required_capability_exception($context, $capability, 'nopermissions', '');
}

$courserating = $PAGE->activityrecord;
$courseratingstructure = new mod_courserating_structure($courserating, $cm, $courseid);

$PAGE->set_pagelayout('popup');

// Print the page header.
$strcourseratings = get_string("modulenameplural", "courserating");
$strcourserating  = get_string("modulename", "courserating");

$courserating_url = new moodle_url('/mod/courserating/index.php', array('id'=>$course->id));
$PAGE->navbar->add($strcourseratings, $courserating_url);
$PAGE->navbar->add(format_string($courserating->name));

$PAGE->set_title($courserating->name);
$PAGE->set_heading($course->fullname);
$PAGE->activityheader->set_title(format_string($courserating->name));
echo $OUTPUT->header();

$continueurl = new moodle_url('/mod/courserating/view.php', array('id' => $id));
if ($courseid) {
    $continueurl->param('courseid', $courseid);
}

$form = new mod_courserating_complete_form(mod_courserating_complete_form::MODE_PRINT,
        $courseratingstructure, 'courserating_print_form');
echo $OUTPUT->continue_button($continueurl);
$form->display();
echo $OUTPUT->continue_button($continueurl);

// Finish the page.
echo $OUTPUT->footer();
