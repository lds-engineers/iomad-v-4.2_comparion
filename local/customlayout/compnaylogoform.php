<?php
defined('MOODLE_INTERNAL') || die;
require_once($CFG->libdir.'/formslib.php');
global $DB, $USER;
$systemcontext = get_context_instance(CONTEXT_SYSTEM);

echo 
    "<style>
        .cst_mybox fieldset#id_faviconheader div#fitem_id_favicon_image>div:first-child {
            display: none !important;
        }
    </style>";

class course_mappingform extends moodleform {
    // Define the form
    function definition() {
		global $CFG,$DB,$USER, $TEXTAREA_OPTIONS;		
		$mform =& $this->_form;

        $imageoverviewfilesoptions = Array ( 
            "maxfiles" => 1, 
            'maxbytes' => 5 * 1024 * 1024, 
            "subdirs" => 0, 
            "accepted_types" => Array (".jpg", ".png" ), 
            "context" => $systemcontext 
        );
        
        // Uday
        $faviconfilesoptions = array(
            "maxfiles" => 1,
            'maxbytes' => 5 * 1024 * 1024,
            "subdirs" => 0,
            "accepted_types" => array(".ico", ".png", ".jpg"),
            "context" => $systemcontext
        );

        $mform->addElement('html', '<div class="cst_mybox" style="margin-bottom: 25px;">'); // uday
        $mform->addElement('header', 'logoheader', get_string('companylogo', 'local_customlayout'));
        $mform->addElement('filemanager', 'logo_image', null, null, $imageoverviewfilesoptions);

        $mform->addElement('header', 'mobiletopheader', get_string('mobiletoplogo', 'local_customlayout'));
        $mform->addElement('filemanager', 'logo_mobile_topbar', null, null, $imageoverviewfilesoptions);
        // $mform->addElement('html', '</div>'); // Uday

        // Uday
        // $mform->addElement('html', '<div style="margin-bottom: 25px;">');
        $mform->addElement('header', 'faviconheader', get_string('companyfavicon', 'local_customlayout')); // "Favicon"
        $mform->addElement('filemanager', 'favicon_image', null, null, $faviconfilesoptions);
        $mform->addElement('html', '</div>');

        $mform->addElement('hidden', 'id');    
        $mform->setType('region', PARAM_MULTILANG); 

        $this->add_action_buttons(false, get_string('savechanges', 'local_customlayout'));   

    }
	

}