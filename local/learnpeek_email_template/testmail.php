<?php
include_once("../../config.php");

global $DB, $USER,$CFG;
  // $courseid = 370;
  // $userid = 2362;
  // $userid = 1757;
  // $courseid = 266;
  // $userid = 1101;
  // $courseid = 339;
  $courseid = 274;
  $userid = 1644;
  $userdata = $DB->get_record("user",array("id"=>$userid));

  $companyinfo = $DB->get_record('company_users', ['userid' => $userid]);
  $companyid = $companyinfo->companyid;
  // $companyid = iomad::get_my_companyid(context_system::instance());
    $gettemplate = $DB->get_record_sql("SELECT * FROM {custom_email_template} WHERE company_id = ? AND email_template = ?",array($companyid,'course-completion'));
    if($gettemplate){
      $emailtemplatedata_user = $gettemplate->email_message;
    }else{
      $get_deflt_template = $DB->get_record_sql("SELECT * FROM {custom_email_template} WHERE company_id = ? AND email_template = ?",array(0,'course-completion'));
      if($get_deflt_template){
        $emailtemplatedata_user = $get_deflt_template->email_message;
      }else{
        $emailtemplatedata_user = 0;
      }
    }
      //get compnay logo
    $comlogodetails = $DB->get_record_sql('SELECT * FROM {company_logo} WHERE company_id = ?',array($companyid));
    if($comlogodetails){
      $companylogo = $DB->get_record_sql("SELECT * FROM {files} WHERE contextid= 1 AND component='landingpage' AND itemid=? AND filesize > 0 AND filearea = ?", array($comlogodetails->id,'logo_image'));
      $logourl = $CFG->wwwroot.'/blocks/custom_mycourse/file.php?id='.$companylogo->pathnamehash;
      // $company_logo = "<img src='" . $logourl . "' style='width:125px;max-height:150px;object-fit:cover;'>";
      $company_logo = "<img src='{$logourl}' width='125' height='auto' style='display:block;border:0;'>";
    }else{
      $company_logo = "";
    }
    $coursedata = $DB->get_record("course",array("id"=>$courseid));
    // print_r($coursedata);

   $user_display_name = fullname($userdata);
   $user_email = $userdata->email;
   $course_name = $coursedata->fullname;
   // $checkedcoursescorm = $DB->get_record("scorm",array("course"=>$courseid));
   // if($checkedcoursescorm){
   //    $scorm_track = $DB->get_record_sql("SELECT * FROM {scorm_scoes_track} WHERE userid = ? AND scormid =? AND element LIKE ?",array($userdata->id,$checkedcoursescorm->id,'cmi.core.score.raw'));
   //    if($scorm_track->value >= 70 ){
   //      $course_grade = $scorm_track->value .' (PASSED)';
   //    }else{
   //      $course_grade = $scorm_track->value .' (FAILED)';
   //    }
   // }else{
   //    $course_grade = "N/A";
   // }

  $course_grade = "N/A";
  $coursegradeitem = $DB->get_record('grade_items', [
      'courseid' => $courseid,
      'itemtype' => 'course'
  ]);

  if($coursegradeitem){

      $usergrade = $DB->get_record('grade_grades', [
          'userid' => $userdata->id,
          'itemid' => $coursegradeitem->id
      ]);     

      if($usergrade){
          $gradep = 0;
          if($usergrade->finalgrade){
              $gradep = $usergrade->finalgrade;
          }

          if($usergrade->rawgrademax){
            $gradem = $usergrade->rawgrademax;
            $gradep = ($usergrade->finalgrade / $gradem) * 100;
          }



          if($gradep >= 70 ){
            $finalgrade = (float)$usergrade->finalgrade;
            // $course_grade = round($finalgrade, 2) . " (PASSED)";
            $course_grade = round($gradep, 2) . "% (PASSED)";

          }else{
            $finalgrade = (float)$usergrade->finalgrade;
             $course_grade = round($gradep, 2) . "% (FAILED)";
          }        
      } else {
            $finalgrade = (float)$usergrade->finalgrade;
            $course_grade = round($gradep, 2) . "%";

      }
  }


  $placeholders = ['{{user_display_name}}','{{course_name}}','{{company_logo}}','{{course_grade}}','{{user_email}}'];
  $replacements = [$user_display_name, $course_name, $company_logo, $course_grade , $user_email];
  $email_body = str_replace($placeholders, $replacements, $emailtemplatedata_user);

  $messagehtml =   "<!DOCTYPE html>
                              <html>
                                  <head>
                                      <title>Mail</title>
                                      <link rel='stylesheet' type='text/css' href='https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css'>
                                  </head>
                                  <body>".$email_body."
                                  </body>
                              </html>";  
  // $fromUser = core_user::get_support_user();

  $getcompany_wu = $DB->get_record_sql(
        "SELECT * FROM {company_users} WHERE companyid = ? AND managertype = ?",
        [$companyid, 1]
    );
    if ($getcompany_wu) {
        $whitelabeluser = $DB->get_record('user', ['id' => $getcompany_wu->userid]);
        $fromUser = $whitelabeluser ? fullname($whitelabeluser) : core_user::get_support_user();
    } else {
        $fromUser = core_user::get_support_user();
    }
    // echo $fromUser;
    echo $messagehtml;
    die();

    $subject = $gettemplate->email_subject;
    $emailuser = (object) [];
    // $emailuser->email = $userdata->email;
     if(!empty($gettemplate->destination_email)){
      $emailuser->email = $gettemplate->destination_email;
    } else {
      $emailuser->email = $userdata->email;
    }
    // $emailuser->email = "suneet.sharma695@gmail.com";
    $emailuser->firstname = $userdata->firstname;
    $emailuser->lastname= $userdata->lastname;
    $emailuser->maildisplay = true;
    $emailuser->mailformat = 1; // 0 (zero) text-only emails, 1 (one) for HTML/Text emails.
    $emailuser->id = 1;
    $emailuser->firstnamephonetic = false;
    $emailuser->lastnamephonetic = false;
    $emailuser->middlename = false;
    $emailuser->username = false;
    $emailuser->alternatename = false;
    if($emailtemplatedata_user){
      // email_to_user($emailuser, $fromUser, $subject, $message = '', $messagehtml, '', '', true, '', '', 79, $companyid);


      // email_learnpeek_to_user($emailuser, $fromUser, $subject, $message = '' , $messagehtml, '', '', true, '', '', 79, $companyid);




      // echo "email send  $userdata->email #compayid $companyid <br>";

    }












