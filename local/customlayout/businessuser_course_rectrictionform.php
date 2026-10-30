<?php
require_once($CFG->dirroot . '/lib/formslib.php');
global $DB, $USER;

class business_users_course_rectriction extends moodleform {
  //Add elements to form
  public function definition() {
       global $CFG, $DB;
        $id   = optional_param('id', 0, PARAM_INT);
        $buserid   = optional_param('buserid', 0, PARAM_INT);
        $companyuser = $DB->get_record('company_users', ['userid' => $buserid]);
        $userdata = $DB->get_record('user', ['id' => $buserid]);

        $companyinfo = $DB->get_record_sql("SELECT * FROM {company}  WHERE id = ?",[$companyuser->companyid]);
       
        $mform = $this->_form;

        $mform->addElement('static', 'username', get_string('username','local_customlayout'), fullname($userdata));
        $mform->addElement('static', 'companyname', get_string('companyname','local_customlayout'), $companyinfo->name);
        $mform->addElement('static', 'allcategoryele', "", '<div id="allcategory"></div>');


        $mform->addElement('hidden', 'buserid'); 
        
        $this->add_action_buttons($cancel = true, $submitlabel = get_string('assign','local_customlayout'));
    }
}