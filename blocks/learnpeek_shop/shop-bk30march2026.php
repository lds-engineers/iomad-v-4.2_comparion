<?php
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/../iomad_company_admin/lib.php');
require_once($CFG->dirroot . '/' . $CFG->admin . '/tool/courserating/lib.php');
require_once("locallib.php");

\block_iomad_commerce\helper::require_commerce_enabled();

$sort         = optional_param('sort', 'name', PARAM_ALPHA);
$dir          = optional_param('dir', 'ASC', PARAM_ALPHA);
$page         = optional_param('page', 0, PARAM_INT);
$companyid    = optional_param('companyid', 0, PARAM_INT);
// $perpage      = optional_param('perpage', $CFG->iomad_max_list_courses, PARAM_INT);        // How many per page.
$perpage = isset($_GET['perpage']) ? intval($_GET['perpage']) : 10;
$PAGE->requires->jquery();

// Setup the navbar.
// Set the name for the page.
$linktext = get_string('shop_title', 'block_learnpeek_shop');
// Set the url.
$linkurl = new moodle_url('/blocks/learnpeek_shop/shop.php');

require_login();

// Page stuff.
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);
$PAGE->navbar->add($linktext);

$contextuser = context_user::instance($USER->id); // User context
$current_user_roles = get_user_roles($contextuser, $USER->id);
$manag_role = array_column($current_user_roles, 'shortname');

$baseurl = new moodle_url('/blocks/learnpeek_shop/shop.php', array('sort' => $sort, 'dir' => $dir, 'perpage' => $perpage));
$returnurl = $baseurl;
if(empty($companyid)){
    $companyid = iomad::get_my_companyid(context_system::instance());
}

/// add code for busers course restriction 
$usertype = $DB->get_record("company_users", array("userid" => $USER->id));
if($usertype->managertype == 2){
    $comapnybuser = $usertype->userid;
}else if($usertype->managertype == 0){
    $childbuser = $DB->get_record("business_user_child", array("child_id" => $USER->id));
    $comapnybuser = $childbuser->parent_id;
}else{
   $comapnybuser = 0; 
}
//end code

// Display the page header.

echo $OUTPUT->header();
echo "
<link rel='stylesheet' href='css/shopstyle.css'>

<style>

.review-star-rating .star.half::before {
  left: -4px !important;
  top: -4px !important;
}
.cst_card_view.course-item {
    padding: 0 5px;
}

@media (max-width: 1213px) {
    .card {
        max-height: auto !important;
        min-height: auto !important;
    }
}
</style>";
echo "<h3>" . get_string('shop_courses_heading', 'block_learnpeek_shop') . "</h3><br>";
flush();


$company_users = $DB->get_records("company_users", array("companyid" => $companyid));
$company_user_ids = array_map(function($user) {
    return $user->userid;
}, $company_users);


if (is_siteadmin()) {
$selectedcompanies = $DB->get_records_sql("SELECT * FROM {company} WHERE suspended !=1 ORDER BY name ASC");
echo '<div class="row align-items-center p-2 w-100 my-2">
    <label for="compnay" class="mx-2">Select Company</label>
    <form id="companyChanged">
        <select name="companyid" id="company" class="p-2 rounded w-100">';
            foreach ($selectedcompanies as $companykey => $companyvalue) {
                echo '<option value="' . $companyvalue->id . '" ' . (($companyid == $companyvalue->id) ? 'selected' : '') . '>' . $companyvalue->name . '</option>';
            };
        echo '
        </select>
    <form>
</div>';
}
// \block_iomad_commerce\helper::show_basket_info();

// // ...**********tag listing and filtering*****************.

// $shoptags = \block_iomad_commerce\helper::get_shop_tags();
    // $shoptags = company_mapped_category($companyid);
    // if (count($shoptags)) {
    //     echo get_string('filter_by_tag', 'block_iomad_commerce');
    //     foreach ($shoptags as $shoptag) {
    //         // echo "<a href='?tag=$shoptag'>$shoptag</a> ";
    //         echo "<a href='?tag={$shoptag['id']}'>{$shoptag['name']}</a> ";
    //     }
    // }

if($comapnybuser){
    $shoptagss = buser_company_mapped_category($companyid,$comapnybuser);
}else{
    $shoptagss = company_mapped_category($companyid);

}
// echo "<pre>";
// echo $comapnybuser;
// print_r($usertype);
// print_r($shoptagss);
// echo "</pre>";
$shoptags = array_column($shoptagss,'name','id');
if (array_key_exists('tag', $_GET)) {
    // $shoptags = \block_iomad_commerce\helper::get_shop_tags();
  
    $qtag = optional_param('tag', '', PARAM_NOTAGS);
    if (isset($shoptags[$qtag])) {
        $SESSION->shoptag = $qtag;
    } else {
        unset($SESSION->shoptag);
    }
} 


///
//get all event course with group
$allevents = array();
// echo "SELECT cm.course FROM {course_modules} AS cm INNER JOIN {modules} as m ON cm.module = m.id INNER JOIN {company_course} as cc ON cc.courseid = cm.course WHERE cm.deletioninprogress =0 AND  m.name = 'zoom' AND cc.companyid = '".$companyid."' GROUP BY cm.course";

// echo "SELECT cm.course, m.name 
// FROM mdl_course_modules AS cm 
// INNER JOIN mdl_modules AS m ON cm.module = m.id 
// INNER JOIN mdl_company_course AS cc ON cc.courseid = cm.course 
// WHERE cm.deletioninprogress = 0 
// AND (m.name = 'reservation' OR m.name = 'zoom') 
// AND cc.companyid = '39' 
// GROUP BY cm.course, m.name";



