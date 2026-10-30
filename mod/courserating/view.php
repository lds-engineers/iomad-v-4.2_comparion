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
 * the first page to view the courserating
 *
 * @author Andreas Grabs
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_courserating
 */
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/courserating/lib.php');

$id = required_param('id', PARAM_INT);
$courseid = optional_param('courseid', false, PARAM_INT);

$current_tab = 'view';

list($course, $cm) = get_course_and_cm_from_cmid($id, 'courserating');
require_course_login($course, true, $cm);
$courserating = $PAGE->activityrecord;

$courseratingcompletion = new mod_courserating_completion($courserating, $cm, $courseid);

$context = context_module::instance($cm->id);

if ($course->id == SITEID) {
    $PAGE->set_pagelayout('incourse');
}
$PAGE->set_url('/mod/courserating/view.php', array('id' => $cm->id));
$PAGE->set_title($courserating->name);
$PAGE->set_heading($course->fullname);
$PAGE->add_body_class('limitedwidth');

// Check access to the given courseid.
if ($courseid AND $courseid != SITEID) {
    require_course_login(get_course($courseid)); // This overwrites the object $COURSE .
}

// Check whether the courserating is mapped to the given courseid.
if (!has_capability('mod/courserating:edititems', $context) &&
        !$courseratingcompletion->check_course_is_mapped()) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('cannotaccess', 'mod_courserating'));
    echo $OUTPUT->footer();
    exit;
}

$viewcompletion = $courseratingcompletion->is_open() && $courseratingcompletion->can_complete() && $courseratingcompletion->can_submit();
$actionbar = new \mod_courserating\output\standard_action_bar(
    $cm->id,
    $viewcompletion,
    $courseratingcompletion->get_resume_page(),
    $courseid
);

/** @var \mod_courserating\output\renderer $renderer */
$renderer = $PAGE->get_renderer('mod_courserating');

// Trigger module viewed event.
$courseratingcompletion->trigger_module_viewed();

/// Print the main part of the page
///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////

$previewimg = $OUTPUT->pix_icon('t/preview', get_string('preview'));
$previewlnk = new moodle_url('/mod/courserating/print.php', array('id' => $id));
if ($courseid) {
    $previewlnk->param('courseid', $courseid);
}
$preview = html_writer::link($previewlnk, $previewimg);

$PAGE->activityheader->set_description("");

// Print the page header.
echo $OUTPUT->header();


$courserating_completed = $DB->get_record('courserating_completed', array('userid' => $USER->id, 'courseid' => $courserating->course));


if (!$courserating_completed) {
    if ($courseratingcompletion->can_complete()) {
        echo $OUTPUT->box_start('generalbox boxaligncenter');
        echo '<div class="add-rating-link">
                <a href="javascript:void(0)" 
                   data-action="tool_courserating-addrating" 
                   data-courseid="' . $courserating->course . '">
                    Write a review
                </a>
                <div>'.$courserating->intro.'</div>
              </div>';
        echo $OUTPUT->box_end();
    }
     $PAGE->requires->js_init_code("
        window.addEventListener('load', function() {
            setTimeout(function() {
                var ratingLink = document.querySelector('.add-rating-link a');
                if (ratingLink) {
                    var event = new MouseEvent('click', {
                        bubbles: true,
                        cancelable: true,
                        view: window
                    });
                    ratingLink.dispatchEvent(event);
                }
            }, 500);
        });
    ");
    // if ($courseratingcompletion->can_complete()) {
    //     echo $OUTPUT->box_start('generalbox boxaligncenter');
    //     echo '<div class="add-rating-link">
    //             <a href="#section" data-action="tool_courserating-addrating" data-courseid="'.$courserating->course.'">
    //                 Write a review
    //             </a>
    //          </div>';
    //     echo $OUTPUT->box_end();
    // }
    // $PAGE->requires->js_init_code("
    //     window.addEventListener('load', function() {
    //         var ratingLink = document.querySelector('.add-rating-link a');
    //         if (ratingLink) {
    //             ratingLink.click();
    //         }
    //     });
    // ");
}else{


    // $coursecertificate = $DB->get_record_sql("SELECT cm.*,m.name FROM {course_modules} as cm INNER JOIN {modules} as m ON cm.module = m.id WHERE cm.course = ".$course->id." AND cm.deletioninprogress = 0 AND (m.name='customcert' OR m.name='iomadcertificate')");
    // // echo "<pre>";
    // // print_r($coursecertificate);
    // // echo "</pre>";
    // if($coursecertificate){
    //      $certurl = $CFG->wwwroot.'/mod/customcert/view.php?id='.$coursecertificate->id.'&downloadown=1';
    //      echo '<a href="'.$certurl.'" class="btn btn-primary" target="_blank">Certificate</a>';
    //     //redirect($certurl);
    // }else{
    //     echo '<span style="margin: auto;text-align: center;background-color: #bed1f4;padding: 12px;border-radius: 10px;color:#000;">Already Submitted<span>';
    // }


    $companydata = $DB->get_record('company_users', ['userid' => $USER->id], '*', MUST_EXIST);
    $companyid   = $companydata->companyid;

    $certificatemodule = $DB->get_record("modules", array("name" => "customcert"));
    $coursmodul = $DB->get_records("course_modules", array('module' => $certificatemodule->id, 'course' => $course->id));
    $allowedmodules = [];
    foreach ($coursmodul as $cm) {

        // Skip hidden / deleting modules
        if (!$cm->visible || $cm->deletioninprogress) {
            continue;
        }

        // Empty availability  allowed
        if (empty($cm->availability)) {
            $allowedmodules[$cm->id] = $cm;
            continue;
        }

        $availability = json_decode($cm->availability, true);

        // Invalid JSON  allow (matches Moodle behaviour)
        if (empty($availability['c'])) {
            $allowedmodules[$cm->id] = $cm;
            continue;
        }

        $hascompanyrule = false;
        $companymatch   = false;

        foreach ($availability['c'] as $cond) {
            if (!empty($cond['type']) && $cond['type'] === 'company') {
                $hascompanyrule = true;

                if ((int)$cond['id'] === (int)$companyid) {
                    $companymatch = true;
                    break;
                }
            }
        }
        if (!$hascompanyrule || $companymatch) {
            $allowedmodules[$cm->id] = $cm;
        }
    }



    if (!empty($allowedmodules)) {
        // Get first allowed course module
        $firstcm = reset($allowedmodules);

        $certurl = $CFG->wwwroot . '/mod/customcert/view.php?id=' . $firstcm->id . '&downloadown=1';
        // echo '<a href="'.$certurl.'" class="btn btn-primary" target="_blank">Certificate</a>';
        redirect(new moodle_url($certurl));
    }else{
        echo '<span style="margin: auto;text-align: center;background-color: #bed1f4;padding: 12px;border-radius: 10px;color:#000;">Already Submitted<span>';
    }
    
}

echo $OUTPUT->footer();