//===========================================================================================================================








    die();
// $companyid = 36;
// $oderinvoiceid = 2538;
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

     echo $messagehtml;
    die('stop');
    $company = $DB->get_record('company', ['id' => $companyid]);
    $getcompany_wu = $DB->get_record_sql("SELECT * FROM {company_users} WHERE companyid = ? AND managertype =?", array($companyid, 1));
    $whitelableuser = $DB->get_record("user", array("id" => $getcompany_wu->userid));
    $white_label_admin_name = fullname($whitelableuser);
    $fromUser =  $white_label_admin_name;

    // $fromUser = core_user::get_support_user();
    $subject = $gettemplate->email_subject;
    $emailuser = (object) [];
    // $emailuser->email = $USER->email;
    // if (!empty($gettemplate->destination_email)) {
    //     $emailuser->email = $gettemplate->destination_email;
    // } else {
    //     $emailuser->email = $USER->email;
    // }
    $emailuser->email = "suneet.sharma695@gmail.com";
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


        if($emailstatus){
          echo "mail send";
        }else{
          echo "mail not send";
        }

    }
























    $userdata = $DB->get_record("user",array("id"=>$userid));

    $companyinfo = $DB->get_record('company_users', ['userid' => $userid]);
    $companyid = $companyinfo->companyid;
    // $companyid = iomad::get_my_companyid(context_system::instance());
      $gettemplate = $DB->get_record_sql("SELECT * FROM {custom_email_template} WHERE company_id = ? AND email_template = ?",array($companyid,'course-completion'));
      if($gettemplate){
        $emailtemplatedata_user = $gettemplate->email_message;
      }else{
        $get_deflt_template = $DB->get_record_sql("SELECT * FROM {custom_email_template} WHERE company_id = ? AND email_template = ?",array(0,'course-completion'));
        if($get_deflt_template){
          $emailtemplatedata_user = $get_deflt_template->email_message;
        }else{
          $emailtemplatedata_user = 0;
        }
      }
        //get compnay logo
      $comlogodetails = $DB->get_record_sql('SELECT * FROM {company_logo} WHERE company_id = ?',array($companyid));
      if($comlogodetails){
        $companylogo = $DB->get_record_sql("SELECT * FROM {files} WHERE contextid= 1 AND component='landingpage' AND itemid=? AND filesize > 0 AND filearea = ?", array($comlogodetails->id,'logo_image'));
        $logourl = $CFG->wwwroot.'/blocks/custom_mycourse/file.php?id='.$companylogo->pathnamehash;
        // $company_logo = "<img src='" . $logourl . "' style='width:125px;max-height:150px;object-fit:cover;'>";
        $company_logo = "<img src='{$logourl}' width='125' height='auto' style='display:block;border:0;'>";
      }else{
        $company_logo = "";
      }
      $coursedata = $DB->get_record("course",array("id"=>$courseid));
      // print_r($coursedata);

     $user_display_name = fullname($userdata);
     $user_email = $userdata->email;
     $course_name = $coursedata->fullname;
     // $checkedcoursescorm = $DB->get_record("scorm",array("course"=>$courseid));
     // if($checkedcoursescorm){
     //    $scorm_track = $DB->get_record_sql("SELECT * FROM {scorm_scoes_track} WHERE userid = ? AND scormid =? AND element LIKE ?",array($userdata->id,$checkedcoursescorm->id,'cmi.core.score.raw'));
     //    if($scorm_track->value >= 70 ){
     //      $course_grade = $scorm_track->value .' (PASSED)';
     //    }else{
     //      $course_grade = $scorm_track->value .' (FAILED)';
     //    }
     // }else{
     //    $course_grade = "N/A";
     // }

    $course_grade = "N/A";
    $coursegradeitem = $DB->get_record('grade_items', [
        'courseid' => $courseid,
        'itemtype' => 'course'
    ]);
    if($coursegradeitem){

        $usergrade = $DB->get_record('grade_grades', [
            'userid' => $userdata->id,
            'itemid' => $coursegradeitem->id
        ]);
        if($usergrade){
            $gradep = 0;
            if($usergrade->finalgrade){
                $gradep = ($usergrade->finalgrade / $usergrade->rawgrademax) * 100;
            }

            if($gradep >= 70 ){
              $finalgrade = (float)$usergrade->finalgrade;
              // $course_grade = round($finalgrade, 2) . " (PASSED)";
              $course_grade = round($gradep, 2) . "% (PASSED)";

            }else{
              $finalgrade = (float)$usergrade->finalgrade;
               $course_grade = round($gradep, 2) . "% (FAILED)";
            }        
        } else {
              $finalgrade = (float)$usergrade->finalgrade;
               $course_grade = round($gradep, 2) . "% (FAILED)";

        }
    }

    $placeholders = ['{{user_display_name}}','{{course_name}}','{{company_logo}}','{{course_grade}}','{{user_email}}'];
    $replacements = [$user_display_name, $course_name, $company_logo, $course_grade , $user_email];
    $email_body = str_replace($placeholders, $replacements, $emailtemplatedata_user);

    $messagehtml =   "<!DOCTYPE html>
                                <html>
                                    <head>
                                        <title>Mail</title>
                                        <link rel='stylesheet' type='text/css' href='https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css'>
                                    </head>
                                    <body>".$email_body."
                                    </body>
                                </html>";  
    // $fromUser = core_user::get_support_user();

    $getcompany_wu = $DB->get_record_sql(
        "SELECT * FROM {company_users} WHERE companyid = ? AND managertype = ?",
        [$companyid, 1]
    );
    if ($getcompany_wu) {
        $whitelabeluser = $DB->get_record('user', ['id' => $getcompany_wu->userid]);
        $fromUser = $whitelabeluser ? fullname($whitelabeluser) : core_user::get_support_user();
    } else {
        $fromUser = core_user::get_support_user();
    }
    // echo $fromUser;
     echo $messagehtml;
    // die();
      $subject = $gettemplate->email_subject;
      $emailuser = (object) [];
      // $emailuser->email = $userdata->email;
       if(!empty($gettemplate->destination_email)){
        $emailuser->email = $gettemplate->destination_email;
      } else {
        // $emailuser->email = $userdata->email;
      }
      // $emailuser->email = "suneet.sharma695@gmail.com";
      $emailuser->email = "admin@pdhstar.com";
      $emailuser->firstname = $userdata->firstname;
      $emailuser->lastname= $userdata->lastname;
      $emailuser->maildisplay = true;
      $emailuser->mailformat = 1; // 0 (zero) text-only emails, 1 (one) for HTML/Text emails.
      $emailuser->id = 1;
      $emailuser->firstnamephonetic = false;
      $emailuser->lastnamephonetic = false;
      $emailuser->middlename = false;
      $emailuser->username = false;
      $emailuser->alternatename = false;
      if($emailtemplatedata_user){
        // email_to_user($emailuser, $fromUser, $subject, $message = '', $messagehtml, '', '', true, '', '', 79, $companyid);
        email_learnpeek_to_user($emailuser, $fromUser, $subject, $message = '' , $messagehtml, '', '', true, '', '', 79, $companyid);
        // echo "email send  $userdata->email #compayid $companyid <br>";

      }

    // uday
    $sitehomecompany = $DB->get_record_sql("SELECT shortname FROM {company} WHERE id = ?",[$companyid]);
    if (in_array($sitehomecompany->shortname, ['PDH STAR'])) {
        $fromUser = core_user::get_support_user();
        $whitelabeluser = null;
        if ($getcompany_wu = $DB->get_record_sql(
            "SELECT * FROM {company_users} WHERE companyid = ? AND managertype = ?", 
            [$companyid, 1]
        )) {
            if ($whitelabeluser = $DB->get_record('user', ['id' => $getcompany_wu->userid])) {
                $fromUser = fullname($whitelabeluser);
    
                $emailuser->email = $whitelabeluser->email;
                // $emailuser->email = $whitelabeluser->email;
                $emailuser->firstname = $whitelabeluser->firstname;
                $emailuser->lastname= $whitelabeluser->lastname;
            }
        }
        if($whitelabeluser){
            email_learnpeek_to_user($emailuser, $fromUser, $subject, $message = '' , $messagehtml, '', '', true, '', '', 79, $companyid);
        }
    }



































    die();

    // $companyid = 36;
    // $userid = 2114;
    $userid = 2101;
    // $userid = 2115;
    $courseid = 291;
    $licenseid = 1403;
    // $licenseid = 1401;
    // $licenseid = 1402;
    $password= '';
    $preview = false;




    // global $DB, $CFG, $USER;
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















