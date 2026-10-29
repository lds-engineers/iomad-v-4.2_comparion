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
 * prints the form to edit the courserating items such moving, deleting and so on
 *
 * @author Andreas Grabs
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_courserating
 */

require_once('../../config.php');
require_once('lib.php');

courserating_init_courserating_session();

$id = required_param('id', PARAM_INT);

if (($formdata = data_submitted()) AND !confirm_sesskey()) {
    throw new \moodle_exception('invalidsesskey');
}

$switchitemrequired = optional_param('switchitemrequired', false, PARAM_INT);
$deleteitem = optional_param('deleteitem', false, PARAM_INT);

list($course, $cm) = get_course_and_cm_from_cmid($id, 'courserating');

$context = context_module::instance($cm->id);
require_login($course, false, $cm);
require_capability('mod/courserating:edititems', $context);
$courserating = $PAGE->activityrecord;
$courseratingstructure = new mod_courserating_structure($courserating, $cm);
$url = new moodle_url('/mod/courserating/edit.php', ['id' => $cm->id]);

if ($switchitemrequired) {
    require_sesskey();
    $items = $courseratingstructure->get_items();
    if (isset($items[$switchitemrequired])) {
        courserating_switch_item_required($items[$switchitemrequired]);
    }
    redirect($url);
}

if ($deleteitem) {
    require_sesskey();
    $items = $courseratingstructure->get_items();
    if (isset($items[$deleteitem])) {
        courserating_delete_item($deleteitem);
    }
    redirect($url);
}

// Process the create template form.
$cancreatetemplates = has_capability('mod/courserating:createprivatetemplate', $context) ||
            has_capability('mod/courserating:createpublictemplate', $context);

//Get the courseratingitems
$lastposition = 0;
$courseratingitems = $DB->get_records('courserating_item', array('courserating'=>$courserating->id), 'position');
if (is_array($courseratingitems)) {
    $courseratingitems = array_values($courseratingitems);
    if (count($courseratingitems) > 0) {
        $lastitem = $courseratingitems[count($courseratingitems)-1];
        $lastposition = $lastitem->position;
    } else {
        $lastposition = 0;
    }
}
$lastposition++;

$PAGE->set_url($url);
$PAGE->set_heading($course->fullname);
$PAGE->set_title($courserating->name);
$actionbar = new \mod_courserating\output\edit_action_bar($cm->id, $url, $lastposition);
$PAGE->activityheader->set_attrs([
    'hidecompletion' => true,
    'description' => ''
]);
$PAGE->add_body_class('limitedwidth');

//Adding the javascript module for the items dragdrop.
if (count($courseratingitems) > 1) {
    $PAGE->requires->strings_for_js(array(
           'pluginname',
           'move_item',
           'position',
        ), 'courserating');
    $PAGE->requires->yui_module('moodle-mod_courserating-dragdrop', 'M.mod_courserating.init_dragdrop',
            array(array('cmid' => $cm->id)));
}

echo $OUTPUT->header();
/** @var \mod_courserating\output\renderer $renderer */
$renderer = $PAGE->get_renderer('mod_courserating');
echo $renderer->main_action_bar($actionbar);
$form = new mod_courserating_complete_form(mod_courserating_complete_form::MODE_EDIT,
        $courseratingstructure, 'courserating_edit_form');
echo '<div id="courserating_dragarea">'; // The container for the dragging area.
$form->display();
echo '</div>';

if ($cancreatetemplates) {
    echo $renderer->create_template_form($id);
}

echo $OUTPUT->footer();
