<?php
use core_completion\progress;
class block_custom_businessuserdashboard extends block_base {
    public function init() {
        $this->title = get_string('plugintitle', 'block_custom_businessuserdashboard');
    }
    public function department_total() {
        global $DB, $USER;

        $sql = "SELECT COUNT(DISTINCT id)
                FROM {customdepartment}
                WHERE businessuserid = ?";

        return $DB->get_field_sql($sql, [$USER->id]);
    }

    public function department_totalusers() {
        global $DB, $USER;

        $sql = "SELECT COUNT(DISTINCT u.id)
                FROM {business_user_child} buc
                INNER JOIN {user} u ON u.id = buc.child_id
                WHERE buc.parent_id = ?
                  AND u.suspended = 0
                  AND u.deleted = 0";

        return $DB->get_field_sql($sql, [$USER->id]);
    }

    public function department_totalmanager() {
        global $DB, $USER;

        $sql = "SELECT COUNT(DISTINCT u.id) 
                FROM {customdepartment_assign} cda 
                INNER JOIN {user} u ON u.id = cda.child_id 
                WHERE cda.parent_id = ? 
                AND cda.is_departmentmanager = 1 
                AND u.suspended = 0 
                AND u.deleted = 0";

        return $DB->get_field_sql($sql, [$USER->id]);
    }

    public function department_totaluserenrolled() {
        global $DB, $USER;

        $sql = "SELECT COUNT(ue.id) 
                FROM {business_user_child} buc 
                INNER JOIN {user} u ON u.id = buc.child_id 
                INNER JOIN {user_enrolments} ue ON ue.userid = u.id 
                INNER JOIN {enrol} e ON e.id = ue.enrolid 
                WHERE buc.parent_id = ? 
                AND u.suspended = 0 
                AND u.deleted=0";
        return $DB->get_field_sql($sql, [$USER->id]);
    }

    public function department_totalusercompletedcourse() {
        global $DB, $USER;

        $sql = "SELECT count(cc.id) 
                FROM {business_user_child} buc 
                INNER JOIN {user} u ON u.id = buc.child_id 
                INNER JOIN {user_enrolments} ue ON ue.userid = u.id 
                INNER JOIN {enrol} e ON e.id = ue.enrolid 
                INNER JOIN {course_completions} cc ON cc.userid = u.id AND cc.course = e.courseid 
                WHERE buc.parent_id = ? 
                AND u.suspended = 0 
                AND u.deleted=0 
                AND cc.timecompleted IS NOT NULL";
        return $DB->get_field_sql($sql, [$USER->id]);
    }

    public function department_totaluseroverduecourses() {
        global $DB , $USER;

        $currenttime = time();
        $sql = "SELECT count(cc.id) 
                FROM {business_user_child} buc 
                INNER JOIN {user} u ON u.id = buc.child_id 
                INNER JOIN {user_enrolments} ue ON ue.userid = u.id 
                INNER JOIN {enrol} e ON e.id = ue.enrolid 
                INNER JOIN {course} c ON c.id = e.courseid 
                INNER JOIN {course_completions} cc ON cc.userid = u.id AND cc.course = e.courseid 
                WHERE buc.parent_id = ? 
                AND u.suspended = 0 
                AND u.deleted=0 
                AND cc.timecompleted IS NULL
                AND c.enddate != 0 AND c.enddate < ?";
        $overduecourses = $DB->get_field_sql($sql, [$USER->id, $currenttime]);
        return $overduecourses;
    }


