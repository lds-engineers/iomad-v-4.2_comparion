<?php
require_once($CFG->dirroot . '/lib/formslib.php');
global $DB, $USER;

class registration extends moodleform {
  //Add elements to form
  public function definition() {
       global $CFG, $DB;

       $companies = $DB->get_records_sql("SELECT * FROM {company}  ORDER BY name ASC");
        $company  = array();

        foreach($companies as $companylist){
            $company[''] = 'Select Company';
            $company[$companylist->id] =    $companylist->name;
            
        }

       $mform = $this->_form;
        // validate_email_mobile_cpr    
        $mform->addElement('text', 'user_name', get_string('user_name','local_user_registration'));
        $mform->addRule('user_name', get_string('missing_user_name','local_user_registration'), 'required', null, 'client', false, false);
        $mform->addRule('user_name','Must contain only lowercase letters, numbers, underscore, dash, or @','regex','/^[a-z0-9_\-@]+$/','client',false,false);
       	$mform->setType('user_name', PARAM_MULTILANG); 
       
        $mform->addElement('text', 'first_name', get_string('first_name','local_user_registration'));
        $mform->addRule('first_name', get_string('missing_first_name','local_user_registration'), 'required', null, 'client', false, false);
        $mform->setType('first_name', PARAM_MULTILANG); 
  
        $mform->addElement('text', 'last_name',  get_string('last_name','local_user_registration'));
        $mform->addRule('last_name', get_string('missing_last_name','local_user_registration'), 'required', null, 'client', false, false);
        $mform->setType('last_name', PARAM_MULTILANG); 
        

        $mform->addElement('text', 'email', get_string('email','local_user_registration'), 'maxlength="254" size="50"');
        $mform->addRule('email', get_string('missing_email','local_user_registration'), 'required', null, 'client', false, false);
        $mform->addRule('email', 'Invalid email pattern', 'email', null, 'client', false, false);
        $mform->setType('email', PARAM_MULTILANG); 

        // $mform->addElement('select', 'company_id', 'Select Company',$company);
        // $mform->addRule('company_id', 'Select Company', 'required', null, 'client', false, false);

        $mform->addElement('password', 'password', get_string('password','local_user_registration'));
        $mform->addRule('password', get_string('missing_password','local_user_registration'), 'required', null, 'client', false, false);
        $mform->addRule('password', 'Password must be at least 6 characters long.', 'minlength', 6, 'client', false, false);
        $mform->setType('password', PARAM_MULTILANG); 

        $mform->addElement('password', 'cpassword', get_string('confirm_password','local_user_registration'));
        $mform->addRule('cpassword', get_string('missing_confirm_password','local_user_registration'), 'required', null, 'client', false, false);
        $mform->setType('cpassword', PARAM_MULTILANG); 
  
        $this->add_action_buttons($cancel = true, $submitlabel = get_string('submit','local_user_registration'));
    }
    function validation($data, $files) {
    	global $CFG, $DB;
        $errors = array();
        $companyid = iomad::get_my_companyid(context_system::instance());
        $validatusername = $DB->get_record_sql("SELECT * FROM {user} WHERE email LIKE '".$data['user_name']."' OR username = '".$data['user_name']."'");
        if($validatusername){
        	$errors['user_name'] = "User name is alreday registered";
        }

        $validateuseremail = $DB->get_record_sql(
            "SELECT u.id 
            FROM {user} u 
            JOIN {company_users} cu ON cu.userid = u.id 
            WHERE (u.email = :email OR u.username = :username) 
                AND cu.companyid = :companyid",
            [
                'email' => $data['email'],
                'username' => $data['email'],
                'companyid' => $companyid
            ]
        );

        if ($validateuseremail) {
            $errors['email'] = "This email is already registered for your company.";
        }
        
        // $validatuseremail = $DB->get_record_sql("SELECT * FROM {user} WHERE email LIKE '".$data['email']."' OR username = '".$data['email']."'");
        // if ($validatuseremail) {
		//     $errors['email'] = "Email is alreday registered";
		// }

        if ($data['cpassword'] != $data['password']) {
            $errors['cpassword'] = "Password should be same.";
        }
     return $errors;
    }
}