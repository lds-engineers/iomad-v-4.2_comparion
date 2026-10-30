<?php
use core_completion\progress;
class block_custom_wdashboard extends block_base {
    public function init() {
        $this->title = '';
    }
    public function get_most_popular_courses() {
        global $DB;
        $companyid = iomad::get_my_companyid(context_system::instance());
        // $sql = "SELECT c.id, c.fullname, COUNT(ue.id) AS enrollments
        //         FROM {company_course} cc
        //         JOIN {course} c ON cc.courseid = c.id
        //         LEFT JOIN {enrol} e ON e.courseid = c.id
        //         LEFT JOIN {user_enrolments} ue ON ue.enrolid = e.id
        //         WHERE cc.companyid = ?
        //         GROUP BY c.id, c.fullname
        //         ORDER BY enrollments DESC";

        $sql = "SELECT 
                c.id , c.fullname,
                COUNT(DISTINCT ue.userid) AS enrollments
                FROM {company_course} cc
                JOIN {course} c ON cc.courseid = c.id
                LEFT JOIN {enrol} e ON e.courseid = c.id
                LEFT JOIN {user_enrolments} ue ON ue.enrolid = e.id
                INNER JOIN {company_users} cu ON cu.userid = ue.userid AND cu.companyid = cc.companyid AND cu.managertype !=1
                WHERE cc.companyid = ?
                GROUP BY c.id, c.fullname
                ORDER BY enrollments DESC";      






        $popular_courses = $DB->get_records_sql($sql, array($companyid));
        return $popular_courses;
    }
    public function courses_old() {
        global $DB;

        $companyid = iomad::get_my_companyid(context_system::instance());
        $company_course = $DB->get_records('company_course', ['companyid' => $companyid]);
        $count = 0;

        if (sizeof($company_course) > 0) {
            foreach ($company_course as $data) {
                $item = $DB->count_records_sql('
                    SELECT COUNT(csc.courseid)  
                    FROM {course_shopsettings} AS css 
                    INNER JOIN {course_shopsettings_courses} AS csc ON csc.itemid = css.id  
                    INNER JOIN {course} AS c ON c.id = csc.courseid 
                    WHERE css.companyid = ? AND c.id = ? AND css.enabled = 1',
                    array($companyid, $data->courseid));
                
                if ($item) {
                    $count += $item;
                }
            }
            
        }
        return $count;
    }

    public function courses() {
        global $DB;

        $companyid = iomad::get_my_companyid(context_system::instance());
        $company_course = $DB->get_records('company_course', ['companyid' => $companyid]);
        $count = 0;

        $compnayallproduct = $DB->get_records_sql("SELECT css.id,css.companyid,css.name,css.enabled,csc.itemid,csc.courseid 
            FROM {course_shopsettings} as css 
            INNER JOIN {course_shopsettings_courses} as csc ON csc.itemid = css.id 
            INNER JOIN {course} AS c ON c.id = csc.courseid 
            WHERE css.companyid = ? AND css.enabled = 1",
            array($companyid));
        $count = count($compnayallproduct);

        // if (sizeof($company_course) > 0) {
        //     foreach ($company_course as $data) {
        //         $item = $DB->count_records_sql('
        //             SELECT COUNT(csc.courseid)  
        //             FROM {course_shopsettings} AS css 
        //             INNER JOIN {course_shopsettings_courses} AS csc ON csc.itemid = css.id  
        //             INNER JOIN {course} AS c ON c.id = csc.courseid 
        //             WHERE css.companyid = ? AND c.id = ? AND css.enabled = 1',
        //             array($companyid, $data->courseid));
                
        //         if ($item) {
        //             $count += $item;
        //         }
        //     }
            
        // }
        return $count;
    }

    public function users() {
        global $DB;
        $companyid = iomad::get_my_companyid(context_system::instance());
        $sql = "SELECT COUNT(distinct u.id)
                FROM {user} u
                INNER JOIN {company_users} cu ON cu.userid = u.id
                WHERE (cu.managertype=0 OR cu.managertype=2) AND u.deleted = 0 AND  cu.companyid = ?";
        $count = $DB->count_records_sql($sql, [$companyid]);
        return $count;
    }

