<?php
require_once('header.php');
require_once($CFG->dirroot. '/blocks/iomad_company_admin/lib.php');
require_once($CFG->dirroot. '/blocks/learnpeek_shop/locallib.php');
\block_iomad_commerce\helper::require_commerce_enabled();

$itemid         = required_param('itemid', PARAM_INT);
$licenseformempty = optional_param('licenseformempty', 0, PARAM_INT);

// Correct the navbar.
// Set the name for the page.
$linktext = get_string('course_shop_title', 'block_learnpeek_shop');
// Set the url.
$linkurl = new moodle_url('/courses');

// Page stuff.
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);
$PAGE->navbar->add($linktext, $linkurl);

// $companyid = iomad::get_my_companyid(context_system::instance());
$current_host = $_SERVER['HTTP_HOST'];
$getcompany = $DB->get_record_sql("SELECT * FROM {company} WHERE hostname LIKE ?", array($current_host));
$companyid = $getcompany->id;

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

    $goroups = $DB->get_records_sql("SELECT * FROM {groups} WHERE courseid = ?",array($item->course_id));
    // echo "<pre>";
    // print_r($goroups);
    // echo "</pre>";
    echo '
    <h1 class="my-3 cst_title"></h1>
     <div class="event-container table-responsive">
    <table class="event-table">
        <thead>
            <tr>
                <th>Event Type</th>
                <th>Location</th>
                <th>Start Date</th>
                <th>Event Schedule</th>
                <th>Seats</th>
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
                            $eventlocation = "";
                            if($eventdata->classroomid){
                                $eventclassroom = $DB->get_record("classroom",array("id"=>$eventdata->classroomid));
                                $eventlocation = $eventclassroom->address;

                            }
                            $eventstarttime = $eventdata->startdatetime;
                            $eventendtime = $eventdata->enddatetime;
                            if($eventstarttime < time()){
                                continue;
                            }
                            $eventhours = floor($eventdata->duration / 3600);
                            $eventminutes = floor(($eventseconds % 3600) / 60);
                            $eventseconds = $eventseconds % 60;
                            $eventduration = sprintf("%02d:%02d:%02d", $eventhours, $eventminutes, $eventseconds);


                            echo 
                            '
                            <tr>
                                <td data-label="Event Type">In Person</td>
                                <td data-label="Location">'.$eventlocation.'</td>
                                <td data-label="Start Date">'. date("M d, Y", $eventstarttime) . '</td>
                                <td data-label="Schedule">' . date("l: h:i A", $eventstarttime) . ' - ' . date("h:i A", $eventendtime) . '</strong></td>
                                <!--<td data-label="Schedule">' . date("l: h:i A", $eventstarttime) . ' - ' . date("h:i A", $eventendtime) . '</strong> | ' . date("M d, Y", $eventendtime) . '</td>-->

                                <td data-label="Seats">'.$available_seats.'</td>
                                <td data-label="Register">
                                    ';
                                    if(array_search($USER->id, array_column($groupmember, 'userid')) !== false) {
                                        echo '<span class="enrol">Enrolled</span>';
                                    }
                                    else {
                                        echo '<a href="'.$CFG->wwwroot.'/courses/guestcart.php?itemid='.$itemid.'&groupid='.$goroup->id.'" class="btn btn-primary '.$buynow.'">Buy now</a>';
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
                                                $licenseformurl = new moodle_url($CFG->wwwroot . '/courses/guestcart.php', ['itemid' => $itemid,'groupid'=>$goroup->id]);
                                                $form = '<form action="' . $licenseformurl->out() .'" method="get">
                                                    <input type="hidden" name="itemid" value="' . $itemid . '" />
                                                    <input type="hidden" name="licenses" value="1" />
                                                    <input type="hidden" name="groupid" value="'.$goroup->id.'" />
                                                    <input type="hidden" name="available_seats" value="'.$available_seats.'" />
                                                    <input type="hidden" name="event_type" value="trainingevent"/>
                                                    ' . $msg . '
                                                    <div style="float:left;display:inline-flex;">
                                                    <label for="id_nlicenses"> How many licenses? </label>
                                                    <input type="text" class="form-control" style="width: 195px;margin-left:20px;" name="nlicenses" id="id_nlicenses" value="" />
                                                    <input type="submit" class="btn btn-primary" style="margin-left:20px;" value="' . get_string('buynow', 'block_learnpeek_shop') . '" />
                                                    </div>
                                                </form>';
                                            }
                                        }
                                    }
                                    if(!empty($form)){
                                         echo 
                                        '<tr>
                                            <td  colspan="6" >'.$form.'</td>
                                        </tr>';

                                    }
                                }

                            }
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



} else {
    echo "<p>" . get_string('courseunavailable', 'block_learnpeek_shop') . "</p>";
}

echo '</div>';

echo '
<style>
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

td[data-label="Schedule"] {
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

.event-table tbody tr{
   background: #fff !important ;
}

.cst_wrapper_parent .table-responsive{
  background : inherit !important ;
}


.cst_wrapper_parent .table-responsive td{
  color: inherit !important ;
  background inherit !important; 
}



td[data-label="Schedule"] {
    background: inherit !important;
    border:none !important ;
}

  .event-table{
      background:transparent;
  }

  .event-table thead{
      display:none;
  }

  .event-table tr{
      display:block;
      margin-bottom:15px;
      background:#fff;
      color:#000;
      border-radius:10px;
      box-shadow:0 4px 15px rgba(0,0,0,0.1);
      padding:12px;
  }

  .event-table td{
      display:flex;
      justify-content:space-between;
      padding:8px 5px;
      border:none;
      font-size:14px;
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
 td[data-label="Schedule"]{
   flex-wrap: wrap !important;
 }
.cst_wrapper_parent .table-responsive td{
  padding: 5px; 
  flex-wrap: wrap !important;
  text-wrap-mode: inherit;
  gap: 8px;
}
  td[data-label="Schedule"]{
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

echo '<div class="custom-cart">'. guest_user_basket_info() .'</div>';
echo $OUTPUT->footer();
