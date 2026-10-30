<?php
require_once($CFG->dirroot . '/lib/formslib.php');
global $DB, $USER;

class create_business_user extends moodleform {
  //Add elements to form
  public function definition() {
        global $CFG, $DB;
        $mform = $this->_form;
        $mform->addElement('text', 'user_name', get_string('user_name', 'local_user_registration'));
        $mform->addRule('user_name', get_string('missing_user_name', 'local_user_registration'), 'required', null, 'client', false, false);
        $mform->addRule('user_name','Must contain only lowercase letters, numbers, underscore, dash, or @','regex','/^[a-z0-9_\-@]+$/','client',false,false);
        $mform->setType('user_name', PARAM_MULTILANG); 
       
        $mform->addElement('text', 'first_name', get_string('first_name', 'local_user_registration'));
        $mform->addRule('first_name', get_string('missing_first_name', 'local_user_registration'), 'required', null, 'client', false, false);
        $mform->setType('first_name', PARAM_MULTILANG); 
  
        $mform->addElement('text', 'last_name', get_string('last_name', 'local_user_registration'));
        $mform->addRule('last_name', get_string('missing_last_name', 'local_user_registration'), 'required', null, 'client', false, false);
        $mform->setType('last_name', PARAM_MULTILANG); 
        
        // $mform->addElement('text', 'company_name', 'Company Name');
        // $mform->addRule('company_name', 'Missing Company Name', 'required', null, 'client', false, false);
        // $mform->setType('company_name', PARAM_MULTILANG); 

        $mform->addElement('text', 'user_company_name', get_string('user_company_name', 'local_user_registration'));
        $mform->addRule('user_company_name', get_string('missing_company_name', 'local_user_registration'), 'required', null, 'client', false, false);
        $mform->setType('user_company_name', PARAM_MULTILANG);
  
        $mform->addElement('text', 'email', get_string('email', 'local_user_registration'));
        $mform->addRule('email', get_string('missing_email', 'local_user_registration'), 'required', null, 'client', false, false);
        $mform->addRule('email', 'Invalid email pattern', 'email', null, 'client', false, false);
        $mform->setType('email', PARAM_MULTILANG); 
  
        $mform->addElement('password', 'password', get_string('password', 'local_user_registration'));
        $mform->addRule('password', get_string('missing_password', 'local_user_registration'), 'required', null, 'client', false, false);
        $mform->addRule('password', 'Password must be at least 6 characters long.', 'minlength', 6, 'client', false, false);
        $mform->setType('password', PARAM_MULTILANG); 

        $mform->addElement('password', 'cpassword', get_string('confirm_password', 'local_user_registration'));
        $mform->addRule('cpassword', get_string('missing_confirm_password', 'local_user_registration'), 'required', null, 'client', false, false);
        $mform->setType('cpassword', PARAM_MULTILANG);
  
        $this->add_action_buttons($cancel = true, $submitlabel = get_string('submit', 'local_user_registration'));
    }
    function validation($data, $files) {
        global $CFG, $DB;
        $errors = array();
        $validatusername = $DB->get_record_sql("SELECT * FROM {user} WHERE email LIKE '".$data['user_name']."' OR username = '".$data['user_name']."'");
        if($validatusername){
            $errors['user_name'] = "User name is alreday registered";
        }

        $validatuseremail = $DB->get_record_sql("SELECT * FROM {user} WHERE email LIKE '".$data['email']."' OR username = '".$data['email']."'");
        if ($validatuseremail) {
            $errors['email'] = "Email is alreday registered";
        }

        $validatcompanyname = $DB->get_record_sql("SELECT * FROM {company} WHERE name  = '".$data['company_name']."'");
        if ($validatcompanyname) {
            $errors['company_name'] = "company name is already used for another company";
        }

        $validatshortname = $DB->get_record_sql("SELECT * FROM {company} WHERE shortname  = '".$data['company_shortname']."'");
        if ($validatshortname) {
            $errors['company_shortname'] = "Company shortname is already used for another company";
        }


        if ($data['cpassword'] != $data['password']) {
            $errors['cpassword'] = "Password should be same.";
        }       

     return $errors;
    }
}