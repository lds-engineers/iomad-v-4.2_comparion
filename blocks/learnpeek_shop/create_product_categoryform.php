<?php
require_once($CFG->dirroot . '/lib/formslib.php');
global $DB, $USER;

class create_product_category extends moodleform {
  //Add elements to form
  public function definition() {
       global $CFG, $DB;
       // $userid = required_param('id',PARAM_INT); 
        $companydatalist = $DB->get_records('company', array('suspended' => 0));
        $companylist  = array();
        foreach($companydatalist as $companydata){
            $companylist[''] = get_string('selectcompanyuser','block_learnpeek_shop');
            $companylist[$companydata->id] = $companydata->name;
        }
        $mform = $this->_form;
        $mform->addElement('text', 'category_name', get_string('productcategoryname','block_learnpeek_shop'));
        $mform->addRule('category_name',  get_string('missingproductcategoryname','block_learnpeek_shop'), 'required', null, 'client', false, false);
        $mform->setType('category_name', PARAM_MULTILANG); 

        $mform->addElement('select', 'company_id', get_string('selectcompany','block_learnpeek_shop'),$companylist);
        $mform->addElement('hidden', 'id', $userdata->id);   
  
        $this->add_action_buttons($cancel = true, $submitlabel = get_string('createcategory','block_learnpeek_shop'));
    }
    function validation($data, $files) {
    	global $CFG, $DB;
        $errors = array();
        return $errors;
    }
}