<?php
require_once($CFG->dirroot . '/lib/formslib.php');
global $DB, $USER;

class whitelabelregistration extends moodleform {
  //Add elements to form
  public function definition() {
       global $CFG, $DB;

       $mform = $this->_form;
        // validate_email_mobile_cpr    
        // $mform->addElement('text', 'username', 'User Name');
        $mform->addElement('text', 'username', get_string('user_name', 'local_user_registration'));
        $mform->addRule('username', 'Missing User Name', 'required', null, 'client', false, false);
        $mform->addRule('username','Must contain only lowercase letters, numbers, underscore, dash, or @','regex','/^[a-z0-9_\-@]+$/','client',false,false);
       	$mform->setType('username', PARAM_MULTILANG); 
       
        // $mform->addElement('text', 'firstname', 'First Name');
        $mform->addElement('text', 'firstname', get_string('first_name', 'local_user_registration'));
        $mform->addRule('firstname', 'Missing First Name', 'required', null, 'client', false, false);
        $mform->setType('firstname', PARAM_MULTILANG); 
  
        // $mform->addElement('text', 'lastname', 'Last Name');
        $mform->addElement('text', 'lastname', get_string('last_name', 'local_user_registration'));
        $mform->addRule('lastname', 'Missing Last Name', 'required', null, 'client', false, false);
        $mform->setType('lastname', PARAM_MULTILANG); 
        

        // $mform->addElement('text', 'email', 'Email Address', 'maxlength="254" size="50"');
        $mform->addElement('text', 'email', get_string('email_address', 'local_user_registration'));
        $mform->addRule('email', 'Missing Email', 'required', null, 'client', false, false);
        $mform->addRule('email', 'Invalid email pattern', 'email', null, 'client', false, false);
        $mform->setType('email', PARAM_MULTILANG); 

        // $mform->addElement('select', 'company_id', 'Select Company',$company);
        // $mform->addRule('company_id', 'Select Company', 'required', null, 'client', false, false);

        // $mform->addElement('password', 'password', 'Password');
            $mform->addElement('text', 'password', get_string('password', 'local_user_registration'));
        $mform->addRule('password', 'Missing Password', 'required', null, 'client', false, false);
        $mform->addRule('password', 'Password must be at least 6 characters long.', 'minlength', 6, 'client', false, false);
        $mform->setType('password', PARAM_MULTILANG); 

        // $mform->addElement('password', 'cpassword', 'Confirm Password');
            $mform->addElement('text', 'cpassword', get_string('confirm_password', 'local_user_registration'));
        $mform->addRule('cpassword', 'Missing Confirm Password', 'required', null, 'client', false, false);
        $mform->setType('cpassword', PARAM_MULTILANG); 
  
        // $this->add_action_buttons($cancel = true, $submitlabel = 'Submit');
        $this->add_action_buttons($cancel = true, get_string('submit', 'local_user_registration'));
    }
    function validation($data, $files) {
    	global $CFG, $DB;
        $errors = array();
        $companyid = iomad::get_my_companyid(context_system::instance());
        $validatusername = $DB->get_record_sql("SELECT * FROM {user} WHERE email LIKE '".$data['username']."' OR username = '".$data['username']."'");
        if($validatusername){
        	$errors['username'] = "User name is alreday registered";
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