<?php

use core_completion\progress;

class test_table extends table_sql {
    function __construct($uniqueid) {
        parent::__construct($uniqueid);
        $columns = array('Student_First_Name', 'Student_Last_Name', 'Email', 'Upload_ID', 'Image', 'User_Courses', 'Edit_Student');
        $this->define_columns($columns);
        $headers = array('Student First Name', 'Student Last Name', 'Email', 'Upload ID', 'Image', 'User Courses', 'Edit Student');
        $this->define_headers($headers);
    }
    function col_Company($values) {
        global $DB;
        $userid = $values->id;
        $name = null;
        $business_user_child = $DB->get_record("business_user_child",array("child_id"=>$userid));
        $parent_id = $business_user_child ? $business_user_child->parent_id : 0;
        $company_users = $DB->get_record("company_users",array("userid"=>$parent_id));
        if($company_users) {
            $companyid = $company_users->companyid;
            $company = $DB->get_record("company",array("id"=>$companyid));
            $name = $company->name;
        }
        return $name;
    }
    function col_Student_First_Name($values) {
        return "<a target='_blank' href='$CFG->wwwroot/user/profile.php?id=$values->id'>$values->firstname</a>";
    }
    function col_Student_Last_Name($values) {
        return "<a target='_blank' href='$CFG->wwwroot/user/profile.php?id=$values->id'>$values->lastname</a>";
    }
    function col_Email($values) {
        return $values->email;
    }
    function col_Tel_No($values) {
        return $values->phone1;
    }
    function col_Address($values) {
        return $values->address;
    }
    function col_Upload_ID($values) {
        global $DB, $CFG;

        $file = $DB->get_record_sql("
            SELECT * FROM {files}
            WHERE contextid = 1 AND component = 'userdocument' AND itemid = ? AND filesize > 0 AND filearea = ? AND filename != '.'",
            array($values->uploadid, 'document')
        );

        if ($file) {
            $fileurl = "$CFG->wwwroot/local/customlayout/file.php?id=$file->pathnamehash";
            $deleteurl = "$CFG->wwwroot/local/customlayout/deletefile.php?imgid=$file->id&id=$values->uploadid&type=docs";

            // Check file extension
            $ext = strtolower(pathinfo($file->filename, PATHINFO_EXTENSION));
            $image_exts = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];

            if (in_array($ext, $image_exts)) {
                // Display image preview
                $output = "<a target='_blank' href='$fileurl'>
                                <img src='$fileurl' width='80px' height='60px'>
                           </a>";
            } else {
                // Display file icon and filename
                $output = "<a target='_blank' href='$fileurl'>
                                <i class='fa fa-file' style='font-size:41px;'></i> {$file->filename}
                           </a>";
            }

            // Add delete button
            $output .= "<br><a class='btn btn-sm btn-danger m-1'
                              href='$deleteurl'
                              onclick='return confirm(\"Are you sure you want to remove this file?\")'>Remove</a>";

            return $output;
        }

