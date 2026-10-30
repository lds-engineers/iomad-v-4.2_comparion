<?php
include_once("../../config.php");
global $CFG, $USER, $DB, $PAGE;

function order_complete_email_to_whitelable($companyid, $oderuserid, $oderinvoiceid)
{

  global $DB, $USER, $CFG;
  $company = $DB->get_record('company', ['id' => $companyid]);
  $getcompany_wu = $DB->get_records_sql("SELECT * FROM {company_users} WHERE companyid = ? AND managertype =?", array($companyid, 1));
  $orderuser = $DB->get_record("user", array("id" => $oderuserid));
 
  foreach ($getcompany_wu as $com_wtuser) {
    // $gettemplate = $DB->get_record_sql("SELECT * FROM {custom_email_template} WHERE company_id = ? AND email_template = ?",array($companyid,'order-email-white-label'));
    $gettemplate = $DB->get_record_sql("SELECT * FROM {custom_email_template} WHERE company_id = ? AND email_template = ?", array(39, 'order-email-white-label'));
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
      $company_logo = "<img src='" . $logourl . "' style='width: 100px;height: 100px;object-fit: contain;''>";
    } else {
      $company_logo = "";
    }


    $invoicedata = $DB->get_record("invoice", array("id" => $oderinvoiceid));
    // $price = $DB->get_record("payments", array("id"=>$invoicedata->paymentid));
    $price = $DB->get_record("payments", array("itemid" => $oderinvoiceid, "userid" => $USER->id));


     // UPDATED: Get coupon and discount details correctly
        $coupon = $DB->get_record_sql(
            "SELECT *, lc.discount_amount as disamount, ci.discount_amount, lc.type 
             FROM {coupon_invoice} ci 
             JOIN {local_coupon} lc ON ci.couponid = lc.id 
             WHERE ci.invoiceid = ?", 
            [$invoicedata->id]
        );
        $coupondiscount= $coupon ? $coupon->disamount : 0;
        $couponamount = $coupon ? $coupon->discount_amount : 0;
        $couponcurrency = $coupon ? ($coupon->type == 'fixed' ? 'USD' : '%') : '';


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
    $order_items_table = '<div class="container-fluid"  style="background-color: #f4f4f7; padding:10px;">
                <div class="row" style="margin:10px 0;">
                   <div class="cst_styling1" style="width:600px; margin:50px auto; border: 1px solid #fefe;   border-radius: 10px; background-color:#fff;  color: #5373ec; font-family:monospace;">

                     <header style="background-color:rgb(16, 133, 241); padding:20px;">
                        <p style="color:#fff; font-size:25px; margin:auto 50px;">New Order ' . $invoicedata->id . '</p>
                     </header>
                     
                   
                      <div style="background-color:#fff; padding:30px;">
                       <p style="color:#000; margin:20px 0"> 
                         Your recieved the following order from joseph gerbino
                       </p>
                       <p style="font-size:18px; color:rgb(16, 133, 241)">
                         [<a>Order ' . $invoicedata->id . '</a>] <b>' . userdate($paymentdate->timecreated, '%B %d %Y') . '</b>
                       </p>
                      <div style="overflow-x:auto">
                      <table class="table table-bordered" border="1" style="color:#5373ec; width:100%; border-collapse:collapse; border-color:#eee;">
                         <thead class="text-white bg_custom">
                            <tr style="text-align:left; font-size:18px; color:#726d6d;">
                               <th style="padding:20px 10px"><b>Product</b></th>
                               <th style="padding:20px 10px"><b>Quantity</b></th>
                               <th style="padding:20px 10px"><b>Total</b></th>
                            </tr>
                         </thead>
                         <tbody>';


  
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
               <td style="padding:10px"><a style="font-size:16px; color:#000;" href="' . $courselink . '">' . $productname . $lquantity . '</a></td>
               <td style="text-align:center; padding:10px 5px; color:#000;">' . $lquantity . '</td>
               <td style="text-align:center; padding:10px 5px; color:#000;"><span>' . number_format($itemtotal, 2) . ' ' . $ordervalue->currency . '</span></td>
            </tr>';
        }

        $total = $subtotal - $couponamount; // UPDATED: Discount applied after subtotal

    $order_items_table .= '
                         </tbody>
                         <tfoot>
                            <tr  class="">
                               <td colspan="2" style="padding:10px; font-weight:bold; color:#726d6d; font-size:16px">Subtotal</td>
                               <td style="text-align:center; padding:5px; color:#726d6d">' . number_format($subtotal, 2) . ' ' . $ordervalue->currency . '</td>
                            </tr>
                            <tr class="">
                               <td colspan="2" style=" padding:10px; font-weight:bold; color:#726d6d; font-size:16px">Discount</td>
                               <td style="text-align:center; padding:5px; color:#726d6d"> - ' . number_format($coupondiscount, 2) . ' ' . $couponcurrency . '</td>
                            </tr>
                            <tr  class="">
                               <td colspan="2" style="padding:10px; font-weight:bold; color:#726d6d; font-size:16px">Total</td>
                               <td style=" text-align:center; padding:5px; color:#726d6d">' . number_format($total, 2) . ' ' . $ordervalue->currency . '</td>
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
    echo $messagehtml =   "<!DOCTYPE html>
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
    // $emailuser->email = $whitelableuser->email;
    $emailuser->email = $USER->email;
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
      email_to_user($emailuser, $fromUser, $subject, $message = '', $messagehtml);
      
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
    $company_logo = "<img src='" . $logourl . "' style='width: 100px; height: 100px; object-fit: contain;'>";
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
        <div class="cst_styling1" style="width:600px; margin:50px auto; border: 1px solid #fefe; border-radius: 10px; background-color:#fff; color: #5373ec; font-family:monospace;">
        <header style="background-color:rgb(16, 133, 241); padding:20px;">
        <p style="color:#fff; font-size:25px; margin:auto 50px;">New Order ' . $invoicedata->id . '</p></header>
        <div style="background-color:#fff; padding:30px;">
        <p style="color:#000; margin:20px 0">You received the following order from ' . $order_user_name . '</p>
        <p style="font-size:18px; color:rgb(16, 133, 241)">[<a>Order ' . $invoicedata->id . '</a>] <b>' . userdate($paymentdate->timecreated, '%B %d %Y') . '</b></p>
        <div style="overflow-x:auto"><table class="table table-bordered" border="1" style="color:#5373ec; width:100%; border-collapse:collapse; border-color:#eee;">
        <thead><tr style="text-align:left; font-size:18px; color:#726d6d;">
        <th style="padding:20px 10px"><b>Product</b></th><th style="padding:20px 10px"><b>Quality</b></th><th style="padding:20px 10px"><b>Total</b></th></tr></thead><tbody>';

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
                <td style="padding:10px"><a style="font-size:12px;" class="cours_orders_links shadow-md text-decoration-underline" href="' . $courselink . '">' . $productname . ' ' . $lquantity . '</a></td>
                <td style="text-align:center; padding:10px 5px; color:#000;">' . $lquantity . '</td>
                <td style="text-align:center; padding:10px 5px; color:#000;"><span>' . number_format($lineTotal, 2) . ' ' . $ordervalue->currency . '</span></td>
            </tr>';
    }

    // ✅ Moved total calculation after loop
    $total = $subtotal - $couponamount;

  $order_items_table .= '
               </tbody>
               <tfoot>
                  <tr class="" style="text-align:left;">
                     <th colspan="2" style="padding:10px; font-weight:bold; color:#726d6d; font-size:16px">Subtotal</th>
                     
                     <td style="text-align:center; padding:5px; color:#726d6d">' . number_format($subtotal, 2) . ' ' . $ordervalue->currency . '</td>
                  </tr>
                  <tr class="" style="text-align:left;">
                     <th colspan="2" style="padding:10px; font-weight:bold; color:#726d6d; font-size:16px">Discount</th>
                    
                     <td style="text-align:center; padding:5px; color:#726d6d"> - ' . number_format($coupondiscount, 2) . ' ' . $couponcurrency . '</td>
                  </tr>
                  <tr class="" style="text-align:left;">
                     <th colspan="2" style="padding:10px; font-weight:bold; color:#726d6d; font-size:16px">Total</th>
                   
                     <td style="text-align:center; padding:5px; color:#726d6d">' . number_format($total, 2) . ' ' . $ordervalue->currency . '</td>
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

  echo $messagehtml =   "<!DOCTYPE html>
                                <html>
                                    <head>
                                        <title>Mail</title>
                                        <link rel='stylesheet' type='text/css' href='https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css'>
                                    </head>
                                    <body>" . $email_body . "
                                    </body>
                                </html>";
  $company = $DB->get_record('company', ['id' => $companyid]);
  $getcompany_wu = $DB->get_record_sql("SELECT * FROM {company_users} WHERE companyid = ? AND managertype =?", array($companyid, 1));
  $whitelableuser = $DB->get_record("user", array("id" => $getcompany_wu->userid));
  $white_label_admin_name = fullname($whitelableuser);
  $fromUser =  $white_label_admin_name . '(' . $company->name . ')';

  // $fromUser = core_user::get_support_user();
  $subject = $gettemplate->email_subject;
  $emailuser = (object) [];
  // $emailuser->email = $USER->email;
  $emailuser->email = $USER->email;
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
    email_to_user($emailuser, $fromUser, $subject, $message = '', $messagehtml);
    
  }
}
function course_complte_email_to_user($courseid)
{
  global $DB, $USER, $CFG;
  $companyid = iomad::get_my_companyid(context_system::instance());

  $gettemplate = $DB->get_record_sql("SELECT * FROM {custom_email_template} WHERE company_id = ? AND email_template = ?", array($companyid, 'course-completion'));
  if ($gettemplate) {
    $emailtemplatedata_user = $gettemplate->email_message;
  } else {
    $get_deflt_template = $DB->get_record_sql("SELECT * FROM {custom_email_template} WHERE company_id = ? AND email_template = ?", array(0, 'course-completion'));
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
    $company_logo = "<img src='" . $logourl . "' style='width: 100px; height: 100px; object-fit: contain;'>";
  } else {
    $company_logo = "";
  }
  $coursedata = $DB->get_record("course", array("id" => $courseid));

  $user_display_name = fullname($USER);
  $course_name = $coursedata->fullname;
  $checkedcoursescorm = $DB->get_record("scorm", array("course" => $courseid));
  if ($checkedcoursescorm) {
    $checkedcoursescorm = $DB->get_record_sql("SELECT * FROM {scorm_scoes_track} WHERE userid = ? AND scormid =? AND element LIKE ?", array($USER->id, $checkedcoursescorm->id, 'cmi.core.score.raw'));
    if ($checkedcoursescorm->value >= 70) {
      $course_grade = $checkedcoursescorm->value . ' (PASSED)';
    } else {
      $course_grade = $checkedcoursescorm->value . ' (FAILED)';
    }
  } else {
    $course_grade = "Grade Not Set";
  }


  $placeholders = ['{{user_display_name}}', '{{course_name}}', '{{company_logo}}', '{{course_grade}}'];
  $replacements = [$user_display_name, $course_name, $company_logo, $course_grade];
  $email_body = str_replace($placeholders, $replacements, $emailtemplatedata_user);

  echo $messagehtml =   "<!DOCTYPE html>
                                <html>
                                    <head>
                                        <title>Mail</title>
                                        <link rel='stylesheet' type='text/css' href='https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css'>
                                    </head>
                                    <body>" . $email_body . "
                                    </body>
                                </html>";

  $fromUser = core_user::get_support_user();;
  $subject = $gettemplate->email_subject;
  $emailuser = (object) [];
  // $emailuser->email = $USER->email;
  $emailuser->email = $USER->email;
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
    // email_to_user($emailuser,$fromUser, $subject, $message = '', $messagehtml);


  }
}
