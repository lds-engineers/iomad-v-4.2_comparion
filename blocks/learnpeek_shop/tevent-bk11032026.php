<?php
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/../iomad_company_admin/lib.php');
// require $CFG->dirroot.'/local/customlayout/file.php');
require_once("locallib.php");
require_login();
\block_iomad_commerce\helper::require_commerce_enabled();

$itemid         = required_param('itemid', PARAM_INT);
$licenseformempty = optional_param('licenseformempty', 0, PARAM_INT);

// Correct the navbar.
// Set the name for the page.
$linktext = get_string('course_shop_title', 'block_learnpeek_shop');
// Set the url.
$linkurl = new moodle_url('/blocks/learnpeek_shop/shop.php');

// Page stuff.
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);
$PAGE->navbar->add($linktext, $linkurl);

$companyid = iomad::get_my_companyid(context_system::instance());

// if ($item = $DB->get_record('course_shopsettings', ['id' => $itemid, 'enabled' => 1, 'companyid' => $companyid])) {
//     $PAGE->navbar->add($item->name);
// }
//"SELECT css.*, c.fullname, c.summary FROM mdl_course_shopsettings as css INNER JOIN mdl_course_shopsettings_courses as csc ON css.id = csc.itemid INNER JOIN mdl_course as c ON c.id = csc.courseid WHERE css.id = 21 AND css.companyid = 3 AND css.enabled = 1"

