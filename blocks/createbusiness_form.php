<?php
require_once($CFG->dirroot . '/lib/formslib.php');
global $DB, $USER;

class registration extends moodleform {
  //Add elements to form
  public function definition() {
        global $CFG, $DB;
        $mform = $this->_form;
        $mform->addElement('html', '<div class="head_info" > <h3 style="background:#839af0;padding:10px;margin:22px 0; border-radius:10px; color:#fff;">Create an Account</h3></div>');

        $mform->addElement('text', 'user_name', 'User Name');
        $mform->addRule('user_name', 'Missing User Name', 'required', null, 'client', false, false);
        $mform->setType('user_name', PARAM_MULTILANG); 
       
        $mform->addElement('text', 'first_name', 'First Name');
        $mform->addRule('first_name', 'Missing First Name', 'required', null, 'client', false, false);
        $mform->setType('first_name', PARAM_MULTILANG); 
  
        $mform->addElement('text', 'last_name', 'Last Name');
        $mform->addRule('last_name', 'Missing Last Name', 'required', null, 'client', false, false);
        $mform->setType('last_name', PARAM_MULTILANG); 
        
        // $mform->addElement('text', 'company_name', 'Company Name');
        // $mform->addRule('company_name', 'Missing Company Name', 'required', null, 'client', false, false);
        // $mform->setType('company_name', PARAM_MULTILANG); 

        $mform->addElement('text', 'user_company_name', 'Company Name');
        $mform->addRule('user_company_name', 'Missing Company Name', 'required', null, 'client', false, false);
        $mform->setType('user_company_name', PARAM_MULTILANG);
  
        $mform->addElement('text', 'email', 'Email Address');
        $mform->addRule('email', 'Missing Email', 'required', null, 'client', false, false);
        $mform->addRule('email', 'Invalid email pattern', 'email', null, 'client', false, false);
        $mform->setType('email', PARAM_MULTILANG); 
  
        $mform->addElement('password', 'password', 'Password');
        $mform->addRule('password', 'Missing Password', 'required', null, 'client', false, false);
        $mform->setType('password', PARAM_MULTILANG); 

        $mform->addElement('password', 'cpassword', 'Confirm Password');
        $mform->addRule('cpassword', 'Missing Confirm Password', 'required', null, 'client', false, false);
        $mform->setType('cpassword', PARAM_MULTILANG);
  
        $this->add_action_buttons($cancel = true, $submitlabel = 'submit');
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