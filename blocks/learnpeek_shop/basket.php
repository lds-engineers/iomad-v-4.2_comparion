<?php
require_once(__DIR__ . '/../../config.php');
require_once("$CFG->libdir/formslib.php");
require_once(dirname(__FILE__) . '/../iomad_company_admin/lib.php');
require_once($CFG->dirroot . '/local/user_registration/locallib.php');

\block_iomad_commerce\helper::require_commerce_enabled();
require_login();
$remove = optional_param('remove', 0, PARAM_INT);

global $DB, $SESSION, $USER;
$PAGE->requires->jquery();
$linktext = get_string('course_shop_title', 'block_iomad_commerce');
$linkurl = new moodle_url('/blocks/iomad_commerce/shop.php');

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);
$PAGE->navbar->add($linktext, $linkurl);
// $PAGE->navbar->add(get_string('basket', 'block_iomad_commerce'));
$couponformCondition = $DB->get_record('coupon_invoice', ['invoiceid' => $SESSION->basketid]);
$companyid = iomad::get_my_companyid(context_system::instance());
$is_hide = check_company_access_restriction();
if($is_hide){
    redirect(new moodle_url($CFG->wwwroot.'/my'),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
    die;
}

class applyCoupon extends moodleform {
    function definition() {
        $mform = $this->_form;
        $mform->addElement('text', 'coupon', get_string('coupon','block_learnpeek_shop'), ['placeholder' => get_string('entercoupon','block_learnpeek_shop')]);
        $mform->setType('coupon', PARAM_TEXT);
        $mform->addRule('coupon', null, null, null, 'client');
        $this->add_action_buttons(true, get_string('apply','block_learnpeek_shop'));
    }
}

$form = new applyCoupon();
if ($form->is_cancelled()) {
    redirect(new moodle_url('/blocks/learnpeek_shop/basket.php'));
} else if ($data = $form->get_data()) {
    $now = time();
    $couponCode = trim($data->coupon);
    $coupon = $DB->get_record('local_coupon', ['code' => $couponCode]);
    $userdata = $DB->get_record('company_users', ['companyid'=> $coupon->companyid]);

    if($coupon->code !== $data->coupon){
         redirect(new moodle_url('/blocks/learnpeek_shop/basket.php'), 'Coupon not found',null, \core\output\notification::NOTIFY_ERROR);
    }

    if(!$userdata){
        redirect(new moodle_url("/blocks/learnpeek_shop/basket.php"), 'Company is not valid',null, \core\output\notification::NOTIFY_ERROR);
    }

    if (empty($coupon->companyid) || $coupon->companyid != $companyid) {
        redirect(new moodle_url("/blocks/learnpeek_shop/basket.php"), 'This coupon is not valid for your company.',null, \core\output\notification::NOTIFY_ERROR);
    }

    if (!$coupon) {
        redirect(new moodle_url('/blocks/learnpeek_shop/basket.php'), 'Coupon not found',null, \core\output\notification::NOTIFY_ERROR);
    }
    if ($coupon->status == 0) {
        redirect(new moodle_url('/blocks/learnpeek_shop/basket.php'), 'Coupon is not active',null, \core\output\notification::NOTIFY_ERROR);
    }
    if (!empty($coupon->starts_at) && $now < $coupon->starts_at) {
        redirect(new moodle_url('/blocks/learnpeek_shop/basket.php'), 'This coupon is not yet active.',null, \core\output\notification::NOTIFY_ERROR);
    }
    if (!empty($coupon->expires_at) && $now > $coupon->expires_at) {
        redirect(new moodle_url('/blocks/learnpeek_shop/basket.php'), 'This coupon has expired.',null, \core\output\notification::NOTIFY_ERROR);
    }

      $totalusage = $DB->count_records('coupon_invoice', ['couponid' => $coupon->id]);
    if ($totalusage >= $coupon->max_uses) {
        redirect(new moodle_url('/blocks/iomad_commerce/basket.php'), 'Coupon usage limit reached.',null, \core\output\notification::NOTIFY_ERROR);
    }
 
    $totalusageuser = $DB->count_records('coupon_invoice', ['couponid' => $coupon->id, 'userid' => $USER->id]);
     if ($totalusageuser >= $coupon->max_uses_user) {
        redirect(new moodle_url('/blocks/iomad_commerce/basket.php'), 'Coupon user usage limit reached.',null, \core\output\notification::NOTIFY_ERROR);
    }

    $invoicedata = $DB->get_record('invoice', ['id' => $SESSION->basketid]);
    if (!$invoicedata) {
        redirect(new moodle_url('/blocks/learnpeek_shop/basket.php'), 'Invoice not found',null, \core\output\notification::NOTIFY_ERROR);
    }

    $invoiceitems = $DB->get_records('invoiceitem', ['invoiceid' => $invoicedata->id]);
    if (empty($invoiceitems)) {
        redirect(new moodle_url('/blocks/learnpeek_shop/basket.php'), 'No products in basket.',null, \core\output\notification::NOTIFY_ERROR);
    }

    if (!$coupon->allow_all_users) {
        $couponuser = $DB->get_record('local_coupon_user', ['couponid' => $coupon->id, 'userid' => $invoicedata->userid]);
        if (!$couponuser) {
            redirect(new moodle_url('/blocks/learnpeek_shop/basket.php'), 'This coupon is not available for you',null, \core\output\notification::NOTIFY_ERROR);
        }
    }

    $eligibleTotal = 0;
    $validproductname = "";
    $iscouponapplied = false;
    if ($coupon->allow_all_products) {
        foreach ($invoiceitems as $item) {
            $eligibleTotal += $item->price * $item->license_allocation;
            $iscouponapplied = true;
        }
    } else {
        $couponproducts = $DB->get_records('local_coupon_product', ['couponid' => $coupon->id]);
        $validproductids = array_map(fn($cp) => $cp->course_shopsetting_id, $couponproducts);
        $allcourse_shopsetting_id = array_column($couponproducts, 'course_shopsetting_id');



        if (empty($validproductids)) {
            redirect(new moodle_url('/blocks/learnpeek_shop/basket.php'), 'This coupon is not assigned to any products.',null, \core\output\notification::NOTIFY_ERROR);
        }
        $validproducts = $DB->get_records_sql("SELECT name from {course_shopsettings} where id in (".implode(",", $allcourse_shopsetting_id).")");
        $validproductname = implode(", ", array_column($validproducts, 'name'));

        foreach ($invoiceitems as $item) {
            if (in_array($item->invoiceableitemid, $validproductids)) {
                $eligibleTotal += $item->price;
                $iscouponapplied = true;
            }
        }
    }

    if ($eligibleTotal < $coupon->min_amount) {
        redirect(new moodle_url('/blocks/learnpeek_shop/basket.php'), "To use this coupon, you must have at least {$coupon->min_amount} in eligible products.");
    }

    $discountamount = 0;
    if ($coupon->type === 'fixed') {
        $discountamount = $coupon->discount_amount;
    } elseif ($coupon->type === 'percent') {
        $discountamount = ($coupon->discount_amount / 100) * $eligibleTotal;
    } else {
        redirect(new moodle_url('/blocks/learnpeek_shop/basket.php'), 'Invalid coupon type',null, \core\output\notification::NOTIFY_ERROR);
    }
    if($iscouponapplied){
        $checkifExist = $DB->get_record('coupon_invoice', ['invoiceid' => $invoicedata->id]);

        $record = new stdClass();
        $record->couponid = $coupon->id;
        $record->userid = $invoicedata->userid;
        $record->invoiceid = $invoicedata->id;
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

        redirect(new moodle_url('/blocks/learnpeek_shop/basket.php'), "Coupon applied successfully. Discount: $" . number_format($discountamount, 2));
    } else {
        if($validproductname){
            redirect(new moodle_url('/blocks/learnpeek_shop/basket.php'), 'Coupon is only valid for the '.$validproductname,null, \core\output\notification::NOTIFY_INFO);

        } else {
            redirect(new moodle_url('/blocks/learnpeek_shop/basket.php'), 'Invalid coupon type',null, \core\output\notification::NOTIFY_ERROR);

        }
    }
}
echo $OUTPUT->header();

flush();

if (!empty($SESSION->basketid)) {
    if ($remove) {
        if ($DB->record_exists_sql('SELECT ii.id
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
            $invoiceid = $SESSION->basketid;
            $invoicedata = $DB->get_record('invoice', ['id' => $invoiceid]);
            $invoiceitemdata = $DB->get_records('invoiceitem', ['invoiceid' => $invoicedata->id]);
            $now = time();
            if (empty($invoiceitemdata)) {
                $rmcoupon = $DB->get_record('coupon_invoice', ['invoiceid' => $invoiceid]);
                if ($rmcoupon) {
                    $DB->delete_records('coupon_invoice', ['invoiceid' => $invoiceid]);
                }
            }

            if (!empty($invoiceitemdata)) {
                $updatecoupondis = $DB->get_record('coupon_invoice', ['invoiceid' => $invoiceid]); 

                if(!empty($updatecoupondis)){
                    $coupon = $DB->get_record('local_coupon', ['id' => $updatecoupondis->couponid]);
                    if ($coupon && $coupon->status == 1 && (empty($coupon->starts_at) || $now >= $coupon->starts_at) && (empty($coupon->expires_at) ||  $now <= $coupon->expires_at)) {

                        $invoiceitems = $DB->get_records('invoiceitem', ['invoiceid' => $invoicedata->id]);

                        $eligibleTotal = 0;
                      
                        if ($coupon->allow_all_products) {
                            foreach ($invoiceitems as $item) {
                                $eligibleTotal += $item->price * $item->license_allocation;  
                            }
                        } else {
                            $couponproducts = $DB->get_records('local_coupon_product', ['couponid' => $coupon->id]);
                            $validproductids = array_map(fn($cp) => $cp->course_shopsetting_id, $couponproducts);

                            foreach ($invoiceitems as $item) {
                                if (in_array($item->invoiceableitemid, $validproductids)) {
                                     $eligibleTotal += $item->price * $item->license_allocation; 
                                }
                            }
                        }

                        if ($eligibleTotal < $coupon->min_amount) {
                            // If not eligible anymore, remove the coupon
                            $DB->delete_records('coupon_invoice', ['invoiceid' => $SESSION->basketid]);
                            redirect(new moodle_url('/blocks/learnpeek_shop/basket.php'), "To use this coupon, you must have at least {$coupon->min_amount} in eligible products.",null, \core\output\notification::NOTIFY_ERROR);
                        } 
                        // Recalculate discount
                        $discountamount = 0;

                        if ($coupon->type === 'fixed') {
                            $discountamount = $coupon->discount_amount;
                        } elseif ($coupon->type === 'percent') {
                            $discountamount = ($coupon->discount_amount / 100) * $eligibleTotal;
                        } 

                        // Update coupon_invoice
                        $updatecoupondis->discount_amount = $discountamount;
                        $updatecoupondis->eligible_total = $eligibleTotal;
                        $updatecoupondis->applied_on = time();
                        $DB->update_record('coupon_invoice', $updatecoupondis);
                    }
 
                }
            }
        }
    }


if(!empty($SESSION->basketid)){
    $couponinvoice = $DB->get_record('coupon_invoice', ['invoiceid' => $SESSION->basketid]);
    if(!empty($couponinvoice)){
        $now = time();
        $coupon = $DB->get_record('local_coupon', ['id' => $couponinvoice->couponid]);

        if ($coupon && $coupon->status == 1 && (empty($coupon->starts_at) || $now >= $coupon->starts_at) && (empty($coupon->expires_at) ||  $now <= $coupon->expires_at)) {
        // if ($coupon && $coupon->status == 1 && userdate($now) >= userdate($coupon->starts_at) && userdate($now) <= userdate($coupon->expires_at)) {

             $invoiceitems = $DB->get_records('invoiceitem', ['invoiceid' => $couponinvoice->invoiceid]);


                $eligibleTotal = 0;
                if ($coupon->allow_all_products) {
                    foreach ($invoiceitems as $item) {
                        $eligibleTotal += $item->price * $item->license_allocation;
                    }
                } else {
                    $couponproducts = $DB->get_records('local_coupon_product', ['couponid' => $coupon->id]);
                    $validproductids = array_map(fn($cp) => $cp->course_shopsetting_id, $couponproducts);

                    foreach ($invoiceitems as $item) {
                        if (in_array($item->invoiceableitemid, $validproductids)) {
                             $eligibleTotal += $item->price * $item->license_allocation;
                        }
                    }
                }

                if ($eligibleTotal < $coupon->min_amount) {
                    // If not eligible anymore, remove the coupon
                    $DB->delete_records('coupon_invoice', ['invoiceid' => $SESSION->basketid]);
                    redirect(new moodle_url('/blocks/learnpeek_shop/basket.php'), "To use this coupon, you must have at least {$coupon->min_amount} in eligible products.",null, \core\output\notification::NOTIFY_ERROR);
                }

                // Recalculate discount
                $discountamount = 0;
                if ($coupon->type === 'fixed') {
                    $discountamount = $coupon->discount_amount;
                } elseif ($coupon->type === 'percent') {
                    $discountamount = ($coupon->discount_amount / 100) * $eligibleTotal;
                }

                // Update coupon_invoice
                $couponinvoice->discount_amount = $discountamount;
                $couponinvoice->eligible_total = $eligibleTotal;
                $couponinvoice->applied_on = time();
                $DB->update_record('coupon_invoice', $couponinvoice);
            }
    }
}



    $baskethtml = \block_iomad_commerce\helper::get_basket_html(1);
    if ($baskethtml) {
        echo $baskethtml;

        echo <<<JS
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const inputs = document.querySelectorAll('.edit_license');

            const originalValues = {};
            inputs.forEach(input => {
                originalValues[input.dataset.id] = input.value;
            });

            const updateBtn = document.createElement('button');
            updateBtn.id = 'updateBtn';
            updateBtn.classList.add('btn', 'btn-primary');
            updateBtn.textContent = 'Update';
            updateBtn.type = 'button';
            updateBtn.style.display = 'none';
            updateBtn.style.marginTop = '20px';
            updateBtn.style.color = '#fff';
            updateBtn.style.border = 'none';
            updateBtn.style.padding = '.375rem .75rem';
            updateBtn.style.borderRadius = '5px';
            updateBtn.style.cursor = 'pointer';
            updateBtn.style.margin = '0 20rem'

            const buttonCountainer = document.getElementById('action-buttons');
            if (buttonCountainer) {
                buttonCountainer.appendChild(updateBtn);
            } else {
                document.body.appendChild(updateBtn);
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
                form.action = '/blocks/learnpeek_shop/update_license_quantity.php';

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
       
        if(empty($couponformCondition)){
        $form->display();
        }
        echo "<p id='action-buttons' class='d-flex flex-wrap align-items-center my-5' style='gap:10px'>";
        if (!empty($_GET['removecoupon']) && !empty($_GET['invoiceid'])) {
            $invoiceid = (int)$_GET['invoiceid'];
            $couponid = (int)$_GET['removecoupon'];

            // Remove coupon entry for this invoice
            $DB->delete_records('coupon_invoice', ['invoiceid' => $invoiceid, 'id' => $couponid]);
            redirect(new moodle_url('basket.php', ['invoiceid' => $invoiceid]), "Coupon is removed");
        }
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


echo '
<style>


.generaltable th, 
.generaltable td{
  white-space: nowrap;
}

input#id_coupon {
    margin: 0 10px;
}

div#fitem_id_coupon{
margin: 20px 0;
}

div#fitem_id_coupon>div {
    padding: 0;
}

div#fgroup_id_buttonar>div {
    padding: 0;
}
.mform {
    padding: 0;
    display: flex;
    align-items: center;
}
.mform div#fgroup_id_buttonar {
    width: 100%;
}


@media(max-width:767px){
.mform {
   flex-direction:column;
   align-items: start;
}

.mform div#fgroup_id_buttonar {
    width: 100%;
    padding-left: 15px;
}


</style>

';

echo $OUTPUT->footer();