$item = $DB->get_record_sql('SELECT css.*, c.id as course_id , c.fullname, c.summary 
    FROM {course_shopsettings} as css 
    INNER JOIN {course_shopsettings_courses} as csc ON css.id = csc.itemid 
    INNER JOIN {course} as c ON c.id = csc.courseid 
    WHERE css.id = ? AND css.companyid = ? AND css.enabled = ?',array($itemid,$companyid,1));

if ($item) {
    $PAGE->navbar->add($item->name);
}
echo $OUTPUT->header();

flush();

// \block_iomad_commerce\helper::show_basket_info();

if ($item) {
    $getcoursebundle = $DB->get_records_sql("SELECT c.*, csc.itemid FROM {course_shopsettings_courses} as csc INNER JOIN {course} as c ON c.id = csc.courseid WHERE csc.itemid =".$itemid);
    // echo "<pre>";
    // print_r($getcoursebundle);
 // //    print_r($item);
    // echo "</pre>"; 

    if(count($getcoursebundle) > 1){
        // product image
        $productimage = $DB->get_record_sql("SELECT * FROM {files} WHERE contextid= 1 AND component='productpage' AND itemid=? AND filesize > 0 AND filearea = ?", array($item->id,'product_image'));
        if ($productimage) {
            $productimageurl = $CFG->wwwroot.'/blocks/custom_mycourse/file.php?id='.$productimage->pathnamehash;
            $product_image =  '<img src="'.$productimageurl.'" alt="'.$item->name.'" class="courseimage">';
        }else{
            $product_image = $CFG->wwwroot.'/blocks/learnpeek_shop/pix/learning.jpeg';
            echo '<img src="'.$dumyimage.'" alt="'.$item->name.'" class="courseimage">';
        }

        // product description

        if ($item->long_description) {
            $productdesc =  $item->long_description;
        } else {
            $productdesc = $item->short_description;
        }
    }else{
       $product_image = course_image($item->course_id);
        $context1 = context_course::instance($item->course_id, MUST_EXIST);

        $productdesc = file_rewrite_pluginfile_urls($item->summary, "pluginfile.php", $context1->id, "course", "summary",null);
       // $productdesc = $item->summary;
    }


    echo '
    <link rel="stylesheet" href="css/shopstyle.css">
    <div class="cst_wrapper_parent">
    <div class="cst_column">
        <h3>' . format_string($item->name) . '</h3>
        <div class="cst_img_parent"> '.$product_image.'</div>';

        $strbuynow = get_string('buynow', 'block_learnpeek_shop');
        $strmoreinfo = get_string('moreinfo', 'block_learnpeek_shop');

      

        if($productdesc){
            echo '
            <div class="cst_course_description">
            '.$productdesc.'
            </div>';
        }
        echo '
        <div class="p-2 cst_addition_course">
                <ul>';
            $course_ids = [];
            if(count($getcoursebundle) > 1){
                foreach ($getcoursebundle as $coursebundle) {
                    array_push($course_ids, $coursebundle->id);
                    echo '
                    <li>
                    <span><img src="'.$CFG->wwwroot.'/blocks/learnpeek_shop/pix/mycourse.png"/></span>
                        <h4><a href="#">'.$coursebundle->fullname.'</a></h4>
                    </li>';
                }

            }
    echo '
        </ul>
    </div>
    </div>';

    // 19092025 suneet code
    $ucontext = context_user::instance($USER->id);
    $ucurrent_user_roles = get_user_roles($ucontext, $USER->id);
    $ucurrent_user_roles = array_values($ucurrent_user_roles);
    $hascompanyreporterrole = (isset($ucurrent_user_roles[0]) && $ucurrent_user_roles[0]->shortname == 'companyreporter');
    $issiteadmin = is_siteadmin();

    $managerrole = $DB->get_record_sql('SELECT * FROM {company_users} WHERE userid = ? AND companyid = ?', array($USER->id,$companyid));
    $managertype = $managerrole->managertype;
    $checkpriceblocks = $DB->get_records('course_shopblockprice', ['itemid' => $item->id], 'price_bracket_start');
    $totalpriceblocks = count($checkpriceblocks);
    $ismanager = $managertype != 0;
    $isindividual = !($ismanager || $hascompanyreporterrole || $issiteadmin);
    // 19092025 suneet code

    if(($totalpriceblocks > 0 && !$isindividual) || $isindividual){ 

        $goroups = $DB->get_records_sql("SELECT * FROM {groups} WHERE courseid = ?",array($item->course_id));
        echo '
        <h1 class="my-3 cst_title"></h1>
         <div class="event-container">
        <table class="event-table">
            <thead>
                <tr>
                    <th>Event Type</th>
                    <th>Location</th>
                    <th>Start Date</th>
                    <th>Event Schedule</th>
                    <!--th>Reserved Seats</th-->
                    <th>Available Seats</th>
                    <!--th>duration</th-->
                    <th>Register</th>
                </tr>
            </thead>
            <tbody>';
                foreach ($goroups as $goroup) {
                    if($goroup){


                        $seattotal_license_allocation = 0;
                        $getinvoiceitem = $DB->get_records_sql("SELECT *  FROM {invoiceitem} WHERE groupid =? AND processed = ?",array($goroup->id,1));

                        foreach ($getinvoiceitem as $getitem) {
                            $seattotal_license_allocation += $getitem->license_allocation;
                        }
                        $seattotal_license_allocation;

                        $groupmember = $DB->get_records_sql("SELECT * FROM {groups_members} WHERE groupid = ?",array($goroup->id));
                        $totalmember = count($groupmember);
                        $groupmember = (array)$groupmember;

                        $available_seats = $goroup->member_allow - $seattotal_license_allocation;
                        $buynow ='';
                        if($available_seats <= 0){
                            $available_seats = "<span class='not_avlble'>Not Available</span>";
                            $buynow = 'notvisible';
                        }
                         
                        $getevent = $DB->get_records_sql("SELECT cm.*,m.name, t.id as teventid ,t.name as t_title, t.startdatetime ,t.enddatetime ,t.classroomid ,t.approvaltype, t.coursecapacity 
                            FROM {course_modules} as cm 
                            INNER JOIN {modules} as m ON cm.module = m.id AND m.name = 'trainingevent' 
                            INNER JOIN {trainingevent} AS t ON cm.instance = t.id 
                            WHERE cm.availability LIKE '%\"type\":\"group\",\"id\":{$goroup->id}%' AND cm.deletioninprogress !=1");
                        foreach ($getevent as $eventdata) {
                            if($eventdata){
                                if($goroup->member_allow != $eventdata->coursecapacity){
                                    $updatetrainingevent = new stdClass();
                                    $updatetrainingevent->id             = $eventdata->teventid;
                                    $updatetrainingevent->coursecapacity = $goroup->member_allow;
                                    $updaterecords = $DB->update_record('trainingevent', $updatetrainingevent);
                                }
                                // echo "<pre>";
                                // print_r($goroup);
                                // echo "=====================";
                                // print_r($eventdata);
                                // echo "</pre>";
                                // echo "equal";
                                $eventlocation = "";
                                if($eventdata->classroomid){
                                    $eventclassroom = $DB->get_record("classroom",array("id"=>$eventdata->classroomid));
                                    $eventlocation = $eventclassroom->address;

                                }
                                $eventstarttime = $eventdata->startdatetime;
                                $eventendtime = $eventdata->enddatetime;

                                $eventhours = floor($eventdata->duration / 3600);
                                $eventminutes = floor(($eventseconds % 3600) / 60);
                                $eventseconds = $eventseconds % 60;
                                $eventduration = sprintf("%02d:%02d:%02d", $eventhours, $eventminutes, $eventseconds);

                                if($eventstarttime <time()){
                                    continue;
                                }

                                echo 
                            '
                            <tr>
                                <td data-label="Event Type">In Person</td>
                                <td data-label="Location">'.$eventlocation.'</td>
                                <td data-label="Start Date">'. date("M d, Y", $eventstarttime) . '</td>
                                <td data-label="Event Schedule" class="eventschedule evttablecoldata">
                                    <div class="evtdayslistcont"><ul class="evtdatedayslist"><li><strong>'. date("l: h:i A", $eventstarttime).' - '.date("h:i A", $eventendtime).'</strong></li></ul></div>
                                </td>

                                <td data-label="Seats">'.$available_seats.'</td>
                                <td data-label="Register">
                                    ';
                                    if(array_search($USER->id, array_column($groupmember, 'userid')) !== false) {
                                        echo '<span class="enrol">Enrolled</span>';
                                    }
                                    else {
                                        echo '<a href="'.$CFG->wwwroot.'/blocks/learnpeek_shop/buynow.php?itemid='.$itemid.'&groupid='.$goroup->id.'" class="btn btn-primary '.$buynow.'">Buy now</a>';
                                    };

                                    echo '                                    
                                </td>
                                </tr>
                            ';
                                // echo 
                                // '<tr>
                                //     <td data-title="event_type" class="event_type evttablecoldata">In Person</td>
                                //     <td data-title="Location" class="location evttablecoldata">
                                //     <h4>
                                //         <span class="evtname">'.$eventlocation.'</span><br>
                                //         <span class="evtloctype"> <!--<i class="fa fa-map-marker"></i>--></span><br>
                                //         <!--<span class="prop"><strong><a href="https://pdhstar.com" class="text-primary"> https://pdhstar.com </a></strong></span>-->
                                //     </h4>
                                //     </td>
                                //     <td data-title="Start Date" class="startdate evttablecoldata">
                                //         '. date("M d, Y", $eventstarttime) .'
                                //     </td>
                                //     <td data-title="Event Schedule" class="eventschedule evttablecoldata">
                                //         <div class="evtdayslistcont"><ul class="evtdatedayslist"><li><strong>'. date("l: h:i A", $eventstarttime).' - '.date("h:i A", $eventendtime).'</strong> | '.date("M d, Y", $eventendtime).'</li></ul></div>
                                //     </td>
                                //     <td data-title="seat" class="language evttablecoldata">'.$available_seats.'</td>
                                //     <!--td data-title="seat" class="language evttablecoldata">'.$goroup->member_allow.'</td-->
                                //     <!--td data-title="duration" class="duration evttablecoldata">'.$eventduration.'</td-->
                                //     <td data-title="Register" class="register evttablecoldata">';
                                //      if(array_search($USER->id, array_column($groupmember, 'userid')) !== false) {
                                //             echo '<span class="enrol">Enrolled</span>';
                                //         }
                                //         else {
                                //             echo '<a href="'.$CFG->wwwroot.'/blocks/learnpeek_shop/buynow.php?itemid='.$itemid.'&groupid='.$goroup->id.'" class="btn btn-primary '.$buynow.'">Buy now</a>';
                                //         };

                                //         echo '
                                //     </td>
                                // </tr>';
                                 $checkavailable_seats =  $goroup->member_allow - $seattotal_license_allocation;
                                //====================================================================================================

                                if($checkavailable_seats > 0){
                                    // var_dump(($item->allow_single_purchase || $item->allow_license_blocks));
                                    if (($item->allow_single_purchase || $item->allow_license_blocks) &&
                                        (iomad::has_capability('block/iomad_commerce:buyitnow', $context) || iomad::has_capability('block/iomad_commerce:buyinbulk', $context))) {
                                        $table = new html_table();
                                        // $table->head = array (get_string('priceoptions', 'block_iomad_commerce'), "", "");
                                        $table->align = array ("left", "center", "center");
                                        $table->width = "600px";
                                        $form = '';

                                        if ($item->allow_license_blocks) {
                                            $priceblocks = $DB->get_records('course_shopblockprice', ['itemid' => $item->id], 'price_bracket_start');

                                            if (count($priceblocks)) {
                                                // var_dump(iomad::has_capability('block/iomad_commerce:buyinbulk', $context));
                                                if (iomad::has_capability('block/iomad_commerce:buyinbulk', $context)) {
                                                    foreach ($priceblocks as $priceblock) {
                                                        $table->data[] = array(get_string('licenseblock_n', 'block_iomad_commerce',
                                                                                           $priceblock->price_bracket_start),
                                                                                $priceblock->currency . ' ' . number_format($priceblock->price, 2),
                                                                                '');
                                                    }

                                                    //if (isloggedin() && iomad::is_company_admin()) {
                                                    $msg = '';
                                                    if ($licenseformempty) {
                                                        $msg = "<p class='error'>" . get_string('licenseformempty', 'block_learnpeek_shop') . "</p>";
                                                    }
                                                    $licenseformurl = new moodle_url($CFG->wwwroot . '/blocks/learnpeek_shop/buynow.php', ['itemid' => $itemid,'groupid'=>$goroup->id]);
                                                    $form = '<form action="' . $licenseformurl->out() .'" method="get">
                                                        <input type="hidden" name="itemid" value="' . $itemid . '" />
                                                        <input type="hidden" name="licenses" value="1" />
                                                        <input type="hidden" name="groupid" value="'.$goroup->id.'" />
                                                        <input type="hidden" name="available_seats" value="'.$available_seats.'" />
                                                        <input type="hidden" name="event_type" value="trainingevent"/>
                                                        ' . $msg . '
                                                        <div class="cst_collpae_extra">
                                                        <label for="id_nlicenses"> How many seats? </label>
                                                        <input type="text" class="form-control" style="width: 195px;margin-left:20px;" name="nlicenses" id="id_nlicenses" value="" />
                                                        <input type="submit" class="btn btn-primary" style="margin-left:20px;" value="' . get_string('buynow', 'block_learnpeek_shop') . '" />
                                                        </div>
                                                    </form>';
                                                }
                                            }
                                        }
                                        if(!empty($form)){
                                             echo 
                                            '<tr class="allow_license">
                                                <td  colspan="6" >'.$form.'</td>
                                            </tr>';

                                        }
                                    }

                                }







                                //====================================================================================================



                                // $form = '';


                                // $form = '<form action="#" method="get">
                                //                 <input type="hidden" name="itemid" value="' . $itemid . '" />
                                //                 <input type="hidden" name="licenses" value="1" />
                                //                 <input type="hidden" name="groupid" value="'.$csection->group_id.'" />
                                //                 <input type="hidden" name="available_seats" value="'.$available_seats.'" />
                                //                 ' . $msg . '
                                //                 <div style="float:left;display:inline-flex;">
                                //                 <label for="id_nlicenses"> How many licenses? </label>
                                //                 <input type="text" class="form-control" style="width: 195px;margin-left:20px;" name="nlicenses" id="id_nlicenses" value="" />
                                //                 <input type="submit" class="btn btn-primary" style="margin-left:20px;" value="' . get_string('buynow', 'block_learnpeek_shop') . '" />
                                //                 </div>
                                //             </form>';

                                // if(!empty($form)){
                                //      echo 
                                //     '<tr>
                                //         <td  colspan="6" >'.$form.'</td>
                                //     </tr>';

                                // }


                            }else{
                                echo "<p>No upcoming events are scheduled at the moment.</p>";
                            }
                        }
                         
                    }
                    
                }
                echo '   
                     
            </tbody>
        </table>
        </div>
        ';
    }else{
        echo "<span class='desable_license' style='color: red;background-color: #f0d7d7;padding: 5px;border-radius: 5px;margin: auto;'>This product is available only for individual users.</span>";
    }
} else {
    echo "<p>" . get_string('courseunavailable', 'block_learnpeek_shop') . "</p>";
}

echo '</div>';

echo '
<style>
.cst_collpae_extra {
    display: flex;
    flex-wrap:wrap;
    gap:5px;
}

.notvisible{
    display : none !important;
}

.cst_wrapper_parent .table-responsive {
    position: static !important;
}


/* Desktop Table */
.event-table{
    width:100%;
    border-collapse: collapse;
    border-radius:12px;
    overflow:hidden;
    background: linear-gradient(120deg,rgb(68, 102, 235) 0%,rgb(160, 178, 242) 100%);
    /*linear-gradient(78deg, rgb(68, 102, 235) 0%, rgb(160, 178, 242) 100%) !important*/
    color:#fff;
}

.event-table thead{
    background:transparent;
}


td[data-label="Register"]{
  white-space: nowrap;
}

.event-table th,
.event-table td{
    padding:12px 14px;
    font-size:14px;
}

.event-table tbody tr{
    background:rgba(255,255,255,0.1);
    border-bottom:1px solid rgba(255,255,255,0.2);
}

.buy-btn{
    padding:8px 14px;
    border-radius:6px;
    background:#083E9E;
    color:#fff;
    border:none;
    cursor:pointer;
}

td[data-label="Schedule"], ul.evtdatedayslist{
    display: block;
    list-style: none !important;
    list-style: none;
    font-size: 16px !important;
    font-weight: 300;
    line-height: 1.67 !important;
    padding: 5px 10px;
    border: 1px solid #0f47ad !important;
    padding: 5px 10px !important;
    background: #f4f4f5 !important;
    color: #5574ed !important;
    border-radius: 5px;
    cursor: pointer;
    margin: 5px;
}

/* ===========================
   MOBILE / TABLET VIEW
===========================*/
@media (max-width: 767px){

.allow_license {
    margin-top: -18px;
    border-top-left-radius: 0 !important;
    border-top-right-radius: 0 !important;
    box-shadow: none !important;
    margin-bottom: 30px !important;
}

.event-table tbody tr{
   background: #fff !important ;
}

.cst_wrapper_parent .table-responsive{
  background : inherit !important ;
}


.cst_wrapper_parent .table-responsive td{
  color: inherit !important ;
  background: inherit !important; 
}



td[data-label="Schedule"], ul.evtdatedayslist{
    background: inherit !important;
    border:none !important ;
}

  .event-table{
      background:transparent;
  }

  .event-table thead{
      display:none;
  }

  .event-table tr {
    display: block;
    margin-bottom: 15px;
    background: #fff;
    color: #000;
    border-radius: 10px;
    box-shadow: 15px -5px 19px rgba(0,0,0,0.1);
    padding: 12px;
    border-left: 0.3px solid #ddd;
    border-right: 0.3px solid #ddd;
}

  .event-table td{
      display:flex;
      justify-content:space-between;
      padding:8px 5px;
      border:none;
      font-size:14px;
      align-items:center;
  }

  .event-table td::before{
      content: attr(data-label);
      font-weight:600;
      color:#2260F6;
  }

  .buy-btn{
      background:#2260F6;
  }
}


@media(max-width:450px){
 td[data-label="Schedule"],
 ul.evtdatedayslist
 {
   flex-wrap: wrap !important;
 }
.cst_wrapper_parent .table-responsive td{
  padding: 5px; 
  flex-wrap: wrap !important;
  text-wrap-mode: inherit;
  gap: 8px;
}
  td[data-label="Schedule"],
  ul.evtdatedayslist{
     padding: 0 !important ;
  }
}


@media (max-width:330px){
  .cst_wrapper_parent .table-responsive td {
    color: inherit !important;
    flex-wrap: wrap !important;
    text-wrap-mode: inherit;
    gap: 8px;
    align-items: center;
    /* padding: 0 !important; */
    margin: 0 !important;
    flex-direction: column;
}
}
</style>
';

//----------------------Rating Section -------------------------//
echo '<style>
@charset "UTF-8";
.course-rate {
display: flex;
margin-bottom: 50px;
}
.course-rate__details {
margin-left: 50px;
flex: 1;
}
.course-rate__summary-value {
font-size: 90px;
text-align: center;
color: #333;
line-height: 1;
}
.course-rate__summary-stars .review-stars-rated {
justify-content: center;
}
.course-rate__summary-text {
text-align: center;
color: #777;
}
.course-rate__details-row {
display: flex;
margin: 0 0 10px 0;
align-items: center;
gap: 10px;
}
.course-rate__details-row .course-rate__details-row-star {
flex: 0 0 45px;
text-align: right;
color: #CCC;
}
.course-rate__details-row .course-rate__details-row-star i {
display: inline-block;
margin-left: 5px;
}
.course-rate__details-row .course-rate__details-row-value {
flex: 1;
position: relative;
margin: 0 30px 0 10px;
}
.course-rate__details-row .course-rate__details-row-value .rating, .course-rate__details-row .course-rate__details-row-value .rating-gray {
height: 15px;
background: #ffb608;
position: absolute;
top: 50%;
margin-top: -7px;
border-radius: 3px;
}
.course-rate__details-row .course-rate__details-row-value .rating-gray {
width: 100%;
background: #EEE;
}
.course-rate__details-row .course-rate__details-row-value .rating-count {
color: #333;
display: inline-block;
position: absolute;
right: -50px;
text-align: right;
line-height: 1;
margin: 5px 0;
}
.review-stars-rated {
// display: flex;
margin-bottom: 10px;
position: relative;
}
.review-stars-rated .review-star {
position: relative;
line-height: 1;
margin: 0 1px;
}
.review-stars-rated .review-star .lp-review-svg-star.fas {
position: absolute;
left: 0;
top: 0;
overflow: hidden;
}
.review-stars-rated .review-star.empty, .review-stars-rated .review-star.filled {
color: #f2b827;
display: inline-block;
position: relative;
}
.review-stars-rated .review-star.empty::before, .review-stars-rated .review-star.filled::before {
line-height: 1;
content: "☆";
vertical-align: top;
white-space: nowrap;
}
.review-stars-rated .review-star.empty:before {
color: #DDD;
content: "☆";
}
.review-stars-rated .review-star.filled {
position: absolute;
top: 0;
left: 0;
}
.review-stars-rated.center {
justify-content: center;
}
.lp-review-svg-star svg {
width: 20px;
}
.lp-review-svg-star.far {
position: relative;
}
.lp-review-svg-star.far svg {
stroke: blue;
fill: blue;
}
.lp-review-svg-star.fas svg {
fill: #ffb60a;
stroke: transparent;
}
.course-review-wrapper {
display: none;
position: fixed;
top: 0;
left: 0;
right: 0;
bottom: 0;
z-index: 999999;
text-align: center;
background: rgba(0, 0, 0, 0.5215686275);
}
.course-review-wrapper.active {
display: block;
}
.review-overlay {
background: #000;
bottom: 0;
left: 0;
position: fixed;
top: 0;
width: 100%;
opacity: 0.6;
}
.review-form {
background-color: rgb(255, 255, 255);
padding: 20px;
position: relative;
width: 80%;
max-width: 600px;
margin-top: 10%;
display: inline-block;
text-align: left;
}
.review-form h4 {
margin: 0 0 15px;
font-weight: 600;
display: flex;
justify-content: space-between;
}
.review-form h4 .close {
border: none;
text-decoration: none;
width: auto;
height: auto;
}
.review-form h4 .close::before {
font-size: 32px;
}
.review-form .review-fields {
margin: 0;
padding: 0;
list-style: none;
}
.review-form .review-fields input[type=text] {
min-height: 50px;
padding: 15px;
width: 100%;
}
.review-form .review-fields textarea {
min-height: 90px;
padding: 15px;
width: 100%;
}
.review-form .review-fields .review-stars {
padding: 0;
}
.review-form .review-fields > li {
margin: 0 0 20px 0;
}
.review-form .review-fields > li > label {
display: block;
margin: 0 0 7px;
}
.review-form .review-fields > li > label .required {
margin-left: 5px;
}
.review-form .review-fields .review-actions {
margin-bottom: 0;
}
.review-form .ajaxload,
.review-form .message {
display: none;
}
.review-form button.submit-review.loading:before {
margin-right: 10px;
}
.review-stars {
margin: 0 0;
list-style: none;
overflow: hidden;
display: flex;
}
.review-stars::after {
clear: both;
content: "";
display: block;
}
.review-stars span::before {
font-size: 25px;
cursor: pointer;
}
.review-stars > li {
height: auto;
list-style: none;
}
.review-stars > li span {
text-decoration: none;
border-bottom: none;
width: auto;
height: auto;
cursor: pointer;
}
.review-stars > li span.hover svg {
fill: #f2b827;
stroke: #f2b827;
}
.review-stars > li a:visited, .review-stars > li a:focus {
border: none;
}
.course-review-wrapper.submitting .ajaxload, .course-review-wrapper.error .message {
display: inline-block;
}
.course-review-wrapper.submitting .ajaxload {
position: absolute;
top: 48%;
left: 48%;
}
.course-review-wrapper.submitting .review-form {
position: relative;
}
.course-review-wrapper.submitting .review-form .form-overlay-review {
background: #000;
bottom: 0;
left: 0;
position: absolute;
top: 0;
width: 100%;
opacity: 0.3;
}
.course-rate {
overflow: hidden;
margin: 10px 10px;
}
.course-rate .review-bar {
width: calc(100% - 125px);
margin-right: 5px;
height: 15px;
background: #DDD;
display: inline-block;
margin: auto;
}
.course-rate .review-bar .rating {
background: #2174ff;
height: 15px;
}
.course-rate .review-number {
margin-bottom: 0;
}
.learnpress-course-review .course-reviews-list, .learnpress-course-review .course-reviews-list-shortcode {
list-style: none;
margin: 10px 0 0 0;
padding: 0;
}
.learnpress-course-review .course-reviews-list li, .learnpress-course-review .course-reviews-list-shortcode li {
margin-bottom: 20px;
padding: 20px;
background-color: #fafafa;
border: 1px solid #ddd;
overflow: hidden;
display: flex;
flex-wrap: wrap;
}
.learnpress-course-review .course-reviews-list li .review-author, .learnpress-course-review .course-reviews-list-shortcode li .review-author {
width: 100px;
margin-right: 20px;
}
.learnpress-course-review .course-reviews-list li .review-author img, .learnpress-course-review .course-reviews-list-shortcode li .review-author img {
width: 100%;
height: auto;
}
.learnpress-course-review .course-reviews-list li .review-text, .learnpress-course-review .course-reviews-list-shortcode li .review-text {
width: 100%;
padding-left: 120px;
}
@media (max-width: 600px) {
.learnpress-course-review .course-reviews-list li .review-text, .learnpress-course-review .course-reviews-list-shortcode li .review-text {
padding-left: 0;
padding-top: 15px;
}
}
.learnpress-course-review .course-reviews-list li .user-name, .learnpress-course-review .course-reviews-list-shortcode li .user-name {
margin: 5px 0;
}
.learnpress-course-review .course-reviews-list li .review-title, .learnpress-course-review .course-reviews-list-shortcode li .review-title {
clear: both;
font-style: italic;
font-weight: 700;
margin: 5px 0;
}
.lp-field-star .lp-course-filter__field div.lp-filter-item-star {
display: flex;
align-items: center;
gap: 10px;
}
.lp-field-star .lp-course-filter__field div.lp-filter-item-star .review-stars-rated {
margin: 0;
}
button.review-details.thim-collapse {
border-radius: 25px;
padding: 6px;
vertical-align: middle;
background-color: #2174ff;
color: white;
border: none;
}    
button#course-review-load-more {
color: white;
background: #2174ff;
border: none;
padding: 10px;
border-radius: 25px;
} 
.collapsible {
max-height: 0;
overflow: auto;
transition: max-height 0.5s ease-out, padding 0.3s ease-out;
}
.collapsible.open {
max-height: 1000px;
padding-top: 1rem;
padding-bottom: 1rem;
}
.review-details i {
transition: transform 0.3s ease;
}
.review-details.rotate i {
transform: rotate(180deg);
} 
.review-content {
    padding-bottom: 30px;
}  
.review-content-reply {
margin-left : 40px
}       
</style>';


if (!$course_ids) {
    $course_ids = [$item->course_id];
}

$company_users = $DB->get_records("company_users", array("companyid" => $companyid));
$company_user_ids = array_map(function($user) {
    return $user->userid;
}, $company_users);

$count = 0;
$courserating = new stdClass();
$courserating->cntall = 0;
$courserating->avgrating = 0;
$courserating->cnt01 = 0;
$courserating->cnt02 = 0;
$courserating->cnt03 = 0;
$courserating->cnt04 = 0;
$courserating->cnt05 = 0;

$total_rating_sum = 0;
$total_rating_count = 0;

foreach ($course_ids as $cid) {
    $ratings = $DB->get_records('tool_courserating_rating', ['courseid' => $cid, 'status' => 1], 'timecreated DESC');
    $company_user_ratings = array_filter($ratings, function($rating) use ($company_user_ids) {
        return in_array($rating->userid, $company_user_ids);
    });
    foreach ($company_user_ratings as $rating) {
        $value = (int) $rating->rating;
        $courserating->cntall++;
        $total_rating_sum += $value;
        $total_rating_count++;
        switch ($value) {
            case 1: $courserating->cnt01++; break;
            case 2: $courserating->cnt02++; break;
            case 3: $courserating->cnt03++; break;
            case 4: $courserating->cnt04++; break;
            case 5: $courserating->cnt05++; break;
        }
    }
}

$courserating->avgrating = $total_rating_count ? round($total_rating_sum / $total_rating_count, 2) : 0;
// $ratingcount = 0;
// foreach ($course_ids as $cid) {
//                     $ratings = $DB->get_records('tool_courserating_rating', ['courseid' => $cid], 'timecreated DESC');

//                     foreach ($ratings as $rating) {
//                          if (!$rating->status) continue;
//                         // echo "<pre>";

//                         // var_dump($rating);
//                         // echo "</pre>";

//                         $ratingcount++;
//                     }

//  }
echo '
<style>
    .hidden-review {
        display: none !important;
    }
    .open-review {
        // display: block !important;
    }
    .review-star-rating .star.half::before {
      left: -4px !important;
      top: 2px !important;
    }
</style>
<br></br>
<div class="landing-review" style="border: 1px solid #0000005e; border-radius: 4px; padding: 4px; margin-top:8px">
    <div class="learnpress-course-review">
        <div class="lp-rating-reviews">
            <div class="reviews row" id="tab-reviews">
                <div class="average-rating col-md-3 col-sm-12" align="center">
                    <div class="p-2 m-2" style="border: 1px solid #0f47ad;">
                        <h1 class="number-rate">' . $courserating->avgrating . '</h1>
                        <div class="review-stars-rated" title="' . $courserating->avgrating . ' out of 5 stars">
                            <div class="review-star">
                                <span class="star text-white review-star-rating">';
                                 echo displayStarRating($courserating->avgrating);
                         echo ' </span>
                            </div>
                            <p class="review-number">' . $courserating->cntall . ' ratings</p>
                        </div>
                    </div>
                </div>

                <div class="detailed_rating col-md-9 col-sm-12">';

                    for ($i = 5; $i >= 1; $i--) {
                        $count = $courserating->{'cnt0' . $i};
                        $percent = $courserating->cntall > 0 ? round(($count / $courserating->cntall) * 100) : 0;
                        echo '
                        <div class="course-rate">
                            <span class="number-star">' . $i . ' star' . ($i > 1 ? 's' : '') . '</span>
                            <div class="review-bar">
                                <div class="rating" style="width: ' . $percent . '%;"></div>
                            </div>
                            <span class="percent">' . $percent . '%</span>
                        </div>';
                    }

            echo '</div>

                <div class="col-md-12" align="center">
                    <button class="review-details thim-collapse"><i class="fa fa-angle-down"></i></button>
                </div>
            </div>

            <div id="course-reviews" class="m-2 collapsible">
                <h3 class="course-review-head">Reviews</h3>
                <ul class="course-reviews-list">';

                $count = 0;
                foreach ($course_ids as $cid) {
                    $ratings = $DB->get_records('tool_courserating_rating', ['courseid' => $cid], 'timecreated DESC');
                    foreach ($ratings as $rating) {
                        if (!$rating->status) continue;
                        $companyid = iomad::get_my_companyid(context_system::instance());

                        // $user = $DB->get_record('user', ['id' => $rating->userid]);
                        // $user = $DB->get_record_sql("SELECT u.*  FROM {company_users} as cu INNER JOIN {user} as u ON cu.userid = u.id AND u.deleted =0 WHERE cu.companyid =? AND cu.managertype = 0 AND cu.userid =? ",array($companyid,$rating->userid));
                        $user = $DB->get_record_sql("SELECT u.*  FROM {company_users} as cu INNER JOIN {user} as u ON cu.userid = u.id AND u.deleted =0 WHERE cu.companyid =? AND cu.userid =? ",array($companyid,$rating->userid));
                        // echo $rating->userid;
                        // echo "<pre>";

                        //  print_r($user);
                        //  echo "</pre>";

 
                        if (!$user) continue;
                        $count++;
                        $visible = $count <= 5 ? '' : 'hidden-review';
                        $fullname = fullname($user);
                        $userpic = $OUTPUT->user_picture($user, ['size' => 128, 'class' => 'avatar avatar-128 photo', 'link' => false]);

                        $stars = '';
                        for ($i = 1; $i <= 5; $i++) {
                            $fill = $i <= $rating->rating ? '#2174ff' : 'none';
                            $stars .= '
                            <span style="display: inline-block;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                    fill="' . $fill . '" stroke="#2174ff" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                </svg>
                            </span>';
                        }

                        echo '
                        <li class="' . $visible . '" data-status="'.$rating->status.'" data-cid="'.$cid.'">
                            <div class="review-author">' . $userpic . '</div>
                            <div class="review-author-info">
                                <h4 class="user-name">' . $fullname . '</h4>
                                <div class="review-stars-rated" title="' . $rating->rating . ' out of 5 stars">' . $stars . '</div>
                            </div>
                            <div class="review-text">
                                <div class="review-content">' . format_text($rating->review, FORMAT_HTML) . '</div>
                                <div class="review-content-reply">';
                                if($rating->reply){
                                    echo '<i>Admin reply : ' . format_text($rating->reply, FORMAT_HTML) . '</i>';
                                }
                                echo '


                                </div>
                            </div>
                        </li>';
                    }
                }

echo '          </ul>';
    if($count>0) {
        echo  '<button class="lp-button course-review-load-more" id="course-review-load-more" data-show="3" data-id="' . $item->course_id . '">View more</button>';

    }
            echo '</div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Ensure only the first 5 reviews are visible
        const reviews = document.querySelectorAll(".course-reviews-list li");
        for (let i = 5; i < reviews.length; i++) {
            reviews[i].classList.add("hidden-review");
        }

        const collapseBtn = document.querySelector(".review-details");
        const reviewSection = document.getElementById("course-reviews");
        if (collapseBtn) {
            collapseBtn.addEventListener("click", function () {
                reviewSection.classList.toggle("open");
                collapseBtn.classList.toggle("rotate");
            });
        }

        const loadMoreBtn = document.getElementById("course-review-load-more");
        loadMoreBtn.addEventListener("click", function () {
            const hiddenItems = document.querySelectorAll(".course-reviews-list .hidden-review");
            const showCount = parseInt(loadMoreBtn.dataset.show);

            for (let i = 0; i < showCount && i < hiddenItems.length; i++) {
                hiddenItems[i].classList.remove("hidden-review");
                hiddenItems[i].classList.add("open-review");
            }

            if (document.querySelectorAll(".hidden-review").length === 0) {
                loadMoreBtn.style.display = "none";
            }
        });
    });
</script>';

echo $OUTPUT->footer();
