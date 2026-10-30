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

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/blocks/iomad_company_admin/lib.php');
require_once($CFG->dirroot . '/local/user_registration/locallib.php');

\block_iomad_commerce\helper::require_commerce_enabled();

require_login(null, false); // Adds to $PAGE, creates $OUTPUT.
$context = context_system::instance();

// Correct the navbar.
// Set the name for the page.
$linktext = get_string('course_shop_title', 'block_iomad_commerce');
// Set the url.
$linkurl = new moodle_url('/blocks/iomad_commerce/checkout.php');
$shopurl = new moodle_url('/blocks/iomad_commerce/shop.php');

// Print the page header.
$PAGE->set_context($context);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);
$PAGE->set_heading(get_string('checkout', 'block_iomad_commerce'));

// Build the nav bar.
$PAGE->navbar->add($linktext, $shopurl);
$PAGE->navbar->add(get_string('checkout', 'block_iomad_commerce'));

// JS For payment gateway.
$PAGE->requires->js_call_amd('core_payment/gateways_modal', 'init');
$is_hide = check_company_access_restriction();
if($is_hide){
    redirect(new moodle_url($CFG->wwwroot.'/my'),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
    die;
}

$data = clone $USER;
$companyid = iomad::get_my_companyid(context_system::instance());
$companyrec = $DB->get_record('company', ['id' => $companyid]);
$usercompanydata = $DB->get_record('user_info_data', ['userid' => $USER->id]);
// $userinvoicedata = $DB->get_record_sql("SELECT * FROM {invoice} WHERE userid = ? AND status = 'p' ORDER BY id DESC LIMIT 1",array($USER->id));
$userinvoicedata = $DB->get_record_sql("SELECT * FROM {invoice} WHERE userid = ? ORDER BY id DESC LIMIT 1",array($USER->id));
if(empty($userinvoicedata->email)){
    $userinvoicedata = $DB->get_record_sql("SELECT * FROM {invoice} WHERE userid = ? AND status = 'p' ORDER BY id DESC LIMIT 1",array($USER->id));
}
if($userinvoicedata){
    $companyname = $userinvoicedata->company ?? $companyrec->name;
    $data->firstname = $userinvoicedata->firstname;
    $data->lastname = $userinvoicedata->lastname;
    $address = explode("<br>", $userinvoicedata->address);
    $data->address = $address[0];
    $data->address2 = $address[1];
    $data->city = $userinvoicedata->city;
    $data->postcode = $userinvoicedata->postcode;
    $data->state = $userinvoicedata->state;
    $data->state_select = $userinvoicedata->state;
    $data->state_text = $userinvoicedata->state;
    $data->country = $userinvoicedata->country;
    $data->email = $userinvoicedata->email;
    $data->phone1 = $userinvoicedata->phone1;
    $data->company = $companyname;
}else{
    $data->company = $usercompanydata->data;
    $data->postcode = $companyrec->postcode;
    $data->city = $companyrec->city;
    $data->state = $companyrec->region;
    $data->state_text = $companyrec->region;
    $data->state_select = $companyrec->region;
    $data->country = $companyrec->country;
}
if (empty($data->country)) {
    $data->country = 'US'; // Default to United States
}
// $data->company = $companyrec->name;
// $data->postcode = $companyrec->postcode;
// $data->city = $companyrec->city;
// $data->state = $companyrec->region;
// echo "<pre>";
// print_r($data);
// echo "userinvoicedata";
// print_r($userinvoicedata);
// echo "</pre>";

$mform = new \block_iomad_commerce\forms\checkout_form($PAGE->url);
$mform->set_data($data);

$error = '';
$displaypage = 1;

$basketid = \block_iomad_commerce\helper::get_basket_id();

if (empty($basketid) || $mform->is_cancelled()) {
redirect(new moodle_url($CFG->wwwroot . '/blocks/iomad_commerce/basket.php'));

} else if ($data = $mform->get_data()) {

    $data->id = $basketid;
    $data->companyid = $companyid;

    $DB->update_record('invoice', $data, array('id' => $data->id));

    // lds suneet custom code
    $allitemprice = $DB->get_record_sql("SELECT SUM(price) AS total_amount FROM {invoiceitem} WHERE invoiceid = ?",[$data->id]);
    $totalamount = floatval($allitemprice->total_amount);
    // if($totalamount > 0){
        $getdiscount = $DB->get_record_sql("SELECT * FROM {coupon_invoice} WHERE invoiceid = ?",[$data->id]);

        if(empty($totalamount) || ($getdiscount && $getdiscount->eligible_total == $getdiscount->discount_amount)){
            $getinvoicedata = $DB->get_record_sql("SELECT * FROM {invoiceitem} WHERE invoiceid = ?",[$data->id]);
            $currentcurrency = $getinvoicedata->currency;

            $getpamnetdata = $DB->get_record_sql("SELECT * FROM {payments} WHERE itemid = ?",[$data->id]);
            if(empty($getpamnetdata)){
                $addpayment = new stdClass();
                $addpayment->component      = "block_iomad_commerce";
                $addpayment->paymentarea    = "invoice";
                $addpayment->paymentarea    = "invoice";
                $addpayment->itemid         = $data->id;
                $addpayment->userid         = $USER->id;
                $addpayment->amount         = 0;
                $addpayment->currency       = $getinvoicedata->currency;
                $addpayment->accountid      = $companyrec->paymentaccount;
                $addpayment->gateway        = "stripe";
                $addpayment->timecreated    = time();
                $addpayment->timemodified   = time();
                $addedpayment = $DB->insert_record("payments", $addpayment);

            }else{
                $addedpayment = $getpamnetdata->id;
            }

            \block_iomad_commerce\payment\service_provider::deliver_order("invoice", $data->id, $addedpayment, $USER->id);

            // Uday
            $current_host = $_SERVER['HTTP_HOST'];

            $company = $DB->get_record_sql(
                "SELECT id FROM {company} WHERE hostname = :host",
                ['host' => $current_host]
            );

            $managerrole = $DB->get_record_sql('SELECT * FROM {company_users} WHERE userid = ? AND companyid = ?', array($USER->id,$company->id));
            $managertype = $managerrole->managertype;
            if($managertype == 1){
                $redirecturl = $CFG->wwwroot . '/blocks/custom_mycourse/mycourse.php?type=enrolled';
            }else{
                $redirecturl = $CFG->wwwroot . '/my/courses.php';
            }

            if ($company && in_array($company->id, array(33, 36))) {
                $redirecturl = $CFG->wwwroot . '/blocks/learnpeek_shop/thankyou.php';
                redirect($redirecturl);
                exit;
            }


            redirect($redirecturl, 'Payment was successful', null, \core\output\notification::NOTIFY_SUCCESS);
            

            // redirect($CFG->wwwroot.'/my/courses.php', 'Payment was successful', null, \core\output\notification::NOTIFY_SUCCESS);
            exit();
        }

    // }
    // lds suneet custom code

    echo $OUTPUT->header();

    $baskethtml = \block_iomad_commerce\helper::get_basket_html();

    echo $baskethtml;

    $paymentoptions = core_payment\helper::gateways_modal_link_params('block_iomad_commerce', 'invoice', $basketid, trim(html_to_text(\block_iomad_commerce\helper::get_invoice_summary($basketid, 0, 0, 0))));
    // echo "<pre>";
    // print_r($data);
    // die;
    $paymentoptions['class'] = 'btn btn-primary';

    echo html_writer::start_tag('p');
    echo html_writer::tag('button', get_string('sendpaymentbutton', 'enrol_fee'), $paymentoptions);
    echo " " . get_string('or', 'block_iomad_commerce') . " ";
    echo html_writer::tag('a', get_string('returntoshop', 'block_iomad_commerce'), ['class' => 'btn btn-secondary',
                                                                                    'href' => new moodle_url($CFG->wwwroot . '/blocks/iomad_commerce/shop.php')]);
    echo html_writer::end_tag('p');

    echo $OUTPUT->footer();
    die;

}

echo $OUTPUT->header();

echo $error;

$mform->display();

echo \block_iomad_commerce\helper::get_basket_html();

echo $OUTPUT->footer();
