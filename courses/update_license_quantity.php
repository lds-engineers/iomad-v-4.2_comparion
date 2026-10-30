<?php
require_once(__DIR__ . '/../config.php');
global $DB, $CFG, $USER;

header('Content-Type: application/json');
require_once($CFG->dirroot. '/blocks/iomad_company_admin/lib.php');
// $companyid = iomad::get_my_companyid(context_system::instance());
$currentHostname = $_SERVER['HTTP_HOST']; // updated variable name
$companyRecord = $DB->get_record_sql("SELECT * FROM {company} WHERE hostname LIKE ?",[$currentHostname]);
$companyid = $companyRecord->id;
// echo "<pre>";
// print_r($_POST[]);
// echo "</pre>";
// die;
$items = $_POST['items'] ?? [];

foreach ($items as $id => $data) {
    $id = (int)$id;
    $nlicenses = isset($data['nlicenses']) ? (int)$data['nlicenses'] : 0;
    $itemid = isset($data['itemid']) ? (int)$data['itemid'] : 0;
    $groupid = isset($data['groupid']) ? (int)$data['groupid'] : 0;
    $coursename = isset($data['coursename']) ? $data['coursename'] : '';
    $available_seats = isset($data['available_seats']) ? (int)$data['available_seats'] : 0;

    if ($nlicenses <= 0 || empty($id)) {
        continue;
    }

    $invoiceitem = new \stdClass;
    $invoiceitem->id = $id;
    $block = \block_iomad_commerce\helper::get_license_block($itemid, $nlicenses);
    if ($block) {
        if ($available_seats && $nlicenses > $available_seats) {
            redirect(
                new moodle_url($CFG->wwwroot . '/courses/guestcartitems.php'),
                "Please try less than {$nlicenses} seats, only {$available_seats} seats are allowed for the course {$coursename}.",
                null,
                \core\output\notification::NOTIFY_ERROR
            );
        }
        if ($nlicenses == 1) {
            $invoiceitem->invoiceableitemtype = 'singlepurchase';
        } else {
            $invoiceitem->invoiceableitemtype = 'licenseblock';
        }
        $invoiceitem->license_allocation = $nlicenses;
         // suneet code overwrite price
        $freecoursdata = $DB->get_record("company_free_product", array("product_id" => $itemid));
        if($freecoursdata){
             $invoiceitem->price = 0;
        }else{
            $invoiceitem->price = $block->price;
        }
        // $invoiceitem->price = $block->price;
        $invoiceitem->currency = $block->currency;
        $DB->update_record('guest_cart_items', $invoiceitem); // chatgpt: this updates the existing record
    }
}

$invoiceid = $SESSION->basketid;
$couponinvoice = $DB->get_record('coupon_invoice', ['guestinvoiceid' => $invoiceid]);

if($couponinvoice){
    $now = time();
    $coupon = $DB->get_record('local_coupon', ['id' => $couponinvoice->couponid]);
    $userdata = $DB->get_record('company_users', ['companyid' => $coupon->companyid]);

    if (empty($coupon->companyid) || $coupon->companyid != $companyid) {
            redirect(new moodle_url("/courses/guestcartitems.php"), 'Coupon company is not matched');
    }

    if (!$coupon) {
        redirect(new moodle_url('/courses/guestcartitems.php'), 'Coupon not found');
    }
    if ($coupon->status == 0) {
        redirect(new moodle_url('/courses/guestcartitems.php'), 'Coupon is not active');
    }
    if (!empty($coupon->starts_at) && $now < $coupon->starts_at) {
        redirect(new moodle_url('/courses/guestcartitems.php'), 'This coupon is not yet active.');
    }
    if (!empty($coupon->expires_at) && $now > $coupon->expires_at) {
        redirect(new moodle_url('/courses/guestcartitems.php'), 'This coupon has expired.');
    }
    $invoicedata = $DB->get_record('guest_cart', ['id' => $SESSION->basketid]);
    if (!$invoicedata) {
        redirect(new moodle_url('/courses/guestcartitems.php'), 'Invoice not found');
    }

    $invoiceitems = $DB->get_records('guest_cart_items', ['guestcartid' => $invoicedata->id]);
    if (empty($invoiceitems)) {
        redirect(new moodle_url('/courses/guestcartitems.php'), 'No products in basket.');
    }

    if ($coupon->allow_all_users != 1) {
        $couponuser = $DB->get_record('local_coupon_user', ['couponid' => $coupon->id, 'userid' => $USER->id]);
        if (!$couponuser) {
            redirect(new moodle_url('/courses/guestcartitems.php'), 'This coupon is not available for you');
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
            redirect(new moodle_url('/courses/guestcartitems.php'), 'This coupon is not assigned to any products.');
        }

        foreach ($invoiceitems as $item) {
            if (in_array($item->invoiceableitemid, $validproductids)) {
                $eligibleTotal += $item->price;
            }
        }
    }

    if ($eligibleTotal < $coupon->min_amount) {
        redirect(new moodle_url('/courses/guestcartitems.php'), "To use this coupon, you must have at least {$coupon->min_amount} in eligible products.");
    }

    $discountamount = 0;
    if ($coupon->type === 'fixed') {
        $discountamount = $coupon->discount_amount;
    } elseif ($coupon->type === 'percent') {
        $discountamount = ($coupon->discount_amount / 100) * $eligibleTotal;
    } else {
        redirect(new moodle_url('/courses/guestcartitems.php'), 'Invalid coupon type');
    }

    $checkifExist = $DB->get_record('coupon_invoice', ['guestinvoiceid' => $invoicedata->id]);

    $record = new stdClass();
    $record->couponid = $coupon->id;
    $record->guestinvoiceid = $invoicedata->id;
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
redirect(
    new moodle_url($CFG->wwwroot . '/courses/guestcartitems.php'),
    "Quantity updated successfully",
    null,
    \core\output\notification::NOTIFY_SUCCESS
);