    public function get_content() {
        global $PAGE, $USER, $DB, $CFG;

        if (!has_capability('block/custom_businessuserdashboard:view', context_block::instance($this->instance->id)) || is_siteadmin()) {
            return null; // completely hide block
        }
        
        $this->content = new stdClass();
        $companyid = iomad::get_my_companyid(context_system::instance());


        $html = '
            <style>
                .dashboard-card {
                    background: #f0f4fa;
                    border: none;
                    border-radius: 8px;
                    padding: 10px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                    display: flex;
                    align-items: center;
                    height: 90px;
                    margin-bottom: 10px;
                }
                .dashboard-card .icon {
                    font-size: 24px;
                    color: #5c8ff9;
                    margin-right: 15px;
                }
                .dashboard-card .text-content {
                    flex: 1;
                }
                .dashboard-card .card-title {
                    font-size: 14px;
                    color: #757575;
                    margin-bottom: 2px;
                }
                .dashboard-card .card-value {
                    font-size: 24px;
                    font-weight: 600;
                    color: #212121;
                    margin-bottom: 2px;
                }
                .dashboard-card .card-subtitle {
                    font-size: 12px;
                    color: #a0a0a0;
                }
                .kpi-section .col-md-4 {
                    padding: 5px;
                }
                .progress {
                    height: 8px;
                    background-color: #e0e0e0;
                    width: 150px;
                    display: inline-block;
                    vertical-align: middle;
                    margin: 0 10px;
                }
                .progress-bar-custom {
                    height: 8px;
                    background-color:rgb(86, 85, 85);
                }
                .course-item {
                    display: flex;
                    align-items: center;
                    margin-bottom: 10px;
                }
                .review-item {
                    display: flex;
                    align-items: center;
                    margin-bottom: 10px;
                }
                .review-item .fa-user-circle {
                    font-size: 24px;
                    color: #757575;
                    margin-right: 10px;
                }
                .review-text {
                    font-size: 12px;
                    color: #757575;
                    margin-right: 10px;
                }
                .rating {
                    color: #ffca28;
                    font-size: 14px;
                }
                h3, h5 {
                    color: #212121;
                }
                .chart-card.cst_columns_area span {
                    width: 70%;
                }

                .dm-dashborad {
                    padding: 20px;
                    border-radius: 10px;
                    box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;
                    box-shadow: rgba(17, 12, 46, 0.15) 0px 48px 100px 0px;
                }
            </style>
        ';

        $totaldepartment    = $this->department_total();
        $totaluser          = $this->department_totalusers();
        $departmentmanagers = $this->department_totalmanager();
        $enrolledcourses    = $this->department_totaluserenrolled();
        $completedcourses   = $this->department_totalusercompletedcourse();
        $overduecourses     = $this->department_totaluseroverduecourses();
        // $assigndepartments = $DB->get_records_sql("SELECT * FROM {customdepartment} WHERE businessuserid = ?", [$USER->id]);
        $departments = $DB->record_exists('customdepartment', ['businessuserid' => $USER->id]);

        if (!$departments) {
            $this->content = new stdClass();
            $this->content->text = '
                <div style="
                    background: #f5f7fb;
                    border: 1px dashed #cfd8dc;
                    border-radius: 10px;
                    padding: 25px;
                    text-align: center;
                    color: #6b7280;
                ">
                    <div style="font-size: 16px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                        No Departments Yet
                    </div>
                    <div style="font-size: 13px;">
                        You haven’t added any departments yet. Create a department to start managing users and courses.
                    </div>
                </div>
            ';

            return $this->content;
        }
        
        $html .='<div class="mt-4 m-3">
                    <div class="kpi-section dm-dashborad bg_custom" style="margin: initial;">
                     <h3 class="mb-3 text-white">' . '</h3>
                       <div class="row">
                           <div class="col-md-4">
                            <div class="dashboard-card">
                                <i class="fas fa-building icon"></i>
                                <div class="text-content">
                                    <div class="card-title">'. get_string('total_departments', 'block_custom_businessuserdashboard') .'</div>
                                    <div class="card-value">'. $totaldepartment  . '</div>
                                    <div class="card-subtitle"></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="dashboard-card">
                                <i class="fas fa-users icon"></i>
                                <div class="text-content">
                                    <div class="card-title">'. get_string('total_users', 'block_custom_businessuserdashboard') .'</div>
                                    <div class="card-value">'. $totaluser .'</div>
                                    <div class="card-subtitle"></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="dashboard-card">
                                <i class="fa-solid fa-user-gear icon"></i>
                                <div class="text-content">
                                    <div class="card-title">'. get_string('department_managers', 'block_custom_businessuserdashboard') .'</div>
                                    <div class="card-value"> '.$departmentmanagers.' </div>
                                    <div class="card-subtitle"></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="dashboard-card">
                                <i class="fas fa-book icon"></i>
                                <div class="text-content">
                                    <div class="card-title">'. get_string('enrolled_cousres', 'block_custom_businessuserdashboard') .'</div>
                                    <div class="card-value">'.$enrolledcourses.'</div>
                                    <div class="card-subtitle"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="dashboard-card">
                                <i class="fas fa-check-circle icon"></i>
                                <div class="text-content">
                                    <div class="card-title">'. get_string('course_completed', 'block_custom_businessuserdashboard') .'</div>
                                    <div class="card-value"> '.$completedcourses.' </div>
                                    <div class="card-subtitle"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="dashboard-card">
                                <i class="fas fa-exclamation-triangle icon"></i>
                                <div class="text-content">
                                    <div class="card-title">'. get_string('overduecourses', 'block_custom_businessuserdashboard') .'</div>
                                    <div class="card-value"> '.$overduecourses.' </div>
                                    <div class="card-subtitle"></div>
                                </div>
                            </div>
                        </div>
                       </div>
                    </div>
                </div>';
            $this->content->text = $html;
            return $this->content;
        }
    }