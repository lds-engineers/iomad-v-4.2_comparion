<?php
use core\notification;
function update_group_seats(\core\event\group_updated $event){
	global  $USER, $CFG, $DB,$PAGE;
	$updategroup = new stdClass();
	$updategroup->id = $event->objectid;
	$updategroup->member_allow = $_POST['total_seat'];
	$updategrouprecords=$DB->update_record('groups', $updategroup);
}

function add_group_seats(\core\event\group_created $event){
	global  $USER, $CFG, $DB,$PAGE;
	$updategroup = new stdClass();
	$updategroup->id =  $event->objectid;
	$updategroup->member_allow = $_POST['total_seat'];
	$updategrouprecords=$DB->update_record('groups', $updategroup);
}

function user_loggedin(\core\event\user_loggedin $event) {
    global $DB, $CFG, $USER,$SESSION;
    $password ='';
    if (!empty($_POST['password'])) {
        $password = $_POST['password'];
    } elseif (!empty($_POST['create_password'])) {
        $password = $_POST['create_password'];
    }
    if ($password) {
	    $getuserpassword = $DB->get_record_sql("SELECT * FROM {sso_user_password} WHERE userid = ?",array($USER->id));
	    if($getuserpassword){
	    	$updatpass = new stdClass();
	        $updatpass->id    	= $getuserpassword->id;
	        $updatpass->password      = md5($password);
	        $updatpass->updated_date  = time();
	        $updatedpass = $DB->update_record("sso_user_password", $updatpass);
	    }else{
	    	$insertpass = new stdClass();
	        $insertpass->userid    	= $USER->id;
	        $insertpass->password      = md5($password);
	        $insertpass->updated_date  = time();
	        $insertedpass = $DB->insert_record("sso_user_password", $insertpass);
	    }
    }
    $guestsession_id = $SESSION->basketid;
    if($guestsession_id){
    	$guestcart = $DB->get_record_sql('SELECT * FROM {guest_cart} WHERE id = ?',[$guestsession_id]);
    	if($guestcart && empty($guestcart->invoiceid)){
    		$curentcompany = $DB->get_record('company', array('hostname' => $_SERVER["SERVER_NAME"]));
    		$companyid = $curentcompany->id;
    		$invoiceRecord = new stdClass();
	        $invoiceRecord->userid    = $USER->id;
	        $invoiceRecord->date      = time();
	        $invoiceId = $DB->insert_record("invoice", $invoiceRecord);
	        if($invoiceId){
	        	// add invoice id in guest cart
	            $updatechartrecords = new stdClass();
	            $updatechartrecords->id        = $guestcart->id;
	            $updatechartrecords->invoiceid = $invoiceId;
	            $updatedchartrecords = $DB->update_record('guest_cart', $updatechartrecords);


	            // suneet code for allow user to purchase product according to user role
	            $managerrole = $DB->get_record_sql('SELECT * FROM {company_users} WHERE userid = ? AND companyid = ?', array($USER->id,$companyid));
				$managertype = $managerrole->managertype;
				$ismanager = $managertype != 0;
				

	            $guestCartItems = $DB->get_records_sql("SELECT * FROM {guest_cart_items} WHERE guestcartid = ?", [$guestcart->id]);
	            $additionalmessage = '';
	            $eligibleTotal = 0;
	            foreach ($guestCartItems as $cartItem) {
	                $invoiceItemRecord = new stdClass();
	                $invoiceItemRecord->invoiceid              = $invoiceId; // fixed from $invoicerecord->id
	                $invoiceItemRecord->invoiceableitemid      = $cartItem->invoiceableitemid;
	                // $invoiceItemRecord->invoiceableitemtype    = $cartItem->invoiceableitemtype;
	                $invoiceItemRecord->quantity               = $cartItem->quantity;
	                $invoiceItemRecord->currency               = $cartItem->currency;
	                $invoiceItemRecord->price                  = $cartItem->price;
	                $invoiceItemRecord->license_allocation     = $cartItem->license_allocation;
	                $invoiceItemRecord->license_validlength    = $cartItem->license_validlength;
	                $invoiceItemRecord->license_shelflife      = $cartItem->license_shelflife;
	                $invoiceItemRecord->groupid                = $cartItem->groupid;
	                $invoiceItemRecord->processed              = $cartItem->processed;
	                if($ismanager){
						$checkpriceblocks = $DB->get_records('course_shopblockprice', ['itemid' => $cartItem->invoiceableitemid], 'price_bracket_start');
				        $totalpriceblocks = count($checkpriceblocks);
				        $productname = $DB->get_record("course_shopsettings",array("id"=>$cartItem->invoiceableitemid));
				        if ($totalpriceblocks <= 0) {
				            $additionalmessage1 = "Product {$productname->name} is available only for individual users.<br>";
				            notification::add($additionalmessage1, notification::WARNING);
				             continue;
				        }else{
				        	// $additionalmessage .= "Product <b>{$product->name}</b> is a license block.<br>";

				            if ($cartItem->invoiceableitemtype == "singlepurchase") {
				                $invoiceItemRecord->invoiceableitemtype = "licenseblock";
				            } else {
				                $invoiceItemRecord->invoiceableitemtype = $cartItem->invoiceableitemtype;
				            }
				        }
					}else{
						if ($cartItem->invoiceableitemtype == "licenseblock") {
							$productname = $DB->get_record("course_shopsettings",array("id"=>$cartItem->invoiceableitemid));
							$checkpriceblocks = $DB->get_record('course_shopblockprice', ['itemid' => $cartItem->invoiceableitemid,'price_bracket_start' => 1]);
							$invoiceItemRecord->invoiceableitemtype = "singlepurchase";
							$invoiceItemRecord->license_allocation = 1;
							$invoiceItemRecord->price = $checkpriceblocks->price;
							$additionalmessage1 = "Product {$productname->name} Mutipule quantity are not allow for individual role users.<br>";
				            notification::add($additionalmessage1, notification::WARNING);
						}else{
							$invoiceItemRecord->invoiceableitemtype    = $cartItem->invoiceableitemtype;
						}
					}
					$eligibleTotal += $invoiceItemRecord->price * $invoiceItemRecord->license_allocation;
	                $updatedcad = $DB->insert_record("invoiceitem", $invoiceItemRecord);
	                
	            }
	            //apply coupon
	            $getcoupon_invoice = $DB->get_record("coupon_invoice",["guestinvoiceid"=>$guestcart->id]);
	            if($getcoupon_invoice){
					$coupon = $DB->get_record('local_coupon', ['id' => $getcoupon_invoice->couponid]);
					if($coupon){
						if ($eligibleTotal > $coupon->min_amount) {
							$discountamount = 0;
                            if ($coupon->type === 'fixed') {
                                $discountamount = $coupon->discount_amount;
                            } elseif ($coupon->type === 'percent') {
                                $discountamount = ($coupon->discount_amount / 100) * $eligibleTotal;
                            }
			                $updatecouponinvoice = new stdClass();
		                    $updatecouponinvoice->discount_amount = $discountamount;
		                    $updatecouponinvoice->eligible_total = $eligibleTotal;
			                $updatecouponinvoice->id        = $getcoupon_invoice->id;
			                $updatecouponinvoice->userid        = $USER->id;
			                $updatecouponinvoice->invoiceid = $invoiceId;
			                $updatedcouponinvoice = $DB->update_record('coupon_invoice', $updatecouponinvoice);
						} else {
							// $DB->delete_records('coupon_invoice', ['invoiceid' => $invoiceId]);
                            notification::add("To use this coupon, you must have at least {$coupon->min_amount} in eligible products.", notification::WARNING);
						}
					}
	            }
	        }
	        $SESSION->basketid = $invoiceId;
    	}

    }
}

function user_password_updated(\core\event\user_password_updated $event) {
    global $DB, $CFG, $USER,$SESSION;

    if (!empty($_POST['newpassword1'])) {
        $newpass = md5($_POST['newpassword1']);
        $getuserpassword = $DB->get_record('sso_user_password', ['userid' => $USER->id]);

        if ($getuserpassword) {

            $updatpass = new stdClass();
            $updatpass->id           = $getuserpassword->id;
            $updatpass->password     = $newpass;
            $updatpass->updated_date = time();
            $updatedpass = $DB->update_record('sso_user_password', $updatpass);

        } else {

            $insertpass = new stdClass();
            $insertpass->userid       = $USER->id;
            $insertpass->password     = $newpass;
            $insertpass->updated_date = time();
            $insertedpass = $DB->insert_record('sso_user_password', $insertpass);

        }
    }
}