// trainee_registration_email_to_whitelable($companyid, $userid, $password= '', $preview = false)
  die();





























    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    $courseid = 331;
    $userid = 1855;
    $userdata = $DB->get_record("user",array("id"=>$userid));

    $companyinfo = $DB->get_record('company_users', ['userid' => $userid]);
    $companyid = $companyinfo->companyid;
    // $companyid = iomad::get_my_companyid(context_system::instance());
      $gettemplate = $DB->get_record_sql("SELECT * FROM {custom_email_template} WHERE company_id = ? AND email_template = ?",array($companyid,'course-completion'));
      if($gettemplate){
        $emailtemplatedata_user = $gettemplate->email_message;
      }else{
        $get_deflt_template = $DB->get_record_sql("SELECT * FROM {custom_email_template} WHERE company_id = ? AND email_template = ?",array(0,'course-completion'));
        if($get_deflt_template){
          $emailtemplatedata_user = $get_deflt_template->email_message;
        }else{
          $emailtemplatedata_user = 0;
        }
      }
        //get compnay logo
      $comlogodetails = $DB->get_record_sql('SELECT * FROM {company_logo} WHERE company_id = ?',array($companyid));
      if($comlogodetails){
        $companylogo = $DB->get_record_sql("SELECT * FROM {files} WHERE contextid= 1 AND component='landingpage' AND itemid=? AND filesize > 0 AND filearea = ?", array($comlogodetails->id,'logo_image'));
        $logourl = $CFG->wwwroot.'/blocks/custom_mycourse/file.php?id='.$companylogo->pathnamehash;
        $company_logo = "<img src='" . $logourl . "' style='width:125px;max-height:150px;object-fit:cover;'>";
      }else{
        $company_logo = "";
      }
      $coursedata = $DB->get_record("course",array("id"=>$courseid));
      // print_r($coursedata);

     $user_display_name = fullname($userdata);
     $user_email = $userdata->email;
     $course_name = $coursedata->fullname;
     $checkedcoursescorm = $DB->get_record("scorm",array("course"=>$courseid));
     if($checkedcoursescorm){
        $scorm_track = $DB->get_record_sql("SELECT * FROM {scorm_scoes_track} WHERE userid = ? AND scormid =? AND element LIKE ?",array($userdata->id,$checkedcoursescorm->id,'cmi.core.score.raw'));
        if($scorm_track->value >= 70 ){
          $course_grade = $scorm_track->value .' (PASSED)';
        }else{
          $course_grade = $scorm_track->value .' (FAILED)';
        }
     }else{
        $course_grade = "N/A";
     }


      $placeholders = ['{{user_display_name}}','{{course_name}}','{{company_logo}}','{{course_grade}}','{{user_email}}'];
      $replacements = [$user_display_name, $course_name, $company_logo, $course_grade , $user_email];
      $email_body = str_replace($placeholders, $replacements, $emailtemplatedata_user);

      $messagehtml =   "<!DOCTYPE html>
                                    <html>
                                        <head>
                                            <title>Mail</title>
                                            <link rel='stylesheet' type='text/css' href='https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css'>
                                        </head>
                                        <body>".$email_body."
                                        </body>
                                    </html>";  
      // $fromUser = core_user::get_support_user();

    $getcompany_wu = $DB->get_record_sql(
        "SELECT * FROM {company_users} WHERE companyid = ? AND managertype = ?",
        [$companyid, 1]
    );
    if ($getcompany_wu) {
        $whitelabeluser = $DB->get_record('user', ['id' => $getcompany_wu->userid]);
        $fromUser = $whitelabeluser ? fullname($whitelabeluser) : core_user::get_support_user();
    } else {
        $fromUser = core_user::get_support_user();
    }
    // echo $fromUser;
    echo $messagehtml;
    die();

      $subject = $gettemplate->email_subject;
      $emailuser = (object) [];
      // $emailuser->email = $userdata->email;
       if(!empty($gettemplate->destination_email)){
        $emailuser->email = $gettemplate->destination_email;
      } else {
        // $emailuser->email = $userdata->email;
        $emailuser->email = "suneet@lds-internation.in";
      }
      // $emailuser->email = "suneet.sharma695@gmail.com";
      $emailuser->firstname = $userdata->firstname;
      $emailuser->lastname= $userdata->lastname;
      $emailuser->maildisplay = true;
      $emailuser->mailformat = 1; // 0 (zero) text-only emails, 1 (one) for HTML/Text emails.
      $emailuser->id = 1;
      $emailuser->firstnamephonetic = false;
      $emailuser->lastnamephonetic = false;
      $emailuser->middlename = false;
      $emailuser->username = false;
      $emailuser->alternatename = false;
      if($emailtemplatedata_user){
        // email_to_user($emailuser, $fromUser, $subject, $message = '', $messagehtml, '', '', true, '', '', 79, $companyid);
        email_learnpeek_to_user($emailuser, $fromUser, $subject, $message = '' , $messagehtml, '', '', true, '', '', 79, $companyid);
        // echo "email send  $userdata->email #compayid $companyid <br>";

      }

    // uday
    $sitehomecompany = $DB->get_record_sql("SELECT shortname FROM {company} WHERE id = ?",[$companyid]);
    if (in_array($sitehomecompany->shortname, ['Sumo1'])) {
        $fromUser = core_user::get_support_user();
        $whitelabeluser = null;
        if ($getcompany_wu = $DB->get_record_sql(
            "SELECT * FROM {company_users} WHERE companyid = ? AND managertype = ?", 
            [$companyid, 1]
        )) {
            if ($whitelabeluser = $DB->get_record('user', ['id' => $getcompany_wu->userid])) {
                $fromUser = fullname($whitelabeluser);
    
                // $emailuser->email = $whitelabeluser->email;
                $emailuser->email = "suneet@ldsengineers.com";
                $emailuser->firstname = $whitelabeluser->firstname;
                $emailuser->lastname= $whitelabeluser->lastname;
            }
        }
        if($whitelabeluser){
            email_learnpeek_to_user($emailuser, $fromUser, $subject, $message = '' , $messagehtml, '', '', true, '', '', 79, $companyid);
        }
    }














