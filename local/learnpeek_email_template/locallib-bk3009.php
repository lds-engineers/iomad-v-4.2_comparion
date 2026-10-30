<?php
include_once("../../config.php");
global $CFG, $USER, $DB, $PAGE;

function order_complete_email_to_whitelable($companyid, $oderuserid, $oderinvoiceid)
{

  global $DB, $USER, $CFG;
  $company = $DB->get_record('company', ['id' => $companyid]);
  $getcompany_wu = $DB->get_records_sql("SELECT * FROM {company_users} WHERE companyid = ? AND managertype =?", array($companyid, 1));
  $orderuser = $DB->get_record("user", array("id" => $oderuserid));

  order_complete_email_to_superadmin($companyid, $oderuserid, $oderinvoiceid);
  //cumulative email are enable
  $cumulative_email = $DB->get_record_sql("SELECT * FROM {company_mail_configuration} WHERE company_id =?  AND cumulative_email = ?",array($companyid,1));
  if($cumulative_email){
     return;
  }

  foreach ($getcompany_wu as $com_wtuser) {
    $gettemplate = $DB->get_record_sql("SELECT * FROM {custom_email_template} WHERE company_id = ? AND email_template = ?",array($companyid,'order-email-white-label'));
    // $gettemplate = $DB->get_record_sql("SELECT * FROM {custom_email_template} WHERE company_id = ? AND email_template = ?", array(39, 'order-email-white-label'));
    if ($gettemplate) {
      $emailtemplatedata_wl = $gettemplate->email_message;
    } else {
      $get_defult_template = $DB->get_record_sql("SELECT * FROM {custom_email_template} WHERE company_id = ? AND email_template = ?", array(0, 'order-email-white-label'));
      if ($get_defult_template) {
        $emailtemplatedata_wl = $get_defult_template->email_message;
      } else {
        $emailtemplatedata_wl = 0;
      }
    }
    $whitelableuser = $DB->get_record("user", array("id" => $com_wtuser->userid));
    $order_user_name = fullname($orderuser);
    $white_label_admin_name = fullname($whitelableuser);

    //get compnay logo
    $comlogodetails = $DB->get_record_sql('SELECT * FROM {company_logo} WHERE company_id = ?', array($companyid));
    if ($comlogodetails) {
      $companylogo = $DB->get_record_sql("SELECT * FROM {files} WHERE contextid= 1 AND component='landingpage' AND itemid=? AND filesize > 0 AND filearea = ?", array($comlogodetails->id, 'logo_image'));
      $logourl = $CFG->wwwroot . '/blocks/custom_mycourse/file.php?id=' . $companylogo->pathnamehash;
      $company_logo = "<img src='" . $logourl . "' style='width: 125px;max-height: 150px;object-fit: cover;'>";
    } else {
      $company_logo = "";
    }


    $invoicedata = $DB->get_record("invoice", array("id" => $oderinvoiceid));
    // $price = $DB->get_record("payments", array("id"=>$invoicedata->paymentid));
    $price = $DB->get_record("payments", array("itemid" => $oderinvoiceid, "userid" => $USER->id));


     // UPDATED: Get coupon and discount details correctly
    $coupon = $DB->get_record_sql(
        "SELECT *, lc.discount_amount as disamount, ci.discount_amount, lc.type,lc.code 
         FROM {coupon_invoice} ci 
         JOIN {local_coupon} lc ON ci.couponid = lc.id 
         WHERE ci.invoiceid = ?", 
        [$invoicedata->id]
    );
    $coupondiscount= $coupon ? $coupon->disamount : 0;
    $couponamount = $coupon ? $coupon->discount_amount : 0;
    $couponcurrency = $coupon ? ($coupon->type == 'fixed' ? 'USD' : '%') : '';
    $couponname = $coupon ? ($coupon->code ? $coupon->code : "") : "";


    $orderdata = $DB->get_records_sql("SELECT ii.*, csc.courseid,css.name 
               FROM {invoiceitem} as ii 
               LEFT JOIN {course_shopsettings} as css ON ii.invoiceableitemid = css.id 
               INNER JOIN {course_shopsettings_courses} as csc ON csc.itemid =ii.invoiceableitemid 
               WHERE ii.invoiceid = ?", array($oderinvoiceid));

    $paymentdate = $DB->get_record('payments', ['itemid' => $oderinvoiceid]);
    $usertype =  $DB->get_record("company_users", array("companyid" => $invoicedata->companyid, "userid" => $invoicedata->userid));
    if ($usertype->managertype == 0) {
      $usercompany = "";
    } else {
      $usercompany = $invoicedata->company;
    }
    $order_items_table = '<div class="container-fluid" style="background-color: #f4f4f7; padding:10px;"><div class="row" style="margin:10px 0;">
        <div class="cst_styling1" style="width:600px; margin:50px auto; border: 1px solid #fefe; border-radius: 10px; background-color:#fff; color: #5373ec; font-family:Helvetica, Helvetica, Roboto, Arial, sans-serif; font-size: 14px;">
        <header style="background-color:rgb(16, 133, 241); padding:20px;">
        <p style="color:#fff; font-size:25px; margin:auto 50px;">New Order ' . $invoicedata->id . '</p></header>
        <div style="background-color:#fff; padding:30px;">
        <p style="color:#000; margin:20px 0">You received the following order from ' . $order_user_name . '</p>
        <p style="font-size:18px; color:rgb(16, 133, 241)">[<a>Order ' . $invoicedata->id . '</a>] <b>' . userdate($paymentdate->timecreated, '%B %d %Y') . '</b></p>
        <div style="overflow-x:auto"><table class="table table-bordered" border="1" style="color:#5373ec; width:100%; border-collapse:collapse; border:2px solid #eee;">
        <thead><tr style="text-align:left; font-size:18px; color:#726d6d;">
        <th style="padding:20px 10px; font-size:14px;font-weight:700">Product</th><th style="padding:20px 10px; font-size:14px;font-weight:700">Quantity</th><th  style="width:60%; padding:20px 10px; font-size:14px; font-weight:700">Total</th></tr></thead><tbody>';                 


  
        // UPDATED: Subtotal calculation before total
        $subtotal = 0;
        foreach ($orderdata as $ordervalue) {
            $getcourse = $DB->get_record("course", array("id" => $ordervalue->courseid));
            $courselink = $getcourse ? $CFG->wwwroot . '/course/view.php?id=' . $getcourse->id : "#"; // UPDATED: Fixed $CFG->wwww typo

            $lquantity = $ordervalue->license_allocation ? " x " . $ordervalue->license_allocation : "";
            $productname = $ordervalue->name ?: ($getcourse ? $getcourse->fullname : '<span style="color: #b70909;cursor: none;background-color:#fdbfbf;">Product deleted</span>'); // UPDATED

            $itemtotal = $ordervalue->price * $ordervalue->license_allocation;
            $subtotal += $itemtotal;

            $order_items_table .= '
            <tr>
               <td style="padding:10px"><a style="color:#636363; font-size:14px; text-decoration:none" class="cours_orders_links shadow-md text-decoration-none" href="' . $courselink . '">' . $productname . $lquantity . '</a></td>
               <td style="padding:10px; color:#636363;">' . $lquantity . '</td>
               <td style="padding:10px; color:#636363;"><span>' . number_format($itemtotal, 2) . ' ' . $ordervalue->currency . '</span></td>
            </tr>';
        }

        $total = $subtotal - $couponamount; // UPDATED: Discount applied after subtotal

    $order_items_table .= '
                        </tbody>
               <tfoot style="border-top:3px solid #ccc">
                  <tr class="" style="text-align:left;">
                     <th colspan="2" style="padding:10px; font-weight:bold; color:#726d6d; font-size:14px">Subtotal:</th>
                               <td style=" padding:10px; color:#726d6d">' . number_format($subtotal, 2) . ' ' . $ordervalue->currency . '</td>
                            </tr>
                            <tr  class="">
                               <td colspan="2" style="padding:10px; font-weight:bold; color:#726d6d; font-size:14px">Coupon used</td>
                               <td style="padding:5px; color:#726d6d">' . $couponname . '</td>
                            </tr>
                            <tr class="">
                               <td colspan="2" style=" padding:10px; font-weight:bold; color:#726d6d; font-size:14px">Discount</td>
                               <td style="padding:5px; color:#726d6d"> - ' . number_format($coupondiscount, 2) . ' ' . $couponcurrency . '</td>
                            </tr>
                            <tr class="" style="text-align:left;">
                               <th colspan="2" style="padding:10px; font-weight:bold; color:#726d6d; font-size:14px">Payment method:</th>
                              
                               <td style="padding:10px; color:#726d6d">'.strtoupper($paymentdate->gateway).'</td>
                            </tr>
                            <tr  class="">
                               <td colspan="2" style="padding:10px; font-weight:bold; color:#726d6d; font-size:14px">Total</td>
                               <td style="padding:5px; color:#726d6d">' . number_format($total, 2) . ' ' . $ordervalue->currency . '</td>
                            </tr>
                            <tr class="" style="text-align:left;">
                               <th colspan="2" style="padding:10px; font-weight:bold; color:#726d6d; font-size:14px">Note:</th>
                             
                               <td style="padding:10px; color:#726d6d">'.$invoicedata->ordernotes.'</td>
                            </tr>
                         </tfoot>
                      </table>
                      </div>
                      </div>
                     <div style="padding:30px">
                       <h2 style="color:rgb(16, 133, 241)">Billing address</h2>
                      
                       <div class="invoice_parent" style="border:1px solid #ccc; padding:10px; color:#000">
                          <p  style="margin:0;">
                         <strong>Invoice ID : </strong>
                         <span>' . $invoicedata->id . '</span>
                      </p>
                       
                      <div class="" style="display:flex; align-items:center">
                         <div style="white-space:nowrap">
                            <strong>Order Status: </strong>
                         </div>

                        <div>
                              <span class="completed_order" style="padding: 6px;  border-radius: 5px;">';
    // if($invoicedata->status == 'p') { 
    $order_items_table .=  'Completed';
    // }
    $order_items_table .= '
                             </span>
                        </div>
                      </div>
                      <div class="col-md-12 bg-light shadow-md p-4 my-4">

                  <address>
                    ' . $usercompany . '
                    ' . $invoicedata->firstname . ' ' . $invoicedata->lastname . '<br>
                    ' . $invoicedata->email . '<br>
                    ' . $invoicedata->phone1 . '<br>
                    ' . $invoicedata->address . '<br>
                    ' . $invoicedata->city . '<br>
                    ' . $invoicedata->state . ' ' . $invoicedata->postcode . '
                  </address>
              </div>
                      </div>
                       

   </div>
                   </div>
                   
                </div>
                </div>
                    <style>

                    </style>';

    $placeholders = ['{{white_label_admin_name}}', '{{order_user_name}}', '{{order_items_table}}', '{{company_logo}}'];
    $replacements = [$white_label_admin_name, $order_user_name, $order_items_table, $company_logo];
    $email_body = str_replace($placeholders, $replacements, $emailtemplatedata_wl);
    $messagehtml =   "<!DOCTYPE html>
                                <html>
                                    <head>
                                        <title>Mail</title>
                                        <link rel='stylesheet' type='text/css' href='https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css'>
                                    </head>
                                    <body>" . $email_body . "
                                    </body>
                                </html>";

    // $fromUser = core_user::get_support_user();
    $fromUser =  $white_label_admin_name . '(' . $company->name . ')';
    $subject = $gettemplate->email_subject;
    $emailuser = (object) [];
    $emailuser->email = $whitelableuser->email;
    if(!empty($gettemplate->destination_email)){
      $emailuser->email = $gettemplate->destination_email;
    } else {
      $emailuser->email = $whitelableuser->email;
    }

    // $emailuser->email = "suneet.sharma695@gmail.com";
    $emailuser->firstname = $whitelableuser->firstname;
    $emailuser->lastname = $whitelableuser->lastname;
    $emailuser->maildisplay = true;
    $emailuser->mailformat = 1; // 0 (zero) text-only emails, 1 (one) for HTML/Text emails.
    $emailuser->id = 1;
    $emailuser->firstnamephonetic = false;
    $emailuser->lastnamephonetic = false;
    $emailuser->middlename = false;
    $emailuser->username = false;
    $emailuser->alternatename = false;

    if ($emailtemplatedata_wl) {
      // $emailstatus = email_to_user($emailuser, $fromUser, $subject, $message = '', $messagehtml, '', '', true, '', '', 79, $companyid);
      $emailstatus = email_learnpeek_to_user($emailuser, $fromUser, $subject, $message = '' , $messagehtml, '', '', true, '', '', 79, $companyid);
      
      if(!empty($gettemplate->destination_email)){
        break;
      }
      
    }
  }
}


function order_complete_email_to_user($companyid, $oderinvoiceid)
{
  global $DB, $USER, $CFG;
  $gettemplate = $DB->get_record_sql("SELECT * FROM {custom_email_template} WHERE company_id = ? AND email_template = ?", array($companyid, 'order-email-user'));
  // $gettemplate = $DB->get_record_sql("SELECT * FROM {custom_email_template} WHERE company_id = ? AND email_template = ?",array(39,'order-email-user'));
  if ($gettemplate) {
    $emailtemplatedata_user = $gettemplate->email_message;
  } else {
    $get_deflt_template = $DB->get_record_sql("SELECT * FROM {custom_email_template} WHERE company_id = ? AND email_template = ?", array(0, 'order-email-user'));
    if ($get_deflt_template) {
      $emailtemplatedata_user = $get_deflt_template->email_message;
    } else {
      $emailtemplatedata_user = 0;
    }
  }

  //get compnay logo
  $comlogodetails = $DB->get_record_sql('SELECT * FROM {company_logo} WHERE company_id = ?', array($companyid));
  if ($comlogodetails) {
    $companylogo = $DB->get_record_sql("SELECT * FROM {files} WHERE contextid= 1 AND component='landingpage' AND itemid=? AND filesize > 0 AND filearea = ?", array($comlogodetails->id, 'logo_image'));
    $logourl = $CFG->wwwroot . '/blocks/custom_mycourse/file.php?id=' . $companylogo->pathnamehash;
    $company_logo = "<img src='" . $logourl . "' style='width: 125px;max-height: 150px;object-fit: cover;'>";
  } else {
    $company_logo = "";
  }

  $order_user_name = fullname($USER);

  $invoicedata = $DB->get_record("invoice", array("id" => $oderinvoiceid));
  $paymentdate = $DB->get_record('payments', ['itemid' => $oderinvoiceid]);

  $price = $DB->get_record("payments", array("itemid" => $oderinvoiceid, "userid" => $USER->id));

    // Get coupon discount
    $coupon = $DB->get_record_sql(
        "SELECT *, lc.discount_amount as disamount, ci.discount_amount, lc.type FROM {coupon_invoice} ci 
         JOIN {local_coupon} lc ON ci.couponid = lc.id 
         WHERE ci.invoiceid = ?",
        [$invoicedata->id]
    );

  $coupondiscount= $coupon ? $coupon->disamount : 0;
    $couponamount = $coupon ? $coupon->discount_amount : 0;
    $couponcurrency = $coupon ? ($coupon->type == 'fixed' ? 'USD' : '%') : '';

    $orderdata = $DB->get_records_sql("SELECT ii.*, csc.courseid, css.name 
        FROM {invoiceitem} as ii 
        LEFT JOIN {course_shopsettings} as css ON ii.invoiceableitemid = css.id 
        INNER JOIN {course_shopsettings_courses} as csc ON csc.itemid = ii.invoiceableitemid 
        WHERE ii.invoiceid = ?", array($oderinvoiceid));

    $usertype = $DB->get_record("company_users", array("companyid" => $invoicedata->companyid, "userid" => $invoicedata->userid));
    $usercompany = ($usertype && $usertype->managertype != 0) ? $invoicedata->company : "";

    // START TABLE
    $order_items_table = '<div class="container-fluid" style="background-color: #f4f4f7; padding:10px;"><div class="row" style="margin:10px 0;">
        <div class="cst_styling1" style="width:600px; margin:50px auto; border: 1px solid #fefe; border-radius: 10px; background-color:#fff; color: #5373ec; font-family:Helvetica, Helvetica, Roboto, Arial, sans-serif; font-size: 14px;">
        <header style="background-color:rgb(16, 133, 241); padding:20px;">
        <p style="color:#fff; font-size:25px; margin:auto 50px;">New Order ' . $invoicedata->id . '</p></header>
        <div style="background-color:#fff; padding:30px;">
        <p style="color:#000; margin:20px 0">You received the following order from ' . $order_user_name . '</p>
        <p style="font-size:18px; color:rgb(16, 133, 241)">[<a>Order ' . $invoicedata->id . '</a>] <b>' . userdate($paymentdate->timecreated, '%B %d %Y') . '</b></p>
        <div style="overflow-x:auto"><table class="table table-bordered" border="1" style="color:#5373ec; width:100%; border-collapse:collapse; border:2px solid #eee;">
        <thead><tr style="text-align:left; font-size:18px; color:#726d6d;">
        <th style="padding:20px 10px; font-size:14px;font-weight:700">Product</th><th style="padding:20px 10px; font-size:14px;font-weight:700">Quantity</th><th  style="width:60%; padding:20px 10px; font-size:14px; font-weight:700">Total</th></tr></thead><tbody>';

    $lquantity = "";
    $subtotal = 0;
    
    foreach ($orderdata as $ordervalue) {

        $getcourse = $DB->get_record("course", array("id" => $ordervalue->courseid));
        $courselink = $getcourse ? $CFG->wwwroot . '/course/view.php?id=' . $getcourse->id : "#";
        $lquantity = $ordervalue->license_allocation ? " x " . $ordervalue->license_allocation : "";
        $productname = $ordervalue->name ?? ($getcourse ? $getcourse->fullname : '<span style="color: #b70909;background-color:#fdbfbf;">Product deleted</span>');

        $lineTotal = $ordervalue->price * $ordervalue->license_allocation;
        $subtotal += $lineTotal;

        $order_items_table .= '
            <tr>
                <td style="padding:10px"><a style="color:#636363; font-size:14px; text-decoration:none" class="cours_orders_links shadow-md text-decoration-none" href="' . $courselink . '">' . $productname . ' ' . $lquantity . '</a></td>
                <td style=" padding:10px; color:#636363;">' . $lquantity . '</td>
                <td style="padding:10px; color:#636363;"><span>' . number_format($lineTotal, 2) . ' ' . $ordervalue->currency . '</span></td>
            </tr>';
    }

    // echo "<pre>";
    // print_r($invoicedata);
    // print_r($paymentdate);
    // echo "</pre>";

    //  Moved total calculation after loop
    $total = $subtotal - $couponamount;

  $order_items_table .= '
               </tbody>
               <tfoot style="border-top:3px solid #ccc">
                  <tr class="" style="text-align:left;">
                     <th colspan="2" style="padding:10px; font-weight:bold; color:#726d6d; font-size:14px">Subtotal:</th>
                     
                     <td style=" padding:10px; color:#726d6d">' . number_format($subtotal, 2) . ' ' . $ordervalue->currency . '</td>
                  </tr>
                  <tr class="" style="text-align:left;">
                     <th colspan="2" style="padding:10px; font-weight:bold; color:#726d6d; font-size:14px">Discount:</th>
                    
                     <td style=" padding:10px; color:#726d6d"> - ' . number_format($coupondiscount, 2) . ' ' . $couponcurrency . '</td>
                  </tr>
                  <tr class="" style="text-align:left;">
                     <th colspan="2" style="padding:10px; font-weight:bold; color:#726d6d; font-size:14px">Payment method:</th>
                    
                     <td style="padding:10px; color:#726d6d">'.strtoupper($paymentdate->gateway).'</td>
                  </tr>
                  <tr class="" style="text-align:left;">
                     <th colspan="2" style="padding:10px; font-weight:bold; color:#726d6d; font-size:14px">Total:</th>
                   
                     <td style="padding:10px; color:#726d6d">' . number_format($total, 2) . ' ' . $ordervalue->currency . '</td>
                  </tr>
                   <tr class="" style="text-align:left;">
                     <th colspan="2" style="padding:10px; font-weight:bold; color:#726d6d; font-size:14px">Note:</th>
                   
                     <td style="padding:10px; color:#726d6d">'.$invoicedata->ordernotes.'</td>
                  </tr>
               </tfoot>
            </table>
            </div>
            </div>
           <div style="padding:30px">
                       <h2 style="color:rgb(16, 133, 241)">Billing address</h2>
                      
                       <div class="invoice_parent" style="border:1px solid #ccc; padding:10px; color:#000">
                          <p  style="margin:0;">
                         <strong>Invoice ID : </strong>
                         <span>' . $invoicedata->id . '</span>
                      </p>
                       
                      <div class="" style="display:flex; align-items:center">
                         <div style="white-space:nowrap">
                            <strong>Order Status: </strong>
                         </div>

                        <div>
                              <span class="completed_order" style="padding: 6px;  border-radius: 5px;">';
  // if($invoicedata->status == 'p') { 
  $order_items_table .=  'Completed';
  // }
  $order_items_table .= '
                             </span>
                        </div>
                      </div>
                      <div class="col-md-12 bg-light shadow-md p-4 my-4">

                  <address>
                    ' . $usercompany . '
                    ' . $invoicedata->firstname . ' ' . $invoicedata->lastname . '<br>
                    ' . $invoicedata->phone1 . '<br>
                    ' . $invoicedata->address . '<br>
                    ' . $invoicedata->city . '<br>
                    ' . $invoicedata->state . ' ' . $invoicedata->postcode . '
                  </address>
              </div>
                      </div>
                       

   </div>
         </div>
         
      </div>
      </div>
          <style>

          </style>';

  // end oderdata
  $placeholders = ['{{order_user_name}}', '{{order_items_table}}', '{{company_logo}}'];
  $replacements = [$order_user_name, $order_items_table, $company_logo];
  $email_body = str_replace($placeholders, $replacements, $emailtemplatedata_user);

  $messagehtml =   "<!DOCTYPE html>
                                <html>
                                    <head>
                                        <title>Mail</title>
                                        <link rel='stylesheet' type='text/css' href='https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css'>
                                    </head>
                                    <body>" . $email_body . "
                                    </body>
                                </html>";

  // echo $messagehtml;
  // die('stop');
  $company = $DB->get_record('company', ['id' => $companyid]);
  $getcompany_wu = $DB->get_record_sql("SELECT * FROM {company_users} WHERE companyid = ? AND managertype =?", array($companyid, 1));
  $whitelableuser = $DB->get_record("user", array("id" => $getcompany_wu->userid));
  $white_label_admin_name = fullname($whitelableuser);
  $fromUser =  $white_label_admin_name . '(' . $company->name . ')';

  // $fromUser = core_user::get_support_user();
  $subject = $gettemplate->email_subject;
  $emailuser = (object) [];
  $emailuser->email = $USER->email;
   if(!empty($gettemplate->destination_email)){
    $emailuser->email = $gettemplate->destination_email;
  } else {
    $emailuser->email = $USER->email;
  }
  // $emailuser->email = "suneet.sharma695@gmail.com";
  $emailuser->firstname = $USER->firstname;
  $emailuser->lastname = $USER->lastname;
  $emailuser->maildisplay = true;
  $emailuser->mailformat = 1; // 0 (zero) text-only emails, 1 (one) for HTML/Text emails.
  $emailuser->id = 1;
  $emailuser->firstnamephonetic = false;
  $emailuser->lastnamephonetic = false;
  $emailuser->middlename = false;
  $emailuser->username = false;
  $emailuser->alternatename = false;
  if ($emailtemplatedata_user) {
    // $emailstatus = email_to_user($emailuser, $fromUser, $subject, $message = '', $messagehtml, '', '', true, '', '', 79, $companyid);
    $emailstatus = email_learnpeek_to_user($emailuser, $fromUser, $subject, $message = '' , $messagehtml, '', '', true, '', '', 79, $companyid);
    // if($emailstatus){
    //   echo "mail send";
    // }else{
    //   echo "mail not send";
    // }
    
  }
}
function order_complete_email_to_superadmin($companyid, $oderuserid, $oderinvoiceid)
{

  global $DB, $USER, $CFG;
  $company = $DB->get_record('company', ['id' => $companyid]);
  $orderuser = $DB->get_record("user", array("id" => $oderuserid));

    // SELECT * FROM `mdl_user` WHERE `id` IN (2,43,552)
  $gettemplate = $DB->get_record_sql("SELECT * FROM {custom_email_template} WHERE email_template = ?",array('order-email-admin'));
  if($gettemplate){
    $admin_order_emailtemplate = $gettemplate->email_message;
    $superadminid = $DB->get_record_sql("SELECT * FROM {config} WHERE name = ?", array('siteadmins'));
    $superadmins = $DB->get_records_sql("SELECT * FROM {user} WHERE id IN(" . $superadminid->value . ")");
      // print_r($superadmins);
    foreach ($superadmins as $superadmin) {
      $superadminuser = $DB->get_record("user", array("id" => $superadmin->id));
      $order_user_name = fullname($orderuser);
      $super_admin_name = fullname($superadminuser);

      //get compnay logo
      $comlogodetails = $DB->get_record_sql('SELECT * FROM {company_logo} WHERE company_id = ?', array($companyid));
      if ($comlogodetails) {
        $companylogo = $DB->get_record_sql("SELECT * FROM {files} WHERE contextid= 1 AND component='landingpage' AND itemid=? AND filesize > 0 AND filearea = ?", array($comlogodetails->id, 'logo_image'));
        $logourl = $CFG->wwwroot . '/blocks/custom_mycourse/file.php?id=' . $companylogo->pathnamehash;
        $company_logo = "<img src='" . $logourl . "' style='width: 125px;max-height: 150px;object-fit: cover;'>";
      } else {
        $company_logo = "";
      }


      $invoicedata = $DB->get_record("invoice", array("id" => $oderinvoiceid));
      // $price = $DB->get_record("payments", array("id"=>$invoicedata->paymentid));
      $price = $DB->get_record("payments", array("itemid" => $oderinvoiceid, "userid" => $USER->id));


       // UPDATED: Get coupon and discount details correctly
          $coupon = $DB->get_record_sql(
              "SELECT *, lc.discount_amount as disamount, ci.discount_amount, lc.type ,lc.code
               FROM {coupon_invoice} ci 
               JOIN {local_coupon} lc ON ci.couponid = lc.id 
               WHERE ci.invoiceid = ?", 
              [$invoicedata->id]
          );
          $coupondiscount= $coupon ? $coupon->disamount : 0;
          $couponamount = $coupon ? $coupon->discount_amount : 0;
          $couponcurrency = $coupon ? ($coupon->type == 'fixed' ? 'USD' : '%') : '';
          $couponname = $coupon ? ($coupon->code ? $coupon->code : "") : "";


      $orderdata = $DB->get_records_sql("SELECT ii.*, csc.courseid,css.name 
                 FROM {invoiceitem} as ii 
                 LEFT JOIN {course_shopsettings} as css ON ii.invoiceableitemid = css.id 
                 INNER JOIN {course_shopsettings_courses} as csc ON csc.itemid =ii.invoiceableitemid 
                 WHERE ii.invoiceid = ?", array($oderinvoiceid));

      $paymentdate = $DB->get_record('payments', ['itemid' => $oderinvoiceid]);
      $usertype =  $DB->get_record("company_users", array("companyid" => $invoicedata->companyid, "userid" => $invoicedata->userid));
      if ($usertype->managertype == 0) {
        $usercompany = "";
      } else {
        $usercompany = $invoicedata->company;
      }
  
        $order_items_table = '<div class="container-fluid" style="background-color: #f4f4f7; padding:10px;"><div class="row" style="margin:10px 0;">
        <div class="cst_styling1" style="width:600px; margin:50px auto; border: 1px solid #fefe; border-radius: 10px; background-color:#fff; color: #5373ec; font-family:Helvetica, Helvetica, Roboto, Arial, sans-serif; font-size: 14px;">
        <header style="background-color:rgb(16, 133, 241); padding:20px;">
        <p style="color:#fff; font-size:25px; margin:auto 50px;">New Order ' . $invoicedata->id . '</p></header>
        <div style="background-color:#fff; padding:30px;">
        <p style="color:#000; margin:20px 0">You received the following order from ' . $order_user_name . '</p>
        <p style="font-size:18px; color:rgb(16, 133, 241)">[<a>Order ' . $invoicedata->id . '</a>] <b>' . userdate($paymentdate->timecreated, '%B %d %Y') . '</b></p>
        <div style="overflow-x:auto"><table class="table table-bordered" border="1" style="color:#5373ec; width:100%; border-collapse:collapse; border:2px solid #eee;">
        <thead><tr style="text-align:left; font-size:18px; color:#726d6d;">
        <th style="padding:20px 10px; font-size:14px;font-weight:700">Product</th><th style="padding:20px 10px; font-size:14px;font-weight:700">Quantity</th><th  style="width:60%; padding:20px 10px; font-size:14px; font-weight:700">Total</th></tr></thead><tbody>';               



          // UPDATED: Subtotal calculation before total
          $subtotal = 0;
          foreach ($orderdata as $ordervalue) {
              $getcourse = $DB->get_record("course", array("id" => $ordervalue->courseid));
              $courselink = $getcourse ? $CFG->wwwroot . '/course/view.php?id=' . $getcourse->id : "#"; // UPDATED: Fixed $CFG->wwww typo

              $lquantity = $ordervalue->license_allocation ? " x " . $ordervalue->license_allocation : "";
              $productname = $ordervalue->name ?: ($getcourse ? $getcourse->fullname : '<span style="color: #b70909;cursor: none;background-color:#fdbfbf;">Product deleted</span>'); // UPDATED

              $itemtotal = $ordervalue->price * $ordervalue->license_allocation;
              $subtotal += $itemtotal;

              $order_items_table .= '
              <tr>
                 <td style="padding:10px"><a style="color:#636363; font-size:14px; text-decoration:none" class="cours_orders_links shadow-md text-decoration-none" href="' . $courselink . '">' . $productname . $lquantity . '</a></td>
                 <td style="padding:10px; color:#636363;">' . $lquantity . '</td>
                 <td style="padding:10px; color:#636363;"><span>' . number_format($itemtotal, 2) . ' ' . $ordervalue->currency . '</span></td>
              </tr>';
          }

          $total = $subtotal - $couponamount; // UPDATED: Discount applied after subtotal

      $order_items_table .= '
                           </tbody>
                           <tfoot style="border-top:3px solid #ccc">
                              <tr class="" style="text-align:left;">
                                 <td colspan="2" style="padding:10px; font-weight:bold; color:#726d6d; font-size:14px">Subtotal</td>
                                 <td style="padding:10px; color:#726d6d">' . number_format($subtotal, 2) . ' ' . $ordervalue->currency . '</td>
                              </tr>
                              <tr  class="">
                                 <td colspan="2" style="padding:10px; font-weight:bold; color:#726d6d; font-size:14px">Coupon used</td>
                                 <td style="padding:10px; color:#726d6d">' . $couponname . '</td>
                              </tr>
                              <tr class="">
                                 <td colspan="2" style="padding:10px; font-weight:bold; color:#726d6d; font-size:14px">Discount</td>
                                 <td style="padding:10px; color:#726d6d"> - ' . number_format($coupondiscount, 2) . ' ' . $couponcurrency . '</td>
                              </tr>
                              <tr class="" style="text-align:left;">
                               <th colspan="2" style="padding:10px; font-weight:bold; color:#726d6d; font-size:14px">Payment method:</th>
                              
                               <td style=" padding:10px; color:#726d6d">'.strtoupper($paymentdate->gateway).'</td>
                            </tr>
                              <tr  class="">
                                 <td colspan="2" style="padding:10px; font-weight:bold; color:#726d6d; font-size:14px">Total</td>
                                 <td style="padding:10px; color:#726d6d">' . number_format($total, 2) . ' ' . $ordervalue->currency . '</td>
                              </tr>
                              <tr class="" style="text-align:left;">
                               <th colspan="2" style="padding:10px; font-weight:bold; color:#726d6d; font-size:14px">Note:</th>
                             
                               <td style="padding:10px; color:#726d6d">'.$invoicedata->ordernotes.'</td>
                            </tr>
                           </tfoot>
                        </table>
                        </div>
                        </div>
                       <div style="padding:30px">
                         <h2 style="color:rgb(16, 133, 241)">Billing address</h2>
                        
                         <div class="invoice_parent" style="border:1px solid #ccc; padding:10px; color:#000">
                            <p  style="margin:0;">
                           <strong>Invoice ID : </strong>
                           <span>' . $invoicedata->id . '</span>
                        </p>
                         
                        <div class="" style="display:flex; align-items:center">
                           <div style="white-space:nowrap">
                              <strong>Order Status: </strong>
                           </div>

                          <div>
                                <span class="completed_order" style="padding: 6px;  border-radius: 5px;">';
      // if($invoicedata->status == 'p') { 
      $order_items_table .=  'Completed';
      // }
      $order_items_table .= '
                               </span>
                          </div>
                        </div>
                        <div class="col-md-12 bg-light shadow-md p-4 my-4">

                    <address>
                      ' . $usercompany . '
                      ' . $invoicedata->firstname . ' ' . $invoicedata->lastname . '<br>
                      ' . $invoicedata->email . '<br>
                      ' . $invoicedata->phone1 . '<br>
                      ' . $invoicedata->address . '<br>
                      ' . $invoicedata->city . '<br>
                      ' . $invoicedata->state . ' ' . $invoicedata->postcode . '
                    </address>
                </div>
                        </div>
                         

     </div>
                     </div>
                     
                  </div>
                  </div>
                      <style>

                      </style>';

      $placeholders = ['{{super_admin_name}}', '{{order_user_name}}', '{{order_items_table}}', '{{company_logo}}'];
      $replacements = [$super_admin_name, $order_user_name, $order_items_table, $company_logo];
      $email_body = str_replace($placeholders, $replacements, $admin_order_emailtemplate);
      $messagehtml =   "<!DOCTYPE html>
                                  <html>
                                      <head>
                                          <title>Mail</title>
                                          <link rel='stylesheet' type='text/css' href='https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css'>
                                      </head>
                                      <body>" . $email_body . "
                                      </body>
                                  </html>";


      $getcompany_wu = $DB->get_record_sql("SELECT * FROM {company_users} WHERE companyid = ? AND managertype =?", array($companyid, 1));
      $whitelableuser = $DB->get_record("user", array("id" => $getcompany_wu->userid));
      $white_label_admin_name = fullname($whitelableuser);
      $fromUser =  $white_label_admin_name . '(' . $company->name . ')';
      // $fromUser = core_user::get_support_user();


       $subj_placeholders = ['{{compnay_name}}'];
       $subj_replacements = [$company->name];
      $email_subject = str_replace($subj_placeholders, $subj_replacements, $gettemplate->email_subject);
  

      $fromUser =  $white_label_admin_name . '(' . $company->name . ')';
      $subject = $email_subject;
      $emailuser = (object) [];
      // $emailuser->email = $whitelableuser->email;
      if(!empty($gettemplate->destination_email)){
        $emailuser->email = $gettemplate->destination_email;
      } else {
        $emailuser->email = $superadminuser->email;
      }
      $emailuser->firstname = $whitelableuser->firstname;
      $emailuser->lastname = $whitelableuser->lastname;
      $emailuser->maildisplay = true;
      $emailuser->mailformat = 1; // 0 (zero) text-only emails, 1 (one) for HTML/Text emails.
      $emailuser->id = 1;
      $emailuser->firstnamephonetic = false;
      $emailuser->lastnamephonetic = false;
      $emailuser->middlename = false;
      $emailuser->username = false;
      $emailuser->alternatename = false;
      // $emailstatus = email_to_user($emailuser, $fromUser, $subject, $message = '', $messagehtml, '', '', true, '', '', 79, $companyid);
      $emailstatus = email_learnpeek_to_user($emailuser, $fromUser, $subject, $message = '', $messagehtml, '', '', true, '', '', 79, $companyid);
      if(!empty($gettemplate->destination_email)){
        break;
      }
    }

  }else{
    return;
  }

  
}


// function course_complte_email_to_user($courseid)
// {
//   global $DB, $USER, $CFG;
//   $companyid = iomad::get_my_companyid(context_system::instance());

//   $gettemplate = $DB->get_record_sql("SELECT * FROM {custom_email_template} WHERE company_id = ? AND email_template = ?", array($companyid, 'course-completion'));
//   if ($gettemplate) {
//     $emailtemplatedata_user = $gettemplate->email_message;
//   } else {
//     $get_deflt_template = $DB->get_record_sql("SELECT * FROM {custom_email_template} WHERE company_id = ? AND email_template = ?", array(0, 'course-completion'));
//     if ($get_deflt_template) {
//       $emailtemplatedata_user = $get_deflt_template->email_message;
//     } else {
//       $emailtemplatedata_user = 0;
//     }
//   }

//   //get compnay logo
//   $comlogodetails = $DB->get_record_sql('SELECT * FROM {company_logo} WHERE company_id = ?', array($companyid));
//   if ($comlogodetails) {
//     $companylogo = $DB->get_record_sql("SELECT * FROM {files} WHERE contextid= 1 AND component='landingpage' AND itemid=? AND filesize > 0 AND filearea = ?", array($comlogodetails->id, 'logo_image'));
//     $logourl = $CFG->wwwroot . '/blocks/custom_mycourse/file.php?id=' . $companylogo->pathnamehash;
//     $company_logo = "<img src='" . $logourl . "' style='width: 100px; height: 100px; object-fit: contain;'>";
//   } else {
//     $company_logo = "";
//   }
//   $coursedata = $DB->get_record("course", array("id" => $courseid));

//   $user_display_name = fullname($USER);
//   $course_name = $coursedata->fullname;
//   $checkedcoursescorm = $DB->get_record("scorm", array("course" => $courseid));
//   if ($checkedcoursescorm) {
//     $checkedcoursescorm = $DB->get_record_sql("SELECT * FROM {scorm_scoes_track} WHERE userid = ? AND scormid =? AND element LIKE ?", array($USER->id, $checkedcoursescorm->id, 'cmi.core.score.raw'));
//     if ($checkedcoursescorm->value >= 70) {
//       $course_grade = $checkedcoursescorm->value . ' (PASSED)';
//     } else {
//       $course_grade = $checkedcoursescorm->value . ' (FAILED)';
//     }
//   } else {
//     $course_grade = "Grade Not Set";
//   }


//   $placeholders = ['{{user_display_name}}', '{{course_name}}', '{{company_logo}}', '{{course_grade}}'];
//   $replacements = [$user_display_name, $course_name, $company_logo, $course_grade];
//   $email_body = str_replace($placeholders, $replacements, $emailtemplatedata_user);

//   echo $messagehtml =   "<!DOCTYPE html>
//                                 <html>
//                                     <head>
//                                         <title>Mail</title>
//                                         <link rel='stylesheet' type='text/css' href='https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css'>
//                                     </head>
//                                     <body>" . $email_body . "
//                                     </body>
//                                 </html>";

//   $fromUser = core_user::get_support_user();;
//   $subject = $gettemplate->email_subject;
//   $emailuser = (object) [];
//   // $emailuser->email = $USER->email;
//   $emailuser->email = $USER->email;
//   $emailuser->firstname = $USER->firstname;
//   $emailuser->lastname = $USER->lastname;
//   $emailuser->maildisplay = true;
//   $emailuser->mailformat = 1; // 0 (zero) text-only emails, 1 (one) for HTML/Text emails.
//   $emailuser->id = 1;
//   $emailuser->firstnamephonetic = false;
//   $emailuser->lastnamephonetic = false;
//   $emailuser->middlename = false;
//   $emailuser->username = false;
//   $emailuser->alternatename = false;
//   if ($emailtemplatedata_user) {
//     // email_to_user($emailuser,$fromUser, $subject, $message = '', $messagehtml);


//   }
// }