    public function newusers() {
        global $DB;

        $companyid = iomad::get_my_companyid(context_system::instance());
        // $startofmonth = strtotime(date('Y-m-01 00:00:00'));
        $startofmonth = strtotime(date("01 F Y"));

        $sql = "SELECT COUNT(u.id)
                FROM {user} u
                INNER JOIN {company_users} cu ON cu.userid = u.id AND u.deleted = 0
                WHERE (cu.managertype=0 OR cu.managertype=2) AND  cu.companyid = ? AND u.timecreated >= ?";
        $count = $DB->count_records_sql($sql, [$companyid, $startofmonth]);
        return $count;
    }

    public function inprogress() {
        global $DB;

        $companyid = iomad::get_my_companyid(context_system::instance());

        $sql = "SELECT COUNT(cc.userid)
                FROM {course_completions} cc
                INNER JOIN {company_users} cu ON cu.userid = cc.userid
                WHERE cu.companyid = ? AND cc.timecompleted IS NULL";
        $count = $DB->count_records_sql($sql, [$companyid]);

        return $count;
    }


    public function inprogress_courses() {
    global $DB;

    $companyid = iomad::get_my_companyid(context_system::instance());

    $sql = "SELECT COUNT(*)
            FROM {course_completions} cc
            INNER JOIN {company_users} cu ON cu.userid = cc.userid
            WHERE cu.companyid = ? AND cc.timecompleted IS NULL";

    $count = $DB->count_records_sql($sql, [$companyid]);

    return $count;
}



    public function overduecourses() {
        global $DB;

        $companyid = iomad::get_my_companyid(context_system::instance());
        $now = time();

        $sql = "SELECT COUNT(cc.userid)
                FROM {course_completions} cc
                INNER JOIN {course} c ON cc.course = c.id
                INNER JOIN {company_course} coc ON coc.courseid = c.id
                WHERE coc.companyid = ?
                  AND cc.timecompleted IS NULL
                  AND cc.timestarted IS NOT NULL
                  AND c.enddate > 0 AND c.enddate < ?";
        $count = $DB->count_records_sql($sql, [$companyid, $now]);

        return $count;
    }

    public function generalcompletionrate() {
        global $DB;

        $companyid = iomad::get_my_companyid(context_system::instance());


                //SELECT COUNT(DISTINCT u.id) FROM `mdl_user_enrolments` as ue INNER JOIN mdl_company_users as cu ON cu.userid = ue.userid INNER JOIN mdl_user as u ON u.id =cu.userid AND u.deleted = 0 WHERE cu.companyid = 42 AND (cu.managertype=0 OR cu.managertype=2);



        $sql_total = "SELECT COUNT( u.id) FROM {user_enrolments} as ue 
                        INNER JOIN {company_users} as cu ON cu.userid = ue.userid 
                        INNER JOIN {user} as u ON u.id =cu.userid AND u.deleted = 0 
                        WHERE cu.companyid = ? AND (cu.managertype=0 OR cu.managertype=2)";

        // $sql_total = "SELECT COUNT(cc.id)
        //               FROM {course_completions} cc
        //               INNER JOIN {company_users} cu ON cu.userid = cc.userid
        //               WHERE cu.companyid = ? AND (cu.managertype=0 OR cu.managertype=2)";
        $sql_completed = "SELECT COUNT(cc.id)
                          FROM {course_completions} cc
                          INNER JOIN {company_users} cu ON cu.userid = cc.userid
                          WHERE cu.companyid = ? AND (cu.managertype=0 OR cu.managertype=2)  AND cc.timecompleted IS NOT NULL";

        $total = $DB->count_records_sql($sql_total, [$companyid]);

        $completed = $DB->count_records_sql($sql_completed, [$companyid]);
        // echo "<pre>";
        // print_r($total);
        // echo "----------------------";
        // print_r($completed);
        // echo "</pre>"; 
        // die;

        if ($total == 0) {
            return '0%';
        }

        $rate = round(($completed / $total) * 100);
        return $rate . '%';
    }

    public function get_latest_reviews() {
        global $DB;
        $companyid = iomad::get_my_companyid(context_system::instance());
        $where = "r.status=1 AND cu.companyid = :companyid";
        $params['companyid'] = $companyid;
        $sql = "SELECT 
                    r.id AS rid,
                    u.id AS userid,
                    u.firstname,
                    u.lastname,
                    r.rating AS rating,
                    r.review AS review,
                    r.timemodified AS timemodified,
                    r.courseid AS courseid,
                    c.fullname AS coursename
                FROM {tool_courserating_rating} r
                INNER JOIN {user} u ON u.id = r.userid
                INNER JOIN {course} c ON c.id = r.courseid
                INNER JOIN {company_users} cu ON cu.userid = u.id
                WHERE $where
                ORDER BY r.timecreated DESC
                LIMIT 10";
        $records = $DB->get_records_sql($sql, $params);
        return $records;
    }

    public function get_content() {
        global $PAGE, $USER, $DB, $CFG;
        
        $this->content = new stdClass();
        $companyid = iomad::get_my_companyid(context_system::instance());

        $labels = [];
        $gross_volume = [];
        $payment_counts = [];
        $gross_reference = [];
        $payment_reference = [];
        $previous_gross_total = 0;
        $previous_payments_total = 0;

        $daysInMonth = date('t');
        $startOfMonth = date('Y-m-01');


        for ($day = 0; $day < $daysInMonth; $day++) {
            $date = date('Y-m-d', strtotime("$startOfMonth +$day days"));
            $label = date('M j', strtotime($date));
            $labels[] = $label;

            // Fill default values
            $gross_volume[$label] = 0;
            $payment_counts[$label] = 0;
        }

        // Fetch current month's Gross Volume data
        $current_gross_sql = "
            SELECT DATE(FROM_UNIXTIME(i.date)) AS invoicedate,
                   SUM(ii.price * ii.license_allocation * ii.quantity) AS total
            FROM {invoice} i
            JOIN {invoiceitem} ii ON ii.invoiceid = i.id
            WHERE i.companyid = :companyid AND i.status = 'p'
              AND MONTH(FROM_UNIXTIME(i.date)) = MONTH(CURDATE())
              AND YEAR(FROM_UNIXTIME(i.date)) = YEAR(CURDATE())
            GROUP BY invoicedate
            ORDER BY invoicedate
        ";
        $current_gross_data = $DB->get_records_sql($current_gross_sql, ['companyid' => $companyid]);

        // Fetch current month's Successful Payment counts
        $current_payment_sql = "
            SELECT DATE(FROM_UNIXTIME(i.date)) AS paymentdate,
                   COUNT(*) AS count
            FROM {invoice} i
            WHERE i.companyid = :companyid AND i.status = 'p'
              AND MONTH(FROM_UNIXTIME(i.date)) = MONTH(CURDATE())
              AND YEAR(FROM_UNIXTIME(i.date)) = YEAR(CURDATE())
            GROUP BY paymentdate
            ORDER BY paymentdate
        ";
        $current_payment_data = $DB->get_records_sql($current_payment_sql, ['companyid' => $companyid]);

        // Populate current month data
        foreach ($current_gross_data as $record) {
            $label = date('M j', strtotime($record->invoicedate));
            $gross_volume[$label] = round($record->total, 2);
        }
        foreach ($current_payment_data as $record) {
            $label = date('M j', strtotime($record->paymentdate));
            $payment_counts[$label] = (int)$record->count;
        }

        $gross_volume = array_values($gross_volume);
        $payment_counts = array_values($payment_counts);

        // Fetch previous month's Gross Volume data
        $prev_gross_sql = "
            SELECT DATE(FROM_UNIXTIME(i.date)) AS invoicedate,
                   SUM((SELECT SUM(ii.price * ii.license_allocation * ii.quantity) FROM {invoiceitem} ii WHERE ii.invoiceid = i.id)) AS total
            FROM {invoice} i
            WHERE i.companyid = :companyid AND i.status = 'p'
              AND MONTH(FROM_UNIXTIME(i.date)) = MONTH(CURDATE() - INTERVAL 1 MONTH)
              AND YEAR(FROM_UNIXTIME(i.date)) = YEAR(CURDATE())
            GROUP BY invoicedate
            ORDER BY invoicedate
        ";
        $gross_reference_data = $DB->get_records_sql($prev_gross_sql, ['companyid' => $companyid]);
        // Fetch previous month's Payment counts
        $prev_payment_sql = "
            SELECT DATE(FROM_UNIXTIME(i.date)) AS paymentdate,
                   COUNT(*) AS count
            FROM {invoice} i
            WHERE i.companyid = :companyid AND i.status = 'p'
              AND MONTH(FROM_UNIXTIME(i.date)) = MONTH(CURDATE() - INTERVAL 1 MONTH)
              AND YEAR(FROM_UNIXTIME(i.date)) = YEAR(CURDATE())
            GROUP BY paymentdate
            ORDER BY paymentdate
        ";

        $payment_reference_data = $DB->get_records_sql($prev_payment_sql, ['companyid' => $companyid]);
        foreach ($gross_reference_data as $record) {
            $gross_reference[] = round($record->total, 2);
            $previous_gross_total += $record->total;
        }

        foreach ($payment_reference_data as $record) {
            $payment_reference[] = (int) $record->count;
            $previous_payments_total += $record->count;
        }

        $current_gross_total = array_sum($gross_volume);
        $current_payments_total = array_sum($payment_counts);
        $gross_change = $previous_gross_total > 0
            ? round((($current_gross_total - $previous_gross_total) / $previous_gross_total) * 100, 1)
            : 0;



        $payment_change = $previous_payments_total > 0
            ? round((($current_payments_total - $previous_payments_total) / $previous_payments_total) * 100, 1)
            : 0;

        $html = '
         <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
         <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
         <style>
            .chart-card {
                background: #fff;
                border: none;
                border-radius: 8px;
                margin-top: 10px;
            }
            .chart-container {
                width: 100%;
                height: 200px;
            }
            .chart-title {
                font-size: 16px;
                color: #212121;
                margin-bottom: 5px;
            }
            .chart-info {
                font-size: 14px;
                color: #757575;
                margin-bottom: 10px;
            }
            .badge {
                padding: 2px 6px;
                border-radius: 4px;
                font-size: 12px;
            }
            .badge-red {
                background-color: red;
            }
            .badge-green {
                background-color: green;
            }
         </style>
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
        </style>';

        $courses = $this->courses();
        $users = $this->users();
        $newusers = $this->newusers();
        $inprogress = $this->inprogress();
        $overdue = $this->overduecourses();
        $completionrate = $this->generalcompletionrate();

        $html .='<div class="mt-4 m-3">
                <h3 class="mb-3">'.get_string('overview','block_custom_wdashboard').'</h3>
                <div class="row kpi-section" style="margin: initial;">
                    <div class="col-md-4">
                        <div class="dashboard-card">
                            <i class="fas fa-book icon"></i>
                            <div class="text-content">
                                <div class="card-title">'.get_string('courses','block_custom_wdashboard').'</div>
                                <div class="card-value">'.$courses.'</div>
                                <div class="card-subtitle">'.get_string('Noofcoursesinshop','block_custom_wdashboard').'</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="dashboard-card">
                            <i class="fas fa-users icon"></i>
                            <div class="text-content">
                                <div class="card-title">'.get_string('users','block_custom_wdashboard').'</div>
                                <div class="card-value">'.$users.'</div>
                                <div class="card-subtitle">'.get_string('totalusers','block_custom_wdashboard').'</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="dashboard-card">
                            <i class="fas fa-user-plus icon"></i>
                            <div class="text-content">
                                <div class="card-title">'.get_string('newusers','block_custom_wdashboard').'</div>
                                <div class="card-value">'.$newusers.'</div>
                                <div class="card-subtitle">'.get_string('thismonth','block_custom_wdashboard').'</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="dashboard-card">
                            <i class="fas fa-clock icon"></i>
                            <div class="text-content">
                                <div class="card-title">'.get_string('inprogress','block_custom_wdashboard').'</div>
                                <div class="card-value">'.$inprogress.'</div>
                                <div class="card-subtitle">'.get_string('workingoncourse','block_custom_wdashboard').'</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="dashboard-card">
                            <i class="fas fa-exclamation-triangle icon"></i>
                            <div class="text-content">
                                <div class="card-title">'.get_string('overduecourses','block_custom_wdashboard').'</div>
                                <div class="card-value">'.$overdue.'</div>
                                <div class="card-subtitle">'.get_string('needreminder','block_custom_wdashboard').'</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="dashboard-card">
                            <i class="fas fa-check-circle icon"></i>
                            <div class="text-content">
                                <div class="card-title">'.get_string('generalcompletionrate','block_custom_wdashboard').'</div>
                                <div class="card-value">'.$completionrate.'</div>
                                <div class="card-subtitle">'.get_string('ofcompletedcourses','block_custom_wdashboard').'</div>
                            </div>
                        </div>
                    </div>
                </div>';
        $bcolorclass_gross_change = $current_gross_total >= $previous_gross_total ? "badge-green": "badge-red"; 
        $bcolorclass_payment_change = $current_payments_total >= $previous_payments_total ? "badge-green": "badge-red"; 
        $html .= '<div class="row mt-3" style="margin: initial;">
                        <div class="col-md-6">
                            <div class="chart-card">
                                <div class="chart-title">'.get_string('grossvolume','block_custom_wdashboard').'
                                    <span class="badge '.$bcolorclass_gross_change.'">' . $gross_change . '%</span>
                                </div>
                                <div class="chart-info">$' . number_format($current_gross_total, 2) . 
                                ' <span>$' . number_format($previous_gross_total, 2) . ' '.get_string('previousmonth','block_custom_wdashboard').'</span></div>
                                <div class="chart-container">
                                    <canvas id="grossVolumeChart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="chart-card">
                                <div class="chart-title">'.get_string('successfulpayments','block_custom_wdashboard').' 
                                    <span class="badge '.$bcolorclass_payment_change.'">' . $payment_change . '%</span>
                                </div>
                                <div class="chart-info">' . $current_payments_total . 
                                ' <span>' . $previous_payments_total . ' '.get_string('previousmonth','block_custom_wdashboard').' </span></div>
                                <div class="chart-container">
                                    <canvas id="successfulPaymentsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>';


        $get_popular_courses = $this->get_most_popular_courses();
        // echo "<pre>";
        // print_r($get_popular_courses);
        // echo "</pre>";
        $html .= '<div class="row mt-3" style="margin: initial;">
                    <div class="col-12">
                        <h5 class="mb-2">'.get_string('mostpopularcourses','block_custom_wdashboard').'</h5>';

        if ($get_popular_courses) {
            foreach ($get_popular_courses as $course) {
                $progress = min(100, $course->enrollments);
                $html .= '<div class="chart-card">
                            <div class="course-item">
                                <span class="col-lg-3 my-2 text-right">' . $course->fullname . '</span>
                                <div class="progress col-lg-4 px-0">
                                    <div class="progress-bar progress-bar-custom" style="width: ' . $progress . '%;"></div>
                                </div>
                                <small class="text-muted col-lg-2">' . $course->enrollments . ' enrollments</small>
                            </div>
                         </div>';
            }
        }else{
            $html .= '<div class="chart-card">
                            <div class="course-item">
                                <span class="norecordsdata">No records found..</span>
                            </div>
                         </div>';

        }

        $html .= '</div></div>';

        $get_latest_reviews = $this->get_latest_reviews();

        $html .= '<div class="row mt-3" style="margin: initial;">
                    <div class="col-12">
                        <h5 class="mb-2">'.get_string('latestreview','block_custom_wdashboard').'</h5>';
                        if ($get_latest_reviews) {
                            foreach ($get_latest_reviews as $data) {
                                $rating = str_repeat('★', (int)$data->rating) . str_repeat('☆', 5 - (int)$data->rating);
                                $timeago = format_time(time() - $data->timemodified);
                                $html .= '<div class="chart-card cst_columns_area mb-2">
                                        <div class="d-flex align-items-center flex-wrap">
                                            <i class="fas fa-user-circle mx-2 fa-2x text-secondary"></i>
                                            <strong class="mx-2">' . fullname($data) . '</strong>
                                            <span class="mx-2">— ' . format_string($data->review) . '</span>
                                            <span class="rating text-warning mx-2" style="white-space: nowrap;">' . $rating . '</span>
                                        </div>
                                    </div>';
                            }
                        }else{
                            $html .= '<div class="chart-card mb-2">
                                        <div class="d-flex align-items-center flex-wrap">
                                            <span class="norecordsdata">No records found..</span>
                                        </div>
                                    </div>';

                        }
        $html .= '</div>
                </div>';
        // $html .= "<script>
        //     const chartLabels = ['Apr 1', 'Apr 5', 'Apr 10', 'Apr 15', 'Apr 20', 'Today'];
        //     const grossVolumeData = [5500, 7800, 6200, 9500, 7000, 6405.80];
        //     const referenceGrossData = [6000, 7000, 6500, 8000, 7500, 6898.10];
        //     const paymentData = [25, 18, 22, 28, 20, 23];
        //     const referencePaymentData = [28, 25, 27, 30, 26, 30];
        // </script>";

        $html .= "<script>
                    const chartLabels = " . json_encode($labels) . ";
                    const grossVolumeData = " . json_encode($gross_volume) . ";
                    const referenceGrossData = " . json_encode($gross_reference) . ";
                    const paymentData = " . json_encode($payment_counts) . ";
                    const referencePaymentData = " . json_encode($payment_reference) . ";
                    console.log('chartLabels', chartLabels);
                    console.log('grossVolumeData', grossVolumeData);
                    console.log('referenceGrossData', referenceGrossData);
                    console.log('paymentData', paymentData);
                    console.log('referencePaymentData', referencePaymentData);
                    // Gross Volume Chart
                    new Chart(document.getElementById('grossVolumeChart').getContext('2d'), {
                        type: 'line',
                        data: {
                            labels: chartLabels,
                            datasets: [
                                {
                                    label: 'Gross Volume',
                                    data: grossVolumeData,
                                    borderColor: '#5c8ff9',
                                    fill: false,
                                    tension: 0,
                                    pointRadius: 0,
                                    borderWidth: 2
                                },
                                {
                                    label: 'Reference',
                                    data: referenceGrossData,
                                    borderColor: '#d3d3d3',
                                    fill: false,
                                    tension: 0,
                                    pointRadius: 0,
                                    borderWidth: 1
                                }
                            ]
                        },
                        options: {
                            scales: {
                                x: {
                                    grid: { display: false },
                                    ticks: {
                                        callback: function(value, index, values) {
                                            if (index === 0) return chartLabels[0];
                                            if (index === values.length - 1) return 'Today';
                                            return '';
                                        },
                                        font: { size: 12 }
                                    }
                                },
                                y: {
                                    grid: { display: false },
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            if (value === 0 || value === Math.max(...grossVolumeData, ...referenceGrossData)) {
                                                return '$' + value.toLocaleString();
                                            }
                                            return '';
                                        },
                                        font: { size: 12 }
                                    }
                                }
                            },
                            plugins: { legend: { display: false } }
                        }
                    });

                    // Successful Payments Chart
                    new Chart(document.getElementById('successfulPaymentsChart').getContext('2d'), {
                        type: 'line',
                        data: {
                            labels: chartLabels,
                            datasets: [
                                {
                                    label: 'Successful Payments',
                                    data: paymentData,
                                    borderColor: '#5c8ff9',
                                    fill: false,
                                    tension: 0,
                                    pointRadius: 0,
                                    borderWidth: 2
                                },
                                {
                                    label: 'Reference',
                                    data: referencePaymentData,
                                    borderColor: '#d3d3d3',
                                    fill: false,
                                    tension: 0,
                                    pointRadius: 0,
                                    borderWidth: 1
                                }
                            ]
                        },
                        options: {
                            scales: {
                                x: {
                                    grid: { display: false },
                                    ticks: {
                                        callback: function(value, index, values) {
                                            if (index === 0) return chartLabels[0];
                                            if (index === values.length - 1) return 'Today';
                                            return '';
                                        },
                                        font: { size: 12 }
                                    }
                                },
                                y: {
                                    grid: { display: false },
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            if (value === 0 || value === Math.max(...paymentData, ...referencePaymentData)) {
                                                return value;
                                            }
                                            return '';
                                        },
                                        font: { size: 12 }
                                    }
                                }
                            },
                            plugins: { legend: { display: false } }
                        }
                    });
                </script>";


            
        $this->content->text = $html;
        return $this->content;
    }
}
