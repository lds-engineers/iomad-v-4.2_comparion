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
 * Manage the various templates available
 *
 * @author Peter Dias
 * @copyright 2021 Peter Dias
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_courserating
 */

require_once("../../config.php");
require_once("lib.php");

$id = required_param('id', PARAM_INT);
$mode = optional_param('mode', '', PARAM_ALPHA);
$templateid = optional_param('deletetemplate', 0, PARAM_INT);

list($course, $cm) = get_course_and_cm_from_cmid($id, 'courserating');
$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/courserating:edititems', $context);

$courserating = $PAGE->activityrecord;
$systemcontext = context_system::instance();

$params = ['id' => $id];
if ($mode) {
    $params += ['mode' => $mode];
}
$url = new moodle_url('/mod/courserating/manage_templates.php', $params);
if ($mode == 'manage') {
    navigation_node::override_active_url($url);
} else {
    navigation_node::override_active_url(new moodle_url('/mod/courserating/view.php', $params));
}

$PAGE->set_url($url);
$actionbar = new \mod_courserating\output\edit_action_bar($cm->id, $url);

$PAGE->set_heading($course->fullname);
$PAGE->set_title($courserating->name);

// Process template deletion.
if ($templateid) {
    require_sesskey();
    require_capability('mod/courserating:deletetemplate', $context);
    $template = $DB->get_record('courserating_template', ['id' => $templateid], '*', MUST_EXIST);

    if ($template->ispublic) {
        require_capability('mod/courserating:createpublictemplate', $systemcontext);
        require_capability('mod/courserating:deletetemplate', $systemcontext);
    }

    courserating_delete_template($template);
    $successurl = new moodle_url('/mod/courserating/manage_templates.php', ['id' => $id]);
    redirect($url, get_string('template_deleted', 'courserating'), null, \core\output\notification::NOTIFY_SUCCESS);
}
$PAGE->activityheader->set_attrs([
    "hidecompletion" => true,
    "description" => ''
]);
echo $OUTPUT->header();
/** @var \mod_courserating\output\renderer $renderer */
$renderer = $PAGE->get_renderer('mod_courserating');
if (!$mode) {
    echo $renderer->main_action_bar($actionbar);
}
echo $OUTPUT->heading(get_string('templates', 'mod_courserating'), 3);

// First we get the course templates.
$templates = courserating_get_template_list($course, 'own');
echo $OUTPUT->box_start('coursetemplates');
echo $OUTPUT->heading(get_string('course'), 4);

$baseurl = new moodle_url('/mod/courserating/use_templ.php', $params);
$tablecourse = new mod_courserating_templates_table('courserating_template_course_table', $baseurl, $mode);
$tablecourse->display($templates);
echo $OUTPUT->box_end();

$templates = courserating_get_template_list($course, 'public');
echo $OUTPUT->box_start('publictemplates');
echo $OUTPUT->heading(get_string('public', 'courserating'), 4);
$tablepublic = new mod_courserating_templates_table('courserating_template_public_table', $baseurl, $mode);
$tablepublic->display($templates);
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