$getevent =$DB->get_records_sql("SELECT cm.course 
    FROM {course_modules} AS cm 
    INNER JOIN {modules} as m ON cm.module = m.id 
    INNER JOIN {company_course} as cc ON cc.courseid = cm.course 
    WHERE cm.deletioninprogress =0 
    AND (m.name = 'reservation' OR m.name = 'zoom') 
    AND cc.companyid = '".$companyid."' 
    GROUP BY cm.course"); 
foreach ($getevent as $getevents) {
   array_push($allevents, $getevents->course);
}
if($allevents){ 
    $eventcourseid = implode(",", $allevents);
    $shop_courseid = ' AND c.id NOT IN ('.$eventcourseid.')';
    $event_courseid = ' AND c.id in ('.$eventcourseid.')';
}else{
    $shop_courseid = ' AND 1=1';
    $event_courseid = 0;
}


//==========================================
$all_tevents = array();
$get_tevent =$DB->get_records_sql("SELECT cm.course 
    FROM {course_modules} AS cm 
    INNER JOIN {modules} as m ON cm.module = m.id 
    INNER JOIN {company_course} as cc ON cc.courseid = cm.course 
    WHERE cm.deletioninprogress =0 
    AND m.name ='trainingevent' 
    AND cc.companyid = '".$companyid."' 
    GROUP BY cm.course"); 
foreach ($get_tevent as $get_tevents) {
   array_push($all_tevents, $get_tevents->course);
}
if($all_tevents){ 
    $tevent_courseid = implode(",", $all_tevents);
    // $shop_courseid = ' AND c.id NOT IN ('.$tevent_courseid.')';
    $tevent_courseid = ' AND c.id in ('.$tevent_courseid.')';
}else{
    // $shop_courseid = ' AND 1=1';
    $tevent_courseid = 0;
}
// echo "<pre>";
// print_r($shop_courseid);
// echo "==========================";
// print_r($tevent_courseid);
// echo "</pre>";


//get all course package
$allcoursepackage = array();
$getpackage = $DB->get_records_sql("SELECT * FROM {course_shopsettings} WHERE program = ? AND companyid = ?", array(1,$companyid));
foreach ($getpackage as $allpackage) {
     array_push($allcoursepackage, $allpackage->id);
}
if($allcoursepackage){
    $packagecourse = implode(",", $allcoursepackage);
}
if(empty($packagecourse)){
    $packagecourse = 0;
}

// echo "<pre>";
// print_r($packagecourse);
// echo "</pre>";

$tagjoin = '';
$tagwhere = '';
$tagfilters = '';
// $sqlparams = ['companyid' => $companyid];
// code for pagination 
$limit = $perpage;
$limit = isset($limit) && is_numeric($limit) ? (int)$limit : 5;
$page = isset($page) && is_numeric($page) ? (int)$page : 0; 

// Calculate the offset
$offset = ($page) * $limit;

// Bind the parameters for SQL
$sqlparams = [
    'companyid' => $companyid, 
    'limit' => $limit,
    'offset' => $offset 
];

//end 
// if (isset($SESSION->shoptag) && $SESSION->shoptag != '') {
//     $tagfilters = "<li>";
//     $tagfilters .= get_string('filtered_by_tag', 'block_iomad_commerce', '<em class="bg_custom text-white p-2 rounded mx-2">' . $shoptags[$SESSION->shoptag] . '</em>' );
//     $tagfilters .= "</li>";

//     // $tagjoin = 'INNER JOIN {course_shoptag} cst ON cst.itemid = csc.itemid
//     //             INNER JOIN {shoptag} st ON cst.shoptagid = st.id';
//     // $tagwhere = ' AND st.tag = :tag ';
//     $tagwhere = ' AND c.category = :tag ';
//     $sqlparams['tag'] = $SESSION->shoptag;
// } else {
//     // $shoptags = \block_iomad_commerce\helper::get_shop_tags();
//     $shoptags = company_mapped_category($companyid);
//     if (count($shoptags)) {
//         echo get_string('filter_by_tag', 'block_iomad_commerce');
//         foreach ($shoptags as $shoptag) {
//             // echo "<a href='?tag=$shoptag'>$shoptag</a> ";
//             echo "<a class='bg_custom btn_category p-2 rounded text-white' href='?tag={$shoptag['id']}'>{$shoptag['name']}</a> ";
//         }
//     }
// }

//updated code suneet 
//code updated
if (isset($SESSION->shoptag) && $SESSION->shoptag != '') {

    $subcategories = lp_get_all_subcategories($SESSION->shoptag);
    if ($subcategories) {
        $SESSION->shoptag = $SESSION->shoptag . ',' . $subcategories;
    }

    // Prepare filter tag name display
    $tagfilters = "<li>";
    if (!empty($shoptags[$_GET['tag']])) {
        $tagfilters .= get_string(
            'filtered_by_tag',
            'block_iomad_commerce',
            '<em class="bg_custom text-white p-2 rounded mx-2">' . $shoptags[$_GET['tag']] . '</em>'
        );
    }
    $tagfilters .= "</li>";

    // Prepare SQL condition using IN()
    $catids = explode(',', $SESSION->shoptag);
    list($in_sql, $in_params) = $DB->get_in_or_equal($catids, SQL_PARAMS_NAMED, 'cat');
    $tagwhere = " AND c.category $in_sql ";
    $sqlparams = array_merge($sqlparams ?? [], $in_params);

    // echo $tagwhere;

} else {
    // Show available categories
    if($comapnybuser){
        $shoptags = buser_company_mapped_category($companyid,$comapnybuser);
    }else{
        $shoptags = company_mapped_category($companyid);

    }
    // $shoptags = company_mapped_category($companyid);
    if (count($shoptags)) {
        echo get_string('filter_by_tag', 'block_iomad_commerce');
        foreach ($shoptags as $shoptag) {
            echo "<a class='bg_custom btn_category p-2 rounded text-white mx-1 my-2 d-inline-block' href='?tag={$shoptag['id']}'>{$shoptag['name']}</a> "; 
        }
    }
}

// end udated code

echo "<ul id='filtering' class='my-3 p-0'>";
echo $tagfilters;

// ...***********search*****************.
$searchkey = '';
if (array_key_exists('q', $_GET)) {
    $searchkey = optional_param('q', '', PARAM_NOTAGS);
    if ($searchkey) {
        $SESSION->shopsearch = $searchkey;
    } else {
        unset($SESSION->shopsearch);
    }
}

$searchwhere = '';
if (isset($SESSION->shopsearch)) {
    $searchkey = $SESSION->shopsearch;
    echo "<li>";
    echo get_string('filtered_by_search', 'block_iomad_commerce', '<em>' . $searchkey . '</em>');
    echo "</li>";

    $searchwhere = ' AND
        (' . $DB->sql_like("c.fullname", ":searchkey1") . '
         OR
         ' . $DB->sql_like("c.shortname", ":searchkey2") . '
         OR
         ' . $DB->sql_like("c.summary", ":searchkey3") . '
         OR
         ' . $DB->sql_like("css.short_description", ":searchkey4") . '
         OR
         ' . $DB->sql_like("css.long_description", ":searchkey5") . '
         OR
         ' . $DB->sql_like("css.name", ":searchkey6") . '
        )
    ';
    for ($i = 1; $i < 7; $i++) {
        $sqlparams['searchkey' . $i] = '%' . $searchkey . '%';
    }
}

if (count($sqlparams) > 1) {
    echo '<a href="?tag=&q=">' . get_string('remove_filter', 'block_iomad_commerce') . '</a>';
}

echo "</ul>";

echo html_writer::start_tag('div', ['style' => 'display:flex; align-items:center; padding-bottom:10px; flex-wrap:wrap;', 'id' => 'shoptagsearch']);
echo html_writer::tag('span', get_string('search'));
echo "<form method='get'><input type='text' name='q' class='form-control' style='width: 195px;margin-left:20px;' value='$searchkey' /></form>";
echo '
<div class="align-items-center paginationfrom">
  <div class="">
    <form id="paginationdatachange" class="d-flex align-items-center justify-content-between" method="get">
    <label for="perpage" class="mx-2 mb-0" style="white-space:nowrap">' . get_string('showentries', 'block_learnpeek_shop') . '</label>
        <select name="perpage" id="perpage" class="p-2 rounded w-100">
            <option value="10" ' . ($perpage == 10 ? 'selected' : '') . '>10</option>
            <option value="25" ' . ($perpage == 25 ? 'selected' : '') . '>25</option>
            <option value="50" ' . ($perpage == 50 ? 'selected' : '') . '>50</option>
            <option value="100" ' . ($perpage == 100 ? 'selected' : '') . '>100</option>
        </select> 
    
</form>
  </div>
</div>
';
echo html_writer::end_tag('div');

// ...***********create course list sql (includes filtering on tags)*****************.
$typewhere = "";
if (!iomad::has_capability('block/iomad_commerce:buyinbulk', $context)) {
    $typewhere = " AND css.allow_single_purchase = 1 ";
}

$categoryfilter = '';
if(!empty($comapnybuser)){
    if($cmappedctg = $DB->get_record_sql("SELECT * FROM {businessuser_courserestriction} WHERE company_id = ? AND user_id = ?", array($companyid,$comapnybuser))){
        if(!empty($cmappedctg->course_category)){
            $categoryfilter = ' and c.category in('.$cmappedctg->course_category.') ';
        }
    }
}

     $sql = 'FROM {course_shopsettings} css
        JOIN {course_shopsettings_courses} csc ON (css.id = csc.itemid)
        JOIN {course} c ON (csc.courseid = c.id '.$shop_courseid.')
        INNER JOIN {company_course} as cc ON cc.courseid=csc.courseid AND cc.companyid = css.companyid
            ' . $tagjoin . '
            LEFT JOIN {course_shopblockprice} sbp ON (css.id = sbp.itemid
                                                  AND sbp.id = (SELECT id FROM {course_shopblockprice}
                                                  WHERE itemid = css.id ORDER BY price LIMIT 1 ))
        WHERE css.enabled = 1 AND css.id NOT IN('.$packagecourse.')
        AND css.companyid ='.$companyid.'
        ' . $tagwhere . $searchwhere . $typewhere . $categoryfilter . '
        GROUP BY css.id, sbp.id ORDER BY css.name ';

// Get the number of Courses.
$items = $DB->get_records_sql('SELECT distinct css.* ,csc.courseid, c.fullname, c.category ' . $sql .'LIMIT '.$offset.', '.$limit, $sqlparams);

// $query = 'SELECT distinct css.* ,csc.courseid, c.fullname, c.category ' . $sql .'LIMIT '.$offset.', '.$limit;

// echo "<pre>";
// echo $query;
// echo "</pre>";


// echo 'SELECT distinct css.* ,csc.courseid ' . $sql .'LIMIT '.$offset.', '.$limit;
        // echo 'SELECT distinct css.* ' . $sql;
// $items = $DB->get_records_sql( 'SELECT distinct css.* ,csc.courseid ' . $sql, $sqlparams);

// echo "<pre>";
// print_r(array('SELECT distinct css.* ,csc.courseid, c.fullname ' . $sql .'LIMIT '.$offset.', '.$limit, $sqlparams));


$totalitem = $DB->get_records_sql('SELECT distinct css.* ' . $sql, $sqlparams);
$itemcount = count($totalitem);

// echo $OUTPUT->paging_bar($itemcount, $page, $perpage, $baseurl);


// echo "<div><h4   class='py-3 mt-3' style='color: #2174ff; text-decoration: underline'> Packages</h4>";
echo "<div><h4 class='py-3 mt-3' style='color: #2174ff; text-decoration: underline'>". get_string('packages', 'block_learnpeek_shop') ."</h4>";
if($packagecourse){
   
    //    ************  Course Package   ***********   //

     $sql = 'FROM {course_shopsettings} css
        JOIN {course_shopsettings_courses} csc ON (css.id = csc.itemid)
        JOIN {course} c ON (csc.courseid = c.id '.$shop_courseid.')
        INNER JOIN {company_course} as cc ON cc.courseid=csc.courseid AND cc.companyid = css.companyid
            ' . $tagjoin . '
            LEFT JOIN {course_shopblockprice} sbp ON (css.id = sbp.itemid
                                                  AND sbp.id = (SELECT id FROM {course_shopblockprice}
                                                  WHERE itemid = css.id ORDER BY price LIMIT 1 ))
        WHERE css.enabled = 1 AND css.id IN ('.$packagecourse.') 
        AND css.companyid ='.$companyid.'
        ' . //$tagwhere . 
        $searchwhere . $typewhere . $categoryfilter . '
        GROUP BY css.id, sbp.id ORDER BY css.name ';

    // Get the number of Courses.
    //echo 'SELECT distinct css.* ,csc.courseid, c.fullname ' . $sql , $sqlparams;
    $packageitems = $DB->get_records_sql('SELECT distinct css.* ,csc.courseid, c.fullname,c.category ' . $sql .'LIMIT '.$offset.', '.$limit, $sqlparams);
    $totalpackage = $DB->get_records_sql('SELECT distinct css.* ' . $sql, $sqlparams);
    $packagecount = count($totalpackage);
    //     echo "<pre>";
    // print_r($packageitems);
    // print_r($totalpackage);
    // echo "</pre>";
    echo $OUTPUT->paging_bar($packagecount, $page, $perpage, $baseurl);


    if($packagecount){
        $strbuynow = get_string('buynow', 'block_learnpeek_shop');
        // $strmoreinfo = get_string('moreinfo', 'block_iomad_commerce');
        $strmoreinfo = "Read More";

        $table = new html_table();
        $table->head = [get_string('Course', 'block_iomad_commerce'), "", ""];
        $table->align = ["left", "center", "center"];
        $table->width = "95%";
        echo ' <div class="row">';
        $allmappedcategory = array();
        $allmappedcoursess = array();
        $filtercategory = "";
        foreach ($packageitems as $packageitem) {
            $hide_product = $DB->get_record_sql("SELECT id FROM {managecompany_product} WHERE product_id = ? AND company_id = ? AND hide_product = 1",[$packageitem->id, $companyid]);
            if ($hide_product) {
                continue;
            }
            $checkpackagecat = $DB->get_record_sql("SELECT product_category_id FROM {product_category_mapping} WHERE product_id =?",array($packageitem->id));
            $filtercategory = array_filter(explode(',',$SESSION->shoptag));
            $mapped_package_prodcat = explode(',',$checkpackagecat->product_category_id);
            $check_common_cat = array_intersect($filtercategory, $mapped_package_prodcat);
            if(!empty($filtercategory) && empty($check_common_cat)){
                continue;
            }
            $totalstudents = 0;
            $allmappedcourse = $DB->get_records("course_shopsettings_courses",array("itemid"=>$packageitem->id));
            foreach ($allmappedcourse as $mappedcourse) {

                $courseexists = $DB->record_exists('course', ['id' => $mappedcourse->courseid]);
                if ($courseexists) {
                    $totalstudents += total_enrolled_compnayusers_bycourse($mappedcourse->courseid);
                    array_push($allmappedcoursess, $mappedcourse->courseid);
                }

               
            }
            if($allmappedcoursess){
                $allcatcourse = implode(",", $allmappedcoursess);
            }else{
                $allcatcourse = 0;
            }
            $courerating = $DB->get_records_sql("SELECT * FROM {tool_courserating_rating} WHERE courseid in ($allcatcourse) AND status = 1");
            $company_user_ratings = array_filter($courerating, function($rating) use ($company_user_ids) {
                return in_array($rating->userid, $company_user_ids);
            });

            if ($company_user_ratings) {
                $total = 0;
                $finalCount = 0;
                $count = isset($company_user_ratings) ? count($company_user_ratings) : 0;
                foreach ($company_user_ratings as $rating) {
                    $total += $rating->rating;
                    $finalCount++;
                }
                $avgrating = sprintf("%.1f", $total / $count);
            } else {
                $finalCount = 0;
                $avgrating = 0;
            }

            // echo "<pre>";
            // print_r($avgrating);
            // print_r($finalCount);
            // echo "</pre>";
            //get all course category

            $allprocoursecat = $DB->get_records_sql("SELECT DISTINCT category FROM {course} WHERE id in(".$allcatcourse.")");
            foreach ($allprocoursecat as $coursecategory) {
                array_push($allmappedcategory, $coursecategory->category);
            }

            if($allmappedcategory){
                $allcatimplode = implode(",", $allmappedcategory);
            }else{
                $allcatimplode = 0;
            }
            // $categoryname = $DB->get_records_sql("SELECT * FROM {course_categories} WHERE id in(".$allcatimplode.")");
            $pkg_mappedcategory = $DB->get_record_sql("SELECT * FROM {product_category_mapping} WHERE product_id = ?",array($packageitem->id));
            $campany_mapp_catg = $DB->get_record_sql("SELECT * FROM {company_category_mapping} WHERE company_id = ?",array($companyid));
            
            // echo $campany_mapp_catg->course_category;
            $com_mcatg = explode(",", $campany_mapp_catg->course_category);
            $pk_mcatg = explode(",", $pkg_mappedcategory->product_category_id);
            $comman_ctg = array_filter(array_intersect($com_mcatg, $pk_mcatg));
            if(!empty($comman_ctg)){
                $package_catid = implode(',', $comman_ctg);
            }else{
                $package_catid = 0;
            }
            $categoryname = $DB->get_records_sql("SELECT * FROM {course_categories} WHERE id in(".$package_catid.")");
            // echo "<pre>";
            // print_r($categoryname);
            // echo "</pre>";






            
            $available = ($packageitem->allow_single_purchase || $packageitem->allow_license_blocks) &&
                         (iomad::has_capability('block/iomad_commerce:buyitnow', $context) || iomad::has_capability('block/iomad_commerce:buyinbulk', $context));
            $price = \block_iomad_commerce\helper::get_lowest_price_text($packageitem);

            if ($available) {
                if ($packageitem->allow_single_purchase) {
                    $buynowurl = new moodle_url($CFG->wwwroot . "/blocks/learnpeek_shop/buynow.php", ['itemid' => $packageitem->id]);
                } else {
                    $buynowurl = new moodle_url($CFG->wwwroot . "/blocks/learnpeek_shop/item.php", ['itemid' => $packageitem->id]);
                }
                $buynowbutton = "<a href='" . $buynowurl->out() . "' class='cst_btn_2 btn rounded-0'>$strbuynow</a>";

                $moreinfourl = new moodle_url($CFG->wwwroot . "/blocks/learnpeek_shop/item.php", ['itemid' => $packageitem->id]);
                $moreinfobutton = "<span class='mr-2'>$price</span> <a href='" . $moreinfourl->out() . "'  class='text-white fw-bold cst_btn btn  mx-2'>$strmoreinfo</a>";
            } else {
                $buynowbutton = "";
                $moreinfourl = new moodle_url($CFG->wwwroot . "/blocks/learnpeek_shop/item.php", ['itemid' => $packageitem->id]);
                $moreinfobutton = "<span class='mr-2'>$price</span>  <a href='" . $moreinfourl->out() . "' class=' text-primary cst_btn btn mx-2'>$strmoreinfo</a>";
            }
            $productimage = $DB->get_record_sql("SELECT * FROM {files} WHERE contextid= 1 AND component='productpage' AND itemid=? AND filesize > 0 AND filearea = ?", array($packageitem->id,'product_image'));
            // echo "<pre>";
            // print_r($productimage);
            // echo "</pre>";
            $productimageurl = $CFG->wwwroot.'/blocks/custom_mycourse/file.php?id='.$productimage->pathnamehash;
            echo '
            <div class="cst_card_view course-item  col-sm-6 col-md-6 col-lg-4">
                <div class="wrapper bg_custom">
                    <div class="featured-img">';
                    if($categoryname){
                        echo '<div class="d-flex flex-wrap" style="position: absolute;">';
                        foreach ($categoryname as $category) {
                            echo '<span class="shopcourse-category mx-1">'.$category->name.'</span>';
                        }
                        echo '</div>';
                    }
                    if($productimage){
                        echo '<img src="'.$productimageurl.'" alt="'.$packageitem->name.'" class="courseimage">';
                    }else{
                        $dumyimage = $CFG->wwwroot.'/blocks/learnpeek_shop/pix/learning.jpeg';
                        echo '<img src="'.$dumyimage.'" alt="'.$packageitem->name.'" class="courseimage">';
                    };

                    echo '
                        <!--<img loading="lazy" decoding="async" src="https://pdhstar.com/wp-content/uploads/OSHA-10-G-AP-342x240.png" alt="" class="" width="342" height="240" />-->
                        <a href="#" class="img-link"></a>
                    </div>
                    <div class="course-content">
                    <div class="d-flex text-white">
                        <h5 class="course-title"> ' . format_string($packageitem->name) . '</h5>
                        </div>
                        <div class="price my-2" style="color: #2174ff;">
                         <!-- <span class="course-origin-price"> $89.00</span>-->
                         <span class="course-price mx-2 aaa d-flex align-items-center text-white">'.$moreinfobutton. '</span>
                        </div> 
                        <div class="d-flex justify-content-between  cst_div_element">
                         <div col-sm-6>';

                         if($packageitem->single_purchase_shelflife && in_array("companydepartmentmanager", $manag_role) || in_array("companymanager", $manag_role)){
                            echo '<span class="course-price w-100 text-white">
                               <a href="'.$CFG->wwwroot.'/blocks/learnpeek_shop/item.php?itemid='.$packageitem->id.'" class="cst_btn_2 btn rounded-0">Add to cart</a>
                               </span>';

                         }else{
                            echo '<span class="course-price w-100 text-white">' . $buynowbutton . '</span>';

                         }
                         echo '
                        </div>
                        <div class="course-meta">
                            <div class="course_meta_lfx col-sm-12 d-flex flex-wrap align-items-center justify-content-start m-0">
                                <span class="star text-white review-star-rating d-flex flex-wrap align-items-center"><small style="font-size:16px">Review:</small><div class="mx-1">' . displayStarRating($avgrating) . '</div> </span>
                                <span class="students text-white"><i class="fa fa-users" aria-hidden="true"></i> '. $totalstudents .' </span>
                            </div>    
                        </div>
                      </div>
                    </div>  
                </div>
            </div>';
        }
    }else{
      echo "<p>There are Course Package on the shop matching your criteria.</p>";
    }
}else{
    echo "<p>There are no Course Package on the shop matching your criteria.</p>";
}
    echo "</div>"; 



echo "<div><h4   class='py-3 mt-3' style='color: #2174ff; text-decoration: underline'>". get_string('ondemandcourses', 'block_learnpeek_shop') ."</h4>";
echo $OUTPUT->paging_bar($itemcount, $page, $perpage, $baseurl);
if ($itemcount) { 
    // echo 'SELECT distinct css.* ,csc.courseid, c.fullname, c.category ' . $sql .'LIMIT '.$offset.', '.$limit, $sqlparams;
    // echo $shop_courseid;
    // echo "<br>";
    // echo $tevent_courseid;

    $strbuynow = get_string('buynow', 'block_learnpeek_shop');
    // $strmoreinfo = get_string('moreinfo', 'block_iomad_commerce');
    // $strmoreinfo = "Read More";

    $table = new html_table();
    $table->head = [get_string('Course', 'block_iomad_commerce'), "", ""];
    $table->align = ["left", "center", "center"];
    $table->width = "95%";
    echo ' <div class="row">';

    

    foreach ($items as $item) {
        // if($item->single_purchase_shelflife && $manag_role[0] == "companydepartmentmanager"){
        //     $strmoreinfo = "Buy Seats";
        // }else{
        //     $strmoreinfo = "Read More";
        // }
        $hide_product = $DB->get_record_sql("SELECT id FROM {managecompany_product} WHERE product_id = ? AND company_id = ? AND hide_product = 1",[$item->id, $companyid]);
        if ($hide_product) {
            continue;
        }
        $redirecturlfiled = $DB->get_record("customfield_field",array("shortname"=>"course_redirect_url"));
        $redirecturldata = $DB->get_record_sql("SELECT * FROM {customfield_data} WHERE fieldid = ? AND instanceid =?",array($redirecturlfiled->id,$item->courseid));
        
        $strmoreinfo = "Read More";
        $courseexists = $DB->record_exists('course', ['id' => $item->courseid]);
        if($courseexists){
            $totalstudents = total_enrolled_compnayusers_bycourse($item->courseid);

        }

        $coursecat = $DB->get_record("course_categories",array("id"=>$item->category));
        //get course avgrating

        $courerating = $DB->get_records('tool_courserating_rating', ['courseid' => $item->courseid, 'status' => 1], 'timecreated DESC');
        $company_user_ratings = array_filter($courerating, function($rating) use ($company_user_ids) {
            return in_array($rating->userid, $company_user_ids);
        });

        if ($company_user_ratings) {
            $total = 0;
            $finalCount = 0;
            $count = isset($company_user_ratings) ? count($company_user_ratings) : 0;
            foreach ($company_user_ratings as $rating) {
                $total += $rating->rating;
                $finalCount++;
            }
            $avgrating = sprintf("%.1f", $total / $count);
        } else {
            $avgrating = 0;
            $finalCount = 0;
        }

        $available = ($item->allow_single_purchase || $item->allow_license_blocks) &&
                     (iomad::has_capability('block/iomad_commerce:buyitnow', $context) || iomad::has_capability('block/iomad_commerce:buyinbulk', $context));
        $price = \block_iomad_commerce\helper::get_lowest_price_text($item);

        if(in_array($item->courseid, $all_tevents)){
            $buynowurl = new moodle_url($CFG->wwwroot . "/blocks/learnpeek_shop/tevent.php", ['itemid' => $item->id]);
            $buynowbutton = "<a href='" . $buynowurl->out() . "' class='cst_btn_2 btn rounded-0'>$strbuynow</a>";
            $moreinfourl = new moodle_url($CFG->wwwroot . "/blocks/learnpeek_shop/tevent.php", ['itemid' => $item->id]);
            $moreinfobutton = "<span class='mr-2'>$price</span>  <a href='" . $moreinfourl->out() . "'  class='text-white fw-bold cst_btn btn  mx-2'>$strmoreinfo</a>";
        } else {
            if ($available) {
                    if ($item->allow_single_purchase) {
                        $buynowurl = new moodle_url($CFG->wwwroot . "/blocks/learnpeek_shop/buynow.php", ['itemid' => $item->id, 'courseid' => $item->courseid]);
                    } else {
                        $buynowurl = new moodle_url($CFG->wwwroot . "/blocks/learnpeek_shop/item.php", ['itemid' => $item->id, 'courseid' => $item->courseid]);
                    }
                $buynowbutton = "<a href='" . $buynowurl->out() . "' class='cst_btn_2 btn rounded-0'>$strbuynow</a>";

                if($redirecturldata && !empty($redirecturldata->value)){
                    $moreinfourl = $redirecturldata->value;
                    $moreinfobutton = "<span class='mr-2'>$price</span> <a href='" . $moreinfourl . "'  class='text-white fw-bold cst_btn btn  mx-2'>$strmoreinfo</a>";
                }else{
                    $moreinfourl = new moodle_url($CFG->wwwroot . "/blocks/learnpeek_shop/item.php", ['itemid' => $item->id]);
                    $moreinfobutton = "<span class='mr-2'>$price</span>  <a href='" . $moreinfourl->out() . "'  class='text-white fw-bold cst_btn btn  mx-2'>$strmoreinfo</a>";
                }
                // $moreinfourl = new moodle_url($CFG->wwwroot . "/blocks/learnpeek_shop/tevent.php", ['itemid' => $item->id]);
                // $moreinfobutton = "$price <a href='" . $moreinfourl->out() . "'  class='text-white fw-bold cst_btn btn  mx-2'>$strmoreinfo</a>";
            } else {
                $buynowbutton = "";
                if($redirecturldata && !empty($redirecturldata->value)){
                    $moreinfourl = $redirecturldata->value;
                    $moreinfobutton = "<span class='mr-2'>$price</span> <a href='" . $moreinfourl . "'  class='text-white fw-bold cst_btn btn  mx-2'>$strmoreinfo</a>";
                }else{
                    $moreinfourl = new moodle_url($CFG->wwwroot . "/blocks/learnpeek_shop/item.php", ['itemid' => $item->id]);
                    $moreinfobutton = "<span class='mr-2'>$price</span>  <a href='" . $moreinfourl->out() . "'  class='text-white fw-bold cst_btn btn  mx-2'>$strmoreinfo</a>";
                }
                // $moreinfourl = new moodle_url($CFG->wwwroot . "/blocks/learnpeek_shop/item.php", ['itemid' => $item->id]);
                // $moreinfobutton = "$price <a href='" . $moreinfourl->out() . "' class=' text-primary cst_btn btn mx-2'>$strmoreinfo</a>";
            }
        }
        
        echo '
        <div class="cst_card_view course-item  col-sm-6 col-md-6 col-lg-4">
            <div class="wrapper bg_custom">
                <div class="featured-img">
                <div class="d-flex flex-wrap" style="position:absolute">
                 <span class="shopcourse-category mx-1">' . $coursecat->name . '</span>
                </div>';
                if(course_image($item->courseid)){
                    echo course_image($item->courseid);
                }else{  
                    $dumyimage = $CFG->wwwroot.'/blocks/learnpeek_shop/pix/learning.jpeg';
                    echo '<img src="'.$dumyimage.'" alt="'.$item->name.'" class="courseimage">';
                };

                echo '
                    <!--<img loading="lazy" decoding="async" src="https://pdhstar.com/wp-content/uploads/OSHA-10-G-AP-342x240.png" alt="" class="" width="342" height="240" />-->
                    <a href="#" class="img-link"></a>
                </div>
                <div class="course-content">
                <div class="d-flex text-white">
                    <h5 class="course-title"> ' . format_string($item->name) . '</h5>
                    </div>
                    <div class="price my-2" style="color: #2174ff;">
                     <!-- <span class="course-origin-price"> $89.00</span>-->
                     <span class="course-price mx-2 bb d-flex align-items-center text-white">'.$moreinfobutton. '</span>
                    </div> 
                    <div class="d-flex justify-content-between  cst_div_element">
						<div col-sm-6>';
							if($item->single_purchase_shelflife && in_array("companydepartmentmanager", $manag_role) || in_array("companymanager", $manag_role)){
								if(in_array($item->courseid, $all_tevents)){
									echo '<span class="course-price w-100 text-white">
		                               <a href="'.$CFG->wwwroot.'/blocks/learnpeek_shop/tevent.php?itemid='.$item->id.'" class="cst_btn_2 btn rounded-0">Add to cart</a>
		                               </span>';

								}else{
									echo '<span class="course-price w-100 text-white">
		                               <a href="'.$CFG->wwwroot.'/blocks/learnpeek_shop/item.php?itemid='.$item->id.'" class="cst_btn_2 btn rounded-0">Add to cart</a>
		                               </span>';
								}
							}else{
							    echo '<span class="course-price w-100 text-white">' . $buynowbutton . '</span>';
							} 


						echo '
						</div>
                    <div class="course-meta">
                        <div class="course_meta_lfx col-sm-12 d-flex flex-wrap align-items-center justify-content-start m-0">
                            <span class="star text-white review-star-rating d-flex flex-wrap align-items-center"><small style="font-size:16px">Review:</small> '.displayStarRating($avgrating).'  </span>
                            <span class="students text-white"><i class="fa fa-users" aria-hidden="true"></i> '. $totalstudents .' </span>
                        </div>    
                    </div>
                  </div>
                </div>  
            </div>
        </div>';
        // $table->data[] = ["<h3>" . format_string($item->name) . "</h3><p>" .$item->short_description . "</p>",
        //                   $moreinfobutton,
        //                   $buynowbutton];
    }
    echo "</div>";
    // if (!empty($table)) {
    //     // echo html_writer::table($table);
    //     echo $OUTPUT->paging_bar($itemcount, $page, $perpage, $baseurl);
    // }

} else {
    echo "<p>" . get_string('nocoursesontheshop', 'block_learnpeek_shop') . "</p>";
}

    echo "<div><h4   class='py-3 mt-3' style='color: #2174ff; text-decoration: underline'>". get_string('livecourses', 'block_learnpeek_shop') ."</h4>";
if($event_courseid){
    // echo "<pre>";
    // print_r($event_courseid);
    // echo "</pre>";
    //    ************  Event List  ***********   //

    if(isset($sqlparams['tag'])){
        $tagwhere = ' AND FIND_IN_SET(:tag, pcm.product_category_id) ';

    }
    $sql = 'FROM {course_shopsettings} css
            JOIN {course_shopsettings_courses} csc ON (css.id = csc.itemid)
            JOIN {course} c ON (csc.courseid = c.id '.$event_courseid.')
            JOIN {company_course} as cc ON cc.courseid=csc.courseid AND cc.companyid = css.companyid
            ' . $tagjoin . '
            LEFT JOIN {course_shopblockprice} sbp ON (css.id = sbp.itemid
            AND sbp.id = (SELECT id FROM {course_shopblockprice}
            WHERE itemid = css.id ORDER BY price LIMIT 1 ))
            LEFT JOIN {product_category_mapping} as pcm ON pcm.product_id = css.id
            WHERE css.enabled = 1
            AND css.companyid ='.$companyid.'
            ' . // $tagwhere . 
            $searchwhere . $typewhere . $categoryfilter . '
            GROUP BY css.id, sbp.id ORDER BY css.name ';

    // Get the number of Courses.
            //echo 'SELECT distinct css.* ,csc.courseid, c.fullname, c.category ,pcm.product_category_id ' . $sql , $sqlparams;
    $eventitems = $DB->get_records_sql('SELECT distinct css.* ,csc.courseid, c.fullname, c.category ,pcm.product_category_id ' . $sql .'LIMIT '.$offset.', '.$limit, $sqlparams);
 
    $totaevent = $DB->get_records_sql('SELECT distinct css.* ' . $sql, $sqlparams);
    $eventcount = count($totaevent);
    echo $OUTPUT->paging_bar($eventcount, $page, $perpage, $baseurl);

    if($eventcount){
        // echo "<pre>";
        // print_r($eventitems);
        // echo "</pre>";
        $strbuynow = get_string('buynow', 'block_learnpeek_shop');
        // $strmoreinfo = get_string('moreinfo', 'block_iomad_commerce');
        $strmoreinfo = "Read More";

        $table = new html_table();
        $table->head = [get_string('Course', 'block_iomad_commerce'), "", ""];
        $table->align = ["left", "center", "center"];
        $table->width = "95%";
        echo ' <div class="row">';
        $filtercategory = "";
        foreach ($eventitems as $eventitem) {
            $hide_product = $DB->get_record_sql("SELECT id FROM {managecompany_product} WHERE product_id = ? AND company_id = ? AND hide_product = 1",[$eventitem->id, $companyid]);
            if ($hide_product) {
                continue;
            }

            $tagcategory = $DB->get_record_sql("SELECT * FROM {product_category_mapping} WHERE product_id =?",array($eventitem->id));
            $filtercategory = array_filter(explode(',',$SESSION->shoptag));
            $mapped_event_prodcat = explode(',',$tagcategory->product_category_id);
            $check_common_cat = array_intersect($filtercategory, $mapped_event_prodcat);
            //  echo "<pre>";
            // print_r($filtercategory);
            // print_r($mapped_event_prodcat);
            // echo "</pre>";
            if(!empty($filtercategory) && empty($check_common_cat)){
                continue;
            }


            if($tagcategory->product_category_id){
                $mappedctag = $tagcategory->product_category_id;
               
            }else{
                $mappedctag = 0;
            }
            
            




            $tagcategoryname = $DB->get_records_sql("SELECT * FROM {course_categories} WHERE id in(".$mappedctag.")");
            // echo "<pre>";
            // print_r($tagcategoryname);
            // echo "</pre>";
            $courseexists = $DB->record_exists('course', ['id' => $eventitem->courseid]);
            if ($courseexists) {
                $totalstudents = total_enrolled_compnayusers_bycourse($eventitem->courseid);
            }
               
            $coursecat = $DB->get_record("course_categories",array("id"=>$eventitem->category));

            $totalseat = $DB->get_record_sql("SELECT c.id AS course_id, cf.shortname AS field_shortname, cfd.value AS field_value FROM {course} as c 
                JOIN {customfield_data} as cfd ON cfd.instanceid = c.id 
                JOIN {customfield_field} as cf ON cf.id = cfd.fieldid 
                WHERE c.id = ? AND cf.shortname LIKE  ?", array($eventitem->courseid,'total_seat'));

            $enrolledusers = enrol_get_course_users($eventitem->courseid);
            // Count the number of enrolled users
            $totalenrolledusers = count($enrolledusers);
            if($totalseat->field_value){
                $total_set_pending = $totalseat->field_value - $totalenrolledusers;
            }

            //get course avgrating
            $courerating = $DB->get_records('tool_courserating_rating', ['courseid' => $eventitem->courseid, 'status' => 1], 'timecreated DESC');
            $company_user_ratings = array_filter($courerating, function($rating) use ($company_user_ids) {
                return in_array($rating->userid, $company_user_ids);
            });

            if ($company_user_ratings) {
                $total = 0;
                $finalCount = 0;
                $count = isset($company_user_ratings) ? count($company_user_ratings) : 0;
                foreach ($company_user_ratings as $rating) {
                    $total += $rating->rating;
                    $finalCount++;
                }
                $avgrating = sprintf("%.1f", $total / $count);
            } else {
                $finalCount = 0;
                $avgrating = 0;
            }


            $available = ($eventitem->allow_single_purchase || $eventitem->allow_license_blocks) &&
                         (iomad::has_capability('block/iomad_commerce:buyitnow', $context) || iomad::has_capability('block/iomad_commerce:buyinbulk', $context));
            $price = \block_iomad_commerce\helper::get_lowest_price_text($eventitem);
            if ($available) {
                if ($eventitem->allow_single_purchase) {
                    $buynowurl = new moodle_url($CFG->wwwroot . "/blocks/learnpeek_shop/event.php", ['itemid' => $eventitem->id]);
                } else {
                    $buynowurl = new moodle_url($CFG->wwwroot . "/blocks/learnpeek_shop/event.php", ['itemid' => $eventitem->id]);
                }
                $buynowbutton = "<a href='" . $buynowurl->out() . "' class='cst_btn_2 btn rounded-0'>$strbuynow</a>";

                $moreinfourl = new moodle_url($CFG->wwwroot . "/blocks/learnpeek_shop/event.php", ['itemid' => $eventitem->id]);
                $moreinfobutton = "<span class='mr-2'>$price</span>  <a href='" . $moreinfourl->out() . "'  class='text-white fw-bold cst_btn btn  mx-2'>$strmoreinfo</a>";
            } else {
                $buynowbutton = "";
                $moreinfourl = new moodle_url($CFG->wwwroot . "/blocks/learnpeek_shop/event.php", ['itemid' => $eventitem->id]);
                $moreinfobutton = "<span class='mr-2'>$price</span> <a href='" . $moreinfourl->out() . "' class=' text-primary cst_btn btn mx-2'>$strmoreinfo</a>";
            }
            $productimage = $DB->get_record_sql("SELECT * FROM {files} WHERE contextid= 1 AND component='productpage' AND itemid=? AND filesize > 0 AND filearea = ?", array($eventitem->id,'product_image'));
            $productimageurl = $CFG->wwwroot.'/blocks/custom_mycourse/file.php?id='.$productimage->pathnamehash;
            
            echo '
            <div class="cst_card_view course-item col-sm-6 col-md-6 col-lg-4">
                <div class="wrapper bg_custom">
                    <div class="featured-img">
                    <div class="d-flex flex-wrap" style="position:absolute">
                    <!--<span class="shopcourse-category mx-1">'.$coursecat->name.'</span-->';
                    if($tagcategoryname){
                        foreach ($tagcategoryname as $tagcategory) {
                            echo '<span class="shopcourse-category mx-1">'.$tagcategory->name.'</span>';
                        }
                    }
                    echo '</div>';
                    if($productimage){
                        echo '<img src="'.$productimageurl.'" alt="'.$eventitem->name.'" class="courseimage">';
                    }else{
                        $dumyimage = $CFG->wwwroot.'/blocks/learnpeek_shop/pix/learning.jpeg';
                        echo '<img src="'.$dumyimage.'" alt="'.$eventitem->name.'" class="courseimage">';
                    };
                    
                    // if(course_image($eventitem->courseid)){
                    //     echo course_image($eventitem->courseid);
                    // }else{
                    //     $dumyimage = $CFG->wwwroot.'/blocks/learnpeek_shop/pix/learning.jpeg';
                    //     echo '<img src="'.$dumyimage.'" alt="'.$eventitem->name.'" class="courseimage">';
                    // };

                    echo '
                        <!--<img loading="lazy" decoding="async" src="https://pdhstar.com/wp-content/uploads/OSHA-10-G-AP-342x240.png" alt="" class="" width="342" height="240" />-->
                        <a href="#" class="img-link"></a>
                    </div>
                    <div class="course-content">
                    <div class="d-flex text-white">
                        <h5 class="course-title"> ' . format_string($eventitem->name) . '</h5>
                        </div>
                        <div class="price my-2" style="color: #2174ff;">
                         <!-- <span class="course-origin-price"> $89.00</span>-->
                         <span class="course-price mx-2 ccc d-flex align-items-center text-white">'.$moreinfobutton. '</span>
                        </div> 
                        <div class="d-flex justify-content-between  cst_div_element">
                         <div col-sm-6>
                              <span class="course-price w-100 text-white">' . $buynowbutton . '</span>
                        </div>
                        <div class="course-meta">
                            <div class="course_meta_lfx col-sm-12 d-flex flex-wrap align-items-center justify-content-start m-0">
                                <span class="star text-white review-star-rating d-flex flex-wrap align-items-center"><small style="font-size:16px">Review:</small><div class="mx-1">' . displayStarRating($avgrating) . '</div>   </span>
                                <span class="students text-white"><i class="fa fa-users" aria-hidden="true"></i> '. $totalstudents .' </span>
                            </div>    
                        </div>
                      </div>
                    </div>  
                </div>
            </div>';
        }
    }else{
      echo "<p>There are no event on the shop matching your criteria.</p>";
    }
}else{
    echo "<p>No upcoming events are scheduled at the moment.</p>";
}
    echo "</div>";    

echo '
<script>
    $(document).on("change", "#company", function(){
        $("#companyChanged").submit();        
    });
</script>
';
echo '
<script>
    $(document).on("change", "#perpage", function(){
        $("#paginationdatachange").submit();        
    });
</script>
';


echo $OUTPUT->footer();
