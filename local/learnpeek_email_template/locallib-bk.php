<?php
include_once("../../config.php");
global $CFG,$USER,$DB,$PAGE;

function order_complete_email_to_whitelable($companyid, $oderuserid, $oderinvoiceid){
	global $DB, $USER, $CFG;
    $company = $DB->get_record('company', ['id' => $companyid]);
    $getcompany_wu = $DB->get_records_sql("SELECT * FROM {company_users} WHERE companyid = ? AND managertype =?",array($companyid,1));
    $orderuser = $DB->get_record("user", array("id"=>$oderuserid));

        foreach ($getcompany_wu as $com_wtuser) {
            $gettemplate = $DB->get_record_sql("SELECT * FROM {custom_email_template} WHERE company_id = ? AND email_template = ?",array($companyid,'order-email-white-label'));
            if($gettemplate){
                $emailtemplatedata_wl = $gettemplate->email_message;
            }else{
              $get_defult_template = $DB->get_record_sql("SELECT * FROM {custom_email_template} WHERE company_id = ? AND email_template = ?",array(0,'order-email-white-label'));
              if($get_defult_template){
                $emailtemplatedata_wl = $get_defult_template->email_message;
              }else{
                $emailtemplatedata_wl = 0;
              }  

            }
            $whitelableuser = $DB->get_record("user", array("id"=>$com_wtuser->userid));
            $order_user_name = fullname($orderuser);
            $white_label_admin_name = fullname($whitelableuser);

            //get compnay logo
            $comlogodetails = $DB->get_record_sql('SELECT * FROM {company_logo} WHERE company_id = ?',array($companyid));
            if($comlogodetails){
              $companylogo = $DB->get_record_sql("SELECT * FROM {files} WHERE contextid= 1 AND component='landingpage' AND itemid=? AND filesize > 0 AND filearea = ?", array($comlogodetails->id,'logo_image'));
              $logourl = $CFG->wwwroot.'/blocks/custom_mycourse/file.php?id='.$companylogo->pathnamehash;
              $company_logo = "<img src='".$logourl."' style='width: 100px;height: 100px;object-fit: contain;''>";
            }else{
              $company_logo = "";

            }

            // echo "<pre>";
            // print_r($whitelableuser);
            // echo "</pre>";




            $invoicedata = $DB->get_record("invoice", array("id"=>$oderinvoiceid));
            // $price = $DB->get_record("payments", array("id"=>$invoicedata->paymentid));
            $price = $DB->get_record("payments", array("itemid"=>$oderinvoiceid,"userid"=>$USER->id));
            $coupon = $DB->get_record_sql(
                "SELECT * FROM {coupon_invoice} ci 
                 JOIN {local_coupon} lc ON ci.couponid = lc.id 
                 WHERE ci.invoiceid = ?", 
                [$oderinvoiceid]
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

            $orderdata = $DB->get_records_sql("SELECT ii.*, csc.courseid,css.name 
               FROM {invoiceitem} as ii 
               LEFT JOIN {course_shopsettings} as css ON ii.invoiceableitemid = css.id 
               INNER JOIN {course_shopsettings_courses} as csc ON csc.itemid =ii.invoiceableitemid 
               WHERE ii.invoiceid = ?", array($oderinvoiceid));
            $usertype =  $DB->get_record("company_users", array("companyid"=>$invoicedata->companyid,"userid"=>$invoicedata->userid));
            if($usertype->managertype == 0){
              $usercompany = "";
            }else{
              $usercompany = $invoicedata->company;
            }
            $order_items_table = '<div class="container-fluid" >
                <div class="row" >
                   <div class="cst_styling1 shadow-md" style="width:60%; margin:50px auto; border: 1px solid #fefe; padding: 30px;  border-radius: 10px; background-color: #f4f4f7; color: #5373ec; font-family:monospace;">
                      <h3  style="background-color:#5373ec; color:#fff; padding:10px; border-radius:5px" class="">Order Details</h3>
                      <div style="background-color:#fff;">
                      <table class="table table-bordered" border="1" style="color:#5373ec; width:100%; border-collapse:collapse">
                         <thead class="text-white bg_custom">
                            <tr>
                               <th style="background-color:#5373ec; color:#fff">Item</th>
                               <th style="background-color:#5373ec; color:#fff">Quantity</th>
                               <th style="background-color:#5373ec; color:#fff">Total</th>
                            </tr>
                         </thead>
                         <tbody>';
                         $total = 0;
                          $lquantity = "";
                            foreach ($orderdata as $ordervalue) {
                            $getcourse = $DB->get_record("course",array("id"=>$ordervalue->courseid));
                            if($getcourse){
                              $courselink = $CFG->wwww.'/course/view.php?id='.$getcourse->id;
                            }else{
                              $courselink = "#";
                            }
                            $total += $ordervalue->price;
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
                            
                            $order_items_table.= '
                            <tr>
                               <td  style=" text-align:center; padding:5px">
                                  <a  style="font-size:12px;" class="cours_orders_links  shadow-md text-decoration-underline" href="'.$courselink.'">'.$productname.' '. $lquantity. '</a>               
                               </td>
                               <td  style=" text-align:center; padding:5px">'.$lquantity.'</td>
                               <td  style=" text-align:center; padding:5px" class="">
                                  <span>'.$ordervalue->price.' '.$ordervalue->currency.'</span>                
                               </td>
                            </tr>';
                         }
                         $order_items_table.= '
                         </tbody>
                         <tfoot>
                            <tr class="">
                               <th style="text-align:center; padding:5px">Subtotal</th>
                               <td style="text-align:center; padding:5px">  </td>
                               <td style="text-align:center; padding:5px">'. number_format($subtotal, 2) .' '.$ordervalue->currency.'</td>
                            </tr>
                            <tr class="">
                               <th style="text-align:center; padding:5px">Discount: ' .  $couponcode . '  </th>
                               <td style="text-align:center; padding:5px">  </td>
                               <td style="text-align:center; padding:5px"> - '. number_format($coupondiscount, 2) .' '.$ordervalue->currency.'</td>
                            </tr>
                            <tr class="">
                               <th style=" text-align:center; padding:5px">Total</th>
                               <td style=" text-align:center; padding:5px"> - </td>
                               <td style=" text-align:center; padding:5px">'. number_format($price->amount, 2) .' '.$ordervalue->currency.'</td>
                            </tr>
                         </tfoot>
                      </table>
                      </div>
                     <div style="background-color:#5373ec; color:#fff; padding:20px; margin:20px 0; border-radius:10px">
                      <p class="">
                         <strong>Invoice ID : </strong>
                         <span>'.$invoicedata->id. '</span>
                      </p>
                       
                      <div class="" style="display:flex; align-items:center">
                         <div style="white-space:nowrap">
                            <strong>Order Status: </strong>
                         </div>

                        <div>
                              <span class="completed_order" style="padding: 6px;  border-radius: 5px;">';
                               // if($invoicedata->status == 'p') { 
                                $order_items_table.=  'Completed';
                               // }
                               $order_items_table.= '
                             </span>
                        </div>
                      </div>
                      </div>
                   </div>
                   
                </div>
                </div>
                    <style>

                    </style>';

            $placeholders = ['{{white_label_admin_name}}', '{{order_user_name}}','{{order_items_table}}','{{company_logo}}'];
            $replacements = [$white_label_admin_name, $order_user_name, $order_items_table, $company_logo];
            $email_body = str_replace($placeholders, $replacements, $emailtemplatedata_wl);
            $messagehtml =   "<!DOCTYPE html>
                                <html>
                                    <head>
                                        <title>Mail</title>
                                        <link rel='stylesheet' type='text/css' href='https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css'>
                                    </head>
                                    <body>".$email_body."
                                    </body>
                                </html>";
            // print_r($fromUser);
            $fromUser = core_user::get_support_user();
            $subject = $gettemplate->email_subject;
            $emailuser = (object) [];
            $emailuser->email = $whitelableuser->email;
            // $emailuser->email = "mukulparcha000@gmail.com";
            $emailuser->firstname = $whitelableuser->firstname;
            $emailuser->lastname= $whitelableuser->lastname;
            $emailuser->maildisplay = true;
            $emailuser->mailformat = 1; // 0 (zero) text-only emails, 1 (one) for HTML/Text emails.
            $emailuser->id = 1;
            $emailuser->firstnamephonetic = false;
            $emailuser->lastnamephonetic = false;
            $emailuser->middlename = false;
            $emailuser->username = false;
            $emailuser->alternatename = false;
            if($emailtemplatedata_wl){
              email_to_user($emailuser,$fromUser, $subject, $message = '', $messagehtml);
              // echo "email send to ".$whitelableuser->email;
              // return = 1;
              // echo "mail send to white lavel";
            }
        }
}

function order_complete_email_to_user($companyid,$oderinvoiceid){
  global $DB, $USER,$CFG;
  $gettemplate = $DB->get_record_sql("SELECT * FROM {custom_email_template} WHERE company_id = ? AND email_template = ?",array($companyid,'order-email-user'));
  if($gettemplate){
    $emailtemplatedata_user = $gettemplate->email_message;
  }else{
    $get_deflt_template = $DB->get_record_sql("SELECT * FROM {custom_email_template} WHERE company_id = ? AND email_template = ?",array(0,'order-email-user'));
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
    $company_logo = "<img src='".$logourl."' style='width: 100px; height: 100px; object-fit: contain;'>";
  }else{
    $company_logo = "";
  }

  $order_user_name = fullname($USER);

  $invoicedata = $DB->get_record("invoice", array("id"=>$oderinvoiceid));
  // echo $oderinvoiceid;
  // echo "<pre>";
  // print_r($invoicedata);
  // echo "</pre>";
  // print_r($invoicedata);

  $price = $DB->get_record("payments", array("itemid"=>$oderinvoiceid,"userid"=>$USER->id));

  $coupon = $DB->get_record_sql(
      "SELECT * FROM {coupon_invoice} ci 
       JOIN {local_coupon} lc ON ci.couponid = lc.id 
       WHERE ci.invoiceid = ?", 
      [$oderinvoiceid]
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


  $orderdata = $DB->get_records_sql("SELECT ii.*, csc.courseid,css.name 
     FROM {invoiceitem} as ii 
     LEFT JOIN {course_shopsettings} as css ON ii.invoiceableitemid = css.id 
     INNER JOIN {course_shopsettings_courses} as csc ON csc.itemid =ii.invoiceableitemid 
     WHERE ii.invoiceid = ?", array($oderinvoiceid));
  $usertype =  $DB->get_record("company_users", array("companyid"=>$invoicedata->companyid,"userid"=>$invoicedata->userid));
  if($usertype->managertype == 0){
    $usercompany = "";
  }else{
    $usercompany = $invoicedata->company;
  }
  $order_items_table = '<div class="container-fluid" >
      <div class="row" >
         <div class="cst_styling1 shadow-md" style="width:60%; margin:50px auto; border: 1px solid #fefe; padding: 30px;  border-radius: 10px; background-color: #f4f4f7; color: #5373ec; font-family:monospace;">
            <h3  style="background-color:#5373ec; color:#fff; padding:10px; border-radius:5px" class="">Order Details</h3>
            <div style="background-color:#fff;">
            <table class="table table-bordered" border="1" style="color:#5373ec; width:100%; border-collapse:collapse">
               <thead class="text-white bg_custom">
                  <tr>
                     <th style="background-color:#5373ec; color:#fff">Item</th>
                     <th style="background-color:#5373ec; color:#fff">Quantity</th>
                     <th style="background-color:#5373ec; color:#fff">Total</th>
                  </tr>
               </thead>
               <tbody>';
               $total = 0;
                $lquantity = "";
                  foreach ($orderdata as $ordervalue) {
                  $getcourse = $DB->get_record("course",array("id"=>$ordervalue->courseid));
                  if($getcourse){
                    $courselink = $CFG->wwww.'/course/view.php?id='.$getcourse->id;
                  }else{
                    $courselink = "#";
                  }
                  $total += $ordervalue->price;
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
                  
                  $order_items_table.= '
                  <tr>
                     <td  style="text-align:center; padding:5px">
                        <a  style="font-size:12px;" class="cours_orders_links  shadow-md text-decoration-underline" href="'.$courselink.'">'.$productname.' '. $lquantity. '</a>               
                     </td>
                     <td  style="text-align:center; padding:5px">'.$lquantity.'</td>
                     <td  style="text-align:center; padding:5px" class="">
                        <span>'.$ordervalue->price.' '.$ordervalue->currency.'</span>                
                     </td>
                  </tr>';
               }
               $order_items_table.= '
               </tbody>
               <tfoot>
                  <tr class="">
                     <th style="text-align:center; padding:5px">Subtotal</th>
                     <td style="text-align:center; padding:5px">  </td>
                     <td style="text-align:center; padding:5px">'. number_format($subtotal, 2) .' '.$ordervalue->currency.'</td>
                  </tr>
                  <tr class="">
                     <th style="text-align:center; padding:5px">Discount: ' .  $couponcode . '  </th>
                     <td style="text-align:center; padding:5px">  </td>
                     <td style="text-align:center; padding:5px"> - '. number_format($coupondiscount, 2) .' '.$ordervalue->currency.'</td>
                  </tr>
                  <tr class="">
                     <th style="text-align:center; padding:5px">Total</th>
                     <td style="text-align:center; padding:5px">  </td>
                     <td style="text-align:center; padding:5px">'. number_format($price->amount, 2) .' '.$ordervalue->currency.'</td>
                  </tr>
               </tfoot>
            </table>
            </div>
           <div style="background-color:#5373ec; color:#fff; padding:20px; margin:20px 0; border-radius:10px">
            <p class="">
               <strong>Invoice ID : </strong>
               <span>'.$invoicedata->id. '</span>
            </p>
             
            <div class="" style="display:flex; align-items:center">
               <div style="white-space:nowrap">
                  <strong>Order Status: </strong>
               </div>

              <div>
                    <span class="completed_order" style="padding: 6px;  border-radius: 5px;">';
                     // if($invoicedata->status == 'p') { 
                      $order_items_table.=  'Completed';
                     // }
                     $order_items_table.= '
                   </span>
              </div>
            </div>
            </div>
         </div>
         
      </div>
      </div>
          <style>

          </style>';

    // end oderdata
  $placeholders = ['{{order_user_name}}','{{order_items_table}}','{{company_logo}}'];
  $replacements = [$order_user_name, $order_items_table, $company_logo];
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
  $fromUser = core_user::get_support_user();;
  $subject = $gettemplate->email_subject;
  $emailuser = (object) [];
  $emailuser->email = $USER->email;
  // $emailuser->email = "suneet.sharma695@gmail.com";
  $emailuser->firstname = $USER->firstname;
  $emailuser->lastname= $USER->lastname;
  $emailuser->maildisplay = true;
  $emailuser->mailformat = 1; // 0 (zero) text-only emails, 1 (one) for HTML/Text emails.
  $emailuser->id = 1;
  $emailuser->firstnamephonetic = false;
  $emailuser->lastnamephonetic = false;
  $emailuser->middlename = false;
  $emailuser->username = false;
  $emailuser->alternatename = false;
  if($emailtemplatedata_user){
    email_to_user($emailuser,$fromUser, $subject, $message = '', $messagehtml);
    // echo "email send to ".$USER->email;

    // echo "mail send to individual";

  }
}
function course_complte_email_to_user($courseid){
  global $DB, $USER,$CFG;
  $companyid = iomad::get_my_companyid(context_system::instance());

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
    $company_logo = "<img src='".$logourl."' style='width: 100px; height: 100px; object-fit: contain;'>";
  }else{
    $company_logo = "";
  }
  $coursedata = $DB->get_record("course",array("id"=>$courseid));
  // print_r($coursedata);

 $user_display_name = fullname($USER);
 $course_name = $coursedata->fullname;
 $checkedcoursescorm = $DB->get_record("scorm",array("course"=>$courseid));
 if($checkedcoursescorm){
    $checkedcoursescorm = $DB->get_record_sql("SELECT * FROM {scorm_scoes_track} WHERE userid = ? AND scormid =? AND element LIKE ?",array($USER->id,$checkedcoursescorm->id,'cmi.core.score.raw'));
    if($checkedcoursescorm->value >= 70 ){
      $course_grade = $checkedcoursescorm->value .' (PASSED)';
    }else{
      $course_grade = $checkedcoursescorm->value .' (FAILED)';
    }
 }else{
    $course_grade = "Grade Not Set";
 }


  $placeholders = ['{{user_display_name}}','{{course_name}}','{{company_logo}}','{{course_grade}}'];
  $replacements = [$user_display_name, $course_name, $company_logo, $course_grade];
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
  $fromUser = core_user::get_support_user();;
  $subject = $gettemplate->email_subject;
  $emailuser = (object) [];
  $emailuser->email = $USER->email;
  // $emailuser->email = "suneet.sharma695@gmail.com";
  $emailuser->firstname = $USER->firstname;
  $emailuser->lastname= $USER->lastname;
  $emailuser->maildisplay = true;
  $emailuser->mailformat = 1; // 0 (zero) text-only emails, 1 (one) for HTML/Text emails.
  $emailuser->id = 1;
  $emailuser->firstnamephonetic = false;
  $emailuser->lastnamephonetic = false;
  $emailuser->middlename = false;
  $emailuser->username = false;
  $emailuser->alternatename = false;
  if($emailtemplatedata_user){
    // email_to_user($emailuser,$fromUser, $subject, $message = '', $messagehtml);

    // echo "mail send";

  }
}
?>