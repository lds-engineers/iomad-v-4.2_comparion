<?php
require_once('../../config.php');
global $DB, $CFG, $PAGE, $USER;
require_once($CFG->dirroot . '/mod/zoom/lib.php');
require_once($CFG->dirroot . '/mod/zoom/locallib.php');
require_once($CFG->dirroot . '/mod/zoom/classes/webservice.php');
require_once($CFG->dirroot . '/course/modlib.php');

function local_manage_course_zoom_get_user_id($user,$required = true) {
  $cache = cache::make('mod_zoom', 'zoomid');
  if (!($zoomuserid = $cache->get($user->id))) {
      $zoomuserid = false;
      try {
          $zoomuser = zoom_get_user(zoom_get_api_identifier($user));
          if ($zoomuser !== false && isset($zoomuser->id) && ($zoomuser->id !== false)) {
              $zoomuserid = $zoomuser->id;
              $cache->set($user->id, $zoomuserid);
          }
      } catch (moodle_exception $error) {
          if ($required) {
              throw $error;
          }
      }
  }
  return $zoomuserid;
}

$user = $DB->get_record("user", array("id"=>91));

$hostid = local_manage_course_zoom_get_user_id($user);
// $zoom = new stdClass();
// $zoom->name = 'testmeeting';
// $zoom->start_time = time();
// $zoom->duration = 3600;
// $zoom->host_id = zoom_get_user_id();
// $zoom->course = $course->id;
// $zoom->section = 0;
// $zoom->module = 37;
// $zoom->modulename = 'zoom';
// $zoom->visible = 1;
// $zoom->visibleoncoursepage = 1;


// $response = add_moduleinfo($zoom, $course);

echo '<pre>';
print_r($user);
print_r($hostid);
// function zoom_add_instance($zoom);
echo '</pre>';
die;


?>
