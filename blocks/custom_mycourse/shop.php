<?php
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/../iomad_company_admin/lib.php');
require_once($CFG->dirroot . '/' . $CFG->admin . '/tool/courserating/lib.php');
require_once("locallib.php");

\block_iomad_commerce\helper::require_commerce_enabled();

$sort         = optional_param('sort', 'name', PARAM_ALPHA);
$dir          = optional_param('dir', 'ASC', PARAM_ALPHA);
$page         = optional_param('page', 0, PARAM_INT);
$companyid         = optional_param('companyid', 0, PARAM_INT);
$perpage      = optional_param('perpage', $CFG->iomad_max_list_courses, PARAM_INT);        // How many per page.
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





// Display the page header.
echo $OUTPUT->header();
echo "<h3>Shop courses </h3><br>";
flush();


if (is_siteadmin())
{
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
</div>
';
}
// \block_iomad_commerce\helper::show_basket_info();

// // ...**********tag listing and filtering*****************.
// if (array_key_exists('tag', $_GET)) {
//     $shoptags = \block_iomad_commerce\helper::get_shop_tags();
//     if (in_array( $_GET['tag'], $shoptags )) {
//         $SESSION->shoptag = optional_param('tag', '', PARAM_NOTAGS);
//     } else {
//         unset($SESSION->shoptag);
//     }
// } 


///
//get all event course with group
$allevents = array();
$getevent =$DB->get_records_sql("SELECT cm.course FROM {course_modules} AS cm INNER JOIN {modules} as m ON cm.module = m.id INNER JOIN {groups} as g ON cm.course = g.courseid INNER JOIN {company_course} as cc ON cc.courseid = cm.course WHERE cm.deletioninprogress =0 AND  m.name = 'zoom' AND cc.companyid = '".$companyid."' GROUP BY cm.course");
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
if (isset($SESSION->shoptag) && $SESSION->shoptag != '') {
    $tagfilters = "<li>";
    $tagfilters .= get_string('filtered_by_tag', 'block_iomad_commerce', '<em>' . $SESSION->shoptag . '</em>' );
    $tagfilters .= "</li>";

    $tagjoin = 'INNER JOIN {course_shoptag} cst ON cst.itemid = csc.itemid
                INNER JOIN {shoptag} st ON cst.shoptagid = st.id';
    $tagwhere = ' AND st.tag = :tag ';
    $sqlparams['tag'] = $SESSION->shoptag;
} else {
    // $shoptags = \block_iomad_commerce\helper::get_shop_tags();
    // if (count($shoptags)) {
    //     echo get_string('filter_by_tag', 'block_iomad_commerce');
    //     foreach ($shoptags as $shoptag) {
    //         echo "<a href='?tag=$shoptag'>$shoptag</a> ";
    //     }
    // }
}

echo "<ul id='filtering'>";
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

// if (count($sqlparams) > 1) {
//     echo '<a href="?tag=&q=">' . get_string('remove_filter', 'block_iomad_commerce') . '</a>';
// }

echo "</ul>";

echo html_writer::start_tag('div', ['style' =>'display:inline-flex;padding-bottom:10px;', 'id' => 'shoptagsearch']);
echo html_writer::tag('span', get_string('search'));
echo "<form method='get'><input type='text' name='q' class='form-control' style='width: 195px;margin-left:20px;' value='$searchkey' /></form>";
echo html_writer::end_tag('div');

// ...***********create course list sql (includes filtering on tags)*****************.
$typewhere = "";
if (!iomad::has_capability('block/iomad_commerce:buyinbulk', $context)) {
    $typewhere = " AND css.allow_single_purchase = 1 ";
}

$sql = 'FROM {course_shopsettings} css
        JOIN {course_shopsettings_courses} csc ON (css.id = csc.itemid)
        JOIN {course} c ON (csc.courseid = c.id '.$shop_courseid.')
        JOIN {company_course} as cc ON cc.courseid=csc.courseid AND cc.companyid = css.companyid
            ' . $tagjoin . '
            LEFT JOIN {course_shopblockprice} sbp ON (css.id = sbp.itemid
                                                  AND sbp.id = (SELECT id FROM {course_shopblockprice}
                                                  WHERE itemid = css.id ORDER BY price LIMIT 1 ))
        WHERE css.enabled = 1
        AND css.companyid ='.$companyid.'
        ' . $tagwhere . $searchwhere . $typewhere . '
        GROUP BY css.id, sbp.id ORDER BY css.name ';

// Get the number of Courses.

// echo 'SELECT distinct css.* ,csc.courseid ' . $sql .'LIMIT '.$offset.', '.$limit;
        // echo 'SELECT distinct css.* ' . $sql;
