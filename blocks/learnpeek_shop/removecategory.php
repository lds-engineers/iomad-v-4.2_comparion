<?php
require('../../config.php');
require_login();

$PAGE->set_context(context_system::instance());
global $DB, $USER;

$id = required_param('id', PARAM_INT);
$returnurl = new moodle_url('/blocks/learnpeek_shop/productcategory.php');
$categorydata = $DB->get_record('product_category', ['id' => $id], '*', MUST_EXIST);

if($categorydata) {
	$DB->delete_records('product_category', ['id' => $id]);
}

redirect($returnurl, 'Category deleted successfully', 2);
