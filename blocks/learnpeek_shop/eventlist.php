<?php
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/../iomad_company_admin/lib.php');
require_once($CFG->dirroot . '/' . $CFG->admin . '/tool/courserating/lib.php');
require_once("locallib.php");

\block_iomad_commerce\helper::require_commerce_enabled();

$sort         = optional_param('sort', 'name', PARAM_ALPHA);
$dir          = optional_param('dir', 'ASC', PARAM_ALPHA);
$page         = optional_param('page', 0, PARAM_INT);
$perpage      = optional_param('perpage', $CFG->iomad_max_list_courses, PARAM_INT);        // How many per page.

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

$baseurl = new moodle_url('/blocks/learnpeek_shop/shop.php', array('sort' => $sort, 'dir' => $dir, 'perpage' => $perpage));
$returnurl = $baseurl;

$companyid = iomad::get_my_companyid(context_system::instance());


// Display the page header.
echo $OUTPUT->header();
echo "<h3>Events</h3><br>";
flush();

\block_iomad_commerce\helper::show_basket_info();

// ...**********tag listing and filtering*****************.
if (array_key_exists('tag', $_GET)) {
    $shoptags = \block_iomad_commerce\helper::get_shop_tags();
    if (in_array( $_GET['tag'], $shoptags )) {
        $SESSION->shoptag = optional_param('tag', '', PARAM_NOTAGS);
    } else {
        unset($SESSION->shoptag);
    }
} 


