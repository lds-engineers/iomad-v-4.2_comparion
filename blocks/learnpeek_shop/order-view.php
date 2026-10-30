<?php
require('../../config.php');
require_login();
global $DB, $USER , $CFG;
$invoiceid = required_param('id', PARAM_INT);
$returnurl = new moodle_url('/blocks/learnpeek_shop/order-details.php', array());
$pagetitle = get_string('orderdetails','block_learnpeek_shop');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);
$invoicedata = $DB->get_record("invoice", array("id"=>$invoiceid));
$price = $DB->get_record("payments", array("id"=>$invoicedata->paymentid));
// $coupon = $DB->get_records_sql("coupon_invoice", array("invoiceid"=>$invoiceid));

$coupon = $DB->get_record_sql(
    "SELECT *, lc.type FROM {coupon_invoice} ci 
     JOIN {local_coupon} lc ON ci.couponid = lc.id 
     WHERE ci.invoiceid = ?", 
    [$invoiceid]
);   
if ($coupon) {
    $coupondiscount = $coupon->discount_amount;  // assuming this field holds the discount value
} else {
    $coupondiscount = 0;  // default discount if coupon not found
}


if ($coupon) {
    $couponcode= $coupon->code;  // assuming this field holds the discount value
} else {
    $couponcode = "";  // default discount if coupon not found
}

