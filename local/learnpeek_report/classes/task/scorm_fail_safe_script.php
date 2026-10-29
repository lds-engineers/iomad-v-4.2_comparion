<?php
namespace local_learnpeek_report\task;

defined('MOODLE_INTERNAL') || die();

class scorm_fail_safe_script extends \core\task\scheduled_task {
    //const HOURS_AFTER_COMPLETION_BEFORE_TRANSFER = 48;

    public function get_name() {
      return "Scorm Updated Status";
    }

     /**
     * Execute the task.
     */
    public function execute() {
        global $DB,$CFG;
        require_once($CFG->dirroot . '/lib/gradelib.php');
        require_once($CFG->dirroot . '/grade/lib.php');
        require_once($CFG->dirroot . '/lib/completionlib.php');
        $courseid = 77;
        $scormid = 129;

        $course = $DB->get_record('course', ['id' => $courseid]);
        $cm = get_coursemodule_from_instance('scorm', $scormid, $courseid);
        $completion = new \completion_info($course);

        $gardeitem = $DB->get_record_sql("SELECT * FROM {grade_items} WHERE courseid = 77 AND iteminstance = 129");
        $gardeitmid = $gardeitem->id;

        $scormallfailduser = $DB->get_records_sql("SELECT DISTINCT st.userid , st.id
                                            FROM {scorm_scoes_track} st 
                                            INNER JOIN {scorm_scoes_track} st1 ON st1.userid = st.userid AND st1.scormid = st.scormid AND st1.attempt = st.attempt 
                                            INNER JOIN {user} u ON u.id = st.userid 
                                            WHERE st.scormid = 129 
                                            AND st.element = 'cmi.core.score.raw' 
                                            AND st.value < 100 
                                            AND st1.value = 'Scene14_Slide10_FreeFormPickOne_0_0' 
                                            AND u.deleted = 0 
                                            AND u.suspended = 0");
        if($scormallfailduser){
            foreach ($scormallfailduser as $failduser) {
                $updatedgrade = $DB->get_record_sql("SELECT * FROM {grade_grades} WHERE itemid = ? AND userid = ? AND rawgrade != 100",[$gardeitmid, $failduser->userid]);
                
                if ($updatedgrade) {
                        // Update grade properly
                        grade_update(
                            'mod/scorm',
                            $courseid,
                            'mod',
                            'scorm',
                            $scormid,
                            0,
                            [
                                $failduser->userid => (object)[
                                    'userid'   => $failduser->userid,
                                    'rawgrade' => 100
                                ]
                            ]
                        );

                        //Update SCORM score
                        $DB->set_field(
                            'scorm_scoes_track',
                            'value',
                            100,
                            [
                                'userid' => $failduser->userid,
                                'scormid' => $scormid,
                                'element' => 'cmi.core.score.raw'
                            ]
                        );

                        //  Update SCORM lesson as passed
                        $DB->set_field(
                            'scorm_scoes_track',
                            'value',
                            'passed',
                            [
                                'userid' => $failduser->userid,
                                'scormid' => $scormid,
                                'element' => 'cmi.core.lesson_status'
                            ]
                        );
                        //  Force activity completion
                        if ($completion->is_enabled($cm)) {
                            $completion->update_state($cm, COMPLETION_COMPLETE, $failduser->userid);
                        }

                        echo "userid $failduser->userid have updated completion";
                    }
            }
            grade_force_full_regrading($courseid);
        } 
    }
}