// require_once($CFG->dirroot . '/mod/customcert/classes/template.php');
// // SELECT cb.* FROM `mdl_customcert_backup` cb INNER JOIN mdl_user_enrolments ue on ue.id = cb.current_enrollment INNER JOIN mdl_user u on u.id = ue.userid WHERE u.deleted = 0;

// $certificatemodule = $DB->get_record("modules", array("name" => "customcert"));
// $all_backup_crt = $DB->get_records_sql("SELECT cb.* FROM mdl_customcert_backup_bk cb INNER JOIN mdl_user_enrolments ue on ue.id = cb.current_enrollment INNER JOIN mdl_user u on u.id = ue.userid WHERE u.deleted = 0 AND cb.course_module_id !=0");
// if($all_backup_crt){
//     $counter = 0;
//     foreach ($all_backup_crt as $certificatebk) {
//             $userid = $certificatebk->userid;
//             $courseid = $certificatebk->courseid;

//             $companydata = $DB->get_record('company_users', ['userid' => $userid], '*', MUST_EXIST);
//             if (!$companydata) {
//                 continue;
//             }
//             $companyid   = $companydata->companyid;

//             $coursmodul = $DB->get_records("course_modules", array('module' => $certificatemodule->id, 'course' => $courseid));

//             $allowedmodules = [];
//             foreach ($coursmodul as $cm) {

