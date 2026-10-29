<?php
require_once(dirname(__FILE__) . '/../../config.php');
require_once('user_cretificate_infoform.php');
require_once($CFG->dirroot . '/blocks/learnpeek_shop/locallib.php');
global $DB, $USER;
require_login();
$PAGE->set_context(context_system::instance());
$context = context_system::instance();
$PAGE->set_context($context);
$id   =  required_param('id',PARAM_INT); 
$pagetitle = "Add Address And Photo";
$PAGE->set_heading($pagetitle);
echo $OUTPUT->header();
echo "<style>
  .mform .form-inline .form-control, .mform .form-inline .custom-select {
  width: 80% !important;
}
</style>";
$userid = $USER->id;
$usercontext = context_user::instance($USER->id); // User context

$addformaaa = new course_addressform();
if ($addformaaa->is_cancelled()) {
     redirect($CFG->wwwroot). "/my"; 
}
elseif($addform = $addformaaa->get_data())
{
    $userdata = new stdClass();
    $userdata->id = $USER->id;
    if($addform->profile_field_certificate_address){
      $userdata->profile_field_certificate_address   = $addform->profile_field_certificate_address;
    }
    if($addform->profile_field_certificate_image){
      $userdata->profile_field_certificate_image   = 1;
    }
    $updatedcertificate = save_user_profile_data($userdata);
    file_save_draft_area_files($addform->profile_field_certificate_image, $usercontext->id, 'profilefield_file', 'files_5', 0);
    if($updatedcertificate){
        redirect($CFG->wwwroot.'/course/view.php?id='.$id);
    }

}

$data = new stdClass(); 
$data->id = $id;
  $userdata = $DB->get_record_sql('SELECT * FROM {user} WHERE id = ?',array($userid));
  if($userdata){
      $caddress = $DB->get_record('user_info_field',array('shortname'=>'certificate_address'));
      $user_caddress  = $DB->get_record('user_info_data', array('userid'=>$userid,'fieldid'=>$caddress->id));
      $certificate_image = file_get_submitted_draft_itemid('files_5');
      file_prepare_draft_area($certificate_image, $usercontext->id, 'profilefield_file', 'files_5', 0);
       $data->profile_field_certificate_address      = $user_caddress->data;
      $data->profile_field_certificate_image       = $certificate_image;
  }

$addformaaa->set_data($data);
$addformaaa->display();



?>

 


<?php  echo $OUTPUT->footer(); ?>
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyD7KZ3dujsZuhVn8E0uagRyQ65_YvpGJcM&libraries=places"></script>
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
    const $input =  $('#id_profile_field_certificate_address');
    // Append link right next to the input
    $input.after($viewLink);
});
  function initAutocomplete() {
    const input = document.getElementById("id_profile_field_certificate_address");
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
