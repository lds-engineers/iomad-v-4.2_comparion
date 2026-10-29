<?php
namespace shared_courses\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

class shared_courses_form extends \moodleform {

    public function definition() {
        global $DB;

        $mform = $this->_form;
        
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $editmode = $this->_customdata['editmode'] ?? false;

        // Course dropdown
        $courses = $DB->get_records_menu('course', null, 'fullname ASC', 'id, fullname');

        $mform->addElement('select', 'course_id', get_string('selectcourse','block_shared_courses'), $courses);
        $mform->addRule('course_id', null, 'required', null, 'client');

        $idprecords = $DB->get_records('auth_iomadsaml2_idps', null, 'displayname ASC', 'displayname, entityid');

        $options = [];

        foreach ($idprecords as $idp) {
            $options[$idp->entityid] = $idp->displayname;
        }
        
        $mform->addElement('select', 'idps_entityid', get_string('selectidentityproviders','block_shared_courses'), $options);
        $mform->addRule('idps_entityid', null, 'required', null, 'client');

        // If editing → disable course selection
        // if ($editmode) {
        //     $mform->freeze('course_id');
        //     $mform->freeze('idps_entityid');
        // }

        if ($editmode) {

            $mform->freeze('course_id');
            $mform->freeze('idps_entityid');

            // Preserve values for submit
            $mform->addElement('hidden', 'course_id');
            $mform->setType('course_id', PARAM_INT);

            $mform->addElement('hidden', 'idps_entityid');
            $mform->setType('idps_entityid', PARAM_TEXT);
        }

        // Status dropdown
        $mform->addElement('select', 'status', get_string('status','block_shared_courses'), [
            1 => get_string('active','block_shared_courses'),
            0 => get_string('inactive','block_shared_courses')
        ]);

        // Hidden id for edit
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons(true, $editmode ? 'Update' : get_string('save','block_shared_courses'));
    }
}