//                 // Skip hidden / deleting modules
//                 if (!$cm->visible || $cm->deletioninprogress) {
//                     continue;
//                 }

//                 // Empty availability  allowed
//                 if (empty($cm->availability)) {
//                     $allowedmodules[$cm->id] = $cm;
//                     continue;
//                 }

//                 $availability = json_decode($cm->availability, true);

//                 // Invalid JSON  allow (matches Moodle behaviour)
//                 if (empty($availability['c'])) {
//                     $allowedmodules[$cm->id] = $cm;
//                     continue;
//                 }

//                 $hascompanyrule = false;
//                 $companymatch   = false;

//                 foreach ($availability['c'] as $cond) {
//                     if (!empty($cond['type']) && $cond['type'] === 'company') {
//                         $hascompanyrule = true;

//                         if ((int)$cond['id'] === (int)$companyid) {
//                             $companymatch = true;
//                             break;
//                         }
//                     }
//                 }
//                 if (!$hascompanyrule || $companymatch) {
//                     $allowedmodules[$cm->id] = $cm;
//                 }
//             }

//             foreach ($allowedmodules as $courscrtmodul) {
//                 $checkbackup = $DB->get_record_sql("SELECT * FROM mdl_customcert_backup WHERE userid =? AND courseid =? AND course_module_id =?",array($userid,$courseid,$courscrtmodul->id));
//                 if(empty($checkbackup)){

//                     $customcert = $DB->get_record('customcert', ['id' => $courscrtmodul->instance], '*', MUST_EXIST);
//                     if (!$DB->record_exists('customcert_issues', ['userid' => $userid, 'customcertid' => $customcert->id])) {
//                         \mod_customcert\certificate::issue_certificate($customcert->id, $userid);
//                     }
//                     $template = $DB->get_record('customcert_templates', ['id' => $customcert->templateid], '*', MUST_EXIST);
//                     $template = new \mod_customcert\template($template);
//                     // echo "$courscrtmodul->id <br> companyid $companyid <br>";


//                     $pdf = $template->generate_pdf(false, $userid, true);
//                     // print_r($pdf);
//                     echo "<pre>";
//                     print_r($courscrtmodul);
//                     print_r($customcert);
//                     echo "</pre>";

//                 $counter++;
//                 }


//             }
//             if ($counter >= 5) {
//                 echo "exicured 5 records";
//                 break;
//             }
    
//     }
// }
// die();

//      $timestart = strtotime("-6 days midnight");
//     $timeend = strtotime("tomorrow midnight") - 1;
//     $companyInvoices = [];
//     $cumulative_companies = $DB->get_records_sql("SELECT * FROM {company_mail_configuration} WHERE cumulative_email = ?", [1]);
//     if ($cumulative_companies) {
//         foreach ($cumulative_companies as $cumulative_company) {
//             $companyid = $cumulative_company->company_id;
//             // Get today's invoices for this company

//             $invoices = $DB->get_records_sql("
//                 SELECT id FROM {invoice}
//                 WHERE status = 'p'
//                   AND companyid = ?
//                   AND date BETWEEN ? AND ?
//             ", [$companyid, $timestart, $timeend]);

//             $user_invoice = array();
//             foreach ($invoices as $invoice) {
//               $user_invoice[] = $invoice->id;
//             }

