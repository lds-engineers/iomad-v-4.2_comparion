<?php
use core_completion\progress;
// include_once($CFG->dirroot . '/course/lib.php');
class block_custom_latest_certificate_message extends block_base {
    public function init() {
        // $this->title = get_string('pluginname', 'block_custom_latest_certificate_message');
        $this->title = '';
    }

    public function display_string_lenght($string,$msgid){
        $string = strip_tags($string);
        if (strlen($string) > 30) {

            // truncate string
            $stringCut = substr($string, 0, 30);
            $endPoint = strrpos($stringCut, ' ');

            //if the string doesn't contain any space then it will cut without word basis.
            $string = $endPoint? substr($stringCut, 0, $endPoint) : substr($stringCut, 0);
            $string .= '... <a href="'.$CFG->wwwroot.'/message/index.php" class="redmore">Read More</a>';
        }
        return $string;
    }

    public function get_enrolled_courses() {
        global $DB,$CFG,$USER;
        require_once($CFG->dirroot . '/lib/enrollib.php');
        $data = enrol_get_users_courses($USER->id);
        return $data;
    }

    public function get_content() {
        global $PAGE, $USER, $DB, $CFG;
        $this->content = new stdClass();
        $enrolcourses = $this->get_enrolled_courses();
        $customcert = $DB->get_record('modules', array("name"=>"customcert"));    
        $iomadcert = $DB->get_record('modules', array("name"=>"iomadcertificate"));
        $scorm_md = $DB->get_record('modules', array("name"=>"scorm"));

        $convt = $DB->get_records('message_conversation_members',array('userid' => $USER->id));
        if($convt){
            $convert = [];
            foreach ($convt as $conv) {
                array_push($convert, $conv->conversationid);
            }
            $cvtid = implode(",",$convert);
            $msg = $DB->get_records_sql("SELECT * FROM {messages} WHERE useridfrom != $USER->id AND conversationid IN ($cvtid) ORDER BY id DESC LIMIT 2");   
        }
        $context = context_user::instance($USER->id);
        $current_user_roles = get_user_roles($context, $USER->id);
        $authandicateuser = null;
        if($current_user_roles){
            $managerrole = array_column($current_user_roles, 'shortname');
            if($managerrole[0]=="companymanager"){
                $authandicateuser = true;
            }else{
                $authandicateuser = false;
            }
        }
        $html22 = null;
        if($authandicateuser) {
        $html2 = '<div class="row" style="margin: -5px;">
                    <div class="col-md-12 my-1" style="min-height:300px">
                        <div class="bg_custom p-3 rounded rounded-3" style="height:100%">
                            <h5 class="my-3 text_color_custom">'.get_string('latestmessages', 'block_custom_latest_certificate_message').'</h5>
                            <div class="row" id="latestmsg">';
                               $html2 .= '<div class="col-md-4 col-sm-4 text_color_custom" style="display: inline-grid;">';     
                               if($msg) {
                                   foreach ($msg as $msgg) {
                                        $html2 .= '<p>'.date('d/m/y h:i A',$msgg->timecreated). '</p>'; 
                                   }
                               }
                               $html2 .= '</div>';     
                               $html2 .= '<div class="col-md-8 col-sm-8 text_color_custom" style="display: inline-grid;">';     
                               if($msg) {
                                   foreach ($msg as $msgg) {
                                        $html2 .= '<p>'.$this->display_string_lenght($msgg->smallmessage,$msgg->id).'</p>'; 
                                   }
                               }
                               $html2 .= '</div>';     

                            $html2 .= '
                            </div>
                        </div>
                    </div>
                 </div>';
            
        } else {

        $html2 = '
        <div class="row">
            <div class="col-md-6 my-1" style="min-height:300px">
             <div class="bg_custom p-3 rounded rounded-3" style="height:100%">  
                <h5 class="my-3 text_color_custom">' . get_string('latestcertificates', 'block_custom_latest_certificate_message') .'</h5>
                <div class="row" id="latestcert">';
                foreach ($enrolcourses as $course) {
                    $percentage = progress::get_course_progress_percentage($course, $USER->id);
                    if($percentage == 100){

                       $cm = $DB->get_records('course_modules', array("course"=>$course->id , "module"=>$customcert->id, "deletioninprogress"=>0));   
                        $icm = $DB->get_records('course_modules', array("course"=>$course->id , "module"=>$iomadcert->id, "deletioninprogress"=>0));
                        $scorm_cm = $DB->get_record_sql('SELECT * FROM {course_modules} WHERE course =? AND module = ? AND deletioninprogress = 0  ORDER BY id ASC', array($course->id ,$scorm_md->id));
                            // echo "<pre>";
                            // print_r($scorm_cm);
                            // echo "</pre>";

                        $course =  $DB->get_record('course', array("id"=>$course->id));
                        $fullname = strip_tags($course->fullname);
                                // echo "<pre>";
                                // print_r($cm);
                                // echo "</pre>";
                        if(!empty($cm)){
                            foreach($cm as $ccm){
                                $modinfo = get_fast_modinfo($course->id);
                                $cminfo = $modinfo->get_cm($ccm->id);
                                if ($cminfo->uservisible) {
                                    $getcertificateissue = $DB->get_record_sql("SELECT * FROM {customcert_issues} WHERE customcertid = ? AND userid = ?",array($ccm->instance, $USER->id));
                                    if($getcertificateissue){
                                        $html2 .= '<div class="col-4 col-md-3 col-sm-3 text-center  mx-2 p-2 rounded rounded-3">
                                                    <a style="display:inline-block" href="'.$CFG->wwwroot.'/mod/customcert/view.php?id='.$ccm->id.'&downloadown=1" target="_blank"><img alt="certificate" src="'.$CFG->wwwroot.'/blocks/custom_mycourse/certificate.png" style="width:100%"><span style="color:white;">'.$fullname.'</span></a>
                                                </div>';
                                    }
                                }  
                            } 
                        }
                        if(!empty($icm)){
                            foreach($icm as $ccm){
                            $html2 .= '<div class="col-4 col-md-3 col-sm-3 text-center  mx-2 p-2 rounded rounded-3">
                                    <a  style="display:inline-block" href="'.$CFG->wwwroot.'/mod/iomadcertificate/view.php?id='.$ccm->id.'&action=get" target="_blank"><img alt="certificate" src="'.$CFG->wwwroot.'/blocks/custom_mycourse/certificate.png" style="width:100%"><span style="color:white;">'.$fullname.'</span></a>
                                </div>';     
                            }
                        }
                        if($scorm_cm){
                          $aicc_scorm = $DB->get_record("scorm", array('id' => $scorm_cm->instance));
                          if($aicc_scorm->version == "AICC"){
                          $html2 .= '<div class="col-4 col-md-3 col-sm-3 text-center  mx-2 p-2 rounded rounded-3">
                                        <a style="display:inline-block" href="'.$CFG->wwwroot.'/mod/scorm/view.php?id='.$scorm_cm->id.'" target="_blank"><img alt="certificate" src="'.$CFG->wwwroot.'/blocks/custom_mycourse/certificate.png" style="width:100%"><span style="color:white;">'.$fullname.'</span></a>
                                    </div>'; 

                          }
                        }
                    }
                }
      $html2 .= '
                 </div>
               </div>
            </div>
            <div class="col-md-6 my-1" style="min-height:300px">
                <div class="bg_custom p-3 rounded rounded-3" style="height:100%">
                <h5 class="my-3 text_color_custom">' . get_string('latestmessages', 'block_custom_latest_certificate_message') .'</h5>
                <div class="row" id="latestmsg">';
           $html2 .= '<div class="col-md-4 col-sm-4 col-4 pd text_color_custom">';     
           foreach ($msg as $msgg) {
                $html2 .= '<p>'.date('d/m/y h:i A',$msgg->timecreated).'</p>'; 
           }
           $html2 .= '</div>';     
           $html2 .= '<div class="col-md-8 col-sm-8 col-4 pd text_color_custom">';     
           foreach ($msg as $msgg) {
                $html2 .= '<p>'.$this->display_string_lenght($msgg->smallmessage,$msgg->id).'</p><hr>'; 
           }
           $html2 .= '</div>';     

        $html2 .= '
               </div>
             </div>
            </div>
        </div>
        ';
    }
        $this->content->text = $html2;

        return $this->content;
    }
}
