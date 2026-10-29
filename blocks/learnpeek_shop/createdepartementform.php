<?php
require_once($CFG->dirroot . '/lib/formslib.php');
global $DB, $USER;

class create_departement extends moodleform {
  //Add elements to form
  public function definition() {
       global $CFG, $DB;

       $getcomapny = $DB->get_records_sql('SELECT * FROM {company} WHERE suspended = 0 ORDER BY name ASC');

        $comapnylists  = array();
        foreach($getcomapny as $comapnylist){
            $comapnylists[''] = 'Select Business User';
            $comapnylists[$comapnylist->id] = $comapnylist->name;
            
        }


       $mform = $this->_form;


        $mform->addElement('select', 'company_id', 'Company',$comapnylists);
        $mform->addElement('select', 'buser_id', 'Business Users','');
        // $mform->addElement('select', 'company_id', 'Company',$comapnylist);
        // $mform->addElement('text', 'firstname', 'First Name');
        // $mform->setType('firstname', PARAM_MULTILANG); 
  
        // $mform->addElement('text', 'lastname', 'Last Name');
        // $mform->setType('lastname', PARAM_MULTILANG); 
        

        // // $mform->addElement('text', 'email', 'Email Address','disabled');
        // // $mform->setType('email', PARAM_MULTILANG); 
        // $mform->addElement('static', 'email1', 'Email Address', $userdata->email);
        // $mform->addElement('hidden', 'email',$userdata->email);
        // $mform->addElement('hidden', 'companyid',$companyid);

        // $mform->addElement('select', 'parent_id', 'Assign as Trainee to Business User',$buss_user);

        // $mform->addElement('password', 'password', 'Password');
        // // $mform->addRule('password', 'Missing Password', 'required', null, 'client', false, false);
        // $mform->setType('password', PARAM_MULTILANG); 
        
        $mform->addElement('hidden', 'id', $userdata->id);   
  
        $this->add_action_buttons($cancel = true, $submitlabel = 'Update');
    }
    function validation($data, $files) {
    	global $CFG, $DB;
        $errors = array();
        return $errors;
    }
}