//             if($user_invoice){
//               $invoice_ids_string = implode(',', $user_invoice);
//               // echo "SELECT i.id, i.userid, p.amount, p.currency, GROUP_CONCAT(css.name) FROM {invoice} i INNER JOIN {invoiceitem} ii on ii.invoiceid = i.id INNER JOIN {payments} p on p.id = i.paymentid LEFT JOIN {course_shopsettings} as css ON ii.invoiceableitemid = css.id WHERE i.id IN($invoice_ids_string) GROUP BY i.id";
//               $getcompanyoders = $DB->get_records_sql("SELECT i.id, i.userid, p.amount, p.currency, GROUP_CONCAT(css.name) FROM {invoice} i INNER JOIN {invoiceitem} ii on ii.invoiceid = i.id INNER JOIN {payments} p on p.id = i.paymentid LEFT JOIN {course_shopsettings} as css ON ii.invoiceableitemid = css.id WHERE i.id IN($invoice_ids_string) GROUP BY i.id");
//               if($getcompanyoders){
//                 $orderdata = '<table style="width:80%; margin:0 auto;" border="1" cellpadding="6" cellspacing="0">';
//                 $orderdata.= '<thead style="background-color:#4466eb; color:#fff; padding:20px !important;">
//                       <tr>
//                           <th style="font-size:20px; padding:10px;">Invoice ID</th>
//                           <th style="font-size:20px;padding:10px;">Name</th>
//                           <th style="font-size:20px;padding:10px;">Product Name(s)</th>
//                           <th style="font-size:20px;padding:10px;">Total Price '.$getcompanyoders->currency.'</th>
//                       </tr>
//                     </thead>';
//                 $orderdata.= '<tbody>';

//                 foreach ($getcompanyoders as $order) {
//                   $invoiceid = $order->id;
//                   $userdata = $DB->get_record("user", array("id" => $order->userid));
//                   $username =  fullname($userdata);;
//                   $products = htmlspecialchars($order->{'group_concat(css.name)'});
//                   $amount = number_format($order->amount, 2); // Format to 2 decimal places

//                   $orderdata.= "<tr>
//                           <td style='padding:20px;'>$invoiceid</td>
//                           <td style='padding:20px;'>$username</td>
//                           <td style='padding:20px;'>$products</td>
//                           <td style='padding:20px;'>$amount</td>
//                         </tr>";
//                 }
//                 $orderdata.= '</tbody>';
//                 $orderdata.= '</table>';
//                 $orderdata;
//                 //get compnay logo
//                 $comlogodetails = $DB->get_record_sql('SELECT * FROM {company_logo} WHERE company_id = ?', array($companyid));
//                 if ($comlogodetails) {
//                   $companylogo = $DB->get_record_sql("SELECT * FROM {files} WHERE contextid= 1 AND component='landingpage' AND itemid=? AND filesize > 0 AND filearea = ?", array($comlogodetails->id, 'logo_image'));
//                   $logourl = $CFG->wwwroot . '/blocks/custom_mycourse/file.php?id=' . $companylogo->pathnamehash;
//                   $company_logo = "<img src='" . $logourl . "' style='width: 125px;max-height: 150px;object-fit: cover;'>";
//                 } else {
//                   $company_logo = "";
//                 }
//                 $gettemplate = $DB->get_record_sql("SELECT * FROM {custom_email_template} WHERE email_template = ?",array('cumulative-order-white-label'));
//                 if($gettemplate){
//                     $getdestination_email =$DB->get_record_sql("SELECT * FROM {custom_email_template} WHERE company_id = ? AND email_template = ?",array($companyid,'order-email-white-label'));


//                     $company = $DB->get_record('company', ['id' => $companyid]);
//                     $getcompany_wu = $DB->get_records_sql("SELECT * FROM {company_users} WHERE companyid = ? AND managertype =?", array($companyid, 1));
//                     foreach($getcompany_wu as $whitelabel){
//                         $whitelableuser = $DB->get_record("user",["id"=>$whitelabel->userid]);
//                         $white_label_admin_name = fullname($whitelableuser);

//                         $emailtemplatedata_wl = $gettemplate->email_message;
//                         $placeholders = ['{{white_label_admin_name}}', '{{all_order_details}}', '{{company_logo}}'];
//                         $replacements = [$white_label_admin_name, $orderdata, $company_logo];
//                         $email_body = str_replace($placeholders, $replacements, $emailtemplatedata_wl);
//                         $messagehtml =   "<!DOCTYPE html>
//                                                     <html>
//                                                         <head>
//                                                             <title>Mail</title>
//                                                             <link rel='stylesheet' type='text/css' href='https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css'>
//                                                         </head>
//                                                         <body>" . $email_body . "
//                                                         </body>
//                                                     </html>";
//                         $fromUser =  $white_label_admin_name . '(' . $company->name . ')';
//                         $subject = $gettemplate->email_subject;
//                         $emailuser = (object) [];
//                         // if(!empty($getdestination_email->destination_email)){
//                         //   $emailuser->email = $gettemplate->destination_email;
//                         // } else {
//                         //   $emailuser->email = $whitelableuser->email;
//                         // }
//                         $emailuser->email = "suneet.sharma695@gmail.com";
//                         $emailuser->firstname = $whitelableuser->firstname;
//                         $emailuser->lastname = $whitelableuser->lastname;
//                         $emailuser->maildisplay = true;
//                         $emailuser->mailformat = 1; // 0 (zero) text-only emails, 1 (one) for HTML/Text emails.
//                         $emailuser->id = 1;
//                         $emailuser->firstnamephonetic = false;
//                         $emailuser->lastnamephonetic = false;
//                         $emailuser->middlename = false;
//                         $emailuser->username = false;
//                         $emailuser->alternatename = false;

