<?php
require_once('header.php');
// Setup the page.
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/courses/guestcartitems.php'));
$PAGE->set_pagelayout('base');
$PAGE->set_title(get_string('course_shop_title', 'block_learnpeek_shop'));
$PAGE->navbar->add(get_string('course_shop_title', 'block_learnpeek_shop'), new moodle_url('/courses'));
$linkurl = new moodle_url('/blocks/iomad_commerce/checkout.php');
$SESSION->wantsurl = $linkurl;
$currentHostname = $_SERVER['HTTP_HOST']; // updated variable name
$companyRecord = $DB->get_record_sql("SELECT * FROM {company} WHERE hostname LIKE ?",[$currentHostname]);

// set back and continue button
$continue = $CFG->wwwroot . '/courses';
$connected_shop_url = get_config(null, "connected_shop_url{$companyid}");
if (!empty($connected_shop_url)) {
    $continue = $connected_shop_url;
}

$is_hide = false;
if($companyRecord){
    // Get saved registration settings.
    $settings = $DB->get_record_sql(
        "SELECT companies
        FROM {registration_settings}
    ORDER BY id ASC
        LIMIT 1"
    );

    if ($settings && !empty($settings->companies)) {

        $blockedcompanies = json_decode($settings->companies, true);

        if (
            is_array($blockedcompanies) &&
            in_array($companyRecord->shortname, $blockedcompanies, true)
        ) {
            $is_hide = true;
        }
    }
}
if ($is_hide) {
    redirect(new moodle_url($CFG->wwwroot.'/my'),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
    die;
}
$companyid = $companyRecord->id;

$cartitemcount = $DB->get_record('guest_cart_items', ['guestcartid' => $SESSION->basketid]);

// Check if basket ID is set.
// if (empty($SESSION->basketid) || empty($cartitemcount)) {
//     redirect(new moodle_url($CFG->wwwroot . "/courses"), 'Cart is empty');
//     exit;
// }


if (empty($SESSION->basketid) || empty($cartitemcount)) {
    $connected_shop_url = get_config(null, "connected_shop_url{$companyid}");

    $continue = !empty($connected_shop_url)
        ? $connected_shop_url
        : new moodle_url('/courses');

    redirect($continue, 'Cart is empty');
}



$couponformCondition = $DB->get_record('coupon_invoice', ['guestinvoiceid' => $SESSION->basketid]);
class applyCoupon extends moodleform {
    function definition() {
        $mform = $this->_form;

        // Label
        $mform->addElement('html', '<label for="id_coupon" style="display:none; font-weight:bold; margin-bottom:5px; margin-left: 55px;">Coupon</label>');

        // Container start
        $mform->addElement('html', '<div class="flex-md-row flex-column" style="display:flex; gap:10px; align-items:center; justify-content:center;">');

        // Coupon input
        $mform->addElement('text', 'coupon', '', [
            'id' => 'id_coupon',
            'placeholder' => 'Enter Coupon Code',
            'style' => 'text-transform: uppercase; border-radius: 30px !important; padding: 0 20px; font-size: 15px; margin-right: 10px;     height: 48px;'
        ]);
        $mform->setType('coupon', PARAM_TEXT);
        $mform->addRule('coupon', null, null, null, 'client');

        // Styled button
        $mform->addElement('submit', 'applycoupon', 'Apply coupon', [
            'style' => 'transition: all 0.5s; height: 48px; line-height: 48px; cursor: pointer; padding: 0 35px; border-radius: 30px;     text-transform: uppercase; color: #fff !important; font-weight: normal;'
        ]);

        // Container end
        $mform->addElement('html', '</div>');
    }
}



$form = new applycoupon();
if ($form->is_cancelled()) {
    redirect(new moodle_url($CFG->wwwroot . '/courses'));
} else if ($data = $form->get_data()) {

    $now = time();
    $couponCode = trim($data->coupon);
    $coupon = $DB->get_record('local_coupon', ['code' => $couponCode]);
    $userdata = $DB->get_record('company_users', ['companyid' => $coupon->companyid]);

    if ($coupon->code !== $data->coupon) {
        redirect(new moodle_url($CFG->wwwroot . "/courses/guestcartitems.php"), 'Coupon not found');
    }

    if (!$userdata) {
        redirect(new moodle_url("/courses/guestcartitems.php"), 'Coupon Company not found');
    }

    if (empty($coupon->companyid) || $coupon->companyid != $companyid) {
        redirect(new moodle_url("/courses/guestcartitems.php"), 'Coupon company is not matched');
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

      $totalusage = $DB->count_records('coupon_invoice', ['couponid' => $coupon->id]);
    if ($totalusage >= $coupon->max_uses) {
        redirect(new moodle_url('/courses/guestcartitems.php'), 'Coupon usage limit reached.');
    }
 
    $totalusageuser = $DB->count_records('coupon_invoice', ['couponid' => $coupon->id, 'userid' => $USER->id]);
     if ($totalusageuser >= $coupon->max_uses_user) {
        redirect(new moodle_url('/courses/guestcartitems.php'), 'Coupon user usage limit reached.');
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

    $checkifExist = $DB->get_record('coupon_invoice', ['sessionid' => $invoicedata->sessionid, 'guestinvoiceid' => $invoicedata->id]);

    $record = new stdClass();
    $record->couponid = $coupon->id;
    $record->sessionid = $invoicedata->sessionid;
    $record->guestinvoiceid = (int) $invoicedata->id;
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

   redirect(
    new moodle_url($CFG->wwwroot . "/courses/guestcartitems.php"));
}


echo $OUTPUT->header();
echo "<a class='btn btn-primary my-2' href='" . $continue . "'>Back</a>";
// Fetch guest cart record.
$guestcartrecord = $DB->get_record("guest_cart", ['id' => $SESSION->basketid]);
$guestcartid = $guestcartrecord->id;


// Fetch guest cart items.
$guestcartitems = $DB->get_records_sql('
    SELECT ii.*, css.name, css.id AS cssid, css.allow_single_purchase, 
           css.allow_license_blocks, css.single_purchase_shelflife
    FROM {guest_cart_items} ii
    INNER JOIN {course_shopsettings} css ON ii.invoiceableitemid = css.id
    WHERE ii.guestcartid = :guestcartid
    ORDER BY ii.id
', ['guestcartid' => $guestcartid]);

$html = "
<style>

.felement {
    max-width: 100%;
    flex: auto;
    width: 100%;
}

.cst_summary {
    transform: translateY(133px);
}
.mform{
    max-width: 1200px;
    margin: auto;
    font-family: Arial, sans-serif;
    transform: translateY(-195px);
    /* display: inline-block; */
    border-bottom: 2px dotted #e0e0e0;
}

.guest-cart-items thead th 
{
padding: 0 10px !important;
    text-transform: capitalize;
    font-weight: 500;
    text-align: left;
    border-top: none !important;
    border-bottom: 2px solid var(--thim-border-color, #e0e0e0);
    border-left: none;
    border-right: none;
}


.guest-cart-items td {
    white-space: nowrap;
    padding: 20 10px !important;
}

#page.drawers {
    height: 100vh;
}


@media(max-width:767px){

.cst_summary{

  transform: translateY(201px);
}

div#fitem_id_applycoupon>.col-md-9,
div#fitem_id_coupon>.col-md-9{
 width:100%;
}

div#fitem_id_applycoupon>.col-md-9>input,
div#fitem_id_coupon>.col-md-9>input{
 width:100%;
}

}
 </style>
";


$html .= '
<div class="guest-cart-items" style="max-width:1200px; margin: auto; font-family: Arial, sans-serif;">

    <!-- Header -->
    <div style="background-color: #f5f6f8; padding: 15px; border-top: 3px solid #0340f4; display: flex; justify-content: space-between; align-items: center; margin-bottom: 50px;">
        <p style="margin: 0; font-size: 14px;">Item(s) have been added to your cart</p>
        <a href="' . $continue . '" style="background-color:#0f47ad; color:#fff; padding: 8px 20px; text-decoration: none; border-radius: 4px;">Continue Shopping</a>
    </div>

    <!-- Table -->
    <div class="table-responsive">
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
        <thead>
            <tr style="border-bottom: 2px solid #eee; text-align: left;">
                <th style="padding: 20px 0;">Product</th>
                <th style="padding: 20px 0; text-align:right;">Price</th>
                <th style="padding: 20px 0; text-align:center;">Quantity</th>
                <th style="padding: 20px 0; text-align:right;">Subtotal</th>
                <th style="padding: 20px 0; text-align:center;"></th>
            </tr>
        </thead>
        <tbody>';

$subtotal = 0;
$currency = 'USD';
// echo "<pre>";
// print_r($guestcartitems);
// echo "</pre>";
foreach ($guestcartitems as $course) {
    $subtotal += $course->price * $course->license_allocation;
    $coursefullname = $course->name ?: 'Unknown Course';
    $currency = isset($course->currency) ? $course->currency : $currency;

    $productSubtotal = $course->price * $course->license_allocation;
    // $quantity_input =  intval($course->license_allocation);
    $checkpriceblocks = $DB->get_records('course_shopblockprice', ['itemid' => $course->invoiceableitemid], 'price_bracket_start');
    $totalpriceblocks = count($checkpriceblocks);
    $licenseInput = intval($course->license_allocation);
    if($totalpriceblocks > 0){

        // event type product
        $seattotal_license_allocation = 0;
        $getinvoiceitem = $DB->get_records_sql("SELECT *  FROM {invoiceitem} WHERE groupid =? AND processed = ?",array($course->groupid,1));
        if($getinvoiceitem){
            foreach ($getinvoiceitem as $getitem) {
                $seattotal_license_allocation += $getitem->license_allocation;
            }
        }
        $seattotal_license_allocation;
        $groups = $DB->get_records_sql("SELECT * FROM {groups} WHERE id = ?",array($course->groupid));
        $group = reset($groups);
        $available_seats = $group->member_allow - $seattotal_license_allocation;
        if ($available_seats > 0 || !empty($available_seats)) {
            $licenseInput = "<input type='number' class='edit_license' name='license_allocation' data-id='{$course->id}' data-name='{$course->name}' data-itemid='$course->invoiceableitemid' data-licenses='1' data-available_seats='{$available_seats}' data-groupid='{$course->groupid}' value='{$course->license_allocation}' min='1' max='{$available_seats}' style='width:60px; display:inline-block;' /> " .
                            get_string('type_quantity_' . ($course->license_allocation > 1 ? 'n' : '1'). '_' . $course->invoiceableitemtype, 'block_iomad_commerce', $course->license_allocation);
        } else {
            $licenseInput = "<input type='number' class='edit_license' name='license_allocation' data-id='{$course->id}' data-name='{$course->name}' data-itemid='$course->invoiceableitemid' data-licenses='1' value='{$course->license_allocation}' min='1' style='width:60px; display:inline-block;' /> " .
                            get_string('type_quantity_' . ($course->license_allocation > 1 ? 'n' : '1'). '_' . $course->invoiceableitemtype, 'block_iomad_commerce', $course->license_allocation);
        }
    } 



    $html .= '
        <tr style="border-bottom: 2px dotted #e0e0e0;">
            <td style="padding: 20px 0px; display: flex; align-items: center; gap: 10px;"> 
                ' . format_string($coursefullname) . '
            </td>
            <td style="padding: 20px 0px; text-align:right;">' . format_float($course->price, 2) . ' ' . $currency . '</td>
            <td style="padding: 20px 0px; text-align:center;">'.$licenseInput.'</td>
            <td style="padding: 20px 0px; text-align:right;">' . format_float($productSubtotal, 2) . ' ' . $currency . '</td>
            <td style="padding: 20px 0px; text-align:center;">
                <button onclick="ondeleteinvoicedata(' . intval($course->id) . ')" class="text-danger" style="background:none; border:none;  font-size:18px; cursor:pointer;"><i class="fa-solid fa-trash mx-2"></i></button>
            </td>
        </tr>';
}

$couponinvoice = $DB->get_record('coupon_invoice', ['sessionid' => $guestcartrecord->sessionid, 'guestinvoiceid' => $guestcartid]);
$couponname = $DB->get_record('local_coupon', ['id' => $couponinvoice->couponid]);
$coupon_discount = !empty($couponinvoice->discount_amount) 
    ? '- $' . format_float((float)$couponinvoice->discount_amount, 2) . ' ' . $currency 
    : format_float(0, 2) . ' ' . $currency;

$total = $subtotal - $couponinvoice->discount_amount;

$html .= '
        </tbody>
    </table> 
</div>
</div>';


$html .= '
 <!-- Summary -->
    <div class="cst_summary col-md-4" style="font-size:16px;  margin-left: auto;">
        <div class="m-2">Subtotal: <strong style="margin-left:6px">$' . format_float($subtotal, 2) . ' ' . $currency . '</strong></div>
        <div class="m-2">Coupon: <strong style="margin-left:6px">' . $coupon_discount . '</strong>'
    . (!empty($couponformCondition) ? ' <button onclick="removecoupon(' . intval($couponinvoice->id) . ')" style="background:none; border:none; color:red; cursor:pointer;">Remove</button>' : '') . '</div>
        <div class="m-2" style="font-size: 18px;">Total: <strong style="color:#0f47ad; margin-left:6px">$' . format_float($total, 2) . ' ' . $currency . '</strong></div>


         <!-- Checkout Buttons -->
    <div class="flex-lg-row flex-column" style="margin: 20px 0; gap:10px;  display: flex; ">
       <div class="">
        <a href="' . $CFG->wwwroot . '/courses/guestcheckout.php"   style=" display:block; background-color:#0f47ad; width: max-content;  color:#fff; padding: 12px; text-align:center; border-radius:3px; text-decoration:none; font-size:16px;">PROCEED TO CHECKOUT</a> 
       </div>

        <div class="" id="emty-div"></div>
    </div>


    </div>
';



$html .= '
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    const guestusercartid  = "' . $SESSION->basketid . '";
    const itemdeleteurl = "' . $CFG->wwwroot . '/courses/guestitemdelete.php";
    const couponremoveurl = "' . $CFG->wwwroot . '/courses/guestcouponremove.php";
    const sesskey = "' . sesskey() . '";

    function ondeleteinvoicedata(id) {
        if (!confirm("Are you sure you want to remove this item from the cart?")) {
            return;
        }

        $.ajax({
            url: itemdeleteurl,
            method: "POST",
            data: {
                action: "deleteitem",
                id: id,
                cartid: guestusercartid,
                sesskey: sesskey,
            },
            success: function(response) {
                if (response.status) {
                    location.reload();
                } else {
                    alert(response.message);
                }
            },
            error: function() {
                alert("Error removing item");
            }
        });
    }

    function removecoupon(couponinvoiceid){
        $.ajax({
            url: couponremoveurl,
            method: "POST",
            data: {
                action: "removecoupon",
                removecouponid: couponinvoiceid,
                sesskey: sesskey,
                cartid: guestusercartid
            },
           success: function(response) {
                if (response.status) {
                    location.reload();
                } else {
                    alert(response.message);
                }
            },
            error: function() {
                alert("Coupon Not Removed");
            }

        })
    }
</script>
'; 
// $html2 = '<a href="'.$CFG->wwwroot.'/blocks/iomad_commerce/checkout.php">Checkout</a>';


// $baskethtml = \block_iomad_commerce\helper::get_basket_html(1);


$html .= <<<JS
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const inputs = document.querySelectorAll('.edit_license');

            const originalValues = {};
            inputs.forEach(input => {
                originalValues[input.dataset.id] = input.value;
            });

            const updateBtn = document.createElement('button');
            updateBtn.id = 'updateBtn';
            updateBtn.style.height ="100%";
            updateBtn.classList.add('btn', 'btn-primary');
            updateBtn.textContent = 'Update';
            updateBtn.type = 'button';
            updateBtn.style.display = 'none';
            updateBtn.style.marginTop = '0';
            updateBtn.style.color = '#fff';
            updateBtn.style.border = 'none';
            updateBtn.style.padding = '0.350rem 2.75rem';
            updateBtn.style.borderRadius = '5px';
            updateBtn.style.height = "48px";
            updateBtn.style.cursor = 'pointer';
            updateBtn.style.textTransform ="uppercase";
            updateBtn.style.fontSize="16px"
            // updateBtn.style.margin = '0 20rem'

            const buttonCountainer = document.getElementById('action-buttons');
            var box  = document.getElementById('emty-div');
            if (buttonCountainer) {
                buttonCountainer.appendChild(updateBtn);
            } else {
                box.appendChild(updateBtn);
            }

            // Show/hide button if any input is changed
            inputs.forEach(input => {
                input.addEventListener('input', () => {
                    let hasChanges = false;
                    inputs.forEach(inp => {
                        const id = inp.dataset.id;
                        if (inp.value !== originalValues[id]) {
                            hasChanges = true;
                        }
                    });
                    updateBtn.style.display = hasChanges ? 'inline-block' : 'none';
                });
            });

            // Submit grouped inputs
            updateBtn.addEventListener('click', () => {
                const items = {};
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/courses/update_license_quantity.php';

                inputs.forEach(input => {
                    var id = input.dataset.id;
                    const currentValue = input.value;

                    if (currentValue !== originalValues[id]) {
                        items[id] = {
                            nlicenses: currentValue,
                            licenses: input.dataset.licenses,
                            itemid: input.dataset.itemid,
                            groupid: input.dataset.groupid,
                            coursename: input.dataset.name,
                            available_seats: input.dataset.available_seats
                        };
                        const hiddenInput1 = document.createElement('input');
                        hiddenInput1.type = 'hidden';
                        hiddenInput1.name = `items[`+id+`][nlicenses]`;
                        hiddenInput1.value = currentValue;
                        form.appendChild(hiddenInput1);

                        const hiddenInput2 = document.createElement('input');
                        hiddenInput2.type = 'hidden';
                        hiddenInput2.name = `items[`+id+`][licenses]`;
                        hiddenInput2.value = input.dataset.licenses;
                        form.appendChild(hiddenInput2);
                        const hiddenInput3 = document.createElement('input');
                        hiddenInput3.type = 'hidden';
                        hiddenInput3.name = `items[`+id+`][itemid]`;
                        hiddenInput3.value = input.dataset.itemid;
                        form.appendChild(hiddenInput3);
                        const hiddenInput4 = document.createElement('input');
                        hiddenInput4.type = 'hidden';
                        hiddenInput4.name = `items[`+id+`][groupid]`;
                        hiddenInput4.value = input.dataset.groupid;
                        form.appendChild(hiddenInput4);
                        const hiddenInput5 = document.createElement('input');
                        hiddenInput5.type = 'hidden';
                        hiddenInput5.name = `items[`+id+`][coursename]`;
                        hiddenInput5.value = input.dataset.name;
                        form.appendChild(hiddenInput5);
                        const hiddenInput6 = document.createElement('input');
                        hiddenInput6.type = 'hidden';
                        hiddenInput6.name = `items[`+id+`][available_seats]`;
                        hiddenInput6.value = input.dataset.available_seats;
                        form.appendChild(hiddenInput6);
                    }
                });

                if (Object.keys(items).length === 0) {
                    alert('No changes to update.');
                    return;
                }

                document.body.appendChild(form);
                form.submit();

            });
        });
        </script>


        JS;


echo $html;

if (empty($couponformCondition)) {
    $form->display();
     // echo $html2;
    // echo $baskethtml;
}
echo '<div class="custom-cart">'. guest_user_basket_info() .'</div>';
echo $OUTPUT->footer();
