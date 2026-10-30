<?php
require("../config.php");

global $DB, $SESSION;
header('Content-Type: application/json');

$action = required_param('action', PARAM_ALPHA);
$cartid = required_param('cartid', PARAM_INT);
$removecouponid = required_param('removecouponid', PARAM_INT); 

require_sesskey();

if($action !== "removecoupon"){
	 echo json_encode(['status' => false, 'message' => "Invalid action"]);
    exit;
}

 if (!$DB->record_exists("guest_cart", ['id' => $cartid])) {
        echo json_encode(['status' => false, 'message' => 'Cart not found']);
        exit;
    }

    if (!$DB->record_exists("coupon_invoice", ['id' => $removecouponid, 'guestinvoiceid' => $cartid])) {
        echo json_encode(['status' => false, 'message' => 'Coupon not found in this cart']);
        exit;
    }

    $DB->delete_records("coupon_invoice", ['id' => $removecouponid, 'guestinvoiceid' => $cartid]);

    echo json_encode(['status' => true, 'message' => 'Coupon deleted successfully']);
    exit;
