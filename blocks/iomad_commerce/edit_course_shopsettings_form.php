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

/**
 * Script to let a user create a course for a particular company.
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once(dirname(__FILE__) . '/../iomad_company_admin/lib.php');
require_once(dirname(__FILE__) . '/../../course/lib.php');

\block_iomad_commerce\helper::require_commerce_enabled();
$systemcontext = get_context_instance(CONTEXT_SYSTEM);
$returnurl      = optional_param('returnurl', '', PARAM_LOCALURL);
$shopsettingsid = optional_param('shopsettingsid', 0, PARAM_INTEGER);
$new            = optional_param('createnew', 0, PARAM_INTEGER);
$default        = optional_param('default', false, PARAM_BOOL);

$context = context_system::instance();
require_login();
$PAGE->set_context($context);

$urlparams = [];
if ($returnurl) {
    $urlparams['returnurl'] = $returnurl;
}
$urlparams['default'] = $default;

$companylist = new moodle_url('/blocks/iomad_commerce/courselist.php', $urlparams);

// Is this the company set of the default set?
if ($default && iomad::has_capability('block/iomad_commerce:manage_default', $context)) {
    $companyid = 0;
    // $companycourses = $DB->get_records_sql_menu("SELECT c.id, c.fullname
    //                                              FROM {course} c
    //                                              JOIN {iomad_courses} ic ON (c.id = ic.courseid)
    //                                              ORDER BY c.fullname");
    $defultcompnayid = iomad::get_my_companyid($context);
    $tempcompany = new company($defultcompnayid);
    $companycourses = $tempcompany->get_menu_courses(true, false);

} else {
    $companyid = iomad::get_my_companyid($context);
    $defultcompnayid = $companyid;
    $company = new company($companyid);
    $companycourses = $company->get_menu_courses(true, false);
}

$priceblocks = [];

if (!$new) {
    $isadding = false;
    $shopsettings = $DB->get_record('course_shopsettings', ['id' => $shopsettingsid], '*', MUST_EXIST);
    $courses = $DB->get_records('course_shopsettings_courses', ['itemid' => $shopsettingsid]);

    $shopsettings->itemcourses = [];
    foreach ($courses as $course) {
        $shopsettings->tags = \block_iomad_commerce\helper::get_course_tags($course->courseid);
        $shopsettings->itemcourses[] = $course->courseid;
    }
    
    //  Get any price bandings
    $shopsettings->block_start = [];
    $pricebands = $DB->get_records('course_shopblockprice', ['itemid' => $shopsettingsid], 'price_bracket_start');
    foreach ($pricebands as $priceband) {
        $shopsettings->item_block_start[] = $priceband->price_bracket_start;
        $shopsettings->item_block_price[] = $priceband->price;
    }
    $shopsettings->short_summary_editor = ['text' => $shopsettings->short_description];
    $shopsettings->summary_editor = ['text' => $shopsettings->long_description];
    $shopsettings->default = $default;
    $shopsettings->currency =  $shopsettings->single_purchase_currency;

    // LDS code 

    $product_image = file_get_submitted_draft_itemid('product_image');
        file_prepare_draft_area($product_image, $systemcontext->id, 'productpage', 'product_image', $shopsettings->id);
    $shopsettings->product_image       = $product_image;
    $getallprocat = array();
    // echo "SELECT product_category_id FROM {product_category_mapping} WHERE product_id =".$shopsettings->id;
    $mapped_packagecat =  $DB->get_record_sql('SELECT product_category_id FROM {product_category_mapping} WHERE product_id = ?',array($shopsettings->id));
    // echo "<pre>";
    // print_r($mapped_packagecat);
    // echo "========================================================";
    // print_r($shopsettings);
    // echo "</pre>";

    // foreach ($getmappedcat1 as $getmappedcat1value) {
    //     array_push($getallprocat,$getmappedcat1value->product_category_id);
    // }

    $shopsettings->product_category = $mapped_packagecat->product_category_id;

    $freeproduct = $DB->get_record("company_free_product",array("product_id"=>$shopsettings->id));
    if($freeproduct){
        $shopsettings->free_course = 1;
    }else{
       $shopsettings->free_course = 0; 
    }

    $hide_product = $DB->get_record("managecompany_product",array("product_id"=>$shopsettings->id));
    if($hide_product){
        $shopsettings->hide_product = $hide_product->hide_product;
    }

    //end lds code
    iomad::require_capability('block/iomad_commerce:edit_course', $context);
} else {
    $isadding = true;
    $shopsettingsid = 0;
    $shopsettings = (object) ['companyid' => $companyid];
    $course = null;
    $priceblocks = null;
    $shopsettings->default = $default;
    if (!empty($CFG->commerce_admin_currency)) {
        $shopsettings->currency = $CFG->commerce_admin_currency;
    } else {
        $shopsettings->currency = 'GBP';
    }

    iomad::require_capability('block/iomad_commerce:add_course', $context);
}

// Correct the navbar.
// Set the name for the page.
$linktext = get_string('course_list_title', 'block_iomad_commerce');
// Set the url.
$linkurl = new moodle_url('/blocks/iomad_commerce/courselist.php');

$title = 'edit_course_shopsettings';
if ($isadding) {
    $title = 'addnewcourse';
}

// Print the page header.
$PAGE->set_context($context);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);
$PAGE->set_heading(get_string($title, 'block_iomad_commerce'));

/* next line copied from /course/edit.php */
$editoroptions = ['maxfiles' => EDITOR_UNLIMITED_FILES,
                  'maxbytes' => $CFG->maxbytes,
                  'trusttext' => false,
                  'noclean' => true];

