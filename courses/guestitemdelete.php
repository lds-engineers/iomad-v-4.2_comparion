<?php
require("../config.php");

global $DB, $SESSION;

function updatecouponincart($couponid, $sessionid, $guestcartid)
{
    global $DB, $SESSION;
    $now = time();

    $coupon = $DB->get_record('local_coupon', ['id' => (int)$couponid]);
    $userdata = $DB->get_record('company_users', ['companyid' => $coupon->companyid]);

    if (empty($coupon)) {
        redirect(new moodle_url($CFG->wwwroot . "/courses/guestcartitems.php"), 'Coupon not found');
    }

    if (!$userdata) {
        redirect(new moodle_url("/blocks/learnpeek_shop/basket.php"), 'Coupon is not available for you');
    }

    if (!$coupon) {
        redirect(new moodle_url($CFG->wwwroot . "/courses/guestcartitems.php"), 'Coupon not found');
    }
    if ($coupon->status == 0) {
        redirect(new moodle_url($CFG->wwwroot . "/courses/guestcartitems.php"), 'Coupon is not active');
    }
    if (!empty($coupon->starts_at) && $now < $coupon->starts_at) {
        redirect(new moodle_url($CFG->wwwroot . "/courses/guestcartitems.php"), 'This coupon is not yet active.');
    }
    if (!empty($coupon->expires_at) && $now > $coupon->expires_at) {
        redirect(new moodle_url($CFG->wwwroot . "/courses/guestcartitems.php"), 'This coupon has expired.');
    }

    $invoicedata = $DB->get_record('guest_cart', ['id' => $SESSION->basketid]);

    if (!$invoicedata) {
        redirect(new moodle_url($CFG->wwwroot . "/courses/guestcartitems.php"), 'Invoice not found');
    }

    $invoiceitems = $DB->get_records('guest_cart_items', ['guestcartid' => $invoicedata->id]);
    if (empty($invoiceitems)) {
        redirect(new moodle_url($CFG->wwwroot . "/courses/guestcartitems.php"), 'No products in basket.');
    }

    if (!$coupon->allow_all_users) {
        $couponuser = $DB->get_record('local_coupon_user', ['couponid' => $coupon->id, 'sessionid' => $invoicedata->sessionid]);
        if (!$couponuser) {
            redirect(new moodle_url($CFG->wwwroot . "/courses/guestcartitems.php"), 'This coupon is not available for you');
        }
    }

    $eligibleTotal = 0;
    if ($coupon->allow_all_products) {
        foreach ($invoiceitems as $item) {
            $eligibleTotal += $item->price * $item->license_allocation;
        }
    } else {
        $couponproducts = $DB->get_records('local_coupon_product', ['couponid' => $coupon->id]);
        $validproductids = array_map(fn($cp) => $cp->course_shopsetting_id, $couponproducts);

        if (empty($validproductids)) {
            redirect(new moodle_url($CFG->wwwroot . "/courses/guestcartitems.php"), 'This coupon is not assigned to any products.');
        }

        foreach ($invoiceitems as $item) {
            if (in_array($item->invoiceableitemid, $validproductids)) {
                $eligibleTotal += $item->price;
            }
        }
    }

    if ($eligibleTotal < $coupon->min_amount) {
        redirect(new moodle_url($CFG->wwwroot . "/courses/guestcartitems.php"), "To use this coupon, you must have at least {$coupon->min_amount} in eligible products.");
    }

    $discountamount = 0;
    if ($coupon->type === 'fixed') {
        $discountamount = $coupon->discount_amount;
    } elseif ($coupon->type === 'percent') {
        $discountamount = ($coupon->discount_amount / 100) * $eligibleTotal;
    } else {
        redirect(new moodle_url($CFG->wwwroot . "/courses/guestcartitems.php"), 'Invalid coupon type');
    }

    $checkifExist = $DB->get_record('coupon_invoice', ['guestinvoiceid' => $guestcartid, 'sessionid' => $sessionid]);

    $record = new stdClass();
    $record->couponid = $coupon->id;
    $record->sessionid = $sessionid;
    $record->guestinvoiceid = (int)$guestcartid;
    $record->discount_amount = $discountamount;
    $record->eligible_total = $eligibleTotal;
    $record->type = $coupon->type;
    $record->rate_or_value = $coupon->discount_amount;
    $record->applied_on = time();

    if ($checkifExist) {
        $record->id = $checkifExist->id;
        $DB->update_record('coupon_invoice', $record);
    } else {
        $DB->insert_record('coupon_invoice', $record);
    }
}


// Set correct content type for JSON response

// Set correct content type for JSON response
header('Content-Type: application/json');

$action = required_param('action', PARAM_ALPHA); 
$id     = required_param('id', PARAM_INT);
$cartid = required_param('cartid', PARAM_INT);

require_sesskey();


if($action !== "deleteitem"){
     echo json_encode(['status' => false, 'message' => "Invalid action"]);
    exit;
}

 
    if (!$DB->record_exists("guest_cart", ['id' => $cartid])) {
        echo json_encode(['status' => false, 'message' => 'Cart not found']);
        exit;
    }

    if (!$DB->record_exists("guest_cart_items", ['id' => $id, 'guestcartid' => $cartid])) {
        echo json_encode(['status' => false, 'message' => 'Item not found in this cart']);
        exit;
    }

    $DB->delete_records("guest_cart_items", ['id' => $id, 'guestcartid' => $cartid]);

    $guestcart = $DB->get_record('guest_cart', ['id' => $cartid]);
    $couponinvoice = $DB->get_record("coupon_invoice", [
        'guestinvoiceid' => $guestcart->id,
        'sessionid'      => $guestcart->sessionid
    ]);

    if (!empty($couponinvoice)) {
        $DB->delete_records("coupon_invoice", [
            'guestinvoiceid' => $guestcart->id,
            'sessionid'      => $guestcart->sessionid
        ]);
        // updatecouponincart($couponinvoice->couponid, $couponinvoice->sessionid, $couponinvoice->guestinvoiceid);
    }

    echo json_encode(['status' => true, 'message' => 'Item deleted successfully']);
    exit;
 