<?php
require_once(dirname(__FILE__) . '/../iomad_company_admin/lib.php');
\block_iomad_commerce\helper::require_commerce_enabled();

$itemid      = required_param('itemid', PARAM_INT);
$nlicenses   = optional_param('nlicenses', 0, PARAM_INT);
$licenses    = optional_param('licenses', 0, PARAM_INT);
$groupid    = optional_param('groupid', 0, PARAM_INT);
$event_type    = optional_param('event_type', 0, PARAM_INT);
$available_seats    = optional_param('available_seats', 0, PARAM_INT);
global $DB;
$PAGE->set_url(new moodle_url($CFG->wwwroot . "/blocks/learnpeek_shop/buynow.php", ['itemid' => $itemid, 'nlicenses' => $nlicenses,'groupid'=>$groupid]));
$PAGE->set_context(context_system::instance());
require_login();
// 19092025 suneet code
$companyid = iomad::get_my_companyid(context_system::instance());
$ucontext = context_user::instance($USER->id);
$ucurrent_user_roles = get_user_roles($ucontext, $USER->id);
$ucurrent_user_roles = array_values($ucurrent_user_roles);
$hascompanyreporterrole = (isset($ucurrent_user_roles[0]) && $ucurrent_user_roles[0]->shortname == 'companyreporter');
$issiteadmin = is_siteadmin();

$managerrole = $DB->get_record_sql('SELECT * FROM {company_users} WHERE userid = ? AND companyid = ?', array($USER->id,$companyid));
$managertype = $managerrole->managertype;
$checkpriceblocks = $DB->get_records('course_shopblockprice', ['itemid' => $itemid], 'price_bracket_start');
$totalpriceblocks = count($checkpriceblocks);
$ismanager = $managertype != 0;
$isindividual = !($ismanager || $hascompanyreporterrole || $issiteadmin);
if (!$isindividual && empty($checkpriceblocks)) {
   redirect(new moodle_url($CFG->wwwroot . '/blocks/learnpeek_shop/shop.php'),'This product is available only for individual users.',null,\core\output\notification::NOTIFY_WARNING);
    exit;
}
if(!$isindividual && $licenses==0){
    $licenses = 1;
    $nlicenses = 1;
}
// 19092025 suneet code

if ($licenses && !$nlicenses) {
    redirect(new moodle_url($CFG->wwwroot . '/blocks/learnpeek_shop/item.php', ['licenseformempty' => 1, 'id' => $itemid]));
}


$context = $PAGE->context;


// Get or create basket.
if (!empty($SESSION->basketid)) {
    if (!$basket = $DB->get_record('invoice', array('id' => $SESSION->basketid, 'status' => \block_iomad_commerce\helper::INVOICESTATUS_BASKET), '*')) {
        $basket = new \stdClass;
        $basket->userid = $USER->id;
        $basket->status = \block_iomad_commerce\helper::INVOICESTATUS_BASKET;
        $basket->date = time();
        $basket->id = $DB->insert_record('invoice', $basket, true);
        $SESSION->basketid = $basket->id;
    }
} else {
    $basket = new \stdClass;
    $basket->userid = $USER->id;
    $basket->status = \block_iomad_commerce\helper::INVOICESTATUS_BASKET;
    $basket->date = time();
    $basket->id = $DB->insert_record('invoice', $basket, true);
    $SESSION->basketid = $basket->id;
}


$invoiceitem = new \stdClass;
$invoiceitem->invoiceid = $basket->id;
$invoiceitem->invoiceableitemid = $itemid;

if ($nlicenses) {
    if ($block = \block_iomad_commerce\helper::get_license_block($itemid, $nlicenses)) {
        $invoiceitem->currency = $block->currency;
        $invoiceitem->price = $block->price;
        $invoiceitem->invoiceableitemtype = 'licenseblock';
        $invoiceitem->license_allocation = $nlicenses;
        $invoiceitem->license_validlength = $block->validlength;
        $invoiceitem->license_shelflife = $block->shelflife;
        if($available_seats){
            if($invoiceitem->license_allocation > $available_seats){
                if($event_type="trainingevent"){
                    redirect(new moodle_url($CFG->wwwroot.'/blocks/learnpeek_shop/tevent.php?itemid='.$itemid),'Please Try less than '.$invoiceitem->license_allocation. ' licenses, only '.$available_seats.' seats are allowed to purchase.', null, \core\output\notification::NOTIFY_ERROR);

                }else{
                    redirect(new moodle_url($CFG->wwwroot.'/blocks/learnpeek_shop/event.php?itemid='.$itemid),'Please Try less than '.$invoiceitem->license_allocation. ' licenses, only '.$available_seats.' seats are allowed to purchase.', null, \core\output\notification::NOTIFY_ERROR);

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
if($groupid){
    $invoiceitem->groupid = $groupid;
}
// suneet code 20092025 overwrite price
$freecoursdata = $DB->get_record("company_free_product", array("product_id" => $itemid));
if($freecoursdata){
     $invoiceitem->price = 0;
}
// suneet code 20092025

//suneet code for Fixed Duplicate product in card 

if(!$isindividual){
    if(!empty($SESSION->basketid)){
        $checkadditem = $DB->get_record_sql("SELECT ii.* FROM {invoiceitem} as ii INNER JOIN {invoice} as i ON i.id = ii.invoiceid WHERE i.id = ? AND ii.invoiceableitemid = ?",[$SESSION->basketid,$itemid]);
        if($checkadditem){
            $newquantity = $invoiceitem->license_allocation + $checkadditem->license_allocation;
            $getupdatedprice = $DB->get_record_sql("SELECT price FROM {course_shopblockprice} WHERE itemid = ? AND price_bracket_start <= ? ORDER BY price_bracket_start DESC LIMIT 1",[$itemid,$newquantity]);

            $updatedinvoiceitem = new \stdClass;
            $updatedinvoiceitem ->id = $checkadditem->id;
            $updatedinvoiceitem ->license_allocation = $newquantity;
            $updatedinvoiceitem ->price = $getupdatedprice->price;
            $updatedinvoiceitemdata = $DB->update_record('invoiceitem', $updatedinvoiceitem);
            if($updatedinvoiceitemdata){
                redirect(new moodle_url($CFG->wwwroot . '/blocks/learnpeek_shop/basket.php'));
                exit();
            }
        }
    }
}
if($isindividual){
    if(!empty($SESSION->basketid)){
        $checkadditem = $DB->get_record_sql("SELECT ii.* FROM {invoiceitem} as ii INNER JOIN {invoice} as i ON i.id = ii.invoiceid WHERE i.id = ? AND ii.invoiceableitemid = ?",[$SESSION->basketid,$itemid]);
        if($checkadditem){
            redirect(new moodle_url($CFG->wwwroot . '/blocks/learnpeek_shop/basket.php'));
            exit();
        }
    }
}
// end dublicate product
$DB->insert_record('invoiceitem', $invoiceitem);

redirect(new moodle_url($CFG->wwwroot . '/blocks/learnpeek_shop/basket.php'));
