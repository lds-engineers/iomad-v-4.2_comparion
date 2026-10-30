<?php
ob_start(); // Start output buffering
require('../../config.php');
require_login();
global $DB,$USER,$CFG;
require_once($CFG->libdir . '/tcpdf/tcpdf.php');
// Get invoice ID
$invoiceid = required_param('id', PARAM_INT);
// Fetch data
$invoicedata = $DB->get_record("invoice", ["id" => $invoiceid], '*', MUST_EXIST);
$payment = $DB->get_record("payments", ["id" => $invoicedata->paymentid]);
$orderdata = $DB->get_records_sql("
    SELECT ii.*, csc.courseid, css.name 
    FROM {invoiceitem} AS ii 
    LEFT JOIN {course_shopsettings} AS css ON ii.invoiceableitemid = css.id 
    INNER JOIN {course_shopsettings_courses} AS csc ON csc.itemid = ii.invoiceableitemid 
    WHERE ii.invoiceid = ?", [$invoiceid]);

$usertype = $DB->get_record("company_users", [
    "companyid" => $invoicedata->companyid, 
    "userid" => $invoicedata->userid
]);

$usercompany = ($usertype && $usertype->managertype != 0) ? $invoicedata->company : '';

$loginusertype =  $DB->get_record("company_users", array("companyid"=>$invoicedata->companyid,"userid"=>$USER->id));
if (!is_siteadmin() &&(empty($loginusertype) || $loginusertype->managertype != 1) && $invoicedata->userid != $USER->id) {
    redirect(new moodle_url($CFG->wwwroot.'/my'), 'Access denied', null, \core\output\notification::NOTIFY_ERROR);
    die();
}



$coupon = $DB->get_record_sql("
    SELECT *, lc.type 
    FROM {coupon_invoice} ci 
    JOIN {local_coupon} lc ON ci.couponid = lc.id 
    WHERE ci.invoiceid = ?", [$invoiceid]);

$coupondiscount = $coupon ? $coupon->discount_amount : 0;
$couponcode = $coupon ? $coupon->code : '';
$discounttext = '-' . number_format($coupondiscount, 2) . ' ' . (($coupon && $coupon->type == 'fixed') ? $payment->currency : '%');

// Handle possible missing timecreated
$orderdate = !empty($invoicedata->date) 
    ? date('F j, Y, g:i a', $invoicedata->date) 
    : 'N/A';

// Setup TCPDF
$pdf = new TCPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Moodle Shop');
$pdf->SetTitle('Invoice #' . $invoiceid);
$pdf->SetMargins(15, 15, 15);
$pdf->AddPage();

$subtotal = 0;

// Start HTML
$html = '
<style>
body { font-family: helvetica, sans-serif; font-size: 12px; }
.invoice-header { font-size: 22px; font-weight: bold; margin-bottom: 5px; color: #333; }
.invoice-subtitle { font-size: 13px; color: #666; }
table { width: 100%; border-collapse: collapse; margin-top: 20px; }
th { background-color: #f2f2f2; color: #333; font-weight: bold; padding: 8px; border: 1px solid #ccc; }
td { padding: 8px; border: 1px solid #ccc; }
tfoot td { font-weight: bold; }
.total-row td { background-color: #f9f9f9; }
</style>

<h1 class="invoice-header">Invoice</h1>
<p class="invoice-subtitle">Order Date: ' . $orderdate . '</p>
<p>Invoice ID: #' . $invoiceid . '</p>
<p>Customer: ' . $invoicedata->firstname . ' ' . $invoicedata->lastname . '</p>
<p>Email: ' . $invoicedata->email . '</p>';

if ($usercompany) {
    $html .= "<p>Company: $usercompany</p>";
}

$html .= '<h3 style="margin-top: 30px;">Order Details</h3>
<table>
    <thead>
        <tr>
            <th>Item</th>
            <th>Cost</th>
            <th>Quantity</th>
            <th>Amount</th>
        </tr>
    </thead>
    <tbody>';

// Order item rows
foreach ($orderdata as $item) {
    $getcourse = $DB->get_record("course", ["id" => $item->courseid]);
    $price = number_format($item->price, 2);
    $qty = $item->license_allocation ?: 1;
    $amount = number_format($item->price * $qty, 2);
    $productname = $item->name ? $item->name : ($getcourse ? $getcourse->fullname : 'Product deleted');
    $subtotal += $item->price * $qty;

    $html .= "<tr>
        <td>$productname</td>
        <td>$price {$payment->currency}</td>
        <td>x $qty</td>
        <td>$amount {$payment->currency}</td>
    </tr>";
}

// Totals
$html .= '</tbody>
<tfoot>
    <tr class="total-row">
        <td colspan="3" align="right">Sub Total</td>
        <td>' . number_format($subtotal, 2) . ' ' . $payment->currency . '</td>
    </tr>
    <tr class="total-row">
        <td colspan="3" align="right">Discount</td>
        <td>' . $discounttext . '</td>
    </tr>
    <tr class="total-row">
        <td colspan="3" align="right">Total</td>
        <td>' . number_format($payment->amount, 2) . ' ' . $payment->currency . '</td>
    </tr>
</tfoot>
</table>';

// Output PDF
$pdf->writeHTML($html, true, false, true, false, '');

ob_end_clean(); // Clean any previous output
$pdf->Output('invoice_' . time(). '.pdf', 'D');
