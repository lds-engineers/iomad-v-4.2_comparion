<?php
require_once("$CFG->libdir/formslib.php");

class department_assign_form extends moodleform {

    function definition() {
        global $DB, $USER;

        $mform = $this->_form;
        list($companyid, $businessusers, $departments, $assignedusers, $departmentid, $potentialusers, $manag_role) = $this->_customdata;
        // echo "<pre>";
        // print_r($potentialusers);
        // echo "</pre>";
       
        if (in_array('companydepartmentmanager', $manag_role)) {
            if (!empty($departmentid)) {
                $selecteddepartment = $DB->get_record('customdepartment', ['id' => $departmentid]);
                if ($selecteddepartment) {
                    $selectedbusinessuserid = $selecteddepartment->businessuserid;
                    $businessusers = [$selectedbusinessuserid => $businessusers[$selectedbusinessuserid]];
                    $departments = [$departmentid => $departments[$departmentid]];
                    $mform->addElement('select', 'businessuserid', 'Business User', $businessusers);
                    $mform->setType('businessuserid', PARAM_INT);
                    $mform->addRule('businessuserid', null, 'required');
                    $mform->setDefault('businessuserid', $selectedbusinessuserid);
                    $mform->addElement('select', 'departmentid', 'Department', $departments);
                    $mform->setType('departmentid', PARAM_INT);
                    $mform->addRule('departmentid', null, 'required');
                    $mform->setDefault('departmentid', $departmentid);
                }
            }
        } else {
            $selectedbusinessuser = $DB->get_record('customdepartment', ['id'=>$departmentid]);
            $mform->addElement('select', 'businessuserid', 'Business User', $businessusers);
            $mform->setType('businessuserid', PARAM_INT);
            $mform->addRule('businessuserid', null, 'required');
            if (!empty($departmentid)) {
                $mform->setDefault('businessuserid', $selectedbusinessuser->businessuserid);
            }
            $mform->addElement('select', 'departmentid', 'Department', $departments);
            $mform->setType('departmentid', PARAM_INT);
            $mform->addRule('departmentid', null, 'required');
            if (!empty($departmentid)) {
                $mform->setDefault('departmentid', $departmentid);
            }
        }

        $types = array("Individual users","Department managers");
        $mform->addElement('select', 'type', '', $types, array('onchange' => 'javascript:myFunctionToDoSomething();'));
        $mform->setType('type', PARAM_INT);
        $mform->addRule('type', null, 'required');

        // Start wrapper
        $mform->addElement('html', '<div class="dual-select-wrapper">');

        // Left column
        $assignedCount = $assignedusers ? count($assignedusers) : 0;
        $mform->addElement('html', '<div class="select-box-column">');
        $html = '<div id="assigned-label"><strong>Assigned users (' . $assignedCount . ')</strong></div>';
        $mform->addElement('select', 'assignedusers', $html, $assignedusers, [
            'multiple' => 'multiple', 'size' => 20, 'id' => 'assignedusers'
        ]);
        $mform->addElement('html', '</div>');

        // Buttons column
        $mform->addElement('html', '<div class="button-column">');
        $mform->addElement('html', '<input type="button" id="add" value="&lt; '.get_string('add', 'local_customlayout').'" class="btn btn-secondary"><br><br>');
        $mform->addElement('html', '<input type="button" id="remove" value="'.get_string('remove', 'local_customlayout').' &gt;" class="btn btn-secondary">');
        $mform->addElement('html', '</div>');

        // Right column
        // $potentialCount = $potentialusers ? count($potentialusers) : 0;
        // $mform->addElement('html', '<div class="select-box-column">');
        // $html = '<div id="potential-label"><strong>All individual users (' . $potentialCount . ')</strong></div>';
        // $mform->addElement('select', 'potentialusers', $html, $potentialusers, [
        //     'multiple' => 'multiple', 'size' => 20, 'id' => 'potentialusers'
        // ]);
        // $mform->addElement('html', '</div>');


        // $potentialCount = $potentialusers ? count($potentialusers) : 0;
        $mform->addElement('html', '<div class="select-box-column">');
        $html = '<div id="potential-label"><strong>All individual users </strong></div>';
        $mform->addElement('select', 'potentialusers', $html, [], [
            'multiple' => 'multiple', 'size' => 20, 'id' => 'potentialusers'
        ]);
        $mform->addElement('html', '</div>');

        // End wrapper
        $mform->addElement('html', '</div>');


        $mform->addElement('hidden', 'finalassigned');
        $mform->setType('finalassigned', PARAM_RAW);

        $mform->addElement('hidden', 'id', $departmentid);
        $mform->setType('id', PARAM_INT);

        $mform->setType('potentialusers', PARAM_RAW);
        $mform->setType('assignedusers', PARAM_RAW);


        $this->add_action_buttons();
    }
}
