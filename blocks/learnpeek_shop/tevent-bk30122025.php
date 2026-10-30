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
        <div class="table-responsive">
        <table class="pdh_eventlist_table">
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


                                echo 
                                '<tr>
                                    <td data-title="event_type" class="event_type evttablecoldata">In Person</td>
                                    <td data-title="Location" class="location evttablecoldata">
                                    <h4>
                                        <span class="evtname">'.$eventlocation.'</span><br>
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
                                    <td data-title="seat" class="language evttablecoldata">'.$available_seats.'</td>
                                    <!--td data-title="seat" class="language evttablecoldata">'.$goroup->member_allow.'</td-->
                                    <!--td data-title="duration" class="duration evttablecoldata">'.$eventduration.'</td-->
                                    <td data-title="Register" class="register evttablecoldata">';
                                     if(array_search($USER->id, array_column($groupmember, 'userid')) !== false) {
                                            echo '<span class="enrol">Enrolled</span>';
                                        }
                                        else {
                                            echo '<a href="'.$CFG->wwwroot.'/blocks/learnpeek_shop/buynow.php?itemid='.$itemid.'&groupid='.$goroup->id.'" class="btn btn-primary '.$buynow.'">Buy now</a>';
                                        };

                                        echo '






                                        
                                    </td>
                                </tr>';
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
                                                        <div style="float:left;display:inline-flex;">
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
                                            '<tr>
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
.notvisible{
    display : none !important;
}
</style>
';


echo $OUTPUT->footer();