/*
// get mamager role user
$companymanager = $DB->get_records_sql('SELECT cu.* FROM {company_users} as cu INNER JOIN {user} as u ON cu.userid = u.id AND u.deleted = 0 WHERE cu.companyid = ? AND cu.managertype = ?', array($companyid,1));
$managuser = array();
foreach ($companymanager as $manager) {
    array_push($managuser, $manager->userid);
}
$mangeruser = implode(",", $managuser);

//get courses id created by mamager role user
$mamagercourse = array();
$companyevent = $DB->get_records_sql("SELECT *  FROM {logstore_standard_log} WHERE eventname LIKE '%course_created%' AND userid in (".$mangeruser.")");

foreach ($companyevent as $companyevents) {
   array_push($mamagercourse, $companyevents->courseid);
}
$eventcourseid = implode(",", $mamagercourse);

*/
//get all event course with group
$allevents = array();
$getevent =$DB->get_records_sql("SELECT cm.course FROM {course_modules} AS cm INNER JOIN {modules} as m ON cm.module = m.id INNER JOIN {groups} as g ON cm.course = g.courseid INNER JOIN {company_course} as cc ON cc.courseid = cm.course WHERE cm.deletioninprogress =0 AND  m.name = 'zoom' AND cc.companyid = '".$companyid."' GROUP BY cm.course");
foreach ($getevent as $getevents) {
   array_push($allevents, $getevents->course);
}
if($allevents){
    $eventcourseid = implode(",", $allevents);
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

        $tagjoin = 'INNER JOIN {course_shoptag} cst ON cst.itemid = csc.id
                    INNER JOIN {shoptag} st ON cst.shoptagid = st.id';
        $tagwhere = ' AND st.tag = :tag ';
        $sqlparams['tag'] = $SESSION->shoptag;
    } else {
        $shoptags = \block_iomad_commerce\helper::get_shop_tags();
        if (count($shoptags)) {
            echo get_string('filter_by_tag', 'block_iomad_commerce');
            foreach ($shoptags as $shoptag) {
                echo "<a href='?tag=$shoptag'>$shoptag</a> ";
            }
        }
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

    if (count($sqlparams) > 1) {
        // echo '<a href="?tag=&q=">' . get_string('remove_filter', 'block_iomad_commerce') . '</a>';
        echo '<a href="?tag=&q=">Show all events</a>';
    }

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
            JOIN {course} c ON (csc.courseid = c.id AND c.id in ('.$eventcourseid.'))
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

    if ($itemcount) {
        $strbuynow = get_string('buynow', 'block_iomad_commerce');
        // $strmoreinfo = get_string('moreinfo', 'block_iomad_commerce');
        $strmoreinfo = "Read More";

        $table = new html_table();
        $table->head = [get_string('Course', 'block_iomad_commerce'), "", ""];
        $table->align = ["left", "center", "center"];
        $table->width = "95%";
        echo ' <div class="row">';
        foreach ($items as $item) {
        	//echo "SELECT c.id AS course_id, cf.shortname AS field_shortname, cfd.value AS field_value FROM mdl_course as c JOIN mdl_customfield_data as cfd ON cfd.instanceid = c.id JOIN mdl_customfield_field as cf ON cf.id = cfd.fieldid WHERE c.id = '".$item->courseid."' AND cf.shortname LIKE  'total_seat'";
        	$totalseat = $DB->get_record_sql("SELECT c.id AS course_id, cf.shortname AS field_shortname, cfd.value AS field_value FROM {course} as c 
        		JOIN {customfield_data} as cfd ON cfd.instanceid = c.id
        		JOIN {customfield_field} as cf ON cf.id = cfd.fieldid
        		WHERE c.id = ? AND cf.shortname LIKE  ?", array($item->courseid,'total_seat'));

            $enrolledusers = enrol_get_course_users($item->courseid);
            // Count the number of enrolled users
            $totalenrolledusers = count($enrolledusers);
    
            if($totalseat->field_value){
                $total_set_pending = $totalseat->field_value - $totalenrolledusers;
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
                    $buynowurl = new moodle_url($CFG->wwwroot . "/blocks/learnpeek_shop/event.php", ['itemid' => $item->id]);
                }
                $buynowbutton = "<a href='" . $buynowurl->out() . "' class='cst_btn_2 btn rounded-0'>$strbuynow</a>";

                $moreinfourl = new moodle_url($CFG->wwwroot . "/blocks/learnpeek_shop/event.php", ['itemid' => $item->id]);
                $moreinfobutton = "$price <a href='" . $moreinfourl->out() . "'  class='text-white fw-bold cst_btn btn  mx-2'>$strmoreinfo</a>";
            } else {
                $buynowbutton = "";
                $moreinfourl = new moodle_url($CFG->wwwroot . "/blocks/learnpeek_shop/event.php", ['itemid' => $item->id]);
                $moreinfobutton = "$price <a href='" . $moreinfourl->out() . "' class=' text-primary cst_btn btn mx-2'>$strmoreinfo</a>";
            }

            






            // print_r($courerating-widget);
            echo '
            <link rel="stylesheet" href="css/shopstyle.css">
            <div class="cst_card_view course-item col-sm-6 col-lg-4 col-xxl-4">
                <div class="wrapper eventlisting_wrapper bg_custom d-flex align-items-center">
                    <div class="featured-img eventlist_img">';
                    if(course_image($item->courseid)){
                        echo course_image($item->courseid);
                    }else{
                        $dumyimage = $CFG->wwwroot.'/blocks/learnpeek_shop/pix/learning.jpeg';
                        echo '<img src="'.$dumyimage.'" alt="'.$item->name.'" class="courseimage">';
                    };

                    echo '
                    <a href="#" class="img-link"></a>
                    </div>
                    <div class="course-content">
                    <div class="d-flex text-white">
                        <h5 class="course-title"> ' . format_string($item->name) . '</h5>
                        </div>
                        <div class="price my-2" style="color: #2174ff;">
                         <span class="course-price mx-2 d-flex align-items-center text-white">'.str_replace("From", "", $moreinfobutton). '</span>
                        </div> 
                        <div class="d-flex justify-start cst_parent_div">
                         <div col-sm-6>
                              <!--span class="course-price w-100 text-white">' . $buynowbutton . '</span-->
                        </div>
                        <div class="course-meta">
                            <div class="course_meta_lfx col-sm-12 px-0">
                             <span class="review_star mx-2 d-flex  flex-column text-white">Review 
                                <div class="d-flex">
                                <div class="star-rating review-star-rating d-flex">'
                                    . displayStarRating($avgrating) .
                                '</div>';
                                    
                                echo '
                                </div>
                            </span>
                            
                            <span class="students d-flex flex-column text-white">Student
                                <div class="d-flex align-items-center"><i class="fa fa-users mx-2" aria-hidden="true"></i> ' . total_enrolled_users_bycourse($item->courseid) . ' 
                                </div>
                            </span>
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
        echo "<p>There are no event on the shop matching your criteria.</p>";
    }
}else{
    echo "<p>No upcoming events are scheduled at the moment.</p>";
}

?>
<?php
// Assume the rating is passed as a number (1 to 5).
//$rating = 4.5; // Example rating value (1.5 stars in this case)




?>





    



<?php
echo $OUTPUT->footer();
?>
