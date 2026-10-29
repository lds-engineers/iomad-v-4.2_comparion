<?php
function setuserprofileimagedata($userid,$imageurl){
  global $PAGE, $CFG,$DB, $USER;
  require_once($CFG->libdir.'/adminlib.php');
  require_once($CFG->libdir.'/filelib.php');
  require_once($CFG->libdir."/weblib.php");
   $path=$imageurl;
   // $imgData = base64_encode(file_get_contents($path));
   $wsfiledata_decoded = file_get_contents($path);
   $fs = get_file_storage();
   $context = context_user::instance($userid, MUST_EXIST);
   $user = core_user::get_user($userid, '*', MUST_EXIST);
   $USER = $user;
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
      $user->deletepicture = 1;
   } else {
      $user->deletepicture = 1;

   }
   core_user::update_picture($user, $filemanageroptions);
 // $DB->set_field('user', 'picture',$imagedataid, array('id' => $userid));
   $DB->delete_records("files", array("contextid"=>$context->id, 'component' => 'user','filearea' => 'newicon'));
   $userdata=$DB->get_record('user',array('id'=>$userid));

   $USER->picture = $userdata->picture;
   \core\event\user_updated::create_from_userid($userid)->trigger();
   purge_all_caches();
	return "sucess";
}