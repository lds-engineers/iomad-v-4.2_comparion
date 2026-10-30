<?php
defined('MOODLE_INTERNAL') || die;
require_once($CFG->libdir.'/formslib.php');
global $DB, $USER;
$systemcontext = get_context_instance(CONTEXT_SYSTEM);
class course_addressform extends moodleform {
    // Define the form
    function definition() {
        global $CFG,$DB,$USER, $TEXTAREA_OPTIONS;       
        $mform =& $this->_form;
        
        $mform->addElement('text', 'profile_field_certificate_address', 'Add Address');
        $mform->addRule('profile_field_certificate_address', 'Missing Address', 'required', null, 'client', false, false);
        $mform->setType('profile_field_certificate_address', PARAM_MULTILANG); 

        $imageoverviewfilesoptions = Array (
            "maxfiles" => 1,
            "maxbytes" => 5 * 1024 * 1024,
            "subdirs" => 0,
            "accepted_types" => Array (".jpg", ".jpeg" , ".png" ),
            "context" => $systemcontext
        );
        $mform->addElement('filemanager', 'profile_field_certificate_image', 'Add Profile Photo', null, $imageoverviewfilesoptions);
        $mform->addRule('profile_field_certificate_image', 'Missing Profile Image', 'required', null, 'client', false, false);
        $mform->addElement('hidden', 'id');    
        $mform->setType('region', PARAM_MULTILANG); 
           $this->add_action_buttons(false, "SAVE");   

    }

    public function validation($data, $files) {
        $errors = array();

        $address = trim($data['profile_field_certificate_address']);
        if (!preg_match('/\b\d{4,6}\b/', $address)) {
            $errors['profile_field_certificate_address'] = 'Zip code is missing in the address.';
        }

        return $errors;
    }
    

}