<?php
require_once($CFG->dirroot . '/lib/formslib.php');
global $DB, $USER;
$systemcontext = get_context_instance(CONTEXT_SYSTEM);

class meeting_form extends moodleform
{

  public function definition()
  {
    $mform = $this->_form; // Don't forget the underscore! 
    global $DB, $CFG, $USER;
    $id   = optional_param('id', 0, PARAM_INT);
    $eventdata = $DB->get_record_sql("SELECT * FROM {custom_eventinfo} WHERE course_id = ?",array($id));

    

    $com_id = iomad::get_my_companyid(context_system::instance());

    $company_mappedcat = $DB->get_record_sql("SELECT * FROM {company_category_mapping} WHERE company_id=?",array($com_id));

    $companydata = $DB->get_record("company",array("id"=>$com_id));
    $companycat_id = $companydata->category;
    if(!is_siteadmin()){

        if(empty($company_mappedcat)){
            redirect(new moodle_url($CFG->wwwroot.'/my'),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
            die;
        } 
    }


    if(empty($company_mappedcat->course_category)){
        $company_mappedcat->course_category = 0;
    }

    $company_mappedcat->course_category;


    $categoryname = $DB->get_records_sql("SELECT id,name FROM {course_categories} WHERE id in(".$company_mappedcat->course_category.")");
    $mcategorylist = array();
    foreach ($categoryname as $ckey => $categorynamevalue) {
        $mcategorylist[$ckey] = $categorynamevalue->name;
    }
    $mcategorylistoptions = array(                    
        'multiple' => true,                                                  


        'noselectionstring' => get_string('selectcategory','local_manage_course'),                                                              
    );
    $eventtype = array(
    	'' => get_string('selecteventtype','local_manage_course'),
	    'single_event' => get_string('single','local_manage_course'),
	    'multy_event' => get_string('multiple','local_manage_course')

    );


    $mform->addElement('text', 'event_name', get_string('eventtitle','local_manage_course'));
    $mform->addRule('event_name', 'Missing Event Title', 'required', null, 'client', false, false);

    $mform->addElement('autocomplete', 'mapped_category_id', get_string('category','local_manage_course'),$mcategorylist,$mcategorylistoptions);
    $mform->addRule('mapped_category_id', 'Select Category', 'required', null, 'client', false, false);
    
    $mform->addElement('select', 'event_type', get_string('eventtype','local_manage_course'),$eventtype);
    $mform->addRule('event_type', 'Select Event Type', 'required', null, 'client', false, false);

    $mform->addElement('text', 'price', get_string('cost','local_manage_course'));
    $mform->addRule('price', 'This field is required', 'required', null, 'client');
    $mform->addRule('price', 'Enter the amount only (numbers)', 'numeric', null, 'client');

    $radioarray=array();
	$radioarray[] = $mform->createElement('radio', 'venue_type', '', get_string('zoommeeting','local_manage_course'), 'zoom');
	$radioarray[] = $mform->createElement('radio', 'venue_type', '', get_string('inperson','local_manage_course'), 'in_person');
	$mform->setDefault('venue_type', 'zoom');
	$mform->addGroup($radioarray, 'radioar', '', array(' '), false);

    // $radioarray=array();
    //     $radioarray[] = $mform->createElement('radio', 'gender', '', 'Male', 'M');
    //     $radioarray[] = $mform->createElement('radio', 'gender', '', 'Female', 'F');
    //     $mform->addGroup($radioarray, '', 'Sex', false, 'class="inline-element"');
    //     $mform->addRule('qf_group_1', 'Missing Sex', 'required', 'client');


	$mform->addElement('text', 'inperson_location', 'Location');
    $mform->addElement('text', 'total_seat', get_string('numberofseats','local_manage_course'));
    $mform->addRule('total_seat', 'Add toatal Seat', 'numeric', null, '');

    $imageoverviewfilesoptions = Array ( "maxfiles" => 1, "maxbytes" => 0, "subdirs" => 0, "accepted_types" => Array (".jpg", ".png","jpeg" ), "context" => $systemcontext );
    $mform->addElement('filemanager', 'product_image',  get_string('Eventimage','local_manage_course'), null, $imageoverviewfilesoptions);



    // $mform->addElement('textarea', 'event_description', 'Description', 'wrap="virtual" rows="20" cols="50"');
	$mform->addElement('editor','summary',  get_string('description','local_manage_course'), null);
	$mform->addHelpButton('summary', 'coursesummary');
	$mform->setType('summary', PARAM_RAW);
	$summaryfields = 'summary';
    $meetingsections = '';
    $secdata = '';
    if($eventdata){
        $sectiondata= unserialize($eventdata->sectiondata);
        foreach ($sectiondata as $key => $section) {
            $secdata .= $this->sectiondata($section, $key);
        }
        $startdata= unserialize($eventdata->starttime);
        $enddata= unserialize($eventdata->endtime);
        // $meetingsections .= '<pre>'.print_r($sectiondata, true).'</pre>';
        // $meetingsections .= '<pre>'.print_r($startdata, true).'</pre>';
        // $meetingsections .= '<pre>'.print_r($enddata, true).'</pre>';
    }
    $meetingsections .= '
    <input type="hidden" name="deletedevents"/>
    <div class="metingssection">';
    if($eventdata){
        $meetingsections .= $secdata;


    }
    $meetingsections .= '</div>';
    $mform->addElement('html', $meetingsections);
    // $mform->addElement('static', 'meetinglink', '<a class="meettimepopup">Add Event Time</a>', '<div class="meettime">' . (($html) ? $html : '') . '</div>');
    $mform->addElement('static', 'add_new_event', '<span class="add_new_event btn btn-primary">'.get_string('addevent','local_manage_course') .'</span>', '');
    $mform->addElement('hidden', 'deletedevents', "");
    // $mform->addElement('hidden', 'category_id', $companycat_id); 
    $mform->addElement('hidden', 'course_id', $id);

    $this->add_action_buttons();

  }

  //Custom validation should be added here
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Check if the value is numeric and greater than 0.
        if (!is_numeric($data['price']) || floatval($data['price']) <= 0) {
            $errors['price'] = 'Please enter a price should be more than 0.';
        }

        return $errors;
    }
  public function sectiondata($data, $index)
  {
    $html = '<div id="fitem_id_meetinglink'.$index.'" class="form-group row  fitem">
    <input type="hidden" name="sections[fitem_id_meetinglink'.$index.'][sectionid]" value="'.$data['sectionid'].'">
    <input type="hidden" name="sections[fitem_id_meetinglink'.$index.'][groupid]" value="'.$data['groupid'].'">
    <div class="col-md-3 col-form-label d-flex pb-0 pr-md-0">
        <span class="d-inline-block ">
            <a class="meettimepopup" data-for="fitem_id_meetinglink'.$index.'" >'.get_string('addeventtime','local_manage_course') .'</a>
        </span>
        <div class="form-label-addon d-flex align-items-center align-self-start">
        </div>
    </div>
    <div class="col-md-9 form-inline align-items-start felement" data-fieldtype="static">
        <div class="form-control-static">
        <div class="meettime" id="meettime'.$index.'">';
        foreach ($data['meetings'] as $key => $metingdata) {
            $html .= $this->preparmeeting($metingdata, $index);
        }
    $html .= '    </div>
        </div>
    </div>
    </div>';
    return $html;
  }
  public function preparmeeting($metingdata, $index)
  {
    $starttime = $metingdata['starttime'];
    $endtime = $metingdata['endtime'];


    $duration = '';
    // $date = date("d m Y",$metingdata['starttime']);
    $startdate = date("D, M d, h:i A", $metingdata['starttime']);

    $end_time = $starttime + $endtime;

    $html = '<div class="meetingdetails">
      <span class="starttime"> '.$startdate.'</span>-
      <span class="endtime"> '.date("h i A", $end_time).' '.date_default_timezone_get() .' </span>  
      <input type="hidden" name="starttime[fitem_id_meetinglink'.$index.'][]" value="'.date("Y-m-d h:i A", $starttime).'">
      <input type="hidden" name="endtime[fitem_id_meetinglink'.$index.'][]" value="'.$endtime.'">                               
      <input type="hidden" name="cmid[fitem_id_meetinglink'.$index.'][]" value="'.$metingdata['cmid'].'">                               
      
      <span class="removemeeting" data-id='.$metingdata['cmid'].'> X </span>
    </div>';
    return $html;
  }
}