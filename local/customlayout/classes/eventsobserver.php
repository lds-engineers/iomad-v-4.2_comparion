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
 * Local plugin "Boost navigation fumbling" - Event observers
 *
 * @package    local_assignment_subscription
 * @copyright  2019 Alexander Bias, Ulm University <alexander.bias@uni-ulm.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// namespace local_assignment_subscription;

defined('MOODLE_INTERNAL') || die();

/**
 * Observer class containing methods monitoring various events.
 *
 * @package    local_assignment_subscription
 * @copyright  2019 Alexander Bias, Ulm University <alexander.bias@uni-ulm.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class local_customlayout_eventsobserver
{
    public static function course_created(\core\event\course_created $event) {
        global $USER, $DB;

        $courseid = $event->courseid;
        $course = $DB->get_record("course", array("id" => $courseid));
        $category = $course ? $course->category : 0;

        $sql = "SELECT * FROM {company_category_mapping} WHERE FIND_IN_SET(:categoryid, course_category)";
        $params = ['categoryid' => $category];
        $company_mappings = $DB->get_records_sql($sql, $params);

        if (!empty($company_mappings)) {
            foreach ($company_mappings as $mapping) {
                $companyid = $mapping->company_id;
                $department = $DB->get_record("department", array("company" => $companyid));
                $dataObj = new stdClass();
                $dataObj->companyid = $companyid;
                $dataObj->courseid = $courseid;
                $dataObj->departmentid = $department ? $department->id : 0;
                $DB->insert_record("company_course", $dataObj);
            }
        }
        // // mapped if course are created in company category
        // $companydata = $DB->get_record('company', array('category' => $category));
        // if ($companydata) {
        //     $department = $DB->get_record('department', array('company' => $companydata->id));
        //     if (!$DB->record_exists('company_course', array('companyid' => $companydata->id, 'courseid' => $courseid))) {
        //         $dataObj = new stdClass();
        //         $dataObj->companyid = $companydata->id;
        //         $dataObj->courseid = $courseid;
        //         $dataObj->departmentid = $department ? $department->id : 0;
        //         $DB->insert_record('company_course', $dataObj);
        //     }
        // }

    }
    public static function course_restored(\core\event\course_restored $event) {
        global $USER, $DB,$CFG;
        require_once($CFG->dirroot. '/blocks/custom_mycourse/lib.php');

        $courseid = $event->courseid;
        $course = $DB->get_record("course", array("id" => $courseid));
        $category = $course ? $course->category : 0;

        $sql = "SELECT * FROM {company_category_mapping} WHERE FIND_IN_SET(:categoryid, course_category)";
        $params = ['categoryid' => $category];
        $company_mappings = $DB->get_records_sql($sql, $params);

        if (!empty($company_mappings)) {
            foreach ($company_mappings as $mapping) {
                $companyid = $mapping->company_id;
                $department = $DB->get_record("department", array("company" => $companyid));
                $dataObj = new stdClass();
                $dataObj->companyid = $companyid;
                $dataObj->courseid = $courseid;
                $dataObj->departmentid = $department ? $department->id : 0;
                $DB->insert_record("company_course", $dataObj);
            }
        }

        // mapped if course are created in company category
        // $companydata = $DB->get_record('company', array('category' => $category));
        // if ($companydata) {
        //     $department = $DB->get_record('department', array('company' => $companydata->id));
        //     if (!$DB->record_exists('company_course', array('companyid' => $companydata->id, 'courseid' => $courseid))) {
        //         $dataObj = new stdClass();
        //         $dataObj->companyid = $companydata->id;
        //         $dataObj->courseid = $courseid;
        //         $dataObj->departmentid = $department ? $department->id : 0;
        //         $DB->insert_record('company_course', $dataObj);
        //     }
        // }

        $path1 = $CFG->cachedirect . '/cache';
        $path2 = $CFG->cachedirect . '/localcache';
        deleteFolderAndFiles($path1);
        deleteFolderAndFiles($path2);

    }

    public static function user_loggedin(\core\event\user_loggedin $event) {
        global $DB, $CFG, $USER;
        require_once($CFG->dirroot. '/local/manage_course/locallib.php');
        require_once($CFG->dirroot . '/blocks/custom_mycourse/locallib.php');
        $categoryids = blocks_custom_mycourse_get_all_category_ids(64);
        $role = $DB->get_record('role', array('shortname'=>'editingteacher'));
        $companycoursenoneditorrole = $DB->get_record('role', array('shortname' => 'companycoursenoneditor'));
        $getcompnatlist = $DB->get_records_sql("SELECT * FROM {company_users} WHERE userid = ? AND managertype = 1",array($USER->id));
        foreach ($getcompnatlist as $company) {
            $companyid = $company->companyid;

            $companydata = $DB->get_record_sql("SELECT * FROM {company} WHERE id = ?",array($companyid));
            $companycategory = $companydata->category;

            $compnaycourses = $DB->get_records_sql("SELECT cc.*,c.category as coursecategory 
                                                        FROM {company_course} as cc 
                                                        INNER JOIN {course} as c ON c.id = cc.courseid 
                                                        WHERE cc.companyid = ? AND c.category = ?",array($companyid,$companycategory));
            if($compnaycourses){
                foreach ($compnaycourses as $compnaycourse) {
                    if (in_array($compnaycourse->coursecategory, $categoryids)) {
                        continue;
                    }
                        // print_r($compnaycourse);
                     $context = context_course::instance($compnaycourse->courseid);
                    $coursealreadyassigned =  $DB->record_exists('role_assignments', array('userid'=>$USER->id, 'roleid'=>$role->id, 'contextid'=>$context->id));
                    if(!$coursealreadyassigned){
                        enrolteacherCourse($compnaycourse->courseid, $USER->id, $role->id,0,time());
                    }
                }
            }
            // enrolled white labe admin in course
            $checkcompcourseenroll = $DB->get_records_sql("SELECT cc.*,c.category as coursecategory 
                                                        FROM {company_course} as cc 
                                                        INNER JOIN {course} as c ON c.id = cc.courseid 
                                                        WHERE cc.companyid = ? ",array($companyid));
            if($checkcompcourseenroll){
                foreach ($checkcompcourseenroll as $courseenroll) {
                    if (in_array($courseenroll->coursecategory, $categoryids)) {
                        continue;
                    }
                    $context = context_course::instance($courseenroll->courseid);
                    $coursealreadyassigned =  $DB->record_exists('role_assignments', array('userid'=>$USER->id, 'roleid'=>$companycoursenoneditorrole->id, 'contextid'=>$context->id));
                    if(!$coursealreadyassigned){
                        echo $courseenroll->courseid ."<br>";
                        enrolteacherCourse($courseenroll->courseid, $USER->id, $companycoursenoneditorrole->id,0,time());
                    }
                     
                }

            }

            // Get white-label-grade allowed courses
            $allwogarecourse = $DB->get_records_sql("SELECT cc.* FROM {customfield_data} cfd 
                                                        JOIN {customfield_field} cf ON cf.id = cfd.fieldid 
                                                        JOIN {company_course} cc ON cc.courseid = cfd.instanceid 
                                                        WHERE cc.companyid = ? 
                                                        AND cf.shortname = 'allow_white_level_grade' 
                                                        AND cfd.value = 1", [$companyid]);
            // whitelable admin enrolled allow garding course with teacher role
            if ($allwogarecourse) {
                foreach ($allwogarecourse as $wogcourse) {

                    $context = context_course::instance($wogcourse->courseid);

                    $alreadyassigned = $DB->record_exists(
                        'role_assignments',
                        [
                            'userid' => $USER->id,
                            'roleid' => $role->id,
                            'contextid' => $context->id
                        ]
                    );

                    if (!$alreadyassigned) {
                        enrolteacherCourse($wogcourse->courseid, $USER->id, $role->id, 0, time());
                    }
                }
            }
                    
        }
    } 
    public static function user_loggedinas(\core\event\user_loggedinas $event) {
        global $DB, $CFG, $USER;
        require_once($CFG->dirroot. '/local/manage_course/locallib.php');
        require_once($CFG->dirroot . '/blocks/custom_mycourse/locallib.php');
        $categoryids = blocks_custom_mycourse_get_all_category_ids(64);
        $role = $DB->get_record('role', array('shortname'=>'editingteacher'));
        $getcompnatlist = $DB->get_records_sql("SELECT * FROM {company_users} WHERE userid = ? AND managertype = 1",array($USER->id));
        foreach ($getcompnatlist as $company) {
            $companyid = $company->companyid;
            $companydata = $DB->get_record_sql("SELECT * FROM {company} WHERE id = ?",array($companyid));
            $companycategory = $companydata->category;
            $compnaycourses = $DB->get_records_sql("SELECT cc.* 
                                                        FROM {company_course} as cc 
                                                        INNER JOIN {course} as c ON c.id = cc.courseid 
                                                        WHERE cc.companyid = ? AND c.category = ?",array($companyid,$companycategory));
            if($compnaycourses){
                foreach ($compnaycourses as $compnaycourse) {
                    if (in_array($compnaycourse->coursecategory, $categoryids)) {
                        continue;
                    }
                        // print_r($compnaycourse);
                     $context = context_course::instance($compnaycourse->courseid);
                    $coursealreadyassigned =  $DB->record_exists('role_assignments', array('userid'=>$USER->id, 'roleid'=>$role->id, 'contextid'=>$context->id));
                    if(!$coursealreadyassigned){
                        enrolteacherCourse($compnaycourse->courseid, $USER->id, $role->id,0,time());
                    }
                }
            }

            // Get white-label-grade allowed courses
            $allwogarecourse = $DB->get_records_sql("SELECT cc.* FROM {customfield_data} cfd 
                                                        JOIN {customfield_field} cf ON cf.id = cfd.fieldid 
                                                        JOIN {company_course} cc ON cc.courseid = cfd.instanceid 
                                                        WHERE cc.companyid = ? 
                                                        AND cf.shortname = 'allow_white_level_grade' 
                                                        AND cfd.value = 1", [$companyid]);
            // whitelable admin enrolled allow garding course with teacher role
            if ($allwogarecourse) {
                foreach ($allwogarecourse as $wogcourse) {

                    $context = context_course::instance($wogcourse->courseid);

                    $alreadyassigned = $DB->record_exists(
                        'role_assignments',
                        [
                            'userid' => $USER->id,
                            'roleid' => $role->id,
                            'contextid' => $context->id
                        ]
                    );

                    if (!$alreadyassigned) {
                        enrolteacherCourse($wogcourse->courseid, $USER->id, $role->id, 0, time());
                    }
                }
            }
                    
        }
    } 

    public static function role_assigned(\core\event\role_assigned $event) {
        global $DB;

        $courseid = $event->courseid;
        $course = $DB->get_record('course', ['id' => $courseid]);
        if (!$course) return;
        $category = $course->category;
        $role = $DB->get_record('role', ['shortname' => 'companyrestriction']);
        if (!$role) return;
        $sql = "
            SELECT cu.userid 
            FROM {company_category_mapping} ccm
            JOIN {company_users} cu ON cu.companyid = ccm.company_id
            WHERE FIND_IN_SET(:categoryid, ccm.course_category)
              AND cu.managertype = 1
        ";
        $params = ['categoryid' => $category];
        $managers = $DB->get_records_sql($sql, $params);
        foreach ($managers as $manager) {
            // self::enrolCourse($courseid, $manager->userid, $role->id, 0, time());
        }
    }

    public static function enrolCourse($courseid, $userid, $roleid,$endtime,$startime) { 
        global $DB, $CFG;
        $query = 'SELECT * FROM {enrol} WHERE enrol = "manual" AND courseid = '.$courseid;
        $enrollmentID = $DB->get_record_sql($query);
        if(!empty($enrollmentID->id)) {
            if (!$DB->record_exists('user_enrolments', array('enrolid'=>$enrollmentID->id, 'userid'=>$userid))) {
                $userenrol = new stdClass();
                $userenrol->status = 0;
                $userenrol->userid = $userid;
                $userenrol->enrolid = $enrollmentID->id;
                $userenrol->timestart  = $startime;
                $userenrol->timeend = $endtime;
                $userenrol->modifierid  = 2;
                $userenrol->timecreated  = time();
                $userenrol->timemodified  = time();
                $enrol_manual = enrol_get_plugin('manual');
                $enrol_manual->enrol_user($enrollmentID, $userid, $roleid, $userenrol->timestart, $userenrol->timeend);
            }
        }
    }

    public static function user_license_assigned(\block_iomad_company_admin\event\user_license_assigned $event) {
        global $DB, $CFG ,$SESSION;
        require_once($CFG->dirroot . '/local/learnpeek_email_template/locallib.php');
        $userid = $event->userid;
        $userlicid = $event->objectid;
        $licenseid = $event->other['licenseid'];
        $courseid = $event->courseid;
        $duedate = $event->other['duedate'];
        // self::course_assignment_email_to_user($userid, $courseid, $licenseid);

        // Stop multi time 
        if (!isset($SESSION->license_email_sent)) {
            $SESSION->license_email_sent = [];
        }

        $key = $userid . '_' . $licenseid;

        // Send email only once per session
        if (empty($SESSION->license_email_sent[$key])) {

            self::course_assignment_email_to_user($userid, $courseid, $licenseid);
            course_assignment_email_to_whitelable($userid, $courseid, $licenseid);

            // Mark as sent
            $SESSION->license_email_sent[$key] = true;
        }

        //// Stop multi time 

        $getinvoiceitem = $DB->get_record_sql("SELECT cli.*,it.groupid FROM {companylicense_info} as cli INNER JOIN {invoiceitem} as it ON it.id = cli.invoiceitem_id WHERE cli.companylicense_id =".$licenseid);
        $groupid = $getinvoiceitem->groupid;
        if($groupid){
            groups_add_member($groupid, $userid);

            // auto book reservation
            $sqlsections = "SELECT *
                FROM {course_sections}
                WHERE availability LIKE :pattern";

            $params = [
                'pattern' => '%"type":"group","id":' . (int)$groupid . '%'
            ];

            $sections = $DB->get_record_sql($sqlsections, $params);
            $getcoursemd = $DB->get_records_sql("SELECT cm.*,m.name 
                                    FROM {course_modules} as cm 
                                    INNER JOIN {modules} as m 
                                    ON cm.module = m.id 
                                    WHERE cm.deletioninprogress !=1 AND m.name = 'reservation' AND section = ?",array($sections->id));
            if($getcoursemd){
                foreach ($getcoursemd as $coursemd) {
                    $res_request = new \stdClass;
                    $res_request->reservation  = $coursemd->instance;
                    $res_request->userid       = $userid;
                    $res_request->timecreated  = time();
                    $resrequest = $DB->insert_record('reservation_request', $res_request);

                }
            }
            // end auto book reservation
        }
        // die;

    }
    
    //overwrite delete event

    public static function course_deleted(\core\event\course_deleted $event) {
        global $DB,$CFG;

        $path1 = $CFG->cachedirect . '/cache';
        $path2 = $CFG->cachedirect . '/localcache';
        deleteFolderAndFiles($path1);
        deleteFolderAndFiles($path2);
    }

    public static function course_module_deleted(\core\event\course_module_deleted $event) {
        global $DB,$CFG;
        $path1 = $CFG->cachedirect . '/cache';
        $path2 = $CFG->cachedirect . '/localcache';
        deleteFolderAndFiles($path1);
        deleteFolderAndFiles($path2);
    }

    public static function course_section_deleted(\core\event\course_section_deleted $event) {
        global $DB,$CFG;
        $path1 = $CFG->cachedirect . '/cache';
        $path2 = $CFG->cachedirect . '/localcache';
        deleteFolderAndFiles($path1);
        deleteFolderAndFiles($path2);
    }

    public static function course_category_deleted(\core\event\course_category_deleted $event) {
        global $DB,$CFG;
        $path1 = $CFG->cachedirect . '/cache';
        $path2 = $CFG->cachedirect . '/localcache';
        deleteFolderAndFiles($path1);
        deleteFolderAndFiles($path2);
    }

    public static function course_assignment_email_to_user($userid, $courseid, $licenseid, $preview = false) {
        global $DB, $CFG, $USER;
        // --- Get company ID of the current logged-in user ---
        $companyid = $DB->get_field('company_users', 'companyid', ['userid' => $USER->id], MUST_EXIST);

        // --- Load template from DB ---
        $gettemplate = $DB->get_record_sql(
            "SELECT * FROM {custom_email_template} WHERE email_template = ?",
            ['notify-user-course-assign']
        );
        if (!$gettemplate) {
            // fallback to global template
            $gettemplate = $DB->get_record_sql(
                "SELECT * FROM {custom_email_template} WHERE company_id = ? AND email_template = ?",
                [0, 'notify-user-course-assign']
            );
        }
        if (!$gettemplate) {
            return false; // no template found
        }

          $userrole = $DB->get_record_sql("SELECT * FROM {company_users} WHERE userid = ? AND managertype = 1",array($USER->id));
          if($userrole){
            $regards_by = "";
                 //get compnay logo
                $comlogodetails = $DB->get_record_sql('SELECT * FROM {company_logo} WHERE company_id = ?', array($companyid));
                if ($comlogodetails) {
                    $companylogo = $DB->get_record_sql("SELECT * FROM {files} WHERE contextid= 1 AND component='landingpage' AND itemid=? AND filesize > 0 AND filearea = ?", array($comlogodetails->id, 'logo_image'));
                    $logourl = $CFG->wwwroot . '/blocks/custom_mycourse/file.php?id=' . $companylogo->pathnamehash;
                    $company_logo = "<img src='" . $logourl . "' style='width: 125px;max-height: 150px;object-fit: cover;'>";
                } else {
                    $company_logo = "";
                }
          }else{
            $regards_by = fullname($USER);
            $company_logo = "";
          }

        $email_template = $gettemplate->email_message ?: '';
        $subject_template = $gettemplate->email_subject ?: "You have been assigned to the course {{course_name}}.";

        // --- Fetch user and course ---
        $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

        // --- Build Assign Table (all courses under same license) ---
        $license_courses = $DB->get_records('companylicense_courses', ['licenseid' => $licenseid]);
        $table_html = '<table border="1" cellpadding="5" cellspacing="0"><tr><th>Course Name</th><th>Link</th></tr>';
        $coursenames = array();
        foreach ($license_courses as $lc) {
            $c = $DB->get_record('course', ['id' => $lc->courseid]);
            if ($c) {
                $coursenames[] = format_string($c->fullname);
                $courseurl = $CFG->wwwroot . '/course/view.php?id=' . $c->id;
                $table_html .= '
                    <tr>
                        <td>' . format_string($c->fullname) . '</td>
                        <td><a href="' . $courseurl . '">'.$courseurl.'</a></td>
                    </tr>';
            }
        }
        $table_html .= '</table>';

        // --- Placeholders and replacements ---
        $placeholders = ['{{user_name}}', '{{course_name}}', '{{assigned_by}}', '{{assign_table}}','{{regards_by}}','{{company_logo}}'];
        $replacements = [
            fullname($user),
            implode(", " , $coursenames),
            fullname($USER),
            $table_html,
            $regards_by,
            $company_logo
        ];

        $email_body = str_replace($placeholders, $replacements, $email_template);
        $subject = str_replace($placeholders, $replacements, $subject_template);

        // --- Build HTML + plain text ---
        $messagehtml = "<!DOCTYPE html>
            <html>
                <head><meta charset='utf-8'><title>{$subject}</title></head>
                <body>$email_body</body>
            </html>";
        $messagetext = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $email_body));
        // --- Preview mode ---
        if ($preview) {
            echo "<h2>Email Preview</h2>";
            echo "<strong>Subject:</strong> " . format_string($subject) . "<br><br>";
            echo $messagehtml;
            exit;
        }

        // --- From user ---
        $companyname = $DB->get_field('company', 'name', ['id' => $companyid]);
        $fromUser = fullname($USER);

        // --- Recipient ---
        $emailuser = (object)[
            'email' => $user->email,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'maildisplay' => true,
            'mailformat' => 1,
            'id' => $user->id
        ];

        if(!empty($gettemplate->destination_email)){
            $emailuser->email = $gettemplate->destination_email;
        }
        // --- Send email ---
        return email_learnpeek_to_user(
            $emailuser,
            $fromUser,
            $subject,
            $messagetext,
            $messagehtml,
            '', '', true, '', '', 79, $companyid
        );
    }
    // // uday
    // public static function course_assignment_email_to_whitelable($userid, $courseid, $licenseid, $preview = false) {
    //     global $DB, $CFG, $USER;
    //     // --- Get company ID of the current logged-in user ---
    //     $companyid = $DB->get_field('company_users', 'companyid', ['userid' => $USER->id], MUST_EXIST);

    //     // --- Load template from DB ---
    //     $gettemplate = $DB->get_record_sql(
    //         "SELECT * FROM {custom_email_template} WHERE email_template = ?",
    //         ['course-enrol-whitelabeladmin']
    //     );
    //     if (!$gettemplate) {
    //         // fallback to global template
    //         $gettemplate = $DB->get_record_sql(
    //             "SELECT * FROM {custom_email_template} WHERE company_id = ? AND email_template = ?",
    //             [0, 'course-enrol-whitelabeladmin']
    //         );
    //     }
    //     if (!$gettemplate) {
    //         return false; // no template found
    //     }

    //     //   $userrole = $DB->get_record_sql("SELECT * FROM {company_users} WHERE userid = ? AND managertype = 1",array($USER->id));
    //     //   if($userrole){
    //     //     $regards_by = "";
    //     //          //get compnay logo
    //     //         $comlogodetails = $DB->get_record_sql('SELECT * FROM {company_logo} WHERE company_id = ?', array($companyid));
    //     //         if ($comlogodetails) {
    //     //             $companylogo = $DB->get_record_sql("SELECT * FROM {files} WHERE contextid= 1 AND component='landingpage' AND itemid=? AND filesize > 0 AND filearea = ?", array($comlogodetails->id, 'logo_image'));
    //     //             $logourl = $CFG->wwwroot . '/blocks/custom_mycourse/file.php?id=' . $companylogo->pathnamehash;
    //     //             $company_logo = "<img src='" . $logourl . "' style='width: 125px;max-height: 150px;object-fit: cover;'>";
    //     //         } else {
    //     //             $company_logo = "";
    //     //         }
    //     //   }else{
    //     //     $regards_by = fullname($USER);
    //     //     $company_logo = "";
    //     //   }

    //     $comlogodetails = $DB->get_record_sql('SELECT * FROM {company_logo} WHERE company_id = ?', array($companyid));
    //     if ($comlogodetails) {
    //         $companylogo = $DB->get_record_sql("SELECT * FROM {files} WHERE contextid= 1 AND component='landingpage' AND itemid=? AND filesize > 0 AND filearea = ?", array($comlogodetails->id, 'logo_image'));
    //         $logourl = $CFG->wwwroot . '/blocks/custom_mycourse/file.php?id=' . $companylogo->pathnamehash;
    //     $company_logo = "<img src='" . $logourl . "' width='125' ' style='display:block; width:125px;  max-width:125px; object-fit:contain;' alt='Company Logo'>";
    //     } else {
    //         $company_logo = "";
    //     }

    //     $email_template = $gettemplate->email_message ?: '';
    //     $subject_template = $gettemplate->email_subject ?: "You have been assigned to the course {{course_name}}.";

    //     // --- Fetch user and course ---
    //     $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
    //     $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

    //     // --- Build Assign Table (all courses under same license) ---
    //     $license_courses = $DB->get_records('companylicense_courses', ['licenseid' => $licenseid]);
    //     $table_html = '<table border="1" cellpadding="5" cellspacing="0"><tr><th>Course Name</th></tr>';
    //     $coursenames = array();
    //     foreach ($license_courses as $lc) {
    //         $c = $DB->get_record('course', ['id' => $lc->courseid]);
    //         if ($c) {
    //             $coursenames[] = format_string($c->fullname);
    //             $table_html .= '<tr><td>' . format_string($c->fullname) . '</td></tr>';
    //         }
    //     }
    //     $table_html .= '</table>';

    //     $fromUser = core_user::get_support_user();
    //     $whitelabeluser = null;
    //     if ($getcompany_wu = $DB->get_record_sql(
    //         "SELECT * FROM {company_users} WHERE companyid = ? AND managertype = ?", 
    //         [$companyid, 1]
    //     )) {
    //         if ($whitelabeluser = $DB->get_record('user', ['id' => $getcompany_wu->userid])) {
    //             $fromUser = fullname($whitelabeluser);
    //         }
    //     }
    //     echo "user - $fromUser";
    //     // --- Placeholders and replacements ---
    //     $placeholders = ['{{user_name}}', '{{name}}', '{{course_name}}', '{{assign_table}}', '{{company_logo}}'];
    //     $replacements = [
    //         $fromUser,
    //         fullname($user),
    //         implode(", " , $coursenames),
    //         $table_html,
    //         $company_logo
    //     ];

    //     $email_body = str_replace($placeholders, $replacements, $email_template);
    //     $subject = str_replace($placeholders, $replacements, $subject_template);

    //     // --- Build HTML + plain text ---
    //     $messagehtml = "<!DOCTYPE html>
    //         <html>
    //             <head><meta charset='utf-8'><title>{$subject}</title></head>
    //             <body>$email_body</body>
    //         </html>";
    //     $messagetext = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $email_body));
    //     // --- Preview mode ---
    //     if ($preview) {
    //         echo "<h2>Email Preview</h2>";
    //         echo "<strong>Subject:</strong> " . format_string($subject) . "<br><br>";
    //         echo $messagehtml;
    //         exit;
    //     }

    //     // --- From user ---
    //     $companyname = $DB->get_field('company', 'name', ['id' => $companyid]);
    //     $fromUser = fullname($USER);

    //     // --- Recipient ---
    //     $emailuser = (object)[
    //         'email' => $whitelabeluser->email,
    //         'firstname' => $whitelabeluser->firstname,
    //         'lastname' => $whitelabeluser->lastname,
    //         'maildisplay' => true,
    //         'mailformat' => 1,
    //         'id' => $whitelabeluser->id
    //     ];

    //     if(!empty($gettemplate->destination_email)){
    //         $emailuser->email = $gettemplate->destination_email;
    //     }
    //     // --- Send email ---
    //     return email_learnpeek_to_user(
    //         $emailuser,
    //         $fromUser,
    //         $subject,
    //         $messagetext,
    //         $messagehtml,
    //         '', '', true, '', '', 79, $companyid
    //     );
    // }

    public static function course_updated(\core\event\course_updated $event) {
        global $USER, $DB, $CFG;
        require_once($CFG->dirroot . '/local/customlayout/locallib.php');
        $targetcategories = [40, 50, 51];
        $allcategories = get_all_subcategory_ids($targetcategories);

        $courseid = $event->courseid;
        $course = $DB->get_record("course", ["id" => $courseid]);
        if ($course) {
            $category = $course->category;
            if (in_array($category, $allcategories)) {
                $customfield = $DB->get_record('customfield_field', ['shortname' => 'scanface_required'], '*', IGNORE_MISSING);
                if ($customfield) {
                    $fieldid = $customfield->id;
                    $custmfdta = $DB->get_record('customfield_data', [
                                        'instanceid' => $courseid,
                                        'fieldid' => $fieldid,
                                    ]);
                    if($custmfdta->value != 1 ){
                        $subject = "Scanface Disabled Courses Alert";
                        $messagehtml = "<p>Scan face disabled course details are mentioned below:</p>";
                        $messagehtml .= $course->fullname . "<br>";
                        $userfrom = \core_user::get_noreply_user();
                        $emailuser = (object) [];
                        $emailuser->email = "contact@learnpeek.com";
                        $emailuser->firstname = 'Admin';
                        $emailuser->lastname = '';
                        $emailuser->maildisplay = true;
                        $emailuser->mailformat = 1; // 0 (zero) text-only emails, 1 (one) for HTML/Text emails.
                        $emailuser->id = 1;
                        $emailuser->firstnamephonetic = false;
                        $emailuser->lastnamephonetic = false;
                        $emailuser->middlename = false;
                        $emailuser->username = false;
                        $emailuser->alternatename = false;
                        email_learnpeek_to_user($emailuser, $userfrom, $subject, $message = '',$messagehtml);
                        $emailuser->email = "suneet@lds-international.in";
                        email_learnpeek_to_user($emailuser, $userfrom, $subject, $message = '',$messagehtml);

                    }
                }
            }
        }

        //update course mapping

        $category = $course ? $course->category : 0;

        $sql = "SELECT *
                  FROM {company_category_mapping}
                 WHERE FIND_IN_SET(:categoryid, course_category)";

        $params = ['categoryid' => $category];
        $company_mappings = $DB->get_records_sql($sql, $params);

        if (!empty($company_mappings)) {
            foreach ($company_mappings as $mapping) {
                $companyid = $mapping->company_id;

                $existing = $DB->record_exists('company_course', [
                    'companyid' => $companyid,
                    'courseid' => $courseid
                ]);

                if ($existing) {
                    continue;
                }

                $department = $DB->get_record(
                    'department',
                    ['company' => $companyid]
                );

                $dataObj = new stdClass();
                $dataObj->companyid = $companyid;
                $dataObj->courseid = $courseid;
                $dataObj->departmentid = $department ? $department->id : 0;

            
                $DB->insert_record('company_course', $dataObj);
            }
        }
    }

}
