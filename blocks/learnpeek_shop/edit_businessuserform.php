<?php
require_once($CFG->dirroot . '/lib/formslib.php');
global $DB, $USER;

class edit_business_user extends moodleform {
  //Add elements to form
  public function definition() {
       global $CFG, $DB;
       $userid = required_param('id',PARAM_INT); 
        $userdata = $DB->get_record_sql('SELECT * FROM {user} WHERE id = ?',array($userid));
        $companydata = $DB->get_record('company_users', array('userid' => $userid));
        $companyid = $companydata->companyid;
        // $companyid = iomad::get_my_companyid(context_system::instance());
        $userfield = $DB->get_record('user_info_field', array('shortname' => 'user_company_name'));

        $busnessuser = $DB->get_records_sql("SELECT u.id,u.firstname,u.lastname,u.email,cu.companyid,uif.data FROM {company_users} as cu INNER JOIN {user} as u ON cu.userid = u.id LEFT JOIN {user_info_data} as uif ON uif.userid = u.id AND uif.fieldid = ? WHERE cu.managertype = 2 AND u.suspended = 0 AND cu.companyid =?", array($userfield->id,$companyid));


        $buss_user  = array();
        foreach($busnessuser as $busnessuser){
            $buss_user[''] = 'Select Business User';
            $buss_user[$busnessuser->id] = $busnessuser->firstname .' '.$busnessuser->lastname .'('.$busnessuser->email.')';
            
        }
       $mform = $this->_form;
        // $mform->addElement('text', 'username', 'User Name','disabled');
       	// $mform->setType('username', PARAM_MULTILANG); 
        // $mform->disabledIf('username', '');
        $mform->addElement('static', 'username1', get_string('username','block_learnpeek_shop'), $userdata->username);
        $mform->addElement('hidden', 'username',$userdata->username);
       
        $mform->addElement('text', 'firstname', get_string('firstname','block_learnpeek_shop'));
        $mform->setType('firstname', PARAM_MULTILANG);
        $mform->freeze('firstname'); 
  
        $mform->addElement('text', 'lastname', get_string('lastname','block_learnpeek_shop'));
        $mform->setType('lastname', PARAM_MULTILANG); 
        $mform->freeze('lastname');
        
        $mform->addElement('static', 'email1', get_string('emailaddress','block_learnpeek_shop'), $userdata->email);
        $mform->addElement('hidden', 'email',$userdata->email);
        $mform->addElement('hidden', 'companyid',$companyid);

        $mform->addElement('text', 'profile_field_user_company_name', get_string('companyname','block_learnpeek_shop'));
        // $mform->addRule('profile_field_user_company_name', 'Missing Company Name', 'required', null, 'client', false, false);
        $mform->setType('profile_field_user_company_name', PARAM_MULTILANG);

        $mform->addElement('password', 'password', get_string('password','block_learnpeek_shop')); 
        // $mform->addRule('password', 'Missing Password', 'required', null, 'client', false, false);
        $mform->setType('password', PARAM_MULTILANG); 
 
        // $mform->addElement('select', 'parent_id', 'Assign as Trainee to Business User',$buss_user);
        $mform->addElement('hidden', 'id', $userdata->id);   
  
        $this->add_action_buttons($cancel = true, $submitlabel = get_string('update','block_learnpeek_shop'));
    }
    function validation($data, $files) {
    	global $CFG, $DB;
        $errors = array();
        return $errors;
    }
}