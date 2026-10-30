<?php
require_once('../../config.php');
global $DB, $CFG, $PAGE, $USER;
require_login();
require_once("create-eventform.php");
require_once("locallib.php");
require_once($CFG->dirroot . '/mod/zoom/lib.php');
require_once($CFG->dirroot . '/mod/zoom/locallib.php');
require_once($CFG->dirroot . '/mod/zoom/classes/webservice.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->dirroot.'/course/lib.php');
$id   = optional_param('id', 0, PARAM_INT);
$PAGE->requires->jquery();

$PAGE->set_url($CFG->wwwroot.'/local/manage_course/create_event.php');
$PAGE->set_pagelayout('base');
$pagename = get_string('createevent','local_manage_course');
$PAGE->set_title($pagename);
$PAGE->set_heading($pagename);
$systemcontext = get_context_instance(CONTEXT_SYSTEM);

function local_manage_course_zoom_get_user_id($required = true) {
  global $USER;
  
  $cache = cache::make('mod_zoom', 'zoomid');
  if (!($zoomuserid = $cache->get($USER->id))) {
      $zoomuserid = false;
      try {
          $zoomuser = zoom_get_user(zoom_get_api_identifier($USER));
          if ($zoomuser !== false && isset($zoomuser->id) && ($zoomuser->id !== false)) {
              $zoomuserid = $zoomuser->id;
              $cache->set($USER->id, $zoomuserid);
          }
      } catch (moodle_exception $error) {
          if ($required) {
              throw $error;
          }
      }
  }
  return $zoomuserid;
}


$hostid = local_manage_course_zoom_get_user_id();
echo $OUTPUT->header();
//company id
$com_id = iomad::get_my_companyid(context_system::instance());
$companydata = $DB->get_record("company",array("id"=>$com_id));

$getcompnayadmin = $DB->get_record_sql("SELECT * FROM {company_users} WHERE companyid = ? AND userid =?",array($com_id,$USER->id));

$allcompanymanageruser = $DB->get_records_sql("SELECT * FROM {company_users} WHERE companyid = ? AND managertype =1",array($com_id));
$allcompnaymanager = array();
foreach ($allcompanymanageruser as $companywhiteuser) {
  array_push($allcompnaymanager, $companywhiteuser->userid);
}
if($allcompnaymanager){
   $allcompwlu = implode(",", $allcompnaymanager);
}




if(is_siteadmin() || $getcompnayadmin->managertype ==1){
  $categoryid = $companydata->category;

  $mform = new meeting_form();
  if ($mform->is_cancelled()) {
    redirect($CFG->wwwroot.'/local/manage_course/eventlist.php');
  }else if ($formdata = $mform->get_data()) {





    if($_POST['deletedevents']){
      $cmids = explode(",",$_POST['deletedevents']);
      foreach ($cmids as $cmid) {
        course_delete_module($cmid);
        
      }
    }

    if($formdata->course_id){
      $eventdata = $DB->get_record_sql("SELECT * FROM {custom_eventinfo} WHERE course_id = ?",array($formdata->course_id));
      $eventdataid = $eventdata->id;

       if($formdata->inperson_location){
           $inpersonlocation = "<a href='https://maps.google.com/?q=".$formdata->inperson_location."' target='_blank'>".$formdata->inperson_location."</a>";

      }
       $inpersonlocation;
        //       echo $formdata->inperson_location;


        // die;



      $coursedata = new stdClass();
      $coursedata->id = $formdata->course_id;
      $coursedata->fullname = $formdata->event_name;
      $coursedata->summary = $formdata->summary['text'];
      $coursedata->overviewfiles_filemanager = $formdata->product_image;
      $updatedcourse = update_course($coursedata, $editoroptions = NULL);


      //updated product section
      $updatedproduct = new stdClass();
      $updatedproduct->id        = $eventdata->product_id;
      $updatedproduct->name      = $coursedata->fullname;
      $updatedproduct->short_description           = $coursedata->summary;
      $updatedproduct->single_purchase_price       = $formdata->price;
      $updatedproduct->single_purchase_validlength = strtotime('30 day', 0);
      $updatedproduct->single_purchase_shelflife   = strtotime('30 day', 0);
      $uproduct = $DB->update_record('course_shopsettings', $updatedproduct);
      if($uproduct){
        $shopblockprice = $DB->get_record("course_shopblockprice",array("itemid"=>$updatedproduct->id));
        file_save_draft_area_files($formdata->product_image, $systemcontext->id, 'productpage', "product_image", $updatedproduct->id);
        if($shopblockprice){
          $priceblock = new stdClass();;
          $priceblock->id               = $shopblockprice->id;
          $priceblock->price                = $updatedproduct->single_purchase_price;
          $priceblock->validlength          = $updatedproduct->single_purchase_validlength;
          $priceblock->shelflife            = $updatedproduct->single_purchase_shelflife;
          $DB->update_record('course_shopblockprice', $priceblock, false, false);
        }
      }

      //updated custom table 
      $upeventinfo = new stdClass();
      if($formdata->inperson_location){
          $inpersonlocation = "<a href='https://maps.google.com/?q=".$formdata->inperson_location."' target='_blank'>".$formdata->inperson_location."</a>";

      }
      $upeventinfo->id        = $eventdataid;
      $upeventinfo->fullname  = $formdata->event_name;
      $upeventinfo->mapped_category_id = implode(",",$formdata->mapped_category_id);
      $upeventinfo->event_type = $formdata->event_type;
      $upeventinfo->price = $formdata->price;
      $upeventinfo->venue_type = $formdata->venue_type;
      $upeventinfo->inperson_location = $inpersonlocation;
      $upeventinfo->total_seat = $formdata->total_seat;
      $upeventinfo->summary = $formdata->summary['text'];
      $upeventinfo->updated_date = time();



      //mapped with category 
      $DB->delete_records("product_category_mapping", array('product_id'=>$eventdata->product_id));
      $mappedgorydata = new stdclass();
      $mappedgorydata->product_category_id      = $upeventinfo->mapped_category_id;
      $mappedgorydata->product_id               = $eventdata->product_id;
      $mappedgorydata->created_date             = time();
      $mapped_cdata = $DB->insert_record('product_category_mapping', $mappedgorydata);
      $course_id = $formdata->course_id;
      if($upeventinfo->venue_type =="in_person"){
        $eventmodulename = "reservation";
        $getreservationmdl = $DB->get_record("modules",array("name"=>"reservation"));
        $eventmodulesid = $getreservationmdl->id;
      }
      if($upeventinfo->venue_type =="zoom"){
        $eventmodulename = "zoom";
        $getzoommodule = $DB->get_record("modules",array("name"=>"zoom"));
        $eventmodulesid = $getzoommodule->id;
      }
      $coursealldata = $DB->get_record("course", array("id" => $course_id));





      if (is_array($_POST['starttime']) && is_array($_POST['endtime'])) {
          $sections = $_POST['sections']??[];
          $cmids = $_POST['cmid'];
          $endtime = $_POST['endtime'];
          $starttime = $_POST['starttime'];

          $eventdata->starttime   = serialize($starttime);
          $eventdata->endtime     = serialize($endtime);
          $eventdata->created_by  = $USER->id;

          $sectiondata = [];
          $g = 0;
          $zooml = new stdClass();
          // echo "<pre>";
          // print_r($sections);
          foreach ($starttime as $meetingKey => $startTimes) {
            $suffix = '';
            if($g){
              $suffix = '-'.$g;
            }

            // create group
            $groupid = 0;
            $sectionid = 0;
            $sectdata = null;

            if(isset($sections[$meetingKey])){
              $groupid = $sections[$meetingKey]['groupid'];
              $sectionid = $sections[$meetingKey]['sectionid'];
              $sectdata = $DB->get_record("course_sections", array("section"=>$sectionid , "course"=>$formdata->course_id));
            }
            if(empty($groupid)){
              $groupscreate = new stdClass();
              $groupscreate->courseid     = $formdata->course_id;
              $groupscreate->visibility   = 0;
              $groupscreate->name         = $upeventinfo->fullname.$suffix;
              $groupscreate->member_allow = $upeventinfo->total_seat;
              $groupscreate->timecreated  = time();
              $groupid = groups_create_group($groupscreate, false, false);
            }else{
              $updategroups = new stdClass();
              $updategroups->id = $groupid;
              $updategroups->member_allow = $formdata->total_seat;
              $updategroups->timemodified = time();
              $DB->update_record("groups", $updategroups);
            }
            if(empty($sectdata)){
              $sectdata = course_create_section($formdata->course_id, 0, false);
              $sectdata->name         = $upeventinfo->fullname;
              $sectdata->availability = '{"op":"&","c":[{"type":"group","id":'.$groupid.'}],"showc":[false]}';
              $DB->update_record("course_sections", $sectdata);
              rebuild_course_cache($course_id, true);
              $sectionid = $sectdata->section;
            }
            //create secton
            if($sectdata){
              // collect meetings
              $meetings = [];
              $cmid = 0;
              foreach ($startTimes as $index => $start) {
                  $startTimestamp = strtotime($start);
                  $duration = isset($endtime[$meetingKey][$index]) ? $endtime[$meetingKey][$index] : 0;
                  $oldid = $cmids[$meetingKey][$index];
                  $meetingstarttime = $startTimestamp;
                  $meetingduration = $duration;
                  if($oldid){
                    $cmid = $oldid;
                    // echo "\nOLD zoom {$meetingKey}-{$index}-$cmid";
                  } else {
                    // echo "\nnew zoom {$meetingKey}-{$index}";
                    //create zoom meeting
                    $zooml->name = $formdata->event_name;
                    $zooml->host_id = $hostid;
                    $zooml->course = $course_id;
                    $zooml->section = $sectionid;
                    $zooml->module = $eventmodulesid;
                    $zooml->modulename = $eventmodulename;
                    $zooml->visible = 1;
                    $zooml->introformat = 1;
                    $zooml->visibleoncoursepage = 1;
                    $zooml->start_time = $meetingstarttime;
                    $zooml->duration = $meetingduration;
                    //reseration
                    $zooml->location = $inpersonlocation;
                    $zooml->teachers = $allcompnaymanager;
                    $zooml->timestart = $meetingstarttime;
                    $zooml->timeend = $meetingstarttime + $meetingduration;
                    $zooml->timeopen = time();
                    $zooml->timeclose = $meetingstarttime;
                    // $zooml->maxrequest = $upeventinfo->total_seat;
                    // echo "<pre>";
                    // print_r($zooml);
                    // echo "</pre>";
                    $zoomdata = add_moduleinfo($zooml, $coursealldata);
                    $cmid = $zoomdata->coursemodule;
                  }

                   // die;
                  $meetings[] = [
                        'starttime' => $meetingstarttime,
                        'endtime' =>  $meetingduration,
                        'cmid' => $cmid
                    ];
              }
              $sectiondata[] = [
                  'groupid' => $groupid,
                  'sectionid' => $sectionid,
                  'meetings' => $meetings
              ];
            }
              $g++;
          }
        
      }

  





      $upeventinfo->sectiondata   = serialize($sectiondata);
      $updatedeventinfo = $DB->update_record('custom_eventinfo', $upeventinfo, false, false);
      purge_all_caches();
       redirect(new moodle_url($CFG->wwwroot.'/local/manage_course/eventlist.php'),'Event Updated', null, \core\output\notification::NOTIFY_SUCCESS);

    }else{
      //create new course
      
      // echo "<pre>";
      // print_r($formdata);
      // echo "</pre>";


      $eventdata = new stdClass();
      $eventdata->fullname = $formdata->event_name;
      $eventdata->category = $categoryid;
      $eventdata->company_id = $com_id;
      $eventdata->summary = $formdata->summary['text'];
      $eventdata->product_image= $formdata->product_image;
      if(empty($formdata->course_id)){
        $course_id = create_event_course($eventdata, $editoroptions);
      }else{
        $course_id = $formdata->course_id; 
      }
      // $course_id = 186;
      if($formdata->inperson_location){
         $inpersonlocation = "<a href='https://maps.google.com/?q=".$formdata->inperson_location."' target='_blank'>".$formdata->inperson_location."</a>";
      }
      $eventdata->mapped_category_id  = implode(",",$formdata->mapped_category_id);
      $eventdata->event_type          = $formdata->event_type;
      $eventdata->total_seat          = $formdata->total_seat;
      $eventdata->venue_type          = $formdata->venue_type;
      $eventdata->inperson_location   = $inpersonlocation;
      $eventdata->price               = $formdata->price;
      $eventdata->course_id           = $course_id; 

      // echo "<pre>";
      // print_r($formdata);
      // print_r($eventdata->venue_type);
      // echo "</pre>";
      // echo $allcompwlu;
      // echo "==================================";
      // die;
      if($eventdata->venue_type =="in_person"){
        $eventmodulename = "reservation";
        $getreservationmdl = $DB->get_record("modules",array("name"=>"reservation"));
        $eventmodulesid = $getreservationmdl->id;
      }
      if($eventdata->venue_type =="zoom"){
        $eventmodulename = "zoom";
        $getzoommodule = $DB->get_record("modules",array("name"=>"zoom"));
        $eventmodulesid = $getzoommodule->id;
      }

      
      $coursedata = $DB->get_record("course", array("id" => $course_id));

      if (is_array($_POST['starttime']) && is_array($_POST['endtime'])) {
          $endtime = $_POST['endtime'];
          $starttime = $_POST['starttime'];

          // echo "<pre>";
          // print_r($_POST);
          // echo "</pre>";

          $eventdata->starttime   = serialize($starttime);
          $eventdata->endtime     = serialize($endtime);
          $eventdata->created_by  = $USER->id;

          $sectiondata = [];
          $g = 0;
          $zooml = new stdClass();
          foreach ($starttime as $meetingKey => $startTimes) {
            $suffix = '';
            if($g){
              $suffix = '-'.$g;
            }
            // create group
            $groupscreate = new stdClass();
            $groupscreate->courseid     = $course_id;
            $groupscreate->visibility   = 0;
            $groupscreate->name         = $formdata->event_name.$suffix;
            $groupscreate->member_allow = $eventdata->total_seat;
            $groupscreate->timecreated  = time();
            $groupid = groups_create_group($groupscreate, false, false);
            //create secton
            if($sectdata = course_create_section($course_id, 0, false)){
              $sectdata->name         = $formdata->event_name;
              $sectdata->availability = '{"op":"&","c":[{"type":"group","id":'.$groupid.'}],"showc":[false]}';
              $DB->update_record("course_sections", $sectdata);
              rebuild_course_cache($course_id, true);
              $sectionid = $sectdata->section;
              // collect meetings
              $meetings = [];
              $cmid = 0;
              foreach ($startTimes as $index => $start) {
                  $startTimestamp = strtotime($start);
                  $duration = isset($endtime[$meetingKey][$index]) ? $endtime[$meetingKey][$index] : 0;
                  $meetingstarttime = $startTimestamp;
                  $meetingduration = $duration;

                  //create zoom meeting
                  // $zooml->name = date("l, j F Y h:i A", $meetingstarttime);
                  $zooml->name = $formdata->event_name;
                  $zooml->host_id = $hostid;
                  $zooml->course = $course_id;
                  $zooml->section = $sectionid;
                  $zooml->module = $eventmodulesid;
                  $zooml->modulename = $eventmodulename;
                  $zooml->visible = 1;
                  $zooml->introformat = 1;
                  $zooml->visibleoncoursepage = 1;
                  $zooml->start_time = $meetingstarttime;
                  $zooml->duration = $meetingduration;
                  //reseration
                  $zooml->location = $inpersonlocation;
                  $zooml->teachers = $allcompnaymanager;
                  $zooml->timestart = $meetingstarttime;
                  $zooml->timeend = $meetingstarttime + $meetingduration;
                  $zooml->timeopen = time();
                  $zooml->timeclose = $meetingstarttime;
                  // $zooml->maxrequest = $eventdata->total_seat;
                  // echo "<pre>";
                  // print_r($zooml);
                  // echo "</pre>";
                  // die;
                  $zoomdata = add_moduleinfo($zooml, $coursedata);
                  $cmid = $zoomdata->coursemodule;
                   // die;
                  $meetings[] = [
                        'starttime' => $meetingstarttime,
                        'endtime' =>  $meetingduration,
                        'cmid' => $cmid
                    ];
              }
              $sectiondata[] = [
                  'groupid' => $groupid,
                  'sectionid' => $sectionid,
                  'meetings' => $meetings
              ];
            }
              $g++;
          }
      }


      // *** create product ********//
      if (!empty($CFG->commerce_admin_currency)) {
          $currency = $CFG->commerce_admin_currency;
      } else {
          $currency = 'GBP';
      }

      $createproduct                              = new stdClass();
      $createproduct->companyid                   = $eventdata->company_id;
      $createproduct->name                        = $eventdata->fullname;
      $createproduct->short_description           = $eventdata->summary;
      $createproduct->allow_single_purchase       = 1;
      $createproduct->enabled                     = 1;
      $createproduct->single_purchase_price       = $eventdata->price;
      $createproduct->single_purchase_currency    = $currency;
      $createproduct->single_purchase_validlength = strtotime('30 day', 0);
      $createproduct->single_purchase_shelflife   = strtotime('30 day', 0);
      $createproduct->program                     = 0;
      $createproduct->instant                     = 1;
      $createproduct->cutofftime                  = 0;
      $createproduct->clearonexpire               = 0;
      $product_id = $DB->insert_record('course_shopsettings', $createproduct);
      if($product_id){
        file_save_draft_area_files($eventdata->product_image, $systemcontext->id, 'productpage', "product_image", $product_id);
        $priceblock = new stdClass();;
        $priceblock->itemid               = $product_id;
        $priceblock->currency             = $currency;
        $priceblock->price_bracket_start  = 1;
        $priceblock->price                = $createproduct->single_purchase_price;
        $priceblock->validlength          = $createproduct->single_purchase_validlength;
        $priceblock->shelflife            = $createproduct->single_purchase_shelflife;
        $DB->insert_record('course_shopblockprice', $priceblock, false, false);
        
        $DB->insert_record('course_shopsettings_courses', ['itemid' => $product_id, 'courseid' => $eventdata->course_id]);

      }
      $eventdata->sectiondata   = serialize($sectiondata);
      $eventdata->product_id  = $product_id;
      $eventdata->status  = 1;
      $eventdata->created_data  = time();

      //mapped with category 
      $mappedgorydata = new stdclass();
      $mappedgorydata->product_category_id      = $eventdata->mapped_category_id;
      $mappedgorydata->product_id               = $eventdata->product_id;
      $mappedgorydata->created_date             = time();
      $mapped_cdata = $DB->insert_record('product_category_mapping', $mappedgorydata);


      $custom_eventinfo = $DB->insert_record('custom_eventinfo', $eventdata);
      purge_all_caches();

       redirect(new moodle_url($CFG->wwwroot.'/blocks/learnpeek_shop/shop.php'),'Event Created', null, \core\output\notification::NOTIFY_SUCCESS);

    }

    
  }
 

  $eventdata = $DB->get_record_sql("SELECT * FROM {custom_eventinfo} WHERE course_id = ?",array($id));
  $product_image = file_get_submitted_draft_itemid('product_image');
        file_prepare_draft_area($product_image, $systemcontext->id, 'productpage', 'product_image', $eventdata->product_id);

  if($eventdata){
      $data = new stdClass(); 
       $data->id                  = $eventdata->id;
       $data->event_name          = $eventdata->fullname;
       $data->mapped_category_id  = $eventdata->mapped_category_id;
       $data->event_type          = $eventdata->event_type;
       $data->price               = $eventdata->price;
       $data->venue_type          = $eventdata->venue_type;
       $data->venue_type          = $eventdata->venue_type;
       $data->inperson_location   = strip_tags($eventdata->inperson_location);
       $data->total_seat          = $eventdata->total_seat;
       $data->summary['text']     = $eventdata->summary;
       $data->product_image       = $product_image;
       $data->sectiondata         = $eventdata->sectiondata;
  }
  $mform->set_data($data);
  $mform->display();
}else{
  redirect(new moodle_url($CFG->wwwroot.'/my'),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
}

echo $OUTPUT->footer();
?>
<!-- The Modal -->'

<style> 
  .cst_modal .btn-group #timePickerstartMeridianAM, 
  .cst_modal .btn-group #timePickerstartMeridianPM,
  .cst_modal .btn-group #timePickerendMeridianAM,
  .cst_modal .btn-group #timePickerendMeridianPM {
  margin: 0 4px;
}
</style>
<div class="modal hide cst_modal" id="myModal">
  <div class="modal-dialog">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Add a meeting
        </h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>

      <!-- Modal body -->
      <div class="modal-body">
        <form>
          <label for="date">Date : </label>
          <input type="date" id="date" name="date" style=" margin:5px" /><br />
          <div style="display: flex; align-items: center; gap:5px">
          <span>Start :</span>
          <div id="timePickerstart" style="margin: 0 5px; border-radius: 5px; padding: 2px"></div>
          </div>
         
            <div style="display: flex; align-items: center; gap:5px">
               <span>End :</span>
                <div id="timePickerend" style="margin: 0 5px; border-radius: 5px; padding: 2px"></div>
            </div>

           <div style="display: flex; gap:2px;">
          <input type="button" class="saveappend" style="" value="Save meeting time"/><br></br>
          <input type="button" class="close" style="font-size: 16px; color: white; border-radius: 5px; font-weight: normal;  padding: 8px 10px; background-color: #c23a34 !important; margin: 0 5px; opacity: 100;"
            value="Cancel">
           </div>
          <div class="eventlistpopup"></div>
        </form>
      </div>
    </div>
  </div>
</div>
<link rel="stylesheet" href="<?php $CFG->wwwroot ?>/local/manage_course/css/style.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
<script src="<?php $CFG->wwwroot ?>/local/manage_course/jquery-time-picker.js"></script>
<script>
  $(function () { 
    const $viewLink = $('<a>', {
      class: 'view-map-link',
      href: '#',
      text: 'View on map',
      target: '_blank',
      css: {
        marginLeft: '10px',
        display: 'none',
        color: '#1a73e8',
        textDecoration: 'underline'
      }
    });
    const $input =  $('#id_inperson_location');
    // Append link right next to the input
    $input.after($viewLink);

  	var currectsection = null;
  	var eventtype = null;
  	function addmetingssection(){
  		if(!eventtype){return;}
  		let availablemettingsection = $(".meettime").length;
  		let html = `<div id="fitem_id_meetinglink${availablemettingsection}" class="form-group row  fitem">
    <div class="col-md-3 col-form-label d-flex pb-0 pr-md-0" id="yui_3_18_1_1_1746535145830_351">
        <span class="d-inline-block " id="yui_3_18_1_1_1746535145830_350">
            <a class="meettimepopup" data-for="fitem_id_meetinglink${availablemettingsection}" ><?php echo get_string('addeventtime', 'local_manage_course'); ?></a>
        </span>
        <div class="form-label-addon d-flex align-items-center align-self-start">
        </div>
    </div>
    <div class="col-md-9 form-inline align-items-start felement" data-fieldtype="static">
        <div class="form-control-static">
        <div class="meettime" id="meettime${availablemettingsection}"></div>
        </div>
    </div>
</div>`;
	$('.metingssection').append(html);

  	}
    // $("#timePickerValue").html($("#timePicker").timepicker().getValue());
    $("#timePickerstart").timepicker({});
    $("#timePickerend").timepicker({});
    $(".close").click(function () {
      // console.log('aaaaaaaaa');
      $(".modal").hide();
    });
    $(document).ready(function () {
    	addmetingssection();
    });
    $(document).on("click", ".meettimepopup", function () {
      console.log('sssssssss');
		if(!eventtype){
			return;
		}
		eventtype = $("#id_event_type").val();
       currectsection = $(this).data("for");
       if(currectsection){
	      $(".eventlistpopup").html($(`#${currectsection}`).find(".meettime").html());
	      $(".modal").show();

       }
    });
    $(document).on("change", "#id_event_type", function () {
    eventtype = $(this).val();
    });
    $(document).ready(function () {
    eventtype = $("#id_event_type").val();
    });
    $(document).on("click", ".add_new_event", function () {
      if(!eventtype){
        alert('Please Select Event Type..');
      }
    	addmetingssection();
    });
    $(document).on("click", ".removemeeting", function () {
      var mid = $(this).data("id");
      if (mid) {
        var deleted = $("[name=deletedevents]").val() + '';
        if (deleted) {
          deleted = deleted.split(",")
        } else {
          deleted = [];
        }
        console.log('mid- ', mid)
        console.log('deletedfddddgfs- ', deleted)
        deleted.push(mid);
        console.log('deleted- ', deleted)
        $("[name=deletedevents]").val(deleted);
      }
      $(this).closest(".meetingdetails").remove();
    });

    $('.saveappend').click(function (e) {
      e.preventDefault();
      var date = $("#date").val();
      console.log('date-----',date);
      const datArray = date.split("-");

      if (date == '') {
        alert('Date is required.');
        return;
      }
      const d = new Date(date);
      var CurrentDate = new Date();
      if (CurrentDate.getTime() >= d.getTime()) {
        alert('Please select the current date or more...');
        return;
      }
      //----------function----------
      const convertTime = timeStr => {
        const [time, modifier] = timeStr.split(' ');
        let [hours, minutes] = time.split(':');
        if (hours === '12') {
            hours = '00';
        }
        if (modifier === 'PM') {
            hours = parseInt(hours, 10) + 12;
        }
        return `${hours}:${minutes}`;
      };
      // GET day
      const weekday = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
      // GET month
      const month = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sept", "Oct", "Nov", "Dec"];
      // GET dat
      var starttime = $("#timePickerstart").timepicker().getValue();
      // console.log('stime-----',starttime);
      var ttttttt = starttime;
      var sttt = convertTime(ttttttt);  
      const startArray = sttt.split(":");
      // console.log('aaaaa----',datArray,'------',startArray);
      const dstart = new Date(datArray[0] ,(datArray[1]-1) ,datArray[2] ,startArray[0], startArray[1]);
      let dat = dstart.getDate();
      let day = weekday[dstart.getDay()];
      let mont = month[dstart.getMonth()];
      console.log('aaaaa222-----------',dstart);
      console.log('dstart.getDay()',dstart.getDay());
      console.log('dstart.getMonth()',dstart.getMonth());
      let starttimestr = dstart.getTime();
      // console.log('starttime',starttime,'dstarttime',dstarttime);
      var endtime = $("#timePickerend").timepicker().getValue();
      // console.log('etime-----',endtime);
      var endttttt = endtime;
      var endttt = convertTime(endttttt); 
      const endArray = endttt.split(":");
      // console.log('bbbbbb----',datArray,'------',endArray);
      const dend = new Date(datArray[0] ,(datArray[1]-1) ,datArray[2] ,endArray[0], endArray[1]);
      // console.log('bbbbbb22-----------',dend);
      let endtimestr = dend.getTime();
      let duration = endtimestr - starttimestr;
      
      // if (CurrentDate.getTime() > starttimestr) {
      //   alert('Please select the current date or more...');
      //   return;
      // }

      if (starttimestr >= endtimestr) {
        alert('Please Enter the right meeting time..');
        return;
      }
      var olddata = $(`#${currectsection}`).find(".meettime").text();
      if(olddata.includes(`${day}, ${mont} ${dat}, ${starttime} -`)){
        alert('Your are not added same meeting again.');
        return;
      }
      // console.log('endtime',endtime,'endtimestr',endtimestr);

      if(eventtype == "single_event"){
      $(`#${currectsection}`).find(".meettime").html(`<div class="meetingdetails">
                                      <span class="starttime"> ${day}, ${mont} ${dat},</span><span class="starttime"> ${starttime} </span>-
                                      <span class="endtime"> ${endtime} </span>  
                                      <input type="hidden" name="starttime[${currectsection}][]" value="${date} ${starttime}">
                                      <input type="hidden" name="endtime[${currectsection}][]" value="${duration/1000}"><?php echo date_default_timezone_get();?>                               
                                      
                                      <span class="removemeeting"> X </span>
                                    </div>`);
      } else {
      	      $(`#${currectsection}`).find(".meettime").append(`<div class="meetingdetails">
                                      <span class="starttime"> ${day}, ${mont} ${dat},</span><span class="starttime"> ${starttime} </span>-
                                      <span class="endtime"> ${endtime} </span>  
                                      <input type="hidden" name="starttime[${currectsection}][]" value="${date} ${starttime}">
                                      <input type="hidden" name="endtime[${currectsection}][]" value="${duration/1000}"><?php echo date_default_timezone_get();?>                               
                                      
                                      <span class="removemeeting"> X </span>
                                    </div>`);
      }
      $(".eventlistpopup").html($(`#${currectsection}`).find(".meettime").html());
    });


  });
</script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
    const zoomRadio = document.getElementById("id_venue_type_zoom");
    const inPersonRadio = document.getElementById("id_venue_type_in_person");
    const locationDiv = document.getElementById("fitem_id_inperson_location");


    function toggleLocationDiv() {
	    if (inPersonRadio.checked) {
	        locationDiv.style.display = "flex";
	    } else {
	        locationDiv.style.display = "none";
	    }
    }
    // Initial check on page load
    toggleLocationDiv();

    // Event listeners
    zoomRadio.addEventListener("change", toggleLocationDiv);
    inPersonRadio.addEventListener("change", toggleLocationDiv);
});
</script>
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyD7KZ3dujsZuhVn8E0uagRyQ65_YvpGJcM&libraries=places"></script>
<!-- <script>
  function initAutocomplete() {
    const input = document.getElementById("id_inperson_location");
    if (!input) return;
    
    const autocomplete = new google.maps.places.Autocomplete(input, {
      types: ["geocode"] // or "address"
    });

    autocomplete.addListener("place_changed", function () {
      const place = autocomplete.getPlace();
      console.log("Selected place:", place);
    });
  }

  window.onload = initAutocomplete;
</script> -->
<script>
  function initAutocomplete() {
    const input = document.getElementById("id_inperson_location");
    const selectedAddressContainer = document.getElementById("selected-address");
    const mapsLink = document.getElementById("maps-link");

    if (!input) return;

    const autocomplete = new google.maps.places.Autocomplete(input, {
      types: ["geocode"]
    });

    autocomplete.addListener("place_changed", function () {
      const place = autocomplete.getPlace();

      if (!place.geometry || !place.geometry.location) {
        console.log("No location data.");
        return;
      }

      const lat = place.geometry.location.lat();
      const lng = place.geometry.location.lng();
      const address = place.formatted_address;

      const mapsUrl = `https://www.google.com/maps/search/?api=1&query=${lat},${lng}`;
      $(".view-map-link").attr("href", mapsUrl).show();
      // mapsLink.href = mapsUrl;
      // mapsLink.textContent = "View on map";
      // selectedAddressContainer.style.display = "inline-block";
    });
  }

  window.onload = initAutocomplete;
</script>





