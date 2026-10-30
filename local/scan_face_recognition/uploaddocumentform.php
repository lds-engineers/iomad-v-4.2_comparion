<?php
defined('MOODLE_INTERNAL') || die;
require_once($CFG->libdir.'/formslib.php');
global $DB, $USER;
$systemcontext = get_context_instance(CONTEXT_SYSTEM);

class uploaddocumentform extends moodleform {
    function definition() {
        $id = $this->_customdata['id'];		
		$mform =& $this->_form;
        $imageoverviewfilesoptions = array(
            'maxfiles' => 1,
            'maxbytes' => 3 * 1024 * 1024,
            'subdirs' => 0,
            'accepted_types' => array(
                '.jpg',
                '.jpeg',
                '.png',
                '.gif',
                '.bmp',
                '.webp',
                '.pdf',
                '.docx'
            ),
            'context' => $systemcontext
        );

        $mform->addElement('filemanager', 'document', null, null, $imageoverviewfilesoptions);
        $mform->addRule('document', get_string('required'), 'required', null, 'client');

        $mform->addElement('hidden','id',$id);
        $mform->setType('id', PARAM_INT);    
        $mform->setType('region', PARAM_MULTILANG); 
        $this->add_action_buttons(false, "SAVE");   
    }
}