// $items = $DB->get_records_sql( 'SELECT distinct css.* ,csc.courseid ' . $sql, $sqlparams);
$items = $DB->get_records_sql('SELECT distinct css.* ,csc.courseid, c.fullname ' . $sql .'LIMIT '.$offset.', '.$limit, $sqlparams);
$totalitem = $DB->get_records_sql('SELECT distinct css.* ' . $sql, $sqlparams);
$itemcount = count($totalitem);

echo $OUTPUT->paging_bar($itemcount, $page, $perpage, $baseurl);
echo "<div><h4   class='py-3' style='color: #2174ff; text-decoration: underline'>On Demand Courses </h4>";

if ($itemcount) {
    $strbuynow = get_string('buynow', 'block_learnpeek_shop');
    // $strmoreinfo = get_string('moreinfo', 'block_iomad_commerce');
    // $strmoreinfo = "Read More";

    $table = new html_table();
    $table->head = [get_string('Course', 'block_iomad_commerce'), "", ""];
    $table->align = ["left", "center", "center"];
    $table->width = "95%";
    echo ' <div class="row">';
    foreach ($items as $item) {
        if($item->single_purchase_shelflife && $manag_role[0] == "companydepartmentmanager"){
            $strmoreinfo = "Buy Seats";
        }else{
            $strmoreinfo = "Read More";
        }

		//get course avgrating
        $courerating = $DB->get_record('tool_courserating_summary', array('courseid' => $item->courseid));
        if($courerating){
        	// $avgrating = $item->fullname .'courseid #'.$courerating->avgrating;
        	$avgrating = sprintf("%.1f", $courerating->avgrating);
        }else{
        	$avgrating = 0;
        }
        $available = ($item->allow_single_purchase || $item->allow_license_blocks) &&
                     (iomad::has_capability('block/iomad_commerce:buyitnow', $context) || iomad::has_capability('block/iomad_commerce:buyinbulk', $context));
        $price = \block_iomad_commerce\helper::get_lowest_price_text($item);

        if ($available) {
            if ($item->allow_single_purchase) {
                $buynowurl = new moodle_url($CFG->wwwroot . "/blocks/learnpeek_shop/buynow.php", ['itemid' => $item->id]);
            } else {
                $buynowurl = new moodle_url($CFG->wwwroot . "/blocks/learnpeek_shop/item.php", ['itemid' => $item->id]);
            }
            $buynowbutton = "<a href='" . $buynowurl->out() . "' class='cst_btn_2 btn rounded-0'>$strbuynow</a>";

            $moreinfourl = new moodle_url($CFG->wwwroot . "/blocks/learnpeek_shop/item.php", ['itemid' => $item->id]);
            $moreinfobutton = "$price <a href='" . $moreinfourl->out() . "'  class='text-white fw-bold cst_btn btn  mx-2'>$strmoreinfo</a>";
        } else {
            $buynowbutton = "";
            $moreinfourl = new moodle_url($CFG->wwwroot . "/blocks/learnpeek_shop/item.php", ['itemid' => $item->id]);
            $moreinfobutton = "$price <a href='" . $moreinfourl->out() . "' class=' text-primary cst_btn btn mx-2'>$strmoreinfo</a>";
        }
        
        echo '
         <link rel="stylesheet" href="css/shopstyle.css">
        <div class="cst_card_view course-item  col-sm-6 col-md-6 col-lg-4">
            <div class="wrapper bg_custom">
                <div class="featured-img">';
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
                     <span class="course-price mx-2 d-flex align-items-center text-white">'.$moreinfobutton. '</span>
                    </div> 
                    <div class="d-flex justify-content-between  cst_div_element">
                     <div col-sm-6>
                          <span class="course-price w-100 text-white">' . $buynowbutton . '</span>
                    </div>
                    <div class="course-meta">
                        <div class="course_meta_lfx col-sm-12">
                            <span class="star text-white review-star-rating"> Review: '.displayStarRating($avgrating).'  </span>
                            <span class="students text-white"><i class="fa fa-users" aria-hidden="true"></i> '. total_enrolled_users_bycourse($item->courseid) .' </span>
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
    if (!empty($table)) {
        // echo html_writer::table($table);
        echo $OUTPUT->paging_bar($itemcount, $page, $perpage, $baseurl);
    }

} else {
    echo "<p>" . get_string('nocoursesontheshop', 'block_learnpeek_shop') . "</p>";
}

    echo "<div><h4   class='py-3' style='color: #2174ff; text-decoration: underline'>Live Courses</h4>";
if($event_courseid){
    //    ************  Event List  ***********   //

    $sql = 'FROM {course_shopsettings} css
            JOIN {course_shopsettings_courses} csc ON (css.id = csc.itemid)
            JOIN {course} c ON (csc.courseid = c.id '.$event_courseid.')
            JOIN {company_course} as cc ON cc.courseid=csc.courseid
            ' . $tagjoin . '
            LEFT JOIN {course_shopblockprice} sbp ON (css.id = sbp.itemid
            AND sbp.id = (SELECT id FROM {course_shopblockprice}
            WHERE itemid = css.id ORDER BY price LIMIT 1 ))
            WHERE css.enabled = 1
            AND css.companyid ='.$companyid.'
            ' . $tagwhere . $searchwhere . $typewhere . '
            GROUP BY css.id, sbp.id ORDER BY css.name ';

    // Get the number of Courses.
    $eventitems = $DB->get_records_sql('SELECT distinct css.* ,csc.courseid, c.fullname ' . $sql , $sqlparams);
    $totaevent = $DB->get_records_sql('SELECT distinct css.* ' . $sql, $sqlparams);
    $eventcount = count($totaevent);

    if($eventcount){
        $strbuynow = get_string('buynow', 'block_learnpeek_shop');
        // $strmoreinfo = get_string('moreinfo', 'block_iomad_commerce');
        $strmoreinfo = "Read More";

        $table = new html_table();
        $table->head = [get_string('Course', 'block_iomad_commerce'), "", ""];
        $table->align = ["left", "center", "center"];
        $table->width = "95%";
        echo ' <div class="row">';
        foreach ($eventitems as $eventitem) {

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
            $courerating = $DB->get_record('tool_courserating_summary', array('courseid' => $eventitem->courseid));
            if($courerating){
                // $avgrating = $item->fullname .'courseid #'.$courerating->avgrating;
                $avgrating = sprintf("%.1f", $courerating->avgrating);
            }else{
                $avgrating = 0;
            }
            $available = ($eventitem->allow_single_purchase || $eventitem->allow_license_blocks) &&
                         (iomad::has_capability('block/iomad_commerce:buyitnow', $context) || iomad::has_capability('block/iomad_commerce:buyinbulk', $context));
            $price = \block_iomad_commerce\helper::get_lowest_price_text($eventitem);
            if ($available) {
                if ($eventitem->allow_single_purchase) {
                    $buynowurl = new moodle_url($CFG->wwwroot . "/blocks/learnpeek_shop/buynow.php", ['itemid' => $eventitem->id]);
                } else {
                    $buynowurl = new moodle_url($CFG->wwwroot . "/blocks/learnpeek_shop/event.php", ['itemid' => $eventitem->id]);
                }
                $buynowbutton = "<a href='" . $buynowurl->out() . "' class='cst_btn_2 btn rounded-0'>$strbuynow</a>";

                $moreinfourl = new moodle_url($CFG->wwwroot . "/blocks/learnpeek_shop/event.php", ['itemid' => $eventitem->id]);
                $moreinfobutton = "$price <a href='" . $moreinfourl->out() . "'  class='text-white fw-bold cst_btn btn  mx-2'>$strmoreinfo</a>";
            } else {
                $buynowbutton = "";
                $moreinfourl = new moodle_url($CFG->wwwroot . "/blocks/learnpeek_shop/event.php", ['itemid' => $eventitem->id]);
                $moreinfobutton = "$price <a href='" . $moreinfourl->out() . "' class=' text-primary cst_btn btn mx-2'>$strmoreinfo</a>";
            }
            
            echo '
             <link rel="stylesheet" href="css/shopstyle.css">
            <div class="cst_card_view course-item col-sm-6 col-md-6 col-lg-4">
                <div class="wrapper bg_custom">
                    <div class="featured-img">';
                    if(course_image($eventitem->courseid)){
                        echo course_image($eventitem->courseid);
                    }else{
                        $dumyimage = $CFG->wwwroot.'/blocks/learnpeek_shop/pix/learning.jpeg';
                        echo '<img src="'.$dumyimage.'" alt="'.$eventitem->name.'" class="courseimage">';
                    };

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
                         <span class="course-price mx-2 d-flex align-items-center text-white">'.$moreinfobutton. '</span>
                        </div> 
                        <div class="d-flex justify-content-between  cst_div_element">
                         <div col-sm-6>
                              <!--<span class="course-price w-100 text-white">' . $buynowbutton . '</span> -->
                        </div>
                        <div class="course-meta">
                            <div class="course_meta_lfx col-sm-12">
                                <span class="star text-white review-star-rating"> Review: '.displayStarRating($avgrating).'  </span>
                                <span class="students text-white"><i class="fa fa-users" aria-hidden="true"></i> '. total_enrolled_users_bycourse($eventitem->courseid) .' </span>
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



echo $OUTPUT->footer();
