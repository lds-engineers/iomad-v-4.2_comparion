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
    $cumulative_email = $DB->get_record_sql("SELECT * FROM {company_mail_configuration} WHERE company_id =?  AND cumulative_email = ?", array($companyid, 1));
    if ($cumulative_email) {
        return;
    }

    foreach ($getcompany_wu as $com_wtuser) {
        $gettemplate = $DB->get_record_sql("SELECT * FROM {custom_email_template} WHERE company_id = ? AND email_template = ?", array($companyid, 'order-email-white-label'));
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
      $company_logo = "<img src='" . $logourl . "' width='125' width='125' ' style='display:block; width:125px;  max-width:125px; object-fit:contain;' alt='Company Logo'>";
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
        $coupondiscount = $coupon ? $coupon->disamount : 0;
        $couponamount = $coupon ? $coupon->discount_amount : 0;
        $couponcurrency = $coupon ? ($coupon->type == 'fixed' ? 'USD' : '%') : '';
        $couponname = $coupon ? ($coupon->code ? $coupon->code : "") : "";


        $orderdata = $DB->get_records_sql("SELECT ii.*, csc.courseid,css.name ,css.program
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
        $order_items_table = '
        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="padding:10px; font-family:Helvetica, Arial, sans-serif; font-size:14px;">
          <tr>
            <td align="center" style="padding:10px;">
              
              <!-- Card wrapper -->
              <table width="600" border="0" cellspacing="0" cellpadding="0" align="center" style="border:1px solid #cccccc; background-color:#ffffff;">
                
                <!-- Header -->
                <tr>
                  <td bgcolor="#5373ec" style="padding:20px; text-align:left;">
                    <p style="margin:0; font-size:25px; font-weight:bold; color:#ffffff; font-family:Helvetica, Arial, sans-serif;">
                      New Order ' . $invoicedata->id . '
                    </p>
                  </td>
                </tr>
                
                <!-- Body -->
                <tr>
                  <td style="padding:30px; background-color:#ffffff; color:#000000; font-size:14px; line-height:20px; font-family:Helvetica, Arial, sans-serif;">
                    
                    <p style="margin:0 0 20px 0; color:#000000;">
                      You received the following order from ' . $order_user_name . '
                    </p>
                    
                    <p style="margin:0 0 20px 0; font-size:18px; color:#1085f1;">
                      [<a href="#" style="color:#1085f1; text-decoration:none;">Order ' . $invoicedata->id . '</a>] 
                      <b style="color:#000000;">' . userdate($paymentdate->timecreated, "%B %d %Y") . '</b>
                    </p>
                    
                    <!-- Items Table -->
                    <table width="100%" border="1" cellspacing="0" cellpadding="10" style="border-collapse:collapse; border:2px solid #eeeeee; color:#5373ec;">
                      <thead>
                        <tr style="text-align:left; font-size:14px; color:#726d6d; background-color:#f9f9f9;">
                          <th style="padding:10px; font-size:14px; font-weight:700;">Product</th>
                          <th style="padding:10px; font-size:14px; font-weight:700;">Quantity</th>
                          <th style="padding:10px; font-size:14px; font-weight:700; width:60%;">Total</th>
                        </tr>
                      </thead>
                      <tbody>';

            // UPDATED: Subtotal calculation before total
            $subtotal = 0;
            foreach ($orderdata as $ordervalue) {
                $getcourse = $DB->get_record("course", array("id" => $ordervalue->courseid));
                if($ordervalue->program==1 || $ordervalue->invoiceableitemtype=="licenseblock"){
                    $courselink = "#";
                }else{
                    $courselink = $getcourse ? $CFG->wwwroot . '/course/view.php?id=' . $getcourse->id : "#";

                }
                // $courselink = $getcourse ? $CFG->wwwroot . '/course/view.php?id=' . $getcourse->id : "#"; // UPDATED: Fixed $CFG->wwww typo

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
                      <tr class="" style="">
                        <th colspan="2" style="text-align:left; padding:10px; font-weight:bold; color:#726d6d; font-size:14px">Subtotal</th>
                        <td style=" padding:10px; color:#726d6d">' . number_format($subtotal, 2) . ' ' . $ordervalue->currency . '</td>
                            </tr>
                            <tr  class="">
                               <td colspan="2" style="padding:10px; font-weight:bold; color:#726d6d; font-size:14px">Coupon used</td>
                               <td style="padding:10px; color:#726d6d">' . $couponname . '</td>
                            </tr>
                            <tr class="">
                               <td colspan="2" style=" padding:10px; font-weight:bold; color:#726d6d; font-size:14px">Discount</td>
                               <td style="padding:10px; color:#726d6d"> - ' . number_format($coupondiscount, 2) . ' ' . $couponcurrency . '</td>
                            </tr>
                            <tr class="" style="=">
                               <th colspan="2" style="text-align:left; padding:10px; font-weight:bold; color:#726d6d; font-size:14px">Payment method:</th>
                              
                               <td style="padding:10px; color:#726d6d">' . strtoupper($paymentdate->gateway) . '</td>
                            </tr>
                            <tr  class="">
                               <td colspan="2" style="padding:10px; font-weight:bold; color:#726d6d; font-size:14px">Total</td>
                               <td style="padding:10px; color:#726d6d">' . number_format($total, 2) . ' ' . $ordervalue->currency . '</td>
                            </tr>
                            <tr class="" style="">
                               <th colspan="2" style="text-align:left; padding:10px; font-weight:bold; color:#726d6d; font-size:14px">Note:</th>
                             
                               <td style="padding:10px; color:#726d6d">' . $invoicedata->ordernotes . '</td>
                            </tr>
                             </tfoot>
                          </table>
                         <div>
                            <h2 style="color:rgb(16, 133, 241)">Billing address</h2>
                          
                            <div class="invoice_parent" style="border:1px solid #ccc; padding:10px; color:#000">
                                <p  style="margin:0;">
                                <strong>Invoice ID : </strong>
                                <span>' . $invoicedata->id . '</span>
                                </p>
                           
                                <div class="" style="display:flex; align-items:center">
                                    <strong>Order Status: </strong>
                                    <span class="completed_order" style="padding: 6px;  border-radius: 5px;">';
                                        $order_items_table .=  'Completed';
                                        $order_items_table .= '
                                    </span>
                                </div>
                                <div class="col-md-12 bg-light shadow-md p-4 my-4">
                                  <address>
                                    ' . $usercompany . '<br>
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
                    </td>
                </tr>
              </table>
            </td>
          </tr>
        </table>';

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

        // echo $messagehtml;

        // $fromUser = core_user::get_support_user();
        $fromUser =  $white_label_admin_name;
        $subject = $gettemplate->email_subject;
        $emailuser = (object) [];
        $emailuser->email = $whitelableuser->email;
        if (!empty($gettemplate->destination_email)) {
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
            $emailstatus = email_learnpeek_to_user($emailuser, $fromUser, $subject, $message = '', $messagehtml, '', '', true, '', '', 79, $companyid);

            if (!empty($gettemplate->destination_email)) {
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
    $company_logo = "<img src='" . $logourl . "' width='125' ' style='display:block; width:125px;  max-width:125px; object-fit:contain;' alt='Company Logo'>";
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

    $coupondiscount = $coupon ? $coupon->disamount : 0;
    $couponamount = $coupon ? $coupon->discount_amount : 0;
    $couponcurrency = $coupon ? ($coupon->type == 'fixed' ? 'USD' : '%') : '';

    $orderdata = $DB->get_records_sql("SELECT ii.*, csc.courseid, css.name ,css.program
        FROM {invoiceitem} as ii 
        LEFT JOIN {course_shopsettings} as css ON ii.invoiceableitemid = css.id 
        INNER JOIN {course_shopsettings_courses} as csc ON csc.itemid = ii.invoiceableitemid 
        WHERE ii.invoiceid = ?", array($oderinvoiceid));

    $usertype = $DB->get_record("company_users", array("companyid" => $invoicedata->companyid, "userid" => $invoicedata->userid));
    $usercompany = ($usertype && $usertype->managertype != 0) ? $invoicedata->company : "";

    // START TABLE
    $order_items_table = '
    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="padding:10px; font-family:Helvetica, Arial, sans-serif; font-size:14px;">
      <tr>
        <td align="center" style="padding:10px;">
          
          <!-- Card wrapper -->
          <table width="500" border="0" cellspacing="0" cellpadding="0" align="center" style="border:1px solid #cccccc; background-color:#ffffff;">
            
            <!-- Header -->
            <tr>
              <td bgcolor="#5373ec" style="padding:20px; text-align:left;">
                <p style="margin:0; font-size:25px; font-weight:bold; color:#ffffff; font-family:Helvetica, Arial, sans-serif;">
                  New Order ' . $invoicedata->id . '
                </p>
              </td>
            </tr>
            
            <!-- Body -->
            <tr>
              <td style="padding:30px; background-color:#ffffff; color:#000000; font-size:14px; line-height:20px; font-family:Helvetica, Arial, sans-serif;">
                
                <p style="margin:0 0 20px 0; color:#000000;">
                  Thank you for your order below!
                </p>
                
                <p style="margin:0 0 20px 0; font-size:18px; color:#1085f1;">
                  [<a href="#" style="color:#1085f1; text-decoration:none;">Order ' . $invoicedata->id . '</a>] 
                  <b style="color:#000000;">' . userdate($paymentdate->timecreated, "%B %d %Y") . '</b>
                </p>
                
                <!-- Items table -->
                <table width="100%" border="1" cellspacing="0" cellpadding="8" style="border-collapse:collapse; border:2px solid #eeeeee; color:#5373ec;">
                  <thead>
                    <tr style="text-align:left; font-size:14px; color:#726d6d; background-color:#f9f9f9;">
                      <th style="padding:10px; font-size:14px; font-weight:700;">Product</th>
                      <th style="padding:10px; font-size:14px; font-weight:700;">Quantity</th>
                      <th style="padding:10px; font-size:14px; font-weight:700;">Total</th>
                    </tr>
                  </thead>
                  <tbody>';

        $lquantity = "";
        $subtotal = 0;

        foreach ($orderdata as $ordervalue) {

            $getcourse = $DB->get_record("course", array("id" => $ordervalue->courseid));
            if($ordervalue->program==1 || $ordervalue->invoiceableitemtype=="licenseblock"){
                $courselink = "#";
            }else{
                $courselink = $getcourse ? $CFG->wwwroot . '/course/view.php?id=' . $getcourse->id : "#";

            }
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
                         <th colspan="2" style="padding:10px; font-weight:bold; color:#726d6d; font-size:14px; text-align:left;">Subtotal</th>
                         
                         <td style=" padding:10px; color:#726d6d">' . number_format($subtotal, 2) . ' ' . $ordervalue->currency . '</td>
                      </tr>
                      <tr class="" style="text-align:left;">
                         <th colspan="2" style="padding:10px; font-weight:bold; color:#726d6d; font-size:14px; text-align:left;">Discount:</th>
                        
                         <td style=" padding:10px; color:#726d6d"> - ' . number_format($coupondiscount, 2) . ' ' . $couponcurrency . '</td>
                      </tr>
                      <tr class="" style="text-align:left;">
                         <th colspan="2" style="padding:10px; font-weight:bold; color:#726d6d; font-size:14px; text-align:left;">Payment method:</th>
                        
                         <td style="padding:10px; color:#726d6d">' . strtoupper($paymentdate->gateway) . '</td>
                      </tr>
                      <tr class="" style="text-align:left;">
                         <th colspan="2" style="padding:10px; font-weight:bold; color:#726d6d; font-size:14px; text-align:left;">Total:</th>
                       
                         <td style="padding:10px; color:#726d6d">' . number_format($total, 2) . ' ' . $ordervalue->currency . '</td>
                      </tr>
                       <tr class="" style="text-align:left;">
                         <th colspan="2" style="padding:10px; font-weight:bold; color:#726d6d; font-size:14px; text-align:left;">Note:</th>
                       
                         <td style="padding:10px; color:#726d6d">' . $invoicedata->ordernotes . '</td>
                      </tr>
                   </tfoot>
                </table>
                   <table style="width:500px; margin:0 auto;  padding:30px; border-collapse:collapse; font-family:Arial, sans-serif; color:#000;">
                      <tr>
                        <td colspan="2" style="padding-bottom: 15px;">
                          <br><h2 style="color:rgb(16, 133, 241); margin:0;">Billing address</h2>
                        </td>
                      </tr>

                      <tr>
                        <td colspan="2" style="border:1px solid #ccc; padding:10px;">
                          
                          <table style="width:100%; border-collapse:collapse;">
                            <tr>
                              <td style="padding:5px 0;">
                                <strong>Invoice ID:</strong>  ' . $invoicedata->id . '
                              </td>
                              <td style="padding:5px 0;">
                               
                              </td>
                            </tr>

                            <tr>
                              <td style="padding:5px 0; vertical-align: top;">
                                <strong>Order Status:</strong> Completed
                              </td>
                              <td style="padding:5px 0;">
                                <span class="completed_order" style="border-radius:5px;">
                                  
                                </span>
                              </td>
                            </tr>

                            <tr>
                              <td colspan="2">
                                <address style="font-style:normal;">
                                  ' . $usercompany . '<br>
                                  ' . $invoicedata->firstname . ' ' . $invoicedata->lastname . '<br>
                                  ' . $invoicedata->phone1 . '<br>
                                  ' . $invoicedata->address . '<br>
                                  ' . $invoicedata->city . '<br>
                                  ' . $invoicedata->state . ' ' . $invoicedata->postcode . '
                                </address>
                              </td>
                            </tr>
                          </table>

                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
        </table>
                ';

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
    $fromUser =  $white_label_admin_name;

    // $fromUser = core_user::get_support_user();
    $subject = $gettemplate->email_subject;
    $emailuser = (object) [];
    $emailuser->email = $USER->email;
    if (!empty($gettemplate->destination_email)) {
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
        $emailstatus = email_learnpeek_to_user($emailuser, $fromUser, $subject, $message = '', $messagehtml, '', '', true, '', '', 79, $companyid);
        // if($emailstatus){
        //   echo "mail send";
        // }else{
        //   echo "mail not send";
        // }

    }
}

function resend_order_complete_email_to_user($oderinvoiceid, $userid, $preview = false)
{
    global $DB, $USER, $CFG;

    // Get user's details
    $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

    // Get user's company.
    $usercompany = $DB->get_record(
        'company_users',
        ['userid' => $userid],
        'companyid'
    );

    $companyid = $usercompany ? $usercompany->companyid : 0;
    
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
    $company_logo = "<img src='" . $logourl . "' width='125' ' style='display:block; width:125px;  max-width:125px; object-fit:contain;' alt='Company Logo'>";
    } else {
        $company_logo = "";
    }

    $order_user_name = fullname($user);

    $invoicedata = $DB->get_record("invoice", array("id" => $oderinvoiceid));
    $paymentdate = $DB->get_record('payments', ['itemid' => $oderinvoiceid]);

    $price = $DB->get_record("payments", array("itemid" => $oderinvoiceid, "userid" => $user->id));

    // Get coupon discount
    $coupon = $DB->get_record_sql(
        "SELECT *, lc.discount_amount as disamount, ci.discount_amount, lc.type FROM {coupon_invoice} ci 
         JOIN {local_coupon} lc ON ci.couponid = lc.id 
         WHERE ci.invoiceid = ?",
        [$invoicedata->id]
    );

    $coupondiscount = $coupon ? $coupon->disamount : 0;
    $couponamount = $coupon ? $coupon->discount_amount : 0;
    $couponcurrency = $coupon ? ($coupon->type == 'fixed' ? 'USD' : '%') : '';

    $orderdata = $DB->get_records_sql("SELECT ii.*, csc.courseid, css.name ,css.program
        FROM {invoiceitem} as ii 
        LEFT JOIN {course_shopsettings} as css ON ii.invoiceableitemid = css.id 
        INNER JOIN {course_shopsettings_courses} as csc ON csc.itemid = ii.invoiceableitemid 
        WHERE ii.invoiceid = ?", array($oderinvoiceid));

    $usertype = $DB->get_record("company_users", array("companyid" => $invoicedata->companyid, "userid" => $invoicedata->userid));
    $usercompany = ($usertype && $usertype->managertype != 0) ? $invoicedata->company : "";

    // START TABLE
    $order_items_table = '
    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="padding:10px; font-family:Helvetica, Arial, sans-serif; font-size:14px;">
      <tr>
        <td align="center" style="padding:10px;">
          
          <!-- Card wrapper -->
          <table width="500" border="0" cellspacing="0" cellpadding="0" align="center" style="border:1px solid #cccccc; background-color:#ffffff;">
            
            <!-- Header -->
            <tr>
              <td bgcolor="#5373ec" style="padding:20px; text-align:left;">
                <p style="margin:0; font-size:25px; font-weight:bold; color:#ffffff; font-family:Helvetica, Arial, sans-serif;">
                  New Order ' . $invoicedata->id . '
                </p>
              </td>
            </tr>
            
            <!-- Body -->
            <tr>
              <td style="padding:30px; background-color:#ffffff; color:#000000; font-size:14px; line-height:20px; font-family:Helvetica, Arial, sans-serif;">
                
                <p style="margin:0 0 20px 0; color:#000000;">
                  Thank you for your order below!
                </p>
                
                <p style="margin:0 0 20px 0; font-size:18px; color:#1085f1;">
                  [<a href="#" style="color:#1085f1; text-decoration:none;">Order ' . $invoicedata->id . '</a>] 
                  <b style="color:#000000;">' . userdate($paymentdate->timecreated, "%B %d %Y") . '</b>
                </p>
                
                <!-- Items table -->
                <table width="100%" border="1" cellspacing="0" cellpadding="8" style="border-collapse:collapse; border:2px solid #eeeeee; color:#5373ec;">
                  <thead>
                    <tr style="text-align:left; font-size:14px; color:#726d6d; background-color:#f9f9f9;">
                      <th style="padding:10px; font-size:14px; font-weight:700;">Product</th>
                      <th style="padding:10px; font-size:14px; font-weight:700;">Quantity</th>
                      <th style="padding:10px; font-size:14px; font-weight:700;">Total</th>
                    </tr>
                  </thead>
                  <tbody>';

        $lquantity = "";
        $subtotal = 0;

        foreach ($orderdata as $ordervalue) {

            $getcourse = $DB->get_record("course", array("id" => $ordervalue->courseid));
            if($ordervalue->program==1 || $ordervalue->invoiceableitemtype=="licenseblock"){
                $courselink = "#";
            }else{
                $courselink = $getcourse ? $CFG->wwwroot . '/course/view.php?id=' . $getcourse->id : "#";

            }
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
                         <th colspan="2" style="padding:10px; font-weight:bold; color:#726d6d; font-size:14px; text-align:left;">Subtotal</th>
                         
                         <td style=" padding:10px; color:#726d6d">' . number_format($subtotal, 2) . ' ' . $ordervalue->currency . '</td>
                      </tr>
                      <tr class="" style="text-align:left;">
                         <th colspan="2" style="padding:10px; font-weight:bold; color:#726d6d; font-size:14px; text-align:left;">Discount:</th>
                        
                         <td style=" padding:10px; color:#726d6d"> - ' . number_format($coupondiscount, 2) . ' ' . $couponcurrency . '</td>
                      </tr>
                      <tr class="" style="text-align:left;">
                         <th colspan="2" style="padding:10px; font-weight:bold; color:#726d6d; font-size:14px; text-align:left;">Payment method:</th>
                        
                         <td style="padding:10px; color:#726d6d">' . strtoupper($paymentdate->gateway) . '</td>
                      </tr>
                      <tr class="" style="text-align:left;">
                         <th colspan="2" style="padding:10px; font-weight:bold; color:#726d6d; font-size:14px; text-align:left;">Total:</th>
                       
                         <td style="padding:10px; color:#726d6d">' . number_format($total, 2) . ' ' . $ordervalue->currency . '</td>
                      </tr>
                       <tr class="" style="text-align:left;">
                         <th colspan="2" style="padding:10px; font-weight:bold; color:#726d6d; font-size:14px; text-align:left;">Note:</th>
                       
                         <td style="padding:10px; color:#726d6d">' . $invoicedata->ordernotes . '</td>
                      </tr>
                   </tfoot>
                </table>
                   <table style="width:500px; margin:0 auto;  padding:30px; border-collapse:collapse; font-family:Arial, sans-serif; color:#000;">
                      <tr>
                        <td colspan="2" style="padding-bottom: 15px;">
                          <br><h2 style="color:rgb(16, 133, 241); margin:0;">Billing address</h2>
                        </td>
                      </tr>

                      <tr>
                        <td colspan="2" style="border:1px solid #ccc; padding:10px;">
                          
                          <table style="width:100%; border-collapse:collapse;">
                            <tr>
                              <td style="padding:5px 0;">
                                <strong>Invoice ID:</strong>  ' . $invoicedata->id . '
                              </td>
                              <td style="padding:5px 0;">
                               
                              </td>
                            </tr>

                            <tr>
                              <td style="padding:5px 0; vertical-align: top;">
                                <strong>Order Status:</strong> Completed
                              </td>
                              <td style="padding:5px 0;">
                                <span class="completed_order" style="border-radius:5px;">
                                  
                                </span>
                              </td>
                            </tr>

                            <tr>
                              <td colspan="2">
                                <address style="font-style:normal;">
                                  ' . $usercompany . '<br>
                                  ' . $invoicedata->firstname . ' ' . $invoicedata->lastname . '<br>
                                  ' . $invoicedata->phone1 . '<br>
                                  ' . $invoicedata->address . '<br>
                                  ' . $invoicedata->city . '<br>
                                  ' . $invoicedata->state . ' ' . $invoicedata->postcode . '
                                </address>
                              </td>
                            </tr>
                          </table>

                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
        </table>
                ';

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
    $fromUser =  $white_label_admin_name;

    // $fromUser = core_user::get_support_user();
    $subject = $gettemplate->email_subject;
    $emailuser = (object) [];
    // $emailuser->email = $user ? $user->email : '';
    // if (!empty($gettemplate->destination_email)) {
    //     $emailuser->email = $gettemplate->destination_email;
    // } else {
    //     $emailuser->email = $user->email;
    // }
    $emailuser->email = !empty($gettemplate->destination_email)
    ? $gettemplate->destination_email
    : $user->email;
    // $emailuser->email = "suneet.sharma695@gmail.com";
    $emailuser->firstname = $user->firstname;
    $emailuser->lastname = $user->lastname;
    $emailuser->maildisplay = true;
    $emailuser->mailformat = 1; // 0 (zero) text-only emails, 1 (one) for HTML/Text emails.
    $emailuser->id = $user->id;
    $emailuser->firstnamephonetic = false;
    $emailuser->lastnamephonetic = false;
    $emailuser->middlename = false;
    $emailuser->username = false;
    $emailuser->alternatename = false;

    // --- Preview mode ---
    if ($preview) {
        echo "<h2>Email Preview</h2>";
        echo "<strong>Subject:</strong> " . format_string($subject) . "<br><br>";
        echo $messagehtml;
        exit;
    }
    if ($emailtemplatedata_user) {
        // $emailstatus = email_to_user($emailuser, $fromUser, $subject, $message = '', $messagehtml, '', '', true, '', '', 79, $companyid);
        $emailstatus = email_learnpeek_to_user($emailuser, $fromUser, $subject, $message = '', $messagehtml, '', '', true, '', '', 79, $companyid);
        if($emailstatus){
            redirect(new moodle_url($CFG->wwwroot.'/blocks/learnpeek_shop/orderlist.php'),'Mail sent successfull', null, \core\output\notification::NOTIFY_SUCCESS);
            die;
        }

    }
}

function order_complete_email_to_superadmin($companyid, $oderuserid, $oderinvoiceid)
{

    global $DB, $USER, $CFG;
    $company = $DB->get_record('company', ['id' => $companyid]);
    $orderuser = $DB->get_record("user", array("id" => $oderuserid));

    // SELECT * FROM `mdl_user` WHERE `id` IN (2,43,552)
    $gettemplate = $DB->get_record_sql("SELECT * FROM {custom_email_template} WHERE email_template = ?", array('order-email-admin'));
    if ($gettemplate) {
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
                $company_logo = "<img src='" . $logourl . "' width='125' ' style='display:block; width:125px;  max-width:125px; object-fit:contain;' alt='Company Logo'>";
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
            $coupondiscount = $coupon ? $coupon->disamount : 0;
            $couponamount = $coupon ? $coupon->discount_amount : 0;
            $couponcurrency = $coupon ? ($coupon->type == 'fixed' ? 'USD' : '%') : '';
            $couponname = $coupon ? ($coupon->code ? $coupon->code : "") : "";


            $orderdata = $DB->get_records_sql("SELECT ii.*, csc.courseid,css.name ,css.program
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

            $order_items_table = '
            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="padding:10px; font-family:Helvetica, Arial, sans-serif; font-size:14px;">
              <tr>
                <td align="center" style="padding:10px;">
                  
                  <!-- Card wrapper -->
                  <table width="600" border="0" cellspacing="0" cellpadding="0" align="center" style="border:1px solid #cccccc; background-color:#ffffff;">
                    
                    <!-- Header -->
                    <tr>
                      <td bgcolor="#5373ec" style="padding:20px; text-align:left;">
                        <p style="margin:0; font-size:25px; font-weight:bold; color:#ffffff; font-family:Helvetica, Arial, sans-serif;">
                         New Order ' . $invoicedata->id . '
                        </p>
                      </td>
                    </tr>
                    
                    <!-- Body -->
                    <tr>
                      <td style="padding:30px; background-color:#ffffff; color:#000000; font-size:14px; line-height:20px; font-family:Helvetica, Arial, sans-serif;">
                        
                        <p style="margin:0 0 20px 0; color:#000000;">
                          You received the following order from ' . $order_user_name . '
                        </p>
                        
                        <p style="margin:0 0 20px 0; font-size:18px; color:#1085f1;">
                          [<a href="#" style="color:#1085f1; text-decoration:none;">Order ' . $invoicedata->id . '</a>] 
                          <b style="color:#000000;">' . userdate($paymentdate->timecreated, "%B %d %Y") . '</b>
                        </p>
                        
                        <!-- Items table -->
                        <table width="100%" border="1" cellspacing="0" cellpadding="10" style="border-collapse:collapse; border:2px solid #eeeeee; color:#5373ec;">
                          <thead>
                            <tr style="text-align:left; font-size:14px; color:#726d6d; background-color:#f9f9f9;">
                              <th style="padding:10px; font-size:14px; font-weight:700;">Product</th>
                              <th style="padding:10px; font-size:14px; font-weight:700;">Quantity</th>
                              <th style="padding:10px; font-size:14px; font-weight:700; width:60%;">Total</th>
                            </tr>
                          </thead>
                          <tbody>';




                        // UPDATED: Subtotal calculation before total
                        $subtotal = 0;
                        foreach ($orderdata as $ordervalue) {
                            $getcourse = $DB->get_record("course", array("id" => $ordervalue->courseid));
                            if($ordervalue->program==1 || $ordervalue->invoiceableitemtype=="licenseblock"){
                                $courselink = "#";
                            }else{
                                $courselink = $getcourse ? $CFG->wwwroot . '/course/view.php?id=' . $getcourse->id : "#";

                            }
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
                              <tr class="">
                               <th colspan="2" style="text-align:left; padding:10px; font-weight:bold; color:#726d6d; font-size:14px">
                               Payment method
                               </th>
                               <td style="padding:10px; color:#726d6d">' . strtoupper($paymentdate->gateway) . '</td>
                            </tr>
                              <tr  class="">
                                 <td colspan="2" style="padding:10px; font-weight:bold; color:#726d6d; font-size:14px">Total</td>
                                 <td style="padding:10px; color:#726d6d">' . number_format($total, 2) . ' ' . $ordervalue->currency . '</td>
                              </tr>
                              <tr class="" style="">
                               <th colspan="2" style="text-align:left; padding:10px; font-weight:bold; color:#726d6d; font-size:14px">Note:</th>
                             
                               <td style="padding:10px; color:#726d6d">' . $invoicedata->ordernotes . '</td>
                            </tr>
                           </tfoot>
                        </table>
                       <div>
                            <h2 style="color:rgb(16, 133, 241)">Billing address</h2>
                            <div class="invoice_parent" style="border:1px solid #ccc; padding:10px; color:#000">
                                <p style="margin:0;">
                                <strong>Invoice ID : </strong>
                                <span>' . $invoicedata->id . '</span>
                                </p>
                                <div class="" style="display:flex; align-items:center">
                                    <strong>Order Status: </strong>
                                    <span class="completed_order" style="padding: 6px;  border-radius: 5px;">';
                                    $order_items_table .=  'Completed';
                                    $order_items_table .= '
                                    </span>
                                </div>
                                <div class="col-md-12 bg-light shadow-md p-4 my-4">
                                    <address>
                                      ' . $usercompany . '<br>
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
                   </td>
                </tr>
              </table>
            </td>
          </tr>
        </table>';

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

             // echo $messagehtml;
            $getcompany_wu = $DB->get_record_sql("SELECT * FROM {company_users} WHERE companyid = ? AND managertype =?", array($companyid, 1));
            $whitelableuser = $DB->get_record("user", array("id" => $getcompany_wu->userid));
            $white_label_admin_name = fullname($whitelableuser);
            $fromUser =  $white_label_admin_name;
            // $fromUser = core_user::get_support_user();


            $subj_placeholders = ['{{compnay_name}}'];
            $subj_replacements = [$company->name];
            $email_subject = str_replace($subj_placeholders, $subj_replacements, $gettemplate->email_subject);


            $fromUser =  $white_label_admin_name;
            $subject = $email_subject;
            $emailuser = (object) [];
            // $emailuser->email = $whitelableuser->email;
            if (!empty($gettemplate->destination_email)) {
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
            if (!empty($gettemplate->destination_email)) {
                break;
            }
        }
    } else {
        return;
    }
}

function trainee_registration_email_to_user($companyid, $userid, $password, $preview = false) {
    global $DB, $CFG;

    //false
    // --- Load template from DB (company specific, fallback to global company_id=0) ---
    $gettemplate = $DB->get_record_sql(
        "SELECT * FROM {custom_email_template} WHERE email_template = ? AND company_id = ?",
        ['user-account-creation',$companyid]
    );
    if (!$gettemplate) {
        $gettemplate = $DB->get_record_sql(
            "SELECT * FROM {custom_email_template} WHERE company_id = ? AND email_template = ?",
            [0, 'user-account-creation']
        );
    }
    if (!$gettemplate) {
        return false; // no template found
    }

    $emailtemplatedata_user = $gettemplate->email_message ?: '';
    $rawsubject             = $gettemplate->email_subject ?: "Your {{company_name}} account has been created";

    // --- Company logo ---
    $comlogodetails = $DB->get_record_sql('SELECT * FROM {company_logo} WHERE company_id = ?', array($companyid));
    if ($comlogodetails) {
      $companylogo = $DB->get_record_sql("SELECT * FROM {files} WHERE contextid= 1 AND component='landingpage' AND itemid=? AND filesize > 0 AND filearea = ?", array($comlogodetails->id, 'logo_image'));
      $logourl = $CFG->wwwroot . '/blocks/custom_mycourse/file.php?id=' . $companylogo->pathnamehash;
      $company_logo = "<img src='" . $logourl . "' style='width: 125px;max-height: 150px;object-fit: cover;'>";
    } else {
      $company_logo = "";
    }
    
    // $company_logo = '';
    // if ($comlogodetails = $DB->get_record('company_logo', ['company_id' => $companyid])) {
    //     $fs = get_file_storage();
    //     $context = context_system::instance(); // use system context
    //     $files = $fs->get_area_files($context->id, 'landingpage', 'logo_image', $comlogodetails->id, 'sortorder', false);
    //     if ($files) {
    //         $file = reset($files);
    //         $logourl = moodle_url::make_pluginfile_url(
    //             $file->get_contextid(),
    //             $file->get_component(),
    //             $file->get_filearea(),
    //             $file->get_itemid(),
    //             $file->get_filepath(),
    //             $file->get_filename()
    //         )->out(false);
    //         $company_logo = "<img src='{$logourl}' style='width:125px;max-height:150px;object-fit:cover;'>";
    //     }
    // }
    // --- Placeholders ---
    $company = $DB->get_record('company', ['id' => $companyid], '*', MUST_EXIST);
    $user    = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
    
    $login_url = $CFG->wwwroot . '/login/index.php';
    $login_url = '<a href="' . $login_url . '" target="_blank">'.$login_url.'</a>';

    $placeholders = ['{{fullname}}','{{username}}', '{{company_name}}', '{{password}}', '{{login_url}}', '{{company_logo}}'];
    $replacements = [
        fullname($user),
        $user->username,
        $company->name,
        $password,
        $login_url,
        $company_logo
    ];

    $email_body = str_replace($placeholders, $replacements, $emailtemplatedata_user);
    $subject    = str_replace($placeholders, $replacements, $rawsubject);

    // --- Build messages ---
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

    // --- From user (whitelabel manager, fallback support) ---
    $fromUser = core_user::get_support_user();
    if ($getcompany_wu = $DB->get_record_sql(
        "SELECT * FROM {company_users} WHERE companyid = ? AND managertype = ?", 
        [$companyid, 1]
    )) {
        if ($whitelabeluser = $DB->get_record('user', ['id' => $getcompany_wu->userid])) {
            $fromUser = fullname($whitelabeluser);
        }
    }

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

    // --- Send ---
    return email_learnpeek_to_user(
        $emailuser,
        $fromUser,
        $subject,
        $messagetext,
        $messagehtml,
        '', '', true, '', '', 79, $companyid
    );
}
 
// uday - trainee creation email to white label
function trainee_registration_email_to_whitelable($companyid, $userid, $password= '', $preview = false) {
    global $DB, $CFG, $USER;
    $gettemplate = $DB->get_record_sql(
        "SELECT * FROM {custom_email_template} WHERE email_template = ? AND company_id = ?",
        ['user-account-creation-whitelabeladmin',$companyid]
    );
    if (!$gettemplate) {
        $gettemplate = $DB->get_record_sql(
            "SELECT * FROM {custom_email_template} WHERE company_id = ? AND email_template = ?",
            [0, 'user-account-creation-whitelabeladmin']
        );
    }
    if (!$gettemplate) {
        return false; // no template found
    }

    $emailtemplatedata_user = $gettemplate->email_message ?: '';
    $rawsubject             = $gettemplate->email_subject ?: "New account has been created";

    $comlogodetails = $DB->get_record_sql('SELECT * FROM {company_logo} WHERE company_id = ?', array($companyid));
    if ($comlogodetails) {
        $companylogo = $DB->get_record_sql("SELECT * FROM {files} WHERE contextid= 1 AND component='landingpage' AND itemid=? AND filesize > 0 AND filearea = ?", array($comlogodetails->id, 'logo_image'));
        $logourl = $CFG->wwwroot . '/blocks/custom_mycourse/file.php?id=' . $companylogo->pathnamehash;
    $company_logo = "<img src='" . $logourl . "' width='125' ' style='display:block; width:125px;  max-width:125px; object-fit:contain;' alt='Company Logo'>";
    } else {
        $company_logo = "";
    }

    $company = $DB->get_record('company', ['id' => $companyid], '*', MUST_EXIST);

    $user    = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
    $new_trainee_register_details = '<br><br><div><strong>New User Registered:</strong></div>
        
        Name: ' . fullname($user) . '<br>
        Email: ' . $user->email . '
    ';


    if($USER->id){
        $context = context_user::instance($USER->id); // User context
        $current_user_roles = get_user_roles($context, $USER->id);
        $currentuser_roles = reset($current_user_roles);
        $manag_role = array_column($current_user_roles, 'shortname');

        $user_role = $manag_role[0] == 'companydepartmentmanager' ? 'Business User' : ($manag_role[0] == 'companyreporter' ? 'Department Manager' : 'Admin');
        $nocompany = false;
        if($manag_role[0] == 'companyreporter'){
            $sql = "SELECT ca.department_id, ca.parent_id
            FROM {customdepartment_assign} ca
            WHERE ca.child_id = :userid
            AND ca.is_departmentmanager = 1
            ORDER BY ca.department_id ASC";

            $department = $DB->get_record_sql($sql, ['userid' => $USER->id]);

            if ($department) {

                $assigneduser = $DB->get_record('user', ['id' => $department->parent_id], '*', MUST_EXIST);
                $fielddata = $DB->get_record('user_info_field',array('shortname'=>'user_company_name'));
                $userfielddata = $DB->get_record('user_info_data', array('userid'=>$department->parent_id,'fieldid'=>$fielddata->id));
                $business_user_details = '<br><br><div><strong>Business User Details:</strong></div>

                    Name: ' . fullname($assigneduser) . '<br>
                    Email: ' . $assigneduser->email . '<br>
                    Company Name: ' . ($userfielddata->data ?? '') . '
                ';
                $nocompany = true;

            }
        }

        $assigneduser    = $DB->get_record('user', ['id' => $USER->id], '*', MUST_EXIST);
        $assigned_by_user_details = '<br><br><div><strong>' . $user_role . ' Details:</strong></div>
            
            Name: ' . fullname($assigneduser) . '<br>
            Email: ' . $assigneduser->email . '<br>
        ';
        if(!$nocompany){
            if($manag_role[0] == 'companymanager'){
                $assigned_by_user_details .= 'Company Name: ' . ($company->name ?? '');
            }else{
                $fielddata = $DB->get_record('user_info_field',array('shortname'=>'user_company_name'));
                $userfielddata = $DB->get_record('user_info_data', array('userid'=>$USER->id,'fieldid'=>$fielddata->id));
                if($userfielddata){
                    $assigned_by_user_details .= 'Company Name: ' . ($userfielddata->data ?? '');
                }
            }
        }
    }

    $fromUser = core_user::get_support_user();
    $whitelabeluser = null;
    if ($getcompany_wu = $DB->get_record_sql(
        "SELECT * FROM {company_users} WHERE companyid = ? AND managertype = ?", 
        [$companyid, 1]
    )) {
        if ($whitelabeluser = $DB->get_record('user', ['id' => $getcompany_wu->userid])) {
            $fromUser = fullname($whitelabeluser);
        }
    }
    $placeholders = ['{{business_user_details}}','{{assigned_by_user_details}}', '{{new_trainee_register_details}}', '{{company_logo}}'];
    $replacements = [
        $business_user_details,
        $assigned_by_user_details,
        $new_trainee_register_details,
        $company_logo
    ];

    $email_body = str_replace($placeholders, $replacements, $emailtemplatedata_user);
    $subject    = str_replace($placeholders, $replacements, $rawsubject);

    $messagehtml = "<!DOCTYPE html>
        <html>
            <head><meta charset='utf-8'><title>{$subject}</title></head>
            <body>$email_body</body>
        </html>";
    $messagetext = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $email_body));

    if ($preview) {
        echo "<h2>Email Preview</h2>";
        echo "<strong>Subject:</strong> " . format_string($subject) . "<br><br>";
        echo $messagehtml;
        die;
    }

    $emailuser = (object)[
        'email' => $whitelabeluser->email,
        'firstname' => $whitelabeluser->firstname,
        'lastname' => $whitelabeluser->lastname,
        'maildisplay' => true,
        'mailformat' => 1,
        'id' => $whitelabeluser->id

    ];
    if(!empty($gettemplate->destination_email)){
        $emailuser->email = $gettemplate->destination_email;
    }
    return email_learnpeek_to_user(
        $emailuser,
        $fromUser,
        $subject,
        $messagetext = '',
        $messagehtml,
        '', '', true, '', '', 79, $companyid
    );
}

// uday - bulk trainee creation email to white label
function bulk_trainee_registration_email_to_whitelable($companyid, $usersdata, $preview = false) {
    global $DB, $CFG, $USER;

    // ================= TEMPLATE =================
    $gettemplate = $DB->get_record_sql(
        "SELECT * FROM {custom_email_template} WHERE email_template = ? AND company_id = ?",
        ['bulk-user-account-creation-whitelabeladmin', $companyid]
    );

    if (!$gettemplate) {
        $gettemplate = $DB->get_record_sql(
            "SELECT * FROM {custom_email_template} WHERE company_id = ? AND email_template = ?",
            [0, 'bulk-user-account-creation-whitelabeladmin']
        );
    }

    if (!$gettemplate) {
        return false;
    }

    $emailtemplatedata_user = $gettemplate->email_message ?: '';
    $rawsubject             = $gettemplate->email_subject ?: "New account has been created";

    // ================= COMPANY LOGO =================
    $company_logo = "";
    if ($comlogodetails = $DB->get_record('company_logo', ['company_id' => $companyid])) {
        if ($companylogo = $DB->get_record_sql(
            "SELECT * FROM {files} WHERE contextid=1 AND component='landingpage' AND itemid=? AND filesize > 0 AND filearea = ?",
            [$comlogodetails->id, 'logo_image']
        )) {
            $logourl = $CFG->wwwroot . '/blocks/custom_mycourse/file.php?id=' . $companylogo->pathnamehash;
            $company_logo = "<img src='{$logourl}' width='125' style='display:block; max-width:125px; object-fit:contain;' alt='Company Logo'>";
        }
    }

    $company = $DB->get_record('company', ['id' => $companyid], '*', MUST_EXIST);

    // ================= CURRENT USER (ASSIGNED BY) =================
    // $context = context_user::instance($USER->id); // User context
    // $current_user_roles = get_user_roles($context, $USER->id);
    // $currentuser_roles = reset($current_user_roles);
    // $manag_role = array_column($current_user_roles, 'shortname');

    // $user_role = $manag_role[0] == 'companydepartmentmanager' ? 'Business User' : ($manag_role[0] == 'companyreporter' ? 'Department Manager' : 'New User');


    // $context = context_user::instance($USER->id);
    // $roles = get_user_roles($context, $USER->id);
    // $roleShortnames = array_column($roles, 'shortname');
    // $user_role = $roleShortnames[0] == 'companymanager' ? 'White Label Admin' :
    //                 ($roleShortnames[0] == 'companyreporter' ? 'Department Manager' : 'New User');

    ///=============show company name ====================//
    $context1 = context_user::instance($USER->id); // User context
    $current_user_roles1 = get_user_roles($context1, $USER->id);
    $manag_role1 = array_column($current_user_roles1, 'shortname');
    $user_role = $manag_role1[0] == 'companydepartmentmanager' ? 'Business User' : ($manag_role1[0] == 'companyreporter' ? 'Department Manager' : 'Admin');

    $field = $DB->get_record('user_info_field', ['shortname' => 'user_company_name']);

    $user_company_name = '';

    if ($field) {
        if($manag_role1[0] == 'companyreporter'){
            $buss_user = $DB->get_record('customdepartment_assign', ['child_id' => $USER->id], '*', MUST_EXIST);
            $bsuerid = $buss_user->parent_id;
            $data = $DB->get_record('user_info_data', [
                'userid' => $bsuerid,
                'fieldid' => $field->id
            ]);
            if ($data) {
                $user_company_name = $data->data;
            }

        }else if($manag_role1[0] == 'companydepartmentmanager'){
            $bsuerid = $USER->id;
            $data = $DB->get_record('user_info_data', [
                'userid' => $bsuerid,
                'fieldid' => $field->id
            ]);
            if ($data) {
                $user_company_name = $data->data;
            }
        }else{
            $user_company_name = $company->name;
        }
    }
    ///=============show company name ====================//

    $assigned_by_user_details = '
        <div><strong>Uploaded by: ' .$user_role. '</strong></div>
        Name: ' . fullname($USER) . '<br>
        Email: ' . $USER->email . '<br>
        Company Name: ' . ($user_company_name ?? '') . '
    ';

    // ================= BUSINESS USER (ASSIGNED TO) =================
    $assigned_to_user_details = '';
    $user_name = '';
    $fullname = '';

    // detect bussinessuser_email from first record
    $businessEmail = $usersdata[0]->bussinessuser_email ?? '';

    if (!empty($businessEmail)) {
        if ($assignedUser = $DB->get_record('user', ['email' => trim($businessEmail)])) {

        $context = context_user::instance($assignedUser->id);
        $roles = get_user_roles($context, $assignedUser->id);
        $roleShortnames = array_column($roles, 'shortname');
        $role = $roleShortnames[0] == 'companydepartmentmanager' ? 'Business User' :
                    ($roleShortnames[0] == 'companyreporter' ? 'Department Manager' : 'Admin');


            $fullname = fullname($assignedUser);
            $user_name = "for {$fullname}";
            $assigned_to_user_details = '
                <div><strong>Assigned To:</strong></div>
                Name: ' . fullname($assignedUser) . '<br>
                Email: ' . $assignedUser->email . '<br>
                Role: ' . $role . '<br>
            ';
        }
    }

    // ================= TRAINEE TABLE =================
    $new_trainee_register_details = '
        <div><strong>New Trainees Details:</strong></div>
        <table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse; width:100%;">
            <thead>
                <tr>
                    <th>S.No</th>
                    <th>Name</th>
                    <th>Email</th>
                </tr>
            </thead>
            <tbody>
    ';

    if (!empty($usersdata)) {
        $i = 1;
        foreach ($usersdata as $user) {
            $new_trainee_register_details .= '
                <tr>
                    <td>' . $i++ . '</td>
                    <td>' . fullname($user) . '</td>
                    <td>' . $user->email . '</td>
                </tr>
            ';
        }
    }

    $new_trainee_register_details .= '</tbody></table>';

    // ================= REMOVE OPTIONAL BLOCKS =================
    if (empty($assigned_to_user_details)) {
        $emailtemplatedata_user = str_replace('{{assigned_to_user_details}}', '', $emailtemplatedata_user);
        $emailtemplatedata_user = str_replace('{{user_name}}', '', $emailtemplatedata_user);
    }

    // ================= PLACEHOLDERS =================
    $placeholders = [
        '{{uploadedby_name}}',
        '{{assignedto_name}}',
        '{{uploaded_by_user_details}}',
        '{{assigned_to_user_details}}',
        '{{new_trainee_register_details}}',
        '{{company_logo}}'
    ];
    // echo "current user name -".fullname($USER);
    // echo "user full name -".$fullname;

    $replacements = [
        fullname($USER),
        $fullname ? "for {$fullname}" : '',
        $assigned_by_user_details,
        $assigned_to_user_details,
        $new_trainee_register_details,
        $company_logo
    ];
    // echo "<pre>";
    // print_r($replacements);
    // echo "</pre>";
    $email_body = str_replace($placeholders, $replacements, $emailtemplatedata_user);
    $subject    = str_replace($placeholders, $replacements, $rawsubject);
    
    // ================= EMAIL FORMAT =================
    $messagehtml = "<html><body>{$email_body}</body></html>";
    $messagetext = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $email_body));

    if ($preview) {
        echo "<h3>Email Preview</h3>";
        echo "<strong>Subject:</strong> {$subject}<br><br>";
        echo $messagehtml;
        die;
    }

    // ================= RECEIVER =================
    // $fromUser = core_user::get_support_user();
    $whitelabeluser = null;

    // if ($getcompany_wu = $DB->get_record_sql(
    //     "SELECT * FROM {company_users} WHERE companyid = ? AND managertype = ?",
    //     [$companyid, 1]
    // )) {
    //     $whitelabeluser = $DB->get_record('user', ['id' => $getcompany_wu->userid]);
    // }

    // if (!$whitelabeluser) {
    //     return false;
    // }

    $fromUser = core_user::get_support_user();
    if ($getcompany_wu = $DB->get_record_sql(
        "SELECT * FROM {company_users} WHERE companyid = ? AND managertype = ?", 
        [$companyid, 1]
    )) {
        if ($whitelabeluser = $DB->get_record('user', ['id' => $getcompany_wu->userid])) {
            $fromUser = fullname($whitelabeluser);
        }
    }

    $emailuser = (object)[
        'id' => $whitelabeluser->id,
        'email' => !empty($gettemplate->destination_email) ? $gettemplate->destination_email : $whitelabeluser->email,
        'firstname' => $whitelabeluser->firstname,
        'lastname' => $whitelabeluser->lastname,
        'maildisplay' => true,
        'mailformat' => 1
    ];

    return email_learnpeek_to_user(
        $emailuser,
        $fromUser,
        $subject,
        $messagetext,
        $messagehtml,
        '', '', true, '', '', 79, $companyid
    );
}

// uday
function course_assignment_email_to_whitelable($userid, $courseid, $licenseid, $preview = false) {
    global $DB, $CFG, $USER;
    // --- Get company ID of the current logged-in user ---
    $companyid = $DB->get_field('company_users', 'companyid', ['userid' => $USER->id], MUST_EXIST);

    // --- Load template from DB ---
    $gettemplate = $DB->get_record_sql(
        "SELECT * FROM {custom_email_template} WHERE email_template = ? AND company_id = ?",
        ['course-enrol-whitelabeladmin',$companyid]
    );
    if (!$gettemplate) {
        $gettemplate = $DB->get_record_sql(
            "SELECT * FROM {custom_email_template} WHERE company_id = ? AND email_template = ?",
            [0, 'course-enrol-whitelabeladmin']
        );
    }
    if (!$gettemplate) {
        return false; // no template found
    }

    $comlogodetails = $DB->get_record_sql('SELECT * FROM {company_logo} WHERE company_id = ?', array($companyid));
    if ($comlogodetails) {
        $companylogo = $DB->get_record_sql("SELECT * FROM {files} WHERE contextid= 1 AND component='landingpage' AND itemid=? AND filesize > 0 AND filearea = ?", array($comlogodetails->id, 'logo_image'));
        $logourl = $CFG->wwwroot . '/blocks/custom_mycourse/file.php?id=' . $companylogo->pathnamehash;
    $company_logo = "<img src='" . $logourl . "' width='125' ' style='display:block; width:125px;  max-width:125px; object-fit:contain;' alt='Company Logo'>";
    } else {
        $company_logo = "";
    }

    $email_template = $gettemplate->email_message ?: '';
    $subject_template = $gettemplate->email_subject ?: "You have been assigned to the course {{course_name}}.";

    // --- Fetch user and course ---
    $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
    $assigneduser    = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

    $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    $company = $DB->get_record('company', ['id' => $companyid], '*', MUST_EXIST);

    $context = context_user::instance($USER->id); // User context
    $current_user_roles = get_user_roles($context, $USER->id);
    $currentuser_roles = reset($current_user_roles);
    $manag_role = array_column($current_user_roles, 'shortname');
    $user_role = $manag_role[0] == 'companydepartmentmanager' ? 'Business User' : ($manag_role[0] == 'companyreporter' ? 'Department Manager' : 'Admin');

    $field = $DB->get_record('user_info_field', ['shortname' => 'user_company_name']);

    $user_company_name = '';

    if ($field) {
        if($manag_role[0] == 'companyreporter'){
            $buss_user = $DB->get_record('customdepartment_assign', ['child_id' => $USER->id], '*', MUST_EXIST);
            $bsuerid = $buss_user->parent_id;


        }else{
            $bsuerid = $USER->id;
        }
        
        $data = $DB->get_record('user_info_data', [
            'userid' => $bsuerid,
            'fieldid' => $field->id
        ]);
        
        if ($data) {
            $user_company_name = $data->data;
        }
    }
    if($manag_role[0] == 'companymanager'){
        $user_company_name = $company->name;
    }

    // --- Build Assign Table (all courses under same license) ---
    $license_courses = $DB->get_records('companylicense_courses', ['licenseid' => $licenseid]);
    $table_html = '<table border="1" cellpadding="5" cellspacing="0"><tr><th>Course Name</th></tr>';
    $coursenames = array();
    foreach ($license_courses as $lc) {
        $c = $DB->get_record('course', ['id' => $lc->courseid]);
        if ($c) {
            $coursenames[] = format_string($c->fullname);
            $table_html .= '<tr><td>' . format_string($c->fullname) . '</td></tr>';
        }
    }
    $table_html .= '</table>';

        $course_assign_to_trainee = '
        <div><strong>Course assigned to trainee:</strong></div>
        
        Course: ' . implode(", " , $coursenames) . '<br>
        Name: ' . fullname($user) . '<br>
        Email: ' . $user->email . '<br>
    ';

    $assigned_by_user_details = '
        <div><strong>' . $user_role . ' Details:</strong></div>
        
        Name: ' . fullname($USER) . '<br>
        Email: ' . $USER->email . '<br>
        Company Name: ' . ($user_company_name ?? '') . '
    ';

    $fromUser = core_user::get_support_user();
    $whitelabeluser = null;
    if ($getcompany_wu = $DB->get_record_sql(
        "SELECT * FROM {company_users} WHERE companyid = ? AND managertype = ?", 
        [$companyid, 1]
    )) {
        if ($whitelabeluser = $DB->get_record('user', ['id' => $getcompany_wu->userid])) {
        // if($USER->id == 1805){
        //     echo "<pre>users-";
        //     print_r($getcompany_wu);
        //     print_r($whitelabeluser);
        //     echo $whitelabeluser->email;
        //     echo "</pre>users-";
        //     die;
        // }    
            $fromUser = fullname($whitelabeluser);
        }
    }
    
    // --- Placeholders and replacements ---
    $placeholders = ['{{user_role}}', '{{assigned_by_user_details}}', '{{course_assign_to_trainee}}', '{{company_logo}}'];
    $replacements = [
        $user_role,
        $assigned_by_user_details,
        $course_assign_to_trainee,
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
    if ($preview &&  $USER->id == 1805 ) {
        echo "<h2>Email Preview</h2>";
        echo "<strong>Subject:</strong> " . format_string($subject) . "<br><br>";
        echo $messagehtml;
        exit;
    }

    // echo $messagehtml;

    // die();
    // --- From user ---
    // $companyname = $DB->get_field('company', 'name', ['id' => $companyid]);
    // $fromUser = fullname($USER);

    // --- Recipient ---
    $emailuser = (object)[
        'email' => $whitelabeluser->email,
        'firstname' => $whitelabeluser->firstname,
        'lastname' => $whitelabeluser->lastname,
        'maildisplay' => true,
        'mailformat' => 1,
        'id' => $whitelabeluser->id
    ];

    if(!empty($gettemplate->destination_email)){
        $emailuser->email = $gettemplate->destination_email;
    }
    // echo "<pre>";
    // print_r($emailuser);
    // echo "</pre>";
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