$mform = new block_iomad_commerce\forms\product_edit_form(new moodle_url('/blocks/iomad_commerce/edit_course_shopsettings_form.php'), $isadding, $shopsettingsid, $companycourses, $priceblocks, $editoroptions, $companyid,$defultcompnayid);
$mform->set_data($shopsettings);

if ($mform->is_cancelled()) {
    redirect($companylist);

} else if ($data = $mform->get_data()) {

    $data->userid = $USER->id;
    // echo "<pre>";
    // print_r($data);
    // echo "<pre>";
    // echo get_config('blocks_iomad_commerce', 'commerce_admin_currency');
     
    // die;
    $transaction = $DB->start_delegated_transaction();

    if ($isadding) {
        $data->single_purchase_currency = $data->currency;
        $data->id = $DB->insert_record('course_shopsettings', $data);
    } else {
        $data->single_purchase_currency = $data->currency;
        $DB->update_record('course_shopsettings', $data);
    }
    // lds code 
    file_save_draft_area_files($data->product_image, $systemcontext->id, 'productpage', "product_image", $data->id);
    // add free product
    if($data->free_course==1){
        $freeproduct = $DB->get_record("company_free_product",array("product_id"=>$data->id));
        if(empty($freeproduct)){
            $freeproductdata = new stdclass();
            $freeproductdata->product_id                = $data->id;
            $freeproductdata->company_id                = $companyid;
            $freeproductdata->status                    = 1;
            $freeproductdata->created_date             = time();
            // echo "neww";
            $addfreeproductdata = $DB->insert_record('company_free_product', $freeproductdata);
        }
    }else if($data->free_course==0){
        $freeproduct = $DB->get_record("company_free_product",array("product_id"=>$data->id));
        if($freeproduct){
            // echo "deleted";
            // die;
            $DB->delete_records("company_free_product", array('product_id'=>$data->id));
        }

    }
    // product hide shop page
    $hide_product = $DB->get_record("managecompany_product",array("product_id"=>$data->id));
    if($hide_product){
        $updatedhideproduct = new stdClass();
        $updatedhideproduct->id = $hide_product->id;
        $updatedhideproduct->hide_product = $data->hide_product;
        $updatedhideproduct->updated_time = time();
        $DB->update_record('managecompany_product', $updatedhideproduct);
    }else{
        $hideproduct = new stdclass();
        $hideproduct->product_id     = $data->id;
        $hideproduct->company_id     = $companyid;
        $hideproduct->hide_product   = $data->hide_product;
        $hideproduct->created_time   = time();
        $DB->insert_record('managecompany_product', $hideproduct);
    }

    // end product hide shop page

    if($data->program==1){
            $getmappedcat =  $DB->get_record('product_category_mapping',array('product_id'=>$data->id));
            if($data->product_category){
                if($getmappedcat){
                    // $DB->delete_records("product_category_mapping", array('product_id'=>$data->id));
                    $categorydata = new stdclass();
                    $categorydata->id                       = $getmappedcat->id;
                    $categorydata->product_category_id      = implode(",",$data->product_category);
                    $categorydata->updated_date             = time();
                    $insertcategorydata = $DB->update_record('product_category_mapping', $categorydata);
                }else{
                    $upcategorydata = new stdclass();
                    $upcategorydata->product_category_id      = implode(",",$data->product_category);;
                    $upcategorydata->product_id                = $data->id;
                    $upcategorydata->created_date             = time();
                    $upcategorydata = $DB->insert_record('product_category_mapping', $upcategorydata);
                }
            }else{
               if($getmappedcat){
                    $DB->delete_records("product_category_mapping", array('product_id'=>$data->id));
               } 
            }

    }
    
    // end code 
    $DB->delete_records('course_shopblockprice', ['itemid' => $data->id]);

    //suneet code for if licenseblock are enable and 1 are not set 
    if (isset($data->item_block_start) && is_array($data->item_block_start)) {
        $item_block_start = $data->item_block_start;
    } else {
        $item_block_start = [];
    }

    if (isset($data->item_block_price) && is_array($data->item_block_price)) {
        $item_block_price = $data->item_block_price;
    } else {
        $item_block_price = [];
    }

    $block_starts = array_map('intval', $item_block_start);
    $valid_blocks = array_filter($block_starts, function($v) { return $v > 0; });
    // print_r($valid_blocks);

    $has_start_1 = in_array(1, $block_starts);
    // If not found, add it manually using single_purchase_price.
    if (!$has_start_1 && !empty($valid_blocks)) {
        $priceblock = new stdClass();
        $priceblock->itemid = $data->id;
        $priceblock->currency = $data->currency;
        $priceblock->price_bracket_start = 1;
        $priceblock->price = $data->single_purchase_price;
        $priceblock->validlength = $data->single_purchase_validlength;
        $priceblock->shelflife = $data->single_purchase_shelflife;
        $DB->insert_record('course_shopblockprice', $priceblock, false, false); 
    }
    //end code

    // Deal with the License price blocks. 
    foreach ($data->item_block_start as $blockid => $itemblock) {
        if (!empty($itemblock)) {
            $priceblock = (object) [];
            $priceblock->itemid = $data->id;
            $priceblock->currency = $data->currency;
            $priceblock->price_bracket_start = $itemblock;
            $priceblock->price = $data->item_block_price[$blockid];
            $priceblock->validlength = $data->single_purchase_validlength;
            $priceblock->shelflife = $data->single_purchase_shelflife;

            $DB->insert_record('course_shopblockprice', $priceblock, false, false);
        }
    }

    // Delete associated courses.
    $DB->delete_records('course_shopsettings_courses', ['itemid' => $data->id]);
    foreach ($data->itemcourses as $itemcourse) {
        $DB->insert_record('course_shopsettings_courses', ['itemid' => $data->id, 'courseid' => $itemcourse]);
    }

    // Delete course_shoptag records.
    $DB->delete_records('course_shoptag', ['itemid' => $data->id]);

    // Find shoptag ids.
    $tags = preg_split('/\s*,\s*/', $data->tags);
    $newcourseshoptagrecord = (object) [];
    $newcourseshoptagrecord->itemid = $data->id;
    foreach ($tags as $tag) {
        if (!$st = $DB->get_record('shoptag', ['tag' => $tag])) {
            $st = (object) [];
            $st->id = $DB->insert_record('shoptag', (object) ['tag' => $tag], true);
        }

        $newcourseshoptagrecord->shoptagid = $st->id;
        $DB->insert_record('course_shoptag', $newcourseshoptagrecord);
    }

    $transaction->allow_commit();

    redirect($companylist, get_string('itemaddedsuccessfully', 'block_iomad_commerce'), null, \core\output\notification::NOTIFY_SUCCESS);

} else {

    echo $OUTPUT->header();

    $mform->display();
echo "<style>
#fitem_id_tags, .tags{
    display:none !important;
}
</style>";
    echo $OUTPUT->footer();
}

