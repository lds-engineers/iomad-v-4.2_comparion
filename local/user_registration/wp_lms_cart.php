<?php
require_once('../../config.php');
// require_once('header.php');
// require_once($CFG->dirroot . '/blocks/iomad_company_admin/lib.php');

global $USER, $SESSION, $CFG;

/**
 * Task: Check user cart is empty or not
 * Conditions:
 * - If cart is empty then redirect to the shop page
 * - else redirect to cart page
 */
$current_host = $_SERVER['HTTP_HOST'];
$getcompany = $DB->get_record_sql("SELECT * FROM {company} WHERE hostname LIKE ?", array($current_host));
$companyid = $getcompany->id;
if (isloggedin()) {
    // echo $SESSION->basketid;
    // echo "<pre>";
    // print_r($SESSION);
    // die();
    if ($SESSION->basketid) {
        $checkcart = $DB->get_record_sql("SELECT * FROM {invoiceitem} WHERE invoiceid = ?", [$SESSION->basketid]);
        if ($checkcart) {
            // echo "<pre>";
            // print_r($checkcart);
            redirect($CFG->wwwroot . '/blocks/learnpeek_shop/basket.php');
        } else {
            $homeurl = get_config(null, "main_site_url{$companyid}");
            header('Location: ' . $homeurl . '/shop');
            exit;
        }
    } else {
        $homeurl = get_config(null, "main_site_url{$companyid}");
        header('Location: ' . $homeurl . '/shop');
        exit;
    }
} else {
    if ($SESSION->basketid) {
        echo $SESSION->basketid;
        $checkcart = $DB->get_record_sql("SELECT * FROM {guest_cart_items} WHERE guestcartid = ?", [$SESSION->basketid]);
        if ($checkcart) {
            redirect($CFG->wwwroot . '/courses/guestcartitems.php');
        } else {
            $homeurl = get_config(null, "main_site_url{$companyid}");
            header('Location: ' . $homeurl . '/shop');
            exit;
        }
    } else {
        $homeurl = get_config(null, "main_site_url{$companyid}");
        header('Location: ' . $homeurl . '/shop');
        exit;
    }
}