// echo "SELECT ii.*, csc.courseid,css.name 
//    FROM {invoiceitem} as ii 
//    LEFT JOIN {course_shopsettings} as css ON ii.invoiceableitemid = css.id 
//    INNER JOIN {course_shopsettings_courses} as csc ON css.id = csc.itemid 
//    WHERE ii.invoiceid =".$invoiceid;
$orderdata = $DB->get_records_sql("SELECT ii.*, csc.courseid,css.name 
   FROM {invoiceitem} as ii 
   LEFT JOIN {course_shopsettings} as css ON ii.invoiceableitemid = css.id 
   INNER JOIN {course_shopsettings_courses} as csc ON csc.itemid =ii.invoiceableitemid 
   WHERE ii.invoiceid = ?", array($invoiceid));
$usertype =  $DB->get_record("company_users", array("companyid"=>$invoicedata->companyid,"userid"=>$invoicedata->userid));
$loginusertype =  $DB->get_record("company_users", array("companyid"=>$invoicedata->companyid,"userid"=>$USER->id));

// echo "<pre>";
// print_R($orderdata);
// die;
$courseid = '';
$islicense = '';
if($usertype->managertype == 0){
  $usercompany = "";
}else{
  $usercompany = $invoicedata->company;
}
if (!is_siteadmin() && empty($loginusertype)) {
    redirect(new moodle_url($CFG->wwwroot.'/my'),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
    die(); 
}
echo $OUTPUT->header();
echo '
<div class="container-fluid" >
<div class="row" >
   <div class="col-md-12 cst_styling1 shadow-md">
      <h3 class="">'.get_string('orderdetails','block_learnpeek_shop').'</h3>
      <table class="table table-bordered" style="color:#5373ec">
         <thead class="text-white bg_custom">
            <tr>
               <th>'.get_string('item','block_learnpeek_shop').'</th>
               <th>'.get_string('quantity','block_learnpeek_shop').'</th> 
               <th>'.get_string('total','block_learnpeek_shop').'</th>
            </tr>
         </thead>
         <tbody>';
         $subtotal = 0;
          $lquantity = "";
            foreach ($orderdata as $ordervalue) {
                    // echo "<pre>";
                    // print_r($ordervalue);
                    // echo "</pre>";
                    // exit();
                  $courseid = $ordervalue->courseid;
                  $islicense = $ordervalue->invoiceableitemtype;
                  $getcourse = $DB->get_record("course",array("id"=>$ordervalue->courseid));
                  if($getcourse){
                    $courselink = $CFG->wwww.'/course/view.php?id='.$getcourse->id;
                  }else{
                    $courselink = "#";
                  }
                  $subtotal += $ordervalue->price * $ordervalue->license_allocation;
                   if($ordervalue->license_allocation){
                     $lquantity = " x ".$ordervalue->license_allocation;
                   }
                  if($ordervalue->name){
                    $productname = $ordervalue->name;
                  }else if($getcourse){
                    $productname = $getcourse->fullname;
                  }else{
                    $productname = '<span style="color: #b70909;cursor: none;background-color:#fdbfbf;">Product deleted<span>';
                  }
            
            echo '
            <tr>
               <td>
                  <a class="cours_orders_links  shadow-md text-decoration-underline" href="'.$courselink.'">'.$productname.' '. $lquantity. '</a>               
               </td>
               <td>'.$lquantity.'</td>
               <td class="">
                  <span>'.number_format($ordervalue->price * $ordervalue->license_allocation, 2).' '.$ordervalue->currency.'</span>                
               </td>
            </tr>

            ';
         }
         echo '
         </tbody>
         <tfoot>
            <tr class="">
               <th>'.get_string('subtotal','block_learnpeek_shop').'</th>
               <td>  </td>
               <td>'. number_format($subtotal, 2) .' '.$ordervalue->currency.'</td>
            </tr>
            <tr class="">
               <th>'.get_string('discount','block_learnpeek_shop').'</th>
               <td>  </td>
               <td> - '. number_format($coupondiscount, 2) .' '.(($coupon->type == 'fixed') ?$ordervalue->currency : '%' ).'</td>
            </tr>
            <tr class="">
               <th>'.get_string('total','block_learnpeek_shop').'</th>
               <td>  </td>
               <td>'. number_format($price->amount, 2) .' '.$ordervalue->currency.'</td>
            </tr>
         </tfoot>
      </table>
     
      <p class="">
         <strong>'.get_string('invoiceid','block_learnpeek_shop').' </strong>
         <span>'.$invoicedata->id. '</span>
      </p>
      <!-- <p class="">
         <strong>Reference ID : </strong>
         <span class="fw-bolder">'.$invoicedata->reference.'</span>
      </p> -->
      <div class="d-flex justify-content-between align-items-center">
         <p class="mb-0">
            <strong>'.get_string('orderstatus','block_learnpeek_shop').'</strong>
            <span class="completed_order order_status">';
            if($invoicedata->status == 'p') { echo  get_string('paid','block_learnpeek_shop');}
            elseif($invoicedata->status == 'pr') { echo  'Partially Refunded';}
            else {echo 'Refunded';}
            echo '
            </span>
            <select class="status_select d-none">
            <option value="" '.($invoicedata->status == '' ? 'selected' : '').'>'.get_string('selectstatus','block_learnpeek_shop').'</option>
            <option value="pr" '.($invoicedata->status == 'pr' ? 'selected' : '').'>'.get_string('partiallyrefunded','block_learnpeek_shop').'</option>
            <option value="r" '.($invoicedata->status == 'r' ? 'selected' : '').'>'.get_string('refunded','block_learnpeek_shop').'</option>
            </select>';
            if (is_siteadmin() || (isset($loginusertype->managertype) && $loginusertype->managertype == 1)) {
               echo '<button class="mx-2 btn btn-primary edit_status">'.get_string('editstatus','block_learnpeek_shop').'</button>';
            }
            echo '
            <button class="mx-2 btn btn-primary d-none save_status"><i class="fa fa-check" aria-hidden="true"></i></button>
            <button class="mx-2 btn btn-primary btn-danger d-none cancel_status"><i class="fa fa-times" aria-hidden="true"></i></button>
           
         </p>

          <span>
               <a href="download_order_pdf.php?id=' . $invoiceid . '" class="btn btn-primary">
                  '.get_string('downloadpdf','block_learnpeek_shop').'
               </a>
            </span>
      </div>
   </div>
   <div class="col-md-12 bg-light shadow-md p-4 my-4">

      <h2> '.get_string('purchaserdetails','block_learnpeek_shop').'</h2>
      <strong>'.get_string('orderdate','block_learnpeek_shop').'</strong> '.date("d M Y", $invoicedata->date)."<br>".'<br>
      <address class="border bg-white rounded-3" style="padding: 10px;">
         '.$usercompany.'<br>
         '.$invoicedata->firstname.' '.$invoicedata->lastname.'<br>
         '.$invoicedata->phone1.'<br>
         '.$invoicedata->address.'<br>
         '.$invoicedata->city.'<br>
         '.$invoicedata->state.' '.$invoicedata->postcode. '
      </address>

   </div>
</div>
</div>
<style>
.cst_styling1 {
    border: 1px solid #fefe;
    padding: 30px;
    margin: 20px 0;
    border-radius: 10px;
    background-color: #f4f4f7;
    color: #5373ec;
}

.table th, .table td{
  border: 1px solid #ffffff;
}


span.completed_order {
    background-color: #b3f4b3;
    padding: 6px;
    color: green;
    border-radius: 5px;
}

.purchase_details {
    padding: 20px;
    background-color: #f2f3f7;
    border-radius: 6px;
}
</style>

';
echo '
<script>
document.addEventListener("DOMContentLoaded", function () {
    const statusText = document.querySelector(".order_status");
    const select = document.querySelector(".status_select");
    const editBtn = document.querySelector(".edit_status");
    const saveBtn = document.querySelector(".save_status");
    const cancelBtn = document.querySelector(".cancel_status");

    if (!statusText || !select || !editBtn || !saveBtn || !cancelBtn) {
        return;
    }

    editBtn.addEventListener("click", () => {
        statusText.classList.add("d-none");
        select.classList.remove("d-none");
        editBtn.classList.add("d-none");
        saveBtn.classList.remove("d-none");
        cancelBtn.classList.remove("d-none");
    });

    cancelBtn.addEventListener("click", () => {
        statusText.classList.remove("d-none");
        select.classList.add("d-none");
        editBtn.classList.remove("d-none");
        saveBtn.classList.add("d-none");
        cancelBtn.classList.add("d-none");
    });

   saveBtn.addEventListener("click", () => {
    const status = select.value;
    const invoiceId = ' . json_encode($invoiceid) . ';
    const courseid = ' . json_encode($courseid) . ';
    const islicense = ' . json_encode($islicense) . ';

    const form = document.createElement("form");
    form.method = "POST";
    form.action = "update_order_status.php";

    const hiddenInput1 = document.createElement("input");
    hiddenInput1.type = "hidden";
    hiddenInput1.name = "id";
    hiddenInput1.value = invoiceId;
    form.appendChild(hiddenInput1);

    const hiddenInput2 = document.createElement("input");
    hiddenInput2.type = "hidden";
    hiddenInput2.name = "status";
    hiddenInput2.value = status;
    form.appendChild(hiddenInput2);

    const hiddenInput3 = document.createElement("input");
    hiddenInput3.type = "hidden";
    hiddenInput3.name = "courseid";
    hiddenInput3.value = courseid;
    form.appendChild(hiddenInput3);

    const hiddenInput4 = document.createElement("input");
    hiddenInput4.type = "hidden";
    hiddenInput4.name = "islicense";
    hiddenInput4.value = islicense;
    form.appendChild(hiddenInput4);

    document.body.appendChild(form);
    form.submit();
});

});
</script>
';

// <option value="p" '.($invoicedata->status == 'p' ? 'selected' : '').'>Paid</option>  // add this option in the edit status select box
echo $OUTPUT->footer(); ?>