        return "";
    }
    function col_Image($values) {
        global $CFG;
        if($values->image){
            return "<a target='_blank' href='$CFG->wwwroot/local/scan_face_recognition/temp/$values->image'><img src='$CFG->wwwroot/local/scan_face_recognition/temp/$values->image' width='80px' height='60px'></a><br><a class='btn btn-sm btn-danger m-1' href='$CFG->wwwroot/local/customlayout/deletefile.php?id=$values->uploadid&image=$values->image&type=img' onclick='return confirm(\"Are you sure, want to remove ?\")'>Remove</a>";
        } else {
            return "";
        }
    }
    function col_User_Courses($values) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/lib/enrollib.php');
        require_once($CFG->dirroot . '/blocks/custom_mycourse/locallib.php');
        $courses = enrol_get_users_courses($values->id);
        // echo "fffff" $courses->category;

        $html = "<table class='table'>
                    <thead>
                        <tr>
                            <td>Course</td>
                            <td>Certificate Date</td>
                            <td>Certificate Number</td>
                            <td></td>
                        </tr>
                    </thead>
                    <tbody>";
        foreach ($courses as $course) {
            $cert = $this->get_certificate($course, $values->id);
            $html .= "<tr>
                    <td><a href='$CFG->wwwroot/course/view.php?id=$course->id' target='_blank'>$course->fullname</a></td>
                    <td>{$cert['date']}</td>
                    <td>{$cert['code']}</td>
                    <td>{$cert['html']}</td>
                  </tr>";
        }
        $html .= "</tbody></table>";
        return $html;
    }

    function col_Edit_Student($values) {
        global $CFG;
        return "<a target='_blank' href='$CFG->wwwroot/blocks/learnpeek_shop/edit_trainee.php?id=$values->id' style='display: inline-block;'>Edit User</a>";
    }
    function col_View_More($values) {
        return "<a target='_blank' href='$CFG->wwwroot/local/customlayout/viewmore.php?id=$values->id'>View More</a>";
    }

    function get_certificate($course, $userid) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/blocks/custom_mycourse/locallib.php');

        $percentage = $this->get_percentage($course, $userid);
        $customcert = (array) $this->get_customcert_certificate($course, $userid);
        // echo "<pre>";
        // print_r($customcert);
        // echo "</pre>";
        $iomadcert = (array) $this->get_iomadcertificate_certificate($course, $userid);

        $result = ['html' => '', 'date' => '', 'code' => ''];

        $categoryids = blocks_custom_mycourse_get_all_category_ids(64);
        $isosha = in_array($course->category, $categoryids);
        if($isosha){
            $getscormscor = $DB->get_record_sql("SELECT u.id as userid, u.firstname, u.lastname, st.value as status,st.timemodified as scormcompleted, st.attempt, st.scormid FROM {scorm_scoes_track} st INNER JOIN {scorm} s ON s.id = st.scormid INNER JOIN {user} u ON u.id = st.userid WHERE st.element = 'cmi.core.lesson_status' AND s.course = ? AND u.id = ?",array($course->id,$userid));
            $rating = $DB->get_record_sql("SELECT r.id AS rid, u.id AS userid, u.firstname, u.lastname, r.rating AS rating, r.review AS review, r.reply AS reply, r.timemodified AS timemodified, r.courseid AS courseid, c.fullname AS coursename FROM {tool_courserating_rating} r INNER JOIN {user} u ON u.id = r.userid INNER JOIN {course} c ON c.id = r.courseid WHERE c.id=? AND u.id = ?",array($course->id,$userid));
            if($getscormscor){
                if($getscormscor->status == "passed" && $rating){
                    $result['html'] .= '<a href="" class="btn btn-primary">Completed</a>';
                    $result['date'] = userdate($getscormscor->scormcompleted);
                    $result['code'] = "";
                } 
            }
        } else if ($percentage == 100) {
            if (!empty($customcert['cmid'])) {
                $result['html'] .= '<a target="_blank" href="'.$CFG->wwwroot.'/mod/customcert/view.php?id='.$customcert['cmid'].'&downloadown=1&userid='.$userid.'" class="btn btn-primary">Download Certificate</a>';
                $result['date'] = userdate($customcert['timecreated']);
                $result['code'] = $customcert['code'];
            }

            if (!empty($iomadcert['cmid'])) {
                $result['html'] .= '<a target="_blank" href="'.$CFG->wwwroot.'/mod/customcert/view.php?id='.$iomadcert['cmid'].'&downloadown=1&userid='.$userid.'" class="btn btn-primary">Download Certificate</a>';
                $result['date'] = userdate($iomadcert['timecreated']);
                $result['code'] = $iomadcert['code'];
            }
        }

        return $result;
    }

    function get_percentage($course,$userid) {
        return progress::get_course_progress_percentage($course,$userid);
    }
    function get_customcert_certificate($course, $userid) {
        global $DB;
        $customcert = $DB->get_record('modules', array("name" => "customcert"));
        // $modinfo = get_fast_modinfo($course);
        // echo $course->id;
        $cm = $DB->get_records('course_modules', array("course"=>$course->id , "module"=>$customcert->id, "deletioninprogress"=>0));
        $cmids = array();
        if(!empty($cm)){
            foreach($cm as $ccm){
                $modinfo = get_fast_modinfo($course->id,$userid);
                $cminfo = $modinfo->get_cm($ccm->id);
                if ($cminfo->uservisible) {
                    $cmids[] = $ccm->id;
                }
            }
        }
        if(empty($cmids)){
            $cmids[]=0;
        }
        $sql = "SELECT c.id AS courseid, cm.id AS cmid, ci.code, ci.timecreated
                FROM {customcert} ct
                INNER JOIN {course} c ON c.id = ct.course
                INNER JOIN {course_modules} cm ON cm.course = c.id AND cm.module = :moduleid AND cm.deletioninprogress = 0
                INNER JOIN {customcert_issues} ci ON ci.customcertid = cm.instance
                INNER JOIN {user} u ON ci.userid = u.id
                WHERE ci.userid = :userid AND ct.course = :courseid AND cm.id={$cmids[0]}";

        $params = [
            'userid' => $userid,
            'courseid' => $course->id,
            'moduleid' => $customcert->id
        ];

        return $DB->get_record_sql($sql, $params);
    }
    function get_iomadcertificate_certificate($course, $userid) {
        global $DB;
        $iomadcert = $DB->get_record('modules', array("name" => "iomadcertificate"));

        $sql = "SELECT c.id AS courseid, cm.id AS cmid, ci.code, ci.timecreated
                FROM {iomadcertificate} ct
                INNER JOIN {customcert_issues} ci ON ci.customcertid = ct.id
                INNER JOIN {user} u ON ci.userid = u.id
                INNER JOIN {course} c ON c.id = ct.course
                INNER JOIN {course_modules} cm ON cm.course = c.id AND cm.module = :moduleid AND cm.deletioninprogress = 0
                WHERE ci.userid = :userid AND ct.course = :courseid";

        $params = [
            'userid' => $userid,
            'courseid' => $course->id,
            'moduleid' => $iomadcert->id
        ];

        return $DB->get_record_sql($sql, $params);
    }

}