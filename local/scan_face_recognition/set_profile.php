<?php
require_once "../../config.php";
global $DB, $CFG, $PAGE, $OUTPUT, $USER;

 require_once($CFG->dirroot . "/course/externallib.php");

$loginuserdata = $DB->get_record('user',array('id'=>$USER->id));
$userpicture = new user_picture($loginuserdata);
$userpicture->size = 1; // Size f1.
$img = $userpicture->get_url($PAGE)->out(false);
$CFG->disableuserimages;
/*echo "<pre>";
print_r($USER->picture); 
print_r($img);
echo "</pre>";*/

$imageurl = "https://portal1.fivestudents.com/images/admin/customer-support.png";

// $userimage = userimagedata($USER->id,$imageurl);
// print_r($userimage);


function userimagedata($userid,$imageurl){
  global $PAGE, $CFG,$DB, $USER;
  require_once($CFG->libdir.'/adminlib.php');
  require_once($CFG->libdir.'/filelib.php');
   $path=$imageurl;
   // $imgData = base64_encode(file_get_contents($path));
   $wsfiledata_decoded = file_get_contents($path);
   $fs = get_file_storage();
   $context = context_user::instance($userid, MUST_EXIST);
   $user = core_user::get_user($userid, '*', MUST_EXIST);
   $USER = $user;
   $newpicture = $user->picture;
   $userdata=$DB->get_record('user',array('id'=>$userid));
   $fileinfo = array(
      'contextid' =>$context->id, 
      'component' => 'user',
      'filearea' => 'newicon',     // usually = table name
      'itemid' => '0',               // usually = ID of row in table
      'filepath' => "/",           // any path beginning and ending in /
      'filename' =>'f1.jpg'
   ); // any filename
   if(!empty($path)){
      $user->imagefile = 0;
      $fs->create_file_from_string($fileinfo, $wsfiledata_decoded);
      $filemanageroptions = array('maxbytes'       => $CFG->maxbytes,
           'subdirs'        => 0,
           'maxfiles'       => 1,
           'accepted_types' => 'optimised_image');
      file_prepare_draft_area($user->imagefile, $context->id, 'user', 'newicon', 0, $filemanageroptions);
      $user->imagefile = $user->imagefile;
   } else {
      $user->deletepicture = 1;

   }
   core_user::update_picture($user, $filemanageroptions);
 // $DB->set_field('user', 'picture',$imagedataid, array('id' => $userid));
   $DB->delete_records("files", array("contextid"=>$context->id, 'component' => 'user','filearea' => 'newicon'));
   // \core\event\user_updated::create_from_userid($userid)->trigger();
    purge_caches();
return "sucess";
   // }

}