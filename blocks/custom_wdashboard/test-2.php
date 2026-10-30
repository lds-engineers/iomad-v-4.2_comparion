<?php
require_once('../../config.php');
global $DB, $CFG, $USER, $PAGE;
// use core_completion\progress;
require_login();
// $userid = optional_param('userid', $USER->id, PARAM_INT);
// if(!is_siteadmin()){
// 	$userid = $USER->id;  // Owner of the page
// }

$strmymoodle = get_string('certificates', 'local_customlayout');
$PAGE->set_title($strmymoodle);
$PAGE->set_heading($strmymoodle);

echo $companyid = iomad::get_my_companyid(context_system::instance());
 
// echo $current_gross_sql = "
//             SELECT DATE(FROM_UNIXTIME(i.date)) AS invoicedate,
//                    SUM(ii.price * ii.license_allocation * ii.quantity) AS total, i.id
//             FROM {invoice} i
//             JOIN {invoiceitem} ii ON ii.invoiceid = i.id
//             WHERE i.companyid = :companyid AND i.status = 'p'
//               AND MONTH(FROM_UNIXTIME(i.date)) = MONTH(CURDATE())
//               AND YEAR(FROM_UNIXTIME(i.date)) = YEAR(CURDATE())
//             GROUP BY invoicedate
//             ORDER BY invoicedate
//         ";
//         $current_gross_data = $DB->get_records_sql($current_gross_sql, ['companyid' => $companyid]);



// $current_payment_sql = "
//             SELECT i.id, DATE(FROM_UNIXTIME(i.date)) AS paymentdate,
//                    COUNT(*) AS count
//             FROM {invoice} i
//             WHERE i.companyid = :companyid AND i.status = 'p'
//               AND MONTH(FROM_UNIXTIME(i.date)) = MONTH(CURDATE())
//               AND YEAR(FROM_UNIXTIME(i.date)) = YEAR(CURDATE())
//             GROUP BY paymentdate
//             ORDER BY paymentdate
//         ";
//         $current_payment_data = $DB->get_records_sql($current_payment_sql, ['companyid' => $companyid]);


$prev_payment_sql = "
            SELECT i.id, DATE(FROM_UNIXTIME(i.date)) AS paymentdate,
                   COUNT(*) AS count
            FROM {invoice} i
            WHERE i.companyid = :companyid AND i.status = 'p'
              AND MONTH(FROM_UNIXTIME(i.date)) = MONTH(CURDATE() - INTERVAL 1 MONTH)
              AND YEAR(FROM_UNIXTIME(i.date)) = YEAR(CURDATE())
            GROUP BY paymentdate
            ORDER BY paymentdate
        ";

        $payment_reference_data = $DB->get_records_sql($prev_payment_sql, ['companyid' => $companyid]);




echo "<pre>";
print_r($payment_reference_data  	);
echo "<pre>";


// echo $OUTPUT->footer();
