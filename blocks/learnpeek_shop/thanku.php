<?php
require_once(dirname(__FILE__) . '/../../config.php');
$orderid    = optional_param('orderid', 0, PARAM_INT);
$amount    = optional_param('amount', 0, PARAM_RAW);
$currency    = optional_param('currency', 0, PARAM_RAW);

$current_host = $_SERVER['HTTP_HOST'];
$getcompany = $DB->get_record_sql("SELECT * FROM {company} WHERE hostname LIKE ?", array($current_host));
require_login();
// if (isset($_SESSION['paymentinvoice'])) {
//     $invoice = $_SESSION['paymentinvoice'];
//     $payment = $DB->get_record("payments", array("id" => $invoice->paymentid));
//     if (empty($orderid)) {
//         redirect(new moodle_url($CFG->wwwroot . '/blocks/learnpeek_shop/thankyou.php', ['orderid' => $invoice->id, 'amount' => $payment->amount, 'currency' => $payment->currency]));
//         exit;
//     }
// } else {
//     redirect("{$CFG->wwwroot}/my");
//     exit;
// }
// if ($orderid != $invoice->id || $amount != $payment->amount || $currency != $payment->currency) {
//     redirect("{$CFG->wwwroot}/my");
//     exit;
// }

$allitems = array();
if($orderid){
    $invoiceitems = $DB->get_records("invoiceitem", array("invoiceid" => $orderid));
    foreach ($invoiceitems as $key => $invoiceitem) {
        $productdata = $DB->get_record("course_shopsettings", array("id" => $invoiceitem->invoiceableitemid));
        $productname = $productdata->name;
        $productmappedcat = $DB->get_record("product_category_mapping",array("product_id"=>$productdata->id));
        if($productmappedcat){
            $productcategory = $productmappedcat->product_category_id;
        }else{
            $produccoursecat = $DB->get_record_sql("SELECT c.category FROM {course_shopsettings_courses} as csc INNER JOIN {course} as c ON c.id = csc.courseid WHERE csc.itemid = ?",array($productdata->id));
            $productcategory = $produccoursecat->category;
        }
        //=====================================
        $category_ids = explode(',', $productcategory); // Convert to array

        $category_names = [];
        $item = array("item_id"=>$invoiceitem->id, "item_name"=>$productname,"item_brand"=>$getcompany->name, "quantity"=>(int)$invoiceitem->license_allocation, "price"=>(float)$invoiceitem->price * (int)$invoiceitem->license_allocation);
        foreach ($category_ids as $index => $id) {
            $category = $DB->get_record('course_categories', ['id' => (int)$id], 'name');
            if ($category) {
                $key = $index == 0 ? 'course_category' : 'course_category' . ($index + 1);
                $category_names[$key] = $category->name;
                $item[$key] = $category->name;
            }
        }
        //=====================================


        array_push($allitems, $item);
    }
    $getcoupon = $DB->get_record_sql("SELECT ci.*,lc.code FROM {coupon_invoice} as ci INNER JOIN {local_coupon} as lc ON lc.id = ci.couponid WHERE ci.invoiceid =?",array($orderid));
    if($getcoupon){
        $couponcode = $getcoupon->code;
    }else{
        $couponcode = "";
    }
}
$paymentamount = $payment->amount;

// echo "<pre>";
// print_r($allitems);
// print_r($getcoupon);
// print_r($getcompany);
// echo "</pre>";
unset($_SESSION['paymentinvoice']);
echo $OUTPUT->header();
?>
<style>
    .cst_thnk_card {
        background: linear-gradient(78deg, rgb(68, 102, 235) 0%, rgb(160, 178, 242) 100%) !important;
    }

    .text-black{
        color: #000 !important;
    }
</style>
<!-- Main container -->
<div class="container d-flex justify-content-center align-items-center">
    <div class="card cst_thnk_card p-2 shadow-lg rounded" style="max-width: 500px; width: 100%;">
        <div class="card-body text-center">
            <h1 class="text-white mb-3">Payment Successful!</h1>
            <p class="lead mb-4 text-white">Thank you for your payment. Your transaction was successfully processed.</p>

            <!-- Navigation buttons -->
            <div class="d-flex justify-content-center gap-3 mt-4">
                <a href="/my/" class="btn btn-success px-4 py-2 text-black" style="background-color:#f0f8ff">
                    Go to My Account
                </a>
            </div>

        </div>
    </div>
</div>

<!-- Bootstrap JS Bundle (includes Popper.js) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom Script to load payment details dynamically -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const amountPaid = "<?php echo $paymentamount; ?>";
        const transactionId = "<?php echo $invoice->paymentid; ?>";

        document.getElementById("amount-paid").textContent = amountPaid;
        document.getElementById("transaction-id").textContent = transactionId;
    });
</script>
<?php
echo '
   <script>
    gtag("event", "purchase", {
        transaction_id: '.$orderid.',
        value: '.$paymentamount.',
        currency: '.$currency.',
        coupon: '.$couponcode.',
        items: '.json_encode($allitems).'
    });
    </script>';

echo $OUTPUT->footer();
