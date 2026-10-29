<?php

use core_completion\progress;

class test_table extends table_sql {
    function __construct($uniqueid, $companyid) {
        parent::__construct($uniqueid);
        $this->companyid = $companyid;

        if((int)$companyid === 48 && !is_siteadmin()){
            $columns = array('Company_Checkbox','firstname', 'lastname', 'Email', 'Upload_ID', 'Image', 'User_Courses', 'Edit_Student');
            $headers = array('Added to Training Connect','First Name', 'Last Name', 'Email', 'Upload ID', 'Image', 'User Courses', 'Edit Student');
        }else{
            $columns = array('firstname', 'lastname', 'Email', 'Upload_ID', 'Image', 'User_Courses', 'Edit_Student');
            $headers = array(get_string('firstname','local_customlayout'), get_string('lastname','local_customlayout'), get_string('email','local_customlayout'), get_string('uploadid','local_customlayout'), get_string('image','local_customlayout'), get_string('usercourses','local_customlayout'), get_string('editstudent','local_customlayout'));
        }
        $this->define_columns($columns);
        $this->define_headers($headers);
    }

    function col_Company_Checkbox($values) {
        global $DB;
        if (is_siteadmin()) {
            return '';
        }
        $companyid = $this->companyid;
        $lastrecord = $DB->get_record_sql("
            SELECT status,created_at
            FROM {sst_cart_allow}
            WHERE company_id = ?
            AND user_id = ?
            ORDER BY id DESC
            LIMIT 1                   
        ", [$companyid, $values->id]);

        $checked = '';
        $checkeddata = '';

        if ($lastrecord && (int)$lastrecord->status === 1) {
            $checked = 'checked="checked"';
            $checkeddata = date('m-d-Y', $lastrecord->created_at);
        }

        // return '<input type="checkbox"
        //             class="company-toggle"
        //             data-userid="'.$values->id.'"
        //             '.$checked.' />';

        return '<input type="checkbox" class="company-toggle" data-userid="'.$values->id.'" '.$checked.' />
                <br>
                <span class="check-date">'.$checkeddata.'</span>';
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
    function col_firstname($values) {
        return "<a target='_blank' href='$CFG->wwwroot/user/profile.php?id=$values->id'>$values->firstname</a>";
    }
    function col_lastname($values) {
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
        // suneet code for get backup course
        $userbackupcrt = $DB->get_records_sql("SELECT * FROM {customcert_backup} WHERE userid =?", array($values->id));

        // extracts all courseid values
        $userbackupcrtcourses = array_column($userbackupcrt, 'courseid');

        $enrolledCourseIds = array_keys($courses);

        // Merge and remove duplicates
        $userallmappedcourse = array_values(array_unique(array_merge($userbackupcrtcourses, $enrolledCourseIds)));

        $courseslist = implode(",",$userallmappedcourse);
        $allusercourses = $DB->get_records_sql("SELECT * FROM {course} WHERE id IN($courseslist)");

        // end suneet code for get backup course
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
        // change $courses to $allusercourses in loop             
        foreach ($allusercourses as $course) {
            $certs = $this->get_certificate($course, $values->id);
            $rowspan = 1;
            if(sizeof($certs) > 0){
                $rowspan = sizeof($certs);
            }
            $html .= "<tr>
                    <td rowspan='{$rowspan}' ><a href='$CFG->wwwroot/course/view.php?id=$course->id' target='_blank'>$course->fullname</a></td>";
            if(sizeof($certs) > 0){
                foreach ($certs as $key => $cert) {
                    if($key != 0){
                        $html .= "
                              </tr>";

                    }
                    $html .= "
                        <td>{$cert['date']}</td>
                        <td>{$cert['code']}</td>
                        <td>{$cert['html']}</td>";
                }
            } else {
                $html .= "
                    <td>Not Completed</td>
                    <td></td>
                    <td></td>";

            }
            $html .= "
                  </tr>";
        }
        $html .= "</tbody></table>";
        return $html;
    }

    function col_Edit_Student($values) {
        global $CFG;
        return "<a target='_blank' href='$CFG->wwwroot/blocks/learnpeek_shop/edit_trainee.php?id=$values->id' style='display: inline-block;'>" . get_string('editusers', 'local_customlayout') . "</a>";
    }
    function col_View_More($values) {
        return "<a target='_blank' href='$CFG->wwwroot/local/customlayout/viewmore.php?id=$values->id'>View More</a>";
    }

    function get_certificate($course, $userid) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/blocks/custom_mycourse/locallib.php');

        $percentage = $this->get_percentage($course, $userid);
        $customcert = (array) $this->get_customcert_certificate($course, $userid);
        $iomadcert = (array) $this->get_iomadcertificate_certificate($course, $userid);
        $results = array();
        $result = ['html' => '', 'date' => '', 'code' => '','name'=>''];
        $usercontext = \context_user::instance($userid);
        $contextid   = $usercontext->id;
        // $backupcrt = $DB->get_records_sql("SELECT * FROM {customcert_backup} WHERE userid = ? AND courseid = ? ORDER BY id DESC",array($userid,$course->id));
        $backupcrt = $DB->get_records_sql("SELECT current_enrollment,id,course_completion_date FROM {customcert_backup} WHERE userid = ? AND courseid = ? ORDER BY id DESC",array($userid,$course->id));
        $enrollmentids = array_column($backupcrt, 'current_enrollment');

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
                    array_push($results, $result);
                } 
            }
        } else {
            if(empty($enrollmentids)){
                $enrollmentids = array(0);
            }
            $enrolements = $DB->get_records_sql("Select ue.*, cc.timecompleted FROM {user_enrolments} ue INNER JOIN {enrol} e on e.id = ue.enrolid INNER JOIN {course_completions} cc on cc.course = e.courseid and cc.userid = ue.userid WHERE ue.id not in(".implode(",", $enrollmentids).") and e.courseid=:courseid and ue.userid=:userid", array("courseid"=>$course->id , "userid"=>$userid));
            foreach ($enrolements as $key => $enrolement) {
                if($enrolement->timecompleted){
                    $result['html'] = '';
                    $result['date'] = userdate($enrolement->timecompleted);
                    $result['code'] = '';
                    array_push($results, $result);
                } else {
                    $result['html'] = '';
                    $result['date'] = 'not completed';
                    $result['code'] = '';
                    array_push($results, $result);
                }
            }
            if($backupcrt){
                foreach ($backupcrt as $bcert) {
                    $ucurrentenroll = $DB->get_record_sql("SELECT * FROM {user_enrolments} WHERE id = ?",array($bcert->current_enrollment));

                    if ($ucurrentenroll) {
                        $docfile = $DB->get_record_sql("
                        SELECT * FROM {files}
                        WHERE contextid = :ctxid
                        AND component = 'user'
                        AND itemid = :itemid
                        AND filesize > 0
                        AND filearea = 'certificate'
                        AND filename != '.'",
                        ['ctxid' => $contextid, 'itemid' => $bcert->id]);
                        if ($docfile) {
                            $license_url = $CFG->wwwroot.'/local/customlayout/file.php?id='.$docfile->pathnamehash;
                        }
                        $result['html'] = '<a target="_blank" href="'.$license_url.'" class="btn btn-primary">Download Certificate</a>';
                        $result['date'] = userdate($customcert['timecreated']);
                        $result['code'] = $customcert['code'];
                        array_push($results, $result);
                        // echo $customcert['timecreated'] . "Record found course_id # $bcert->courseid current_enrollment # $bcert->current_enrollment userid # $userid  <br>";
                    } else {
                        $docfile = $DB->get_record_sql("
                        SELECT * FROM {files}
                        WHERE contextid = :ctxid
                        AND component = 'user'
                        AND itemid = :itemid
                        AND filesize > 0
                        AND filearea = 'certificate'
                        AND filename != '.'",
                        ['ctxid' => $contextid, 'itemid' => $bcert->id]);
                        if ($docfile) {
                            $license_url = $CFG->wwwroot.'/local/customlayout/file.php?id='.$docfile->pathnamehash;
                        }
                        // $license_url = $CFG->wwwroot.'/local/customlayout/file.php?id='.$docfile->pathnamehash;
                        $result['html'] = '<a target="_blank" href="'.$license_url.'" class="btn btn-primary" style="margin-left:5px;">Download Certificate</a>';
                        $result['date'] = userdate($bcert->course_completion_date); 
                        $result['code'] = "Past Certificate";
                        array_push($results, $result);
                        // echo $bcert->course_completion_date . "old certificate course_id # $bcert->courseid current_enrollment # $bcert->current_enrollment userid # $userid<br>";
                    }
                }

            }
            //end 

        }

        // if ($percentage == 100) {
        //     if (!empty($customcert['cmid'])) {
        //         $result['html'] .= '<a target="_blank" href="'.$CFG->wwwroot.'/mod/customcert/view.php?id='.$customcert['cmid'].'&downloadown=1&userid='.$userid.'" class="btn btn-primary">Download Certificate</a>';
        //         $result['date'] = userdate($customcert['timecreated']);
        //         $result['code'] = $customcert['code'];
        //     }

        //     if (!empty($iomadcert['cmid'])) {
        //         $result['html'] .= '<a target="_blank" href="'.$CFG->wwwroot.'/mod/customcert/view.php?id='.$iomadcert['cmid'].'&downloadown=1&userid='.$userid.'" class="btn btn-primary">Download Certificate</a>';
        //         $result['date'] = userdate($iomadcert['timecreated']);
        //         $result['code'] = $iomadcert['code'];
        //     }
        // }

        return $results;
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