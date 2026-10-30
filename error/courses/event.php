<?php
require_once(dirname(__FILE__) . '/../config.php');
global $CFG, $DB;
require_once($CFG->dirroot. '/blocks/iomad_company_admin/lib.php');
require_once($CFG->dirroot. '/blocks/learnpeek_shop/locallib.php');
require_once($CFG->dirroot . '/courses/header.php');
\block_iomad_commerce\helper::require_commerce_enabled();
die;
$itemid         = required_param('itemid', PARAM_INT);
$licenseformempty = optional_param('licenseformempty', 0, PARAM_INT);

// Correct the navbar.
// Set the name for the page.
$linktext = get_string('course_shop_title', 'block_learnpeek_shop');
// Set the url.
$linkurl = new moodle_url('/blocks/learnpeek_shop/shop.php');
// require_login();
// Page stuff.
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);
$PAGE->requires->jquery();
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

// echo "<pre>";
// print_r($item);
// echo "</pre>";
if ($item) {
    $PAGE->navbar->add($item->fullname);
}
echo $OUTPUT->header();

flush();

// \block_iomad_commerce\helper::show_basket_info();

if ($item) {
    // echo "<pre>";
    // print_r($item);
    // echo "</pre>";
    $productimage = $DB->get_record_sql("SELECT * FROM {files} WHERE contextid= 1 AND component='productpage' AND itemid=? AND filesize > 0 AND filearea = ?", array($item->id,'product_image'));
    $productimageurl = $CFG->wwwroot.'/blocks/custom_mycourse/file.php?id='.$productimage->pathnamehash;

    echo '
    <link rel="stylesheet" href="css/shopstyle.css">
    <div class="cst_column">
        <h3>' . format_string($item->name) . '</h3>
        <div class="cst_img_parent">';
            if($productimage){
                echo '<img src="'.$productimageurl.'" alt="'.$item->name.'" class="courseimage">';
            }else{
                $dumyimage = $CFG->wwwroot.'/blocks/learnpeek_shop/pix/learning.jpeg';
                echo '<img src="'.$dumyimage.'" alt="'.$item->name.'" class="courseimage">';
            };
        // if(course_image($item->course_id)){
        //     echo course_image($item->course_id);
        // }else{
        //     $dumyimage = $CFG->wwwroot.'/blocks/learnpeek_shop/pix/learning.jpeg';
        //     echo '<img src="'.$dumyimage.'" alt="'.$item->name.'" class="courseimage">';
        // };
        echo '</div>';

        $strbuynow = get_string('buynow', 'block_learnpeek_shop');
        $strmoreinfo = get_string('moreinfo', 'block_learnpeek_shop');
        if ($item->long_description) {
            $productdesc =  $item->long_description;
        } else {
            $productdesc = $item->summary;
        }

        if($productdesc){
            echo '
            <div class="cst_course_description">
            '.$productdesc.'
            </div>
            ';
        }

    echo '
    </div>';

    // $coursesection =  $DB->get_records_sql("SELECT * FROM {course_sections} WHERE course = ? AND visible = ? AND name IS NOT NULL", array($item->course_id,1));

    $coursesection =  $DB->get_records_sql("SELECT
     cs.*, g.id as group_id, g.participation,g.member_allow 
     FROM 
     {course_sections} as cs 
     LEFT JOIN 
     {groups} as g 
     ON JSON_UNQUOTE(JSON_EXTRACT(cs.availability, '$.c[0].id')) = g.id 
     WHERE cs.course = ? AND visible = ? AND cs.name IS NOT NULL", 
     array($item->course_id,1));



    $sectionstatus = "";
    foreach ($coursesection as $csection) {
        $getsectionstatus = $DB->get_records_sql("SELECT cm.*,m.name 
                                    FROM {course_modules} as cm 
                                    INNER JOIN {modules} as m 
                                    ON cm.module = m.id 
                                    WHERE cm.deletioninprogress !=1 AND (m.name = 'reservation' OR m.name = 'zoom' OR m.name ='trainingevent' OR m.name ='trainingevent') AND section = ?",array($csection->id));

        if($getsectionstatus){
            $sectionstatus = "";

        }else{
           $sectionstatus = "section_notvisible"; 
        }






        $seattotal_license_allocation = 0;
        $getinvoiceitem = $DB->get_records_sql("SELECT *  FROM {invoiceitem} WHERE groupid =? AND processed = ?",array($csection->group_id,1));

        foreach ($getinvoiceitem as $getitem) {
            $seattotal_license_allocation += $getitem->license_allocation;
        }
        $seattotal_license_allocation;


        $groupmember = $DB->get_records_sql("SELECT * FROM {groups_members} WHERE groupid = ?",array($csection->group_id));
        $totalmember = count($groupmember);
        $groupmember = (array)$groupmember;
        // $available_seats = $csection->member_allow - $totalmember;
        $available_seats = $csection->member_allow - $seattotal_license_allocation;

        //check login user purchase status 
        // $checkuserpurstatus = $DB->get_record_sql("SELECT *  FROM {invoiceitem} as it INNER JOIN {invoice} as i on it.invoiceid = i.id WHERE groupid = ? AND i.userid = ? AND it.processed = ?",array($csection->group_id,$USER->id,1));

        // echo "<pre>";
        // print_r($checkuserpurstatus);
        // echo "</pre>";
        // if($checkuserpurstatus->status){


        // }


        $buynow = '';
        if($available_seats <= 0){
            $available_seats = "<span class='not_avlble'>Not Available</span>";
            $buynow = 'notvisible';
        }
        if(array_search($USER->id, array_column($groupmember, 'userid')) !== false) {
            $purchasestatus =  '<span class="enrol">Enrolled</span>';
        }
        else {
            $purchasestatus = '<a href="'.$CFG->wwwroot.'/blocks/learnpeek_shop/buynow.php?itemid='.$itemid.'&groupid='.$csection->group_id.'" class="buy_btn bg-primary btn-sm '.$buynow.'">'.get_string('buynow', 'block_learnpeek_shop').'</a>';
        }

        $tablehtml = '
        <div class="accordion my-2 border rounded rounded-3 '.$sectionstatus.'" id="accordion'.$csection->id. '">
          <div class="card">
            <div class="card-header bg_custom" id="heading'.$csection->id. '">
              <h2 class="mb-0 d-flex align-items-center justify-content-between">
                <a href="#" class="cst_font m-0 text-white" data-toggle="collapse" data-target="#collapse'.$csection->id.'" aria-expanded="true" aria-controls="collapse'.$csection->id. '">'.$csection->name. '</a>


                <span class="cst_font text-white">Reserved  Seats : '.$seattotal_license_allocation. '</span>
                <span class="cst_font text-white">Available Seats : '.$available_seats. '</span>
                '.$purchasestatus.'
                <button class="btn btn-link btn-block text-left text-white" type="button" data-toggle="collapse" data-target="#collapse'.$csection->id.'" aria-expanded="true" aria-controls="collapse'.$csection->id. '">
                <i class="fa-solid fa-chevron-down"></i>
                </button> 
              </h2>
            </div>';
            $tablehtml .= '
            <div id="collapse'.$csection->id.'" class="collapse show" aria-labelledby="heading'.$csection->id.'" data-parent="#accordion'.$csection->id.'">
                <div class="card-body">
                <h1 class="my-3 cst_title"></h1>
                <table class="pdh_eventlist_table">
                    <thead>
                        <tr>
                            <th>Event Type</th>
                            <th>Location</th>
                            <th>Start Date</th>
                            <th>Event Schedule</th>
                            
                        </tr>
                    </thead>
                    <tbody>';

            $tablecontect = '';
            $tablefootercontent = '';
                            //                 $getevent =$DB->get_records_sql("SELECT cm.course 
                            // FROM {course_modules} AS cm 
                            // INNER JOIN {modules} as m ON cm.module = m.id 
                            // INNER JOIN {company_course} as cc ON cc.courseid = cm.course 
                            // WHERE cm.deletioninprogress =0 
                            // AND (m.name = 'reservation' OR m.name = 'zoom')
                            // AND cc.companyid = '".$companyid."' 
                            // GROUP BY cm.course");



                        $getgroupevents = $DB->get_records_sql("SELECT cm.*,m.name 
                                FROM {course_modules} as cm 
                                INNER JOIN {modules} as m 
                                ON cm.module = m.id 
                                WHERE cm.deletioninprogress !=1 AND (m.name = 'reservation' OR m.name = 'zoom' OR m.name ='trainingevent') AND section = ?",array($csection->id));

                            
                        
                        foreach ($getgroupevents as $groupevent) {
                            if($groupevent){
                                $event_type         = $groupevent->name;
                                if($groupevent->name=="zoom"){
                                    $eventdata   = $DB->get_record_sql("SELECT * FROM {zoom} WHERE id = ? ORDER BY start_time ASC", array($groupevent->instance));
                                    $event_name         = "Zoom";
                                    $event_type         = "Virtual";
                                    $event_starttime    = $eventdata->start_time;
                                    $event_duration     = $eventdata->duration;
                                    $event_timezone     = $eventdata->timezone;
                                    $event_endtime      = $eventdata->end_date_time;

                                    $eventstarttime = $event_starttime;
                                    $eventendtime = $event_starttime + $event_duration;

                                    $eventhours = floor($event_duration / 3600);
                                    $eventminutes = floor(($eventseconds % 3600) / 60);
                                    $eventseconds = $eventseconds % 60;
                                    $eventduration = sprintf("%02d:%02d:%02d", $eventhours, $eventminutes, $eventseconds);
                                }
                                if($groupevent->name=="reservation"){
                                    $eventdata   = $DB->get_record_sql("SELECT * FROM {reservation} WHERE id = ? ORDER BY timestart ASC", array($groupevent->instance));
                                    // echo "<pre>";
                                    // print_r($eventdata);
                                    // echo "</pre>";
                                    // $event_name         = $eventdata->location;
                                    $event_type = "In Person";
                                    $eventstarttime    = $eventdata->timestart;
                                    $eventendtime     = $eventdata->timeend;

                                     $event_name = $eventdata->location;
                                }

                                // echo "<pre>";
                                // print_r($groupevent);
                                // echo "</pre>";

                                if($groupevent->name=="trainingevent"){
                                    $eventdata   = $DB->get_record_sql("SELECT * FROM {trainingevent} WHERE id = ? ORDER BY startdatetime ASC", array($groupevent->instance));
                                    // echo "<pre>";
                                    // print_r($eventdata);
                                    // echo "</pre>";
                                    // $event_name         = $eventdata->location;
                                    $event_type = "In Person";
                                    $eventstarttime    = $eventdata->startdatetime;
                                    $eventendtime     = $eventdata->enddatetime;

                                     $event_name = $eventdata->location;
                                }





                                if($eventstarttime < time()){
                                    continue;
                                }

                                //===========================================================
                                $tablecontect.= '
                                <tr>
                                    <td data-title="Location" class="location evttablecoldata" style="text-align: center;"><b>'.$event_type.'</b></td>
                                    <td data-title="Location" class="location evttablecoldata">
                                    <h4>
                                        <span class="evtname">' . $event_name .'</span><br>
                                        <span class="evtloctype"> <!--<i class="fa fa-map-marker"></i>--></span><br>
                                        <!--<span class="prop"><strong><a href="https://pdhstar.com" class="text-primary"> https://pdhstar.com </a></strong></span>-->
                                    </h4>
                                    </td>
                                    <td data-title="Start Date" class="startdate evttablecoldata">
                                        '. date("M d, Y", $eventstarttime) .'
                                    </td>
                                    <td data-title="Event Schedule" class="eventschedule evttablecoldata">
                                        <div class="evtdayslistcont"><ul class="evtdatedayslist"><li><strong>'. date("l: h:i A", $eventstarttime).' - '.date("h:i A", $eventendtime).'</strong> | '.date("M d, Y", $eventendtime).'</li></ul></div>
                                    </td>
                                     
                                    
                                    
                                </tr>';
                                
                            }

                        }  
                $tablehtml .= $tablecontect;
              $tablehtml .= '
                    </tbody>
                    </table>';
                    $checkavailable_seats =  $csection->member_allow - $seattotal_license_allocation;
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
                                        $licenseformurl = new moodle_url($CFG->wwwroot . '/blocks/learnpeek_shop/buynow.php', ['itemid' => $itemid,'groupid'=>$csection->group_id]);
                                        $form = '<form action="' . $licenseformurl->out() .'" method="get">
                                            <input type="hidden" name="itemid" value="' . $itemid . '" />
                                            <input type="hidden" name="licenses" value="1" />
                                            <input type="hidden" name="groupid" value="'.$csection->group_id.'" />
                                            <input type="hidden" name="available_seats" value="'.$available_seats.'" />
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

                            if (!empty($table)) {
                                if($tablecontect){
                                    $tablefootercontent .= "<a name='buynow'></a>";
                                    $tablefootercontent .= html_writer::table($table);
                                    $tablefootercontent .= $form;
                                    $tablefootercontent .= "<br>";
                                    $tablefootercontent .= html_writer::tag('a', get_string('back'), ['class' => 'btn btn-secondary', 'href' => $linkurl]);
                                }
                            }
                        }

                    }
                $tablehtml .= $tablefootercontent;

                $tablehtml .= '
              </div>
            </div>
          </div>
        </div>
        ';
            if($tablecontect){
                echo $tablehtml;

            }

        
    }
} else {
    echo "<p>" . get_string('courseunavailable', 'block_learnpeek_shop') . "</p>";
}
echo '
<style>
.notvisible{
    display : none !important;
}
    .btn-block{
      width:auto;
    }

 .cst_font {
    font-size: 16px;
    font-weight: 400;
    color: #0f47ad;
}
  
button.btn.collapsed {
    transform: rotate(180deg);
    transition: all .4s ease-in-out;
}

.pdh_eventlist_table {
    margin: 20px 0;
    width: 100%;
}
.buy_btn{
  border:1px solid #fff;
  color:#fff;
  border-radius:10px;
  padding: 10px;
  box-shadow: rgba(50, 50, 93, 0.25) 0px 13px 27px -5px, rgba(0, 0, 0, 0.3) 0px 8px 16px -8px;
}

  .buy_btn:hover{
     color:#fff;
  }
  .section_notvisible{
    display:none;
  }
.location.evttablecoldata h4 {
  display: flex;
  font-size: 17px;
}
</style>
';
echo $OUTPUT->footer();