//                         echo $messagehtml;
//                         echo "<pre>";
//                         print_r($emailuser);
//                         echo "</pre>";
//                         $mialsend = email_learnpeek_to_user($emailuser, $fromUser, $subject, $message = '', $messagehtml, '', '', 9, $companyid);
//                         if($mialsend){
//                             echo "mail send";
//                         }else{
//                             echo "not send";
//                         }
//                         if(!empty($getdestination_email->destination_email)){
//                             break;
//                         }

//                     }
                   

//                 }

                  

//               }
//             }
//         }

//     }
// die;
// global $DB, $CFG;

// $timestart = strtotime("today midnight");
// $timeend = strtotime("tomorrow midnight") - 1;

// $companyInvoices = [];

// $cumulative_companies = $DB->get_records_sql("
//     SELECT cmc.company_id, c.name AS company_name
//     FROM {company_mail_configuration} cmc
//     JOIN {company} c ON c.id = cmc.company_id
//     WHERE cmc.cumulative_email = ?", [1]);

// if ($cumulative_companies) {
//     foreach ($cumulative_companies as $company) {
//         $companyid = $company->company_id;
//         $companyname = $company->company_name;

//         // Get today's invoice IDs for this company
//         $invoices = $DB->get_records_sql("
//             SELECT id FROM {invoice}
//             WHERE status = 'p'
//               AND companyid = ?
//               AND date BETWEEN ? AND ?
//         ", [$companyid, $timestart, $timeend]);

//         $invoice_ids = array_keys($invoices);

//         if (!empty($invoice_ids)) {
//             // Fetch all invoice items (no key reliance)
//             list($in_sql, $in_params) = $DB->get_in_or_equal($invoice_ids, SQL_PARAMS_QM);
//             echo $in_params;
//             echo "<pre>";
//             print_r($in_params);
//             echo "<pre>";
//             $invoiceDetails = $DB->get_records_sql("
//                 SELECT 
//                     ii.invoiceid,
//                     p.userid,
//                     css.name AS product_name,
//                     ii.price
//                 FROM {invoiceitem} ii
//                 LEFT JOIN {course_shopsettings} css ON ii.invoiceableitemid = css.id
//                 LEFT JOIN {payments} p ON p.itemid = ii.invoiceid
//                 WHERE ii.invoiceid $in_sql
//             ", $in_params);

//             // Group invoice items properly per invoice+user
//             $grouped = [];

//             foreach ($invoiceDetails as $record) {
//                 $invId = $record->invoiceid;
//                 $userId = $record->userid ?? '-';

//                 $key = $invId . '_' . $userId;

//                 if (!isset($grouped[$key])) {
//                     $grouped[$key] = [
//                         'invoiceid' => $invId,
//                         'userid' => $userId,
//                         'products' => [],
//                         'total' => 0
//                     ];
//                 }

//                 // Add product and total
//                 $grouped[$key]['products'][] = $record->product_name ?: '(Unnamed Product)';
//                 $grouped[$key]['total'] += floatval($record->price);
//             }

//             // Save grouped invoice data by company
//             $companyInvoices[$companyid] = [
//                 'company_name' => $companyname,
//                 'invoices' => $grouped
//             ];
//         }
//     }
// }

// // Render output as HTML table
// echo '<h2>Daily Invoice Summary</h2>';

// foreach ($companyInvoices as $companyid => $data) {
//     echo "<h3>Company: {$data['company_name']} (ID: $companyid)</h3>";
//     echo '<table border="1" cellpadding="6" cellspacing="0">';
//     echo '<thead>
//             <tr>
//                 <th>Invoice ID</th>
//                 <th>User ID</th>
//                 <th>Product Name(s)</th>
//                 <th>Total Price (USD)</th>
//             </tr>
//           </thead>';
//     echo '<tbody>';

//     foreach ($data['invoices'] as $entry) {
//         $invoiceid = $entry['invoiceid'];
//         $userid = $entry['userid'];
//         $products = implode(', ', $entry['products']);
//         $total = number_format($entry['total'], 2);

//         echo "<tr>
//                 <td>$invoiceid</td>
//                 <td>$userid</td>
//                 <td>$products</td>
//                 <td>$total</td>
//               </tr>";
//     }

//     echo '</tbody>';
//     echo '</table>';
// }


// //===================================================================================================================
// die;

// $timestart = strtotime("today midnight");
// $timeend = strtotime("tomorrow midnight") - 1;

// // Final structure: [companyid => [invoiceid => [... invoice details ...]]]
// $companyInvoices = [];

// // Step 1: Get all companies with cumulative_email enabled
// $cumulative_companies = $DB->get_records_sql("
//     SELECT cmc.company_id, c.name AS company_name
//     FROM {company_mail_configuration} cmc
//     JOIN {company} c ON c.id = cmc.company_id
//     WHERE cmc.cumulative_email = ?", [1]);

// if ($cumulative_companies) {
//     foreach ($cumulative_companies as $company) {
//         $companyid = $company->company_id;
//         $companyname = $company->company_name;

