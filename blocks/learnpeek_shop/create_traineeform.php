<?php
require_once($CFG->dirroot . '/lib/formslib.php');
global $DB, $USER;

class create_business_trainee extends moodleform {
  //Add elements to form
  public function definition() {
        global $CFG, $DB, $USER;
        $mform = $this->_form;

        $departmentoptions = [];

        $context = context_user::instance($USER->id);
        $current_user_roles = get_user_roles($context, $USER->id);

        if ($current_user_roles) {
            $managerrole = array_column($current_user_roles, 'shortname');

            if ($managerrole[0] == "companyreporter") {
                $department_ids = [];
                $userdepartments = $DB->get_records_sql("SELECT * FROM {customdepartment_assign} WHERE child_id = ? AND is_departmentmanager = 1",[$USER->id]);

                foreach ($userdepartments as $department) {
                    $department_ids[] = $department->department_id;
                }

                if (!empty($department_ids)) {
                    $insql = implode(',', $department_ids);
                    $departments = $DB->get_records_sql("SELECT * FROM {customdepartment} WHERE id IN ($insql)");

                    $departmentoptions[''] = get_string('selectdepartment','block_learnpeek_shop');
                    foreach ($departments as $dept) {
                        $departmentoptions[$dept->id] = $dept->name;
                    }
                    // echo "<pre>";
                    // print_r($departmentoptions);
                    // echo "</pre>";
                    // echo count($departmentoptions);
                    if (count($departmentoptions) > 2) {
                        $mform->addElement('select', 'departmentid', get_string('selectdepartment', 'block_learnpeek_shop'), $departmentoptions);
                        $mform->addRule('departmentid', get_string('pleaseselectdepartment', 'block_learnpeek_shop'), 'required', null, 'client');
                    }
                }
            }
        }

        // $mform->addElement('text', 'user_name', 'User Name');
        $mform->addElement('text', 'user_name', get_string('username', 'block_learnpeek_shop'));
        $mform->addRule('user_name', get_string('missingusername', 'block_learnpeek_shop'), 'required', null, 'client', false, false);
        $mform->addRule('user_name','Must contain only lowercase letters, numbers, underscore, dash, or @','regex','/^[a-z0-9_\-@]+$/','client',false,false); 
       	$mform->setType('user_name', PARAM_MULTILANG); 
       
        // $mform->addElement('text', 'first_name', 'First Name');
        $mform->addElement('text', 'first_name', get_string('firstname', 'block_learnpeek_shop'));
        $mform->addRule('first_name', get_string('missingfirstname', 'block_learnpeek_shop'), 'required', null, 'client', false, false);
        $mform->setType('first_name', PARAM_MULTILANG); 
  
        // $mform->addElement('text', 'last_name', 'Last Name');
        $mform->addElement('text', 'last_name', get_string('lastname', 'block_learnpeek_shop'));
        $mform->addRule('last_name', get_string('missinglastname', 'block_learnpeek_shop'), 'required', null, 'client', false, false);
        $mform->setType('last_name', PARAM_MULTILANG); 
        

        $mform->addElement('text', 'email', get_string('emailaddress', 'block_learnpeek_shop'), 'maxlength="254" size="50"');
        $mform->addRule('email', get_string('missingemail', 'block_learnpeek_shop'), 'required', null, 'client', false, false);
        $mform->addRule('email', 'Invalid email pattern', 'email', null, 'client', false, false);
        $mform->setType('email', PARAM_MULTILANG); 

        // $mform->addElement('password', 'password', 'Password');
        $mform->addElement('password', 'password', get_string('password', 'block_learnpeek_shop'));
        $mform->addRule('password', get_string('missingpassword', 'block_learnpeek_shop'), 'required', null, 'client', false, false);
        $mform->addRule('password', 'Password must be at least 6 characters long.', 'minlength', 6, 'client', false, false);
        $mform->setType('password', PARAM_MULTILANG); 

        // $mform->addElement('password', 'cpassword', 'Confirm Password');
        $mform->addElement('password', 'cpassword', get_string('confirmpassword', 'block_learnpeek_shop'));
        $mform->addRule('cpassword', get_string('missingconfirmpassword', 'block_learnpeek_shop'), 'required', null, 'client', false, false);
        $mform->setType('cpassword', PARAM_MULTILANG); 
  
        $this->add_action_buttons($cancel = true, $submitlabel = get_string('signup', 'block_learnpeek_shop'));
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