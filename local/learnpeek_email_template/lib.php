<?php

/**
 * Hook into Moodle page head output.
 * File: local/yourplugin/lib.php
 */

function local_learnpeek_email_template_before_standard_html_head() {
    global $DB, $PAGE;

    $current_host = isset($_SERVER['HTTP_HOST']) ? strtolower($_SERVER['HTTP_HOST']) : '';
    $request_uri  = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';
    $fullurl = $scheme . '://' . $current_host . $request_uri;

    $company = $DB->get_record_sql(
        "SELECT id FROM {company} WHERE hostname = :host",
        ['host' => $current_host]
    );

    $is_company_33     = ($company && intval($company->id) === 33);
    $is_host_flaggerny = ($current_host === 'flaggerny.learnpeek.com');

    $target_thankyou_fullurl = 'thankyou.php';
    $is_thankyou_fullurl = (str_contains($fullurl, $target_thankyou_fullurl));
    $html = '';
    if($company){
        $settag = get_config(null, "googletagmanager{$company->id}");
        if(!empty($settag)){
        /*
        <!-- Google tag (gtag.js) -->
                        <script async src="https://www.googletagmanager.com/gtag/js?id='.$settag.'"></script>
                        <script>
                        window.dataLayer = window.dataLayer || [];
                        function gtag(){dataLayer.push(arguments);}
                        gtag("js", new Date());
                        gtag("config", "'.$settag.'");
                        </script>

        */


                    $html .= '
        <!-- Google Tag Manager -->
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({\'gtm.start\':
        new Date().getTime(),event:\'gtm.js\'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!=\'dataLayer\'?\'&l=\'+l:\'\';j.async=true;j.src=
        \'https://www.googletagmanager.com/gtm.js?id=\'+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,\'script\',\'dataLayer\',\''.$settag.'\');</script>
        <!-- End Google Tag Manager -->
                        ';
        }

    }
    if ($is_company_33 && $is_host_flaggerny && !$is_thankyou_fullurl) {
        // $html .= '<!-- Google tag (gtag.js) -->
        //     <script async src="https://www.googletagmanager.com/gtag/js?id=AW-17540585907"></script>
        //     <script>
        //     window.dataLayer = window.dataLayer || [];
        //     function gtag(){dataLayer.push(arguments);}
        //     gtag("js", new Date());
        //     gtag("config", "AW-17540585907");
        //     </script>';
        // $html .= '<!-- Google tag (gtag.js) -->
        //     <script async src="https://www.googletagmanager.com/gtag/js?id=G-1ZZ44CW27G"></script>
        //     <script>
        //       window.dataLayer = window.dataLayer || [];
        //       function gtag(){dataLayer.push(arguments);}
        //       gtag(\'js\', new Date());
        //       gtag(\'config\', \'G-1ZZ44CW27G\');
        //     </script>
        //     <script async src="https://www.googletagmanager.com/gtag/js?id=AW-17540585907"></script>
        //     <!-- Google tag (gtag.js) --> 
        //     <script>
        //         window.dataLayer = window.dataLayer || []; function gtag(){dataLayer.push(arguments);} gtag("js", new Date()); gtag("config", "AW-17540585907");
        //     </script>';

    }
    if (($is_company_33 || $is_host_flaggerny) && $is_thankyou_fullurl) {
        // $orderid    = optional_param('orderid', 0, PARAM_INT);
        // $allitems = array();
        // if($orderid){
        //     $invoiceitems = $DB->get_records("invoiceitem", array("invoiceid" => $orderid));
        //     foreach ($invoiceitems as $key => $invoiceitem) {
        //         $productdata = $DB->get_record("course_shopsettings", array("id" => $invoiceitem->invoiceableitemid));
        //         $productname = $productdata->name;
        
        //         array_push($allitems, array("item_id"=>$invoiceitem->id, "item_name"=>$productname, "quantity"=>(int)$invoiceitem->quantity, "price"=>(float)$invoiceitem->price));
        //     }
            
        // }
        // $html .= "
        //     <!-- Google Tag: GA4 + Google Ads -->
        //     <script async src='https://www.googletagmanager.com/gtag/js?id=G-1ZZ44CW27G'></script>
             
        //     <script>
        //       window.dataLayer = window.dataLayer || [];
        //           function gtag(){ dataLayer.push(arguments); }
                 
        //           // Initialize gtag
        //           gtag('js', new Date());
                 
        //           // Configure both GA4 and Google Ads
        //           gtag('config', 'G-1ZZ44CW27G');
        //           gtag('config', 'AW-17540585907');
                 
        //           // Wait until DOM is loaded
        //           document.addEventListener('DOMContentLoaded', function () {
        //             const urlParams = new URLSearchParams(window.location.search);
        //             const orderid = urlParams.get('orderid');
        //             const amount = parseFloat(urlParams.get('amount') || '0');
        //             const currency = urlParams.get('currency') || 'USD';
                 
             
        //         if (orderid && currency) {
        //           // Fire GA4 purchase event
        //           gtag('event', 'purchase', {
        //             transaction_id: orderid,
        //             value: amount,
        //             currency: currency,
        //             items: ".json_encode($allitems)."
        //           });
             
        //           // Fire Google Ads conversion
        //               gtag('event', 'conversion', {
        //                 send_to: 'AW-17540585907/-7UECPS4xZ8bELOzgKxB',
        //                 value: amount,
        //                 currency: currency,
        //                 transaction_id: orderid
        //               });
        //             } else {
        //               console.warn('Missing order ID or currency.');
        //             }
        //           });
        //     </script>";

    }
    // $html .='<style>
    //                 #lp-full-loader {
    //                     position: fixed;
    //                     top: 0;
    //                     left: 0;
    //                     width: 100%;
    //                     height: 100vh;
    //                     background: rgba(10, 10, 10, 0.95);
    //                     display: flex;
    //                     align-items: center;
    //                     justify-content: center;
    //                     z-index: 9999999999;
    //                 }
    //                 .lp-spinner {
    //                     width: 70px;
    //                     height: 70px;
    //                     border: 6px solid #e0e0e0;
    //                     border-top-color: #3498db;
    //                     border-radius: 50%;
    //                     animation: lp-spin 1s infinite linear;
    //                 }
    //                 @keyframes lp-spin {
    //                     0% { transform: rotate(0deg); }
    //                     100% { transform: rotate(360deg); }
    //                 }
    //                 </style>

    //                 <div id="lp-full-loader">
    //                     <div class="lp-spinner"></div>
    //                 </div>';
    $pagetype = $PAGE->pagetype;
    if(str_contains($pagetype, "mod-scorm-view")){
        $html .='
            <style>
                #lp-full-loader {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100vh;
                    background: rgba(10, 10, 10, 0.95);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 9999999999;
                }
                .lp-spinner {
                    width: 70px;
                    height: 70px;
                    border: 6px solid #e0e0e0;
                    border-top-color: #3498db;
                    border-radius: 50%;
                    animation: lp-spin 1s infinite linear;
                }
                @keyframes lp-spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            </style>
            <div id="lp-full-loader">
                <div class="lp-spinner"></div>
            </div>
        ';
    }

    return $html;
}
function local_learnpeek_email_template_before_footer() {
    $html = '


    <script>
    window.addEventListener("load", function () {
        const loader = document.getElementById("lp-full-loader");
        if (loader) {
            loader.style.opacity = "0";
            setTimeout(() => loader.remove(), 400);
        }
    });

    setTimeout(() => {
        const loader = document.getElementById("lp-full-loader");
        if (loader) loader.remove();
    }, 9000);
    </script>
    ';

    echo $html;
}

function delete_user_data_in_course($userid, $courseid)
{
    global $DB;

    $dbman = $DB->get_manager();
    // --- 0) Feedback / review anonymization ---
    $feedbackcount = $DB->count_records('tool_courserating_rating', ['userid' => $userid, 'courseid' => $courseid]);
    if ($feedbackcount > 0) {
        $DB->execute(
            "
            UPDATE {tool_courserating_rating}
            SET backup_userid = userid,
                userid = NULL
            WHERE userid = :userid
            AND courseid = :courseid",
            ['userid' => $userid, 'courseid' => $courseid]
        );
    }


    if ($dbman->table_exists('courserating_completed')) {
        $reviewcount = $DB->count_records_select(
            'courserating_completed',
            "userid = ? AND courseid = ?",
            [$userid, $courseid]
        );
        if ($reviewcount > 0) {
            $DB->delete_records_select(
                'courserating_completed',
                "userid = ? AND courseid = ?",
                [$userid, $courseid]
            );
        }
    }
    
    // --- 1) Completion records --- 
    // $DB->delete_records('course_completions', ['userid' => $userid, 'course' => $courseid]);

    $completion = $DB->get_record('course_completions', ['userid' => $userid, 'course' => $courseid]);

    if ($completion) {
        // Insert into your backup table (assumes structure matches)
        $DB->insert_record('course_completions_backup', $completion);

        // Delete from the original table
        $DB->delete_records('course_completions', ['id' => $completion->id]);
    }

    
    $DB->delete_records_select('course_modules_completion', "userid = ? AND coursemoduleid IN (SELECT id FROM {course_modules} WHERE course = ?)", [$userid, $courseid]);

    $DB->delete_records_select('course_completion_crit_compl', "userid = ? AND course = ?", [$userid, $courseid]);

    // --- 2) Grade items & history (if present) --- 

    $DB->delete_records_select('grade_grades', "userid = ? AND itemid IN (SELECT id FROM {grade_items} WHERE courseid = ?)", [$userid, $courseid]);

    if ($dbman->table_exists('grade_grades_history')) {
        $DB->delete_records_select('grade_grades_history', "userid = ? AND itemid IN (SELECT id FROM {grade_items} WHERE courseid = ?)", [$userid, $courseid]);
    }

    // --- 2) Scan Face Recognition ---
    // no delete required
    /* if ($dbman->table_exists('lcl_scan_face_recognition')) {
        $scanface = $DB->count_records('lcl_scan_face_recognition', ['userid' => $userid, 'courseid' => $courseid]);
        if ($scanface > 0) {
            $DB->delete_records('lcl_scan_face_recognition', ['userid' => $userid, 'courseid' => $courseid]);
        }
    } */
    if ($dbman->table_exists('scorm') && $dbman->table_exists('scorm_scoes_track')) {
        // Get all SCORM IDs in the course
        $scormids = $DB->get_fieldset_select('scorm', 'id', 'course = ?', [$courseid]);

        if (!empty($scormids)) {
            // Build IN clause with named parameters
            list($insql, $inparams) = $DB->get_in_or_equal($scormids, SQL_PARAMS_NAMED, 'scormid_');

            // Prepare all query parameters
            $params = $inparams;
            $params['userid'] = $userid;
            $params['timemodified'] = time();

            // Perform a bulk update directly (no need for count)
            $DB->execute(
                "
                UPDATE {scorm_scoes_track}
                SET backup_userid = userid,
                    userid = NULL,
                    timemodified = :timemodified
                WHERE userid = :userid AND scormid $insql
                ",
                $params
            );
        }
    }


    // --- 4) Feedback Completed ---
    if ($dbman->table_exists('feedback') && $dbman->table_exists('feedback_completed')) {
        $feedbackcomp = $DB->count_records_select(
            'feedback_completed',
            "userid = ? AND feedback IN (SELECT id FROM {feedback} WHERE course = ?)",
            [$userid, $courseid]
        );
        if ($feedbackcomp > 0) {
            $DB->delete_records_select(
                'feedback_completed',
                "userid = ? AND feedback IN (SELECT id FROM {feedback} WHERE course = ?)",
                [$userid, $courseid]
            );
        }
    }

    // --- 5) Certificates ---
    if ($dbman->table_exists('customcert') && $dbman->table_exists('customcert_issues')) {
        $customcert = $DB->count_records_select(
            'customcert_issues',
            "userid = ? AND customcertid IN (SELECT id FROM {customcert} WHERE course = ?)",
            [$userid, $courseid]
        );
        if ($customcert > 0) {
            $DB->delete_records_select(
                'customcert_issues',
                "userid = ? AND customcertid IN (SELECT id FROM {customcert} WHERE course = ?)",
                [$userid, $courseid]
            );
        }
    }

    // --- 6) Suneet code Quiz Attempts Backup & Delete ---
    if ($dbman->table_exists('quiz') && $dbman->table_exists('quiz_attempts')) {

        // Get all quiz IDs from this course
        $quizids = $DB->get_fieldset_select('quiz', 'id', 'course = ?', [$courseid]);

        if (!empty($quizids)) {

            $quizlist = implode(',', array_map('intval', $quizids));

            $quizattempt = "SELECT qa.* 
                             FROM {quiz_attempts} qa 
                             WHERE qa.userid = :userid AND qa.quiz IN ($quizlist)";

            $attempts = $DB->get_records_sql($quizattempt, [
                'userid' => $userid
            ]);

            if (!empty($attempts)) {

                foreach ($attempts as $attempt) {
                    
                    $quiz_grades = $DB->get_record('quiz_grades', ['userid' => $userid, 'quiz' => $attempt->quiz]);

                    $attempt->backup_userid = $attempt->userid;
                    $attempt->qa_id = $attempt->id;
                    $attempt->quiz_grades = $quiz_grades->grade;
                    $attempt->backup_time = time();

                    // Insert into backup table
                    $DB->insert_record('quiz_attempts_backup', $attempt);


                    // Delete original attempt
                    $DB->delete_records(
                        'quiz_attempts',
                        ['id' => $attempt->id]
                    );
                }

                // Delete quiz grades
                $gradesql = "
                    DELETE FROM {quiz_grades}
                    WHERE userid = :userid
                    AND quiz IN ($quizlist)
                ";

                $DB->execute($gradesql, [
                    'userid' => $userid
                ]);
                
            }
        }
    }

    // --- Reset viewed modules ---
    if ($dbman->table_exists('course_modules_viewed')) {

        $DB->delete_records_select(
            'course_modules_viewed',
            "userid = ? 
             AND coursemoduleid IN (
                 SELECT id FROM {course_modules} WHERE course = ?
             )",
            [$userid, $courseid]
        );
    }
    
    \cache_helper::purge_all();

}