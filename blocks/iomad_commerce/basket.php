<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package   block_iomad_commerce
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../config.php');
require_once("$CFG->libdir/formslib.php");
require_once(dirname(__FILE__) . '/../iomad_company_admin/lib.php');
redirect($CFG->wwwroot."/blocks/learnpeek_shop/basket.php");
die;

\block_iomad_commerce\helper::require_commerce_enabled();

$companyid = iomad::get_my_companyid(context_system::instance());
$remove = optional_param('remove', 0, PARAM_INT);

global $DB, $SESSION;
// Correct the navbar.
// Set the name for the page.
$linktext = get_string('course_shop_title', 'block_iomad_commerce');
// Set the url.
$linkurl = new moodle_url('/blocks/iomad_commerce/shop.php');

// Page stuff:.
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);
$PAGE->navbar->add($linktext, $linkurl);
$PAGE->navbar->add(get_string('basket', 'block_iomad_commerce'));


$coupon = $DB->get_records('local_coupon');

class applyCoupon extends moodleform
{
    function definition()
    {
        $mform = $this->_form;

        $mform->addElement('text', 'coupon', 'Coupon', ['placeholder' => "Enter Coupon"]);
        $mform->setType('coupon', PARAM_TEXT);
        $mform->addRule('coupon', null, null, null, 'client');

        $this->add_action_buttons(true, 'Apply');
    }
}

$form = new applyCoupon();
if ($form->is_cancelled()) {
    redirect(new moodle_url('blocks/iomad_commerce/basket.php'));
} else if ($data = $form->get_data()) {

    $now = time();
    $couponCode = trim($data->coupon);
    $coupon = $DB->get_record('local_coupon', ['code' => $couponCode]);
    $userdata = $DB->get_record('company_users', ['companyid'=> $coupon->companyid]);

    if (!$coupon) {
        redirect(new moodle_url('/blocks/iomad_commerce/basket.php'), 'Coupon not found');
    }

    if(!$userdata){
        redirect(new moodle_url("/blocks/learnpeek_shop/basket.php"), 'Company is not match');
    } 

    // if (empty($userdata) || $userdata != $companyid) {
    //     redirect(new moodle_url("/blocks/learnpeek_shop/basket.php"), 'Coupon is not available for you');
    // }

    // Check start and expiry
    if (!empty($coupon->starts_at) && $now < $coupon->starts_at) {
        redirect(new moodle_url('/blocks/iomad_commerce/basket.php'), 'This coupon is not yet active.');
    }

    if (!empty($coupon->expires_at) && $now > $coupon->expires_at) {
        redirect(new moodle_url('/blocks/iomad_commerce/basket.php'), 'This coupon has expired.');
    }

    // Get basket
    $invoicedata = $DB->get_record('invoice', ['id' => $SESSION->basketid]);
    if (!$invoicedata) {
        redirect(new moodle_url('/blocks/iomad_commerce/basket.php'), 'Invoice not found');
    }

    $invoiceitems = $DB->get_records('invoiceitem', ['invoiceid' => $invoicedata->id]);
    if (empty($invoiceitems)) {
        redirect(new moodle_url('/blocks/iomad_commerce/basket.php'), 'No products in basket.');
    }

    // Check coupon user eligibility
    $couponuser = $DB->get_record('local_coupon_user', ['couponid' => $coupon->id, 'userid' => $invoicedata->userid]);
    if (!$couponuser) {
        redirect(new moodle_url("/blocks/iomad_commerce/basket.php"), 'This coupon is not available for you');
    }

    // Max uses check
     $totalusage = $DB->count_records('coupon_invoice', ['couponid' => $coupon->id]);
    if ($totalusage >= $coupon->max_uses) {
        redirect(new moodle_url('/blocks/iomad_commerce/basket.php'), 'Coupon usage limit reached.');
    }
 
    $totalusageuser = $DB->count_records('coupon_invoice', ['couponid' => $coupon->id, 'userid' => $USER->id]);
     if ($totalusageuser >= $coupon->max_uses_user) {
        redirect(new moodle_url('/blocks/iomad_commerce/basket.php'), 'Coupon user usage limit reached.');
    }

    // Get product restrictions for this coupon
    $couponproducts = $DB->get_records('local_coupon_product', ['couponid' => $coupon->id]);
    $validproductids = array_map(fn($cp) => $cp->course_shopsetting_id, $couponproducts);

    if (empty($validproductids)) {
        redirect(new moodle_url('/blocks/iomad_commerce/basket.php'), 'This coupon is not assigned to any products.');
    }


    // Calculate eligible amount
    $eligibleTotal = 0;
    foreach ($invoiceitems as $item) {
        if (in_array($item->invoiceableitemid, $validproductids)) {
            $eligibleTotal += $item->price;
        }
    }

    // Minimum order check
    if ($eligibleTotal <= $coupon->min_amount) {
        redirect(new moodle_url('/blocks/iomad_commerce/basket.php'), "To use this coupon, you must have at least ${$coupon->min_amount} in eligible products.");
    }

    // Calculate discount
    if ($coupon->type == 'fixed') {
        $discountamount = $coupon->discount_amount;
    } elseif ($coupon->type == 'percent') {
        $discountamount = ($coupon->discount_amount / 100) * $eligibleTotal;
    } else {
        redirect(new moodle_url('/blocks/iomad_commerce/basket.php'), 'Invalid coupon type');
    }

    // TODO: Store the applied coupon and discount info (optional)

    redirect(new moodle_url('/blocks/iomad_commerce/basket.php'), "Coupon applied successfully. Discount: $" . number_format($discountamount, 2));
}

echo $OUTPUT->header();

flush();

if (!empty($SESSION->basketid)) {
    if ($remove) {
        // Before deleting
        // check that the record to be removed is on the current user's basket
        // (and not on an invoice or on somebody else's basket).
        if (
            $DB->record_exists_sql('SELECT ii.id
                                      FROM {invoiceitem} ii
                                     WHERE ii.id = :toberemoved
                                       AND
                                    EXISTS ( SELECT id
                                             FROM {invoice} i
                                             WHERE i.id = :basketid
                                             AND i.status = :status
                                             AND i.id = ii.invoiceid
                                             )', array('basketid' => $SESSION->basketid, 'status' => \block_iomad_commerce\helper::INVOICESTATUS_BASKET, 'toberemoved' => $remove))
        ) {
            $DB->delete_records('invoiceitem', array('id' => $remove));
        }
    }

    $baskethtml = \block_iomad_commerce\helper::get_basket_html(1);

    if ($baskethtml) {
        echo $baskethtml;
        $form->display();
        echo "<p>";
        if (!\block_iomad_commerce\helper::check_multiple_currencies($SESSION->basketid)) {
            echo '<a href="' . new moodle_url($CFG->wwwroot . '/blocks/iomad_commerce/checkout.php') . '" class="btn btn-primary">' . get_string('checkout', 'block_iomad_commerce') .
                '</a> ' . get_string('or', 'block_iomad_commerce');
        }
        echo ' <a href="' . new moodle_url($CFG->wwwroot . '/blocks/iomad_commerce/shop.php') . '" class="btn btn-secondary">' . get_string('returntoshop', 'block_iomad_commerce') .
            '</a></p> ';
    } else {
        echo '<p>' . get_string('emptybasket', 'block_iomad_commerce') . '</p>';
        echo '<p><a href="' . new moodle_url($CFG->wwwroot . '/blocks/iomad_commerce/shop.php') . '" class="btn btn-secondary">' . get_string('returntoshop', 'block_iomad_commerce') .
            '</a></p> ';
    }
} else {
    echo '<p>' . get_string('emptybasket', 'block_iomad_commerce') . '</p>';
}

echo $OUTPUT->footer();