//         // Get today's invoice IDs for this company
//         $invoices = $DB->get_records_sql("
//             SELECT id FROM {invoice}
//             WHERE status = 'p'
//               AND companyid = ?
//               AND date BETWEEN ? AND ?
//         ", [$companyid, $timestart, $timeend]);

//         $invoice_ids = array_keys($invoices);

//         if (!empty($invoice_ids)) {
//             // Prepare invoice details query
//             list($in_sql, $in_params) = $DB->get_in_or_equal($invoice_ids, SQL_PARAMS_QM);
//             $invoiceDetails = $DB->get_records_sql("
//                 SELECT 
//                     ii.invoiceid,
//                     p.userid,
//                     css.name AS product_name,
//                     ii.price
//                 FROM {invoiceitem} ii
//                 LEFT JOIN {course_shopsettings} css ON ii.invoiceableitemid = css.id
//                 LEFT JOIN {payments} p ON p.itemid = ii.invoiceid
//                 WHERE ii.invoiceid $in_sql
//             ", $in_params);

//             // Group and calculate totals
//             $grouped = [];
//             foreach ($invoiceDetails as $record) {
//                 $invId = $record->invoiceid;
//                 $userId = $record->userid;

//                 $key = $invId . '_' . $userId;
//                 if (!isset($grouped[$key])) {
//                     $grouped[$key] = [
//                         'invoiceid' => $invId,
//                         'userid' => $userId,
//                         'products' => [],
//                         'total' => 0
//                     ];
//                 }

//                 $grouped[$key]['products'][] = $record->product_name;
//                 $grouped[$key]['total'] += floatval($record->price);
//             }

//             // Save this company's data
//             $companyInvoices[$companyid] = [
//                 'company_name' => $companyname,
//                 'invoices' => $grouped
//             ];
//         }
//     }
// }

// // Render as HTML table
// echo '<h2>Daily Invoice Summary</h2>';

// foreach ($companyInvoices as $companyid => $data) {
//     echo "<h3>Company: {$data['company_name']} (ID: $companyid)</h3>";
//     echo '<table border="1" cellpadding="6" cellspacing="0">';
//     echo '<thead>
//             <tr>
//                 <th>Invoice ID</th>
//                 <th>User ID</th>
//                 <th>Product Name(s)</th>
//                 <th>Total Price (USD)</th>
//             </tr>
//           </thead>';
//     echo '<tbody>';

//     foreach ($data['invoices'] as $entry) {
//         $invoiceid = $entry['invoiceid'];
//         $userid = $entry['userid'];
//         $products = implode(', ', $entry['products']);
//         $total = number_format($entry['total'], 2);

//         echo "<tr>
//                 <td>$invoiceid</td>
//                 <td>$userid</td>
//                 <td>$products</td>
//                 <td>$$total</td>
//               </tr>";
//     }

//     echo '</tbody>';
//     echo '</table><br>';
// }



// die;
//   $whitelableuser = $DB->get_record("user", array("id"=>$USER->id));

// echo"<pre>";
// print_r($whitelableuser);
// echo"</pre>";

// exit();

//  $gettemplate = $DB->get_record_sql("SELECT * FROM {custom_email_template} WHERE company_id = ? AND email_template = ?",array(39,'order-email-white-label'));
// //  echo"<pre>";
// // var_dump($gettemplate);
// // echo"</pre>";

// // exit();
//             if($gettemplate){
//                 $emailtemplatedata_wl = $gettemplate->email_message;

//             }else{ 
//               $get_defult_template = $DB->get_record_sql("SELECT * FROM {custom_email_template} WHERE company_id = ? AND email_template = ?",array(0,'order-email-white-label'));
//               if($get_defult_template){
//                 $emailtemplatedata_wl = $get_defult_template->email_message;
//               }else{
//                 $emailtemplatedata_wl = 0;
//               }  
//           } 
   // $fromUser = core_user::get_support_user();
  //   $companyid =33; 
  // $messagehtml = "Test mail for companyid : '".$companyid."'";

  //           // $fromUser =  $white_label_admin_name . '(' . $company->name . ')';
  //           $fromUser =  "SUMO Admin" . '( sump lds)';
  //           $subject = "test mail sumo user suneet";
  //           $emailuser = (object) [];
  //           // $emailuser->email = $whitelableuser->email;
  //           $emailuser->email = "suneet.sharma695@gmail.com";
  //           $emailuser->firstname = $whitelableuser->firstname;
  //           $emailuser->lastname= $whitelableuser->lastname;
  //           $emailuser->maildisplay = true;
  //           $emailuser->mailformat = 1; // 0 (zero) text-only emails, 1 (one) for HTML/Text emails.
  //           $emailuser->id = 1;
  //           $emailuser->firstnamephonetic = false;
  //           $emailuser->lastnamephonetic = false;
  //           $emailuser->middlename = false;
  //           $emailuser->username = false;
  //           $emailuser->alternatename = false;
  //           // $emailsend =   email_to_user($emailuser,$fromUser, $subject, $message = '', $messagehtml,$companyid);
  //           $emailsent = email_to_user($emailuser, $fromUser, $subject, $message, $messagehtml, '', '', 9, $companyid);

  //           if($emailsent){

  //             echo "email send to ";
            
  //           }else{

  //             echo "mail not send to white lavel";            
  //           }
