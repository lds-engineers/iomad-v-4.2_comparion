<?php
require_once('header.php');
require_once($CFG->dirroot . '/blocks/iomad_company_admin/lib.php');

\block_iomad_commerce\helper::require_commerce_enabled();

$itemid      = required_param('itemid', PARAM_INT);
$nlicenses   = optional_param('nlicenses', 0, PARAM_INT);
$licenses    = optional_param('licenses', 0, PARAM_INT);
$groupid    = optional_param('groupid', 0, PARAM_INT);
$event_type    = optional_param('event_type', 0, PARAM_INT);
$available_seats    = optional_param('available_seats', 0, PARAM_INT);
$currentHostname = $_SERVER['HTTP_HOST']; // updated variable name
$companyRecord = $DB->get_record_sql("SELECT * FROM {company} WHERE hostname LIKE ?",[$currentHostname]);
$companyid = $companyRecord->id;
// echo 'itemid    ---------- '.$itemid.'<br>';
// echo 'nlicenses    ---------- '.$nlicenses.'<br>';
// echo 'licenses     ---------- '.$licenses.'<br>';
// echo 'groupid      ---------- '.$groupid.'<br>';
// echo 'event_type   ---------- '.$event_type.'<br>';
// echo 'available_seats     ---------- '.$available_seats.'<br>';
// echo "basket id  - $SESSION->basketid";
// die('stop');

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
        redirect(new moodle_url("/courses/guestcartitems.php"), 'Coupon Company not found');
    }

    if (empty($coupon->companyid) || $coupon->companyid != $companyid) {
        redirect(new moodle_url("/courses/guestcartitems.php"), 'Coupon Company not matched');
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


if ($licenses && !$nlicenses) {
    redirect(new moodle_url($CFG->wwwroot . '/blocks/learnpeek_shop/item.php', ['licenseformempty' => 1, 'id' => $itemid]));
}

$PAGE->set_url(new moodle_url($CFG->wwwroot . "/courses/guestcart.php", ['itemid' => $itemid, 'nlicenses' => $nlicenses, 'groupid' => $groupid]));
$PAGE->set_context(context_system::instance());
$context = $PAGE->context;

$newsessionid = session_id();

// Get or create basket.
if (!empty($SESSION->basketid)) {
    if (!$basket = $DB->get_record('guest_cart', array('id' => $SESSION->basketid, 'status' => \block_iomad_commerce\helper::INVOICESTATUS_BASKET), '*')) {
        $basket = new stdClass();
        $basket->sessionid = $newsessionid;
        $basket->status = \block_iomad_commerce\helper::INVOICESTATUS_BASKET;
        $basket->date = time();
        $basket->id = $DB->insert_record('guest_cart', $basket);
        $SESSION->basketid = $basket->id;
    }
} else {
    $basket = new stdClass();
    $basket->sessionid = $newsessionid;
    $basket->status = \block_iomad_commerce\helper::INVOICESTATUS_BASKET;
    $basket->date = time();
    $basket->id = $DB->insert_record('guest_cart', $basket);
    $SESSION->basketid = $basket->id;
}

$invoiceitem = new stdClass();
$invoiceitem->guestcartid = $basket->id;
$invoiceitem->invoiceableitemid = $itemid;

if ($nlicenses) {
    if ($block = \block_iomad_commerce\helper::get_license_block($itemid, $nlicenses)) {
        $invoiceitem->currency = $block->currency;
        $invoiceitem->price = $block->price;
        $invoiceitem->invoiceableitemtype = 'licenseblock';
        $invoiceitem->license_allocation = $nlicenses;
        $invoiceitem->license_validlength = $block->validlength;
        $invoiceitem->license_shelflife = $block->shelflife;
        if ($available_seats) {
            if ($invoiceitem->license_allocation > $available_seats) {
                if ($event_type = "trainingevent") {
                    redirect(new moodle_url($CFG->wwwroot . '/blocks/learnpeek_shop/tevent.php?itemid=' . $itemid), 'Please Try less than ' . $invoiceitem->license_allocation . ' licenses, only ' . $available_seats . ' seats are allowed to purchase.', null, \core\output\notification::NOTIFY_ERROR);
                } else {
                    redirect(new moodle_url($CFG->wwwroot . '/blocks/learnpeek_shop/event.php?itemid=' . $itemid), 'Please Try less than ' . $invoiceitem->license_allocation . ' licenses, only ' . $available_seats . ' seats are allowed to purchase.', null, \core\output\notification::NOTIFY_ERROR);
                }
            }
        }
    }
} else {
    // Single purchase.
    if ($course = $DB->get_record('course_shopsettings', array('id' => $itemid), '*', MUST_EXIST)) {

        $invoiceitem->currency = $course->single_purchase_currency;
        $invoiceitem->price = $course->single_purchase_price;
        $invoiceitem->invoiceableitemtype = 'singlepurchase';
        $invoiceitem->license_allocation = 1;
        $invoiceitem->license_validlength = $course->single_purchase_validlength;
        $invoiceitem->license_shelflife = 0;
    }
}
if ($groupid) {
    $invoiceitem->groupid = $groupid;
}

// suneet code 20092025 overwrite price
$freecoursdata = $DB->get_record("company_free_product", array("product_id" => $itemid));
if($freecoursdata){
     $invoiceitem->price = 0;
}
// suneet code 20092025
//Fixed duplicate product in card 
if(!empty($SESSION->basketid)){
    if($groupid){
        $checkadditem = $DB->get_record_sql("SELECT ii.* FROM {guest_cart_items} as ii INNER JOIN {guest_cart} as i ON i.id = ii.guestcartid WHERE i.id = ? AND ii.invoiceableitemid = ? AND ii.groupid = ?",[$SESSION->basketid,$itemid,$groupid]);
    }else{
        $checkadditem = $DB->get_record_sql("SELECT ii.* FROM {guest_cart_items} as ii INNER JOIN {guest_cart} as i ON i.id = ii.guestcartid WHERE i.id = ? AND ii.invoiceableitemid = ?",[$SESSION->basketid,$itemid]);
    }
    if($checkadditem){
        $checkpriceblocks = $DB->get_records('course_shopblockprice', ['itemid' => $checkadditem->invoiceableitemid], 'price_bracket_start');
        $totalpriceblocks = count($checkpriceblocks);
        if($totalpriceblocks > 0){
            $newquantity = $invoiceitem->license_allocation + $checkadditem->license_allocation;
            $getupdatedprice = $DB->get_record_sql("SELECT price FROM {course_shopblockprice} WHERE itemid = ? AND price_bracket_start <= ? ORDER BY price_bracket_start DESC LIMIT 1",[$itemid,$newquantity]);

            $updatedinvoiceitem = new \stdClass;
            $updatedinvoiceitem ->id = $checkadditem->id;
            $updatedinvoiceitem ->license_allocation = $newquantity;
            $updatedinvoiceitem ->price = $getupdatedprice->price;
            // echo "<pre>";
            // print_r($checkadditem);
            // print_r($updatedinvoiceitem);        

            $updatedinvoiceitemdata = $DB->update_record('guest_cart_items', $updatedinvoiceitem);
            if($updatedinvoiceitemdata){
                redirect(new moodle_url($CFG->wwwroot . '/courses/guestcartitems.php'));
                exit();
            }
                exit();
        }else{
            redirect(new moodle_url($CFG->wwwroot . '/courses/guestcartitems.php'), 'Multiple Seats Not Allow for this product' . $course->name . '', null, \core\output\notification::NOTIFY_ERROR);
             exit();

        }
        // redirect(new moodle_url($CFG->wwwroot . '/courses/guestcartitems.php'));
        // exit();
    }
}
//end
$aslid = $DB->insert_record('guest_cart_items', $invoiceitem);
$guestcart = $DB->get_record('guest_cart', ['id' => $SESSION->basketid]);
$couponinvoice = $DB->get_record("coupon_invoice", ['guestinvoiceid' => $guestcart->id, 'sessionid' => $guestcart->sessionid]);
if(!empty($couponinvoice)){
    updatecouponincart($couponinvoice->couponid, $couponinvoice->sessionid, $couponinvoice->guestinvoiceid);
}

redirect(new moodle_url($CFG->wwwroot . '/courses/guestcartitems.php'));
