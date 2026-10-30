<?php
require_once($CFG->dirroot . '/lib/formslib.php');
global $DB, $USER;
$systemcontext = get_context_instance(CONTEXT_SYSTEM);
class testoutgoingmail extends moodleform {
  //Add elements to form
    public function definition() {
        global $CFG, $DB;
        $id   = optional_param('id', 0, PARAM_INT);

        $mform = $this->_form;
        $mform->addElement('text', 'to_email_address', get_string('toemailaddress','local_learnpeek_email_template'), 'maxlength="254" size="50"');
        $mform->addRule('to_email_address', get_string('invalidemailpattern','local_learnpeek_email_template'), 'email', null, 'client', false, false);
        $mform->addRule('to_email_address', 'Add Email address', 'required', null, 'client', false, false);
        $mform->setType('to_email_address', PARAM_MULTILANG); 
        
        // $mform->addElement('text', 'from_email_address', 'From username or email address');
        // $mform->setType('from_email_address', PARAM_MULTILANG); 

        $mform->addElement('text', 'email_subject', get_string('additionalsubject','local_learnpeek_email_template'));
        $mform->setType('email_subject', PARAM_MULTILANG); 

        $mform->addElement('hidden', 'mapped_id', $id);

        $this->add_action_buttons($cancel = true, $submitlabel = get_string('sendatestmessage','local_learnpeek_email_template'));
    }
    function validation($data, $files) {
    	global $CFG, $DB;
        $errors = array();
        return $errors;
    }
}