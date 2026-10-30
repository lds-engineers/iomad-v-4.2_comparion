<?php
require_once($CFG->dirroot . '/lib/formslib.php');
global $DB, $USER;

class create_business_trainee extends moodleform {
  //Add elements to form
  public function definition() {
       global $CFG, $DB;
       $mform = $this->_form;
        $mform->addElement('text', 'user_name', 'User Name');
        $mform->addRule('user_name', 'Missing User Name', 'required', null, 'client', false, false);
        $mform->addRule('user_name','Must contain only lowercase letters, numbers, underscore, dash, or @','regex','/^[a-z0-9_\-@]+$/','client',false,false); 
       	$mform->setType('user_name', PARAM_MULTILANG); 
       
        $mform->addElement('text', 'first_name', 'First Name');
        $mform->addRule('first_name', 'Missing First Name', 'required', null, 'client', false, false);
        $mform->setType('first_name', PARAM_MULTILANG); 
  
        $mform->addElement('text', 'last_name', 'Last Name');
        $mform->addRule('last_name', 'Missing Last Name', 'required', null, 'client', false, false);
        $mform->setType('last_name', PARAM_MULTILANG); 
        

        $mform->addElement('text', 'email', 'Email Address', 'maxlength="254" size="50"');
        $mform->addRule('email', 'Missing Email', 'required', null, 'client', false, false);
        $mform->addRule('email', 'Invalid email pattern', 'email', null, 'client', false, false);
        $mform->setType('email', PARAM_MULTILANG); 

        $mform->addElement('password', 'password', 'Password');
        $mform->addRule('password', 'Missing Password', 'required', null, 'client', false, false);
        $mform->addRule('password', 'Password must be at least 6 characters long.', 'minlength', 6, 'client', false, false);
        $mform->setType('password', PARAM_MULTILANG); 

        $mform->addElement('password', 'cpassword', 'Confirm Password');
        $mform->addRule('cpassword', 'Missing Confirm Password', 'required', null, 'client', false, false);
        $mform->setType('cpassword', PARAM_MULTILANG); 
  
        $this->add_action_buttons($cancel = true, $submitlabel = 'Sign up');
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