<?php
require_once($CFG->dirroot . '/lib/formslib.php');
global $DB, $USER;
$systemcontext = get_context_instance(CONTEXT_SYSTEM);
class create_email_template extends moodleform {
  //Add elements to form
    public function definition() {
        global $CFG, $DB;
        $id   = optional_param('id', 0, PARAM_INT);

        $email_template = array(
            '' => 'Select Template',
            'order-email-user' => 'Order email to User',
            'order-email-white-label' => 'Order email to White Label Admin',
            'course-completion' => 'Course completion to User',
        );
        if (is_siteadmin()) {
            $email_template['cumulative-order-white-label'] = 'Cumulative Order Email';
            $email_template['order-email-admin'] = 'Forward Order Email to Admin';
            $email_template['register-email-user'] = 'User Registration Email'; // Uday
            $email_template['user-account-creation'] = 'New Account Created Notification'; // Uday
            $email_template['notify-user-course-assign'] = get_string('courseassignmentnotification','local_learnpeek_email_template'); // Uday
            $email_template['user-account-creation-whitelabeladmin'] = 'Created user Email to White Label Admin'; // Uday
            $email_template['bulk-user-account-creation-whitelabeladmin'] = 'Bulk users created Email to White Label Admin'; // Uday
            $email_template['course-enrol-whitelabeladmin'] = 'Course Enrol Email to White Label Admin'; // Uday
            $email_template['course-completion-whitelabeladmin'] = 'Course completion to White Label'; // Uday
        }

        $companyid = iomad::get_my_companyid(context_system::instance());

		$companylists = array();
		$getcompanies = $DB->get_records_sql('SELECT * FROM {company} WHERE suspended = 0 ORDER BY name ASC');

		// If site admin, show all companies + Default Template
		if (is_siteadmin()) {
		    $companylists[0] = get_string('defaulttemplate','local_learnpeek_email_template');
		    foreach ($getcompanies as $company) {
		        $companylists[$company->id] = $company->name;
		    }
		} else {
		    // For normal users, only show their own company
		    foreach ($getcompanies as $company) {
		        if ($company->id == $companyid) {
		            $companylists[$company->id] = $company->name;
		            break;
		        }
		    }
		}

        $mform = $this->_form;
        $infohtml = "<ul>".get_string('dynamicvaribaleforemailtemplate','local_learnpeek_email_template')."<b>:". get_string('orderemailuser','local_learnpeek_email_template')."</b>
                        <li>".get_string('purchaseusername','local_learnpeek_email_template')." {{order_user_name}}</li>
                        <li>".get_string('orderdetails','local_learnpeek_email_template')." {{order_items_table}}</li>
                    </ul>

                    <ul>".get_string('dynamicvaribaleforemailtemplate','local_learnpeek_email_template')."<b>:". get_string('orderemailadmin','local_learnpeek_email_template')."</b>
                        <li>".get_string('whitelabelname','local_learnpeek_email_template')." {{white_label_admin_name}}</li>
                        <li>".get_string('purchaseusername','local_learnpeek_email_template')." {{order_user_name}}</li>
                        <li>".get_string('orderdetails','local_learnpeek_email_template')." {{order_items_table}}</li>
                    </ul>


                    <ul>".get_string('dynamicvaribaleforemailtemplate','local_learnpeek_email_template')." <b>:". get_string('orderemailadmin','local_learnpeek_email_template')."</b>
                        <li>".get_string('coursecompleteduser','local_learnpeek_email_template')." {{user_display_name}}</li>
                        <li>".get_string('coursename','local_learnpeek_email_template')." {{course_grade}}</li>
                    </ul>

                    <ul>".get_string('dynamicvaribaleforemailtemplate','local_learnpeek_email_template')."<b>:".get_string('cumulativeorder','local_learnpeek_email_template')."</b>
                        <li>".get_string('allorders','local_learnpeek_email_template')." {{all_order_details}}</li>
                        <li>".get_string('whitelabelname','local_learnpeek_email_template')." {{white_label_admin_name}}</li>
                    </ul>

                    <ul>".get_string('dynamicvaribaleforemailtemplate','local_learnpeek_email_template')."<b>: ".get_string('forwardorder','local_learnpeek_email_template')."</b>
                        <li>".get_string('superadminname','local_learnpeek_email_template')."   {{super_admin_name}}</li>
                        <li>".get_string('purchaseusername','local_learnpeek_email_template')."   {{order_user_name}}</li>
                        <li".get_string('orderdetails','local_learnpeek_email_template')."  {{order_items_table}}</li>
                        <li>".get_string('subjectfield','local_learnpeek_email_template')." :".get_string('companyname','local_learnpeek_email_template'). "{{compnay_name}}</li>

                    </ul>
                    <ul>".get_string('dynamicvaribaleforemailtemplate','local_learnpeek_email_template')." <b>:".get_string('courseassignment','local_learnpeek_email_template')." </b>
                        <li>".get_string('userfullname','local_learnpeek_email_template')." {{user_name}}</li>
                        <li>".get_string('assignedby','local_learnpeek_email_template')." {{assigned_by}}</li>
                        <li>".get_string('courselist','local_learnpeek_email_template')." {{assign_table}}</li>
                        <li>".get_string('assignedusername','local_learnpeek_email_template')." {{regards_by}}</li>
                        <li>".get_string('companylogo','local_learnpeek_email_template')." {{company_logo}}</li>
                        <li>".get_string('subjectfield','local_learnpeek_email_template')." :".get_string('coursename','local_learnpeek_email_template')."{{course_name}}</li>
                    </ul>
                    <ul>".get_string('dynamicvaribaleforemailtemplate','local_learnpeek_email_template')."<b>:".get_string('newaccount','local_learnpeek_email_template')."</b>
                        <li>".get_string('userfullname','local_learnpeek_email_template')." {{fullname}}</li>
                        <li>".get_string('companyname','local_learnpeek_email_template')." {{company_name}}</li>
                        <li>".get_string('username','local_learnpeek_email_template')."Username {{username}}</li>
                        <li>".get_string('password','local_learnpeek_email_template')." {{password}}</li>
                        <li>".get_string('loginurl','local_learnpeek_email_template')." {{login_url}}</li>
                        <li>".get_string('subjectfield','local_learnpeek_email_template')." :".get_string('coursename','local_learnpeek_email_template')."{{company_name}}</li>
                    </ul>
                    <ul>".get_string('dynamicvaribaleforemailtemplate','local_learnpeek_email_template')."<b>:".get_string('userregistration','local_learnpeek_email_template')." </b>
                        <li>".get_string('userfullname','local_learnpeek_email_template')." {{user_name}}</li>
                        <li>".get_string('companyname','local_learnpeek_email_template')."{{company_name}}</li>
                        <li>".get_string('loginurl','local_learnpeek_email_template')." {{login_url}}</li>
                        <li>".get_string('subjectfield','local_learnpeek_email_template')." : ".get_string('coursename','local_learnpeek_email_template')." {{company_name}}</li>
                    </ul>
                    <ul>".get_string('dynamicvaribaleforemailtemplate','local_learnpeek_email_template')."<b>:".get_string('courseenrolmentwhitelable','local_learnpeek_email_template')." </b>
                        <li>".get_string('userrole','local_learnpeek_email_template')." {{user_role}}</li>
                        <li>".get_string('assignbyuserdetails_enrol','local_learnpeek_email_template')." {{assigned_by_user_details}}</li>
                        <li>".get_string('assigncoursedetails_enrol','local_learnpeek_email_template')." {{course_assign_to_trainee}}</li>
                    </ul>
                    <ul>".get_string('dynamicvaribaleforemailtemplate','local_learnpeek_email_template')."<b>:".get_string('createdusertowhitelable','local_learnpeek_email_template')." </b>
                        <li>".get_string('assignbyuserdetails_created','local_learnpeek_email_template')." {{assigned_by_user_details}}</li>
                        <li>".get_string('newtraineedetails','local_learnpeek_email_template')." {{new_trainee_register_details}}</li>
                    </ul>
                    <ul>".get_string('dynamicvaribaleforemailtemplate','local_learnpeek_email_template')."<b>:".get_string('createdbulkusertowhitelable','local_learnpeek_email_template')." </b>
                        <li>".get_string('assignedbyfullname','local_learnpeek_email_template')." {{uploadedby_name}}</li>
                        <li>".get_string('assignedtofullname','local_learnpeek_email_template')." {{assignedto_name}}</li>
                        <li>".get_string('assignbyuserdetails','local_learnpeek_email_template')." {{uploaded_by_user_details}}</li>
                        <li>".get_string('assigntouserdetails','local_learnpeek_email_template')." {{assigned_to_user_details}}</li>
                        <li>".get_string('newtraineedetails','local_learnpeek_email_template')." {{new_trainee_register_details}}</li>
                    </ul>

                    <ul>".get_string('globalvariable','local_learnpeek_email_template')."
                        <li>".get_string('companylogo','local_learnpeek_email_template')." {{company_logo}}</li>
                    </ul>";
        $mform->addElement('select', 'company_id', get_string('selectcompany','local_learnpeek_email_template'),$companylists);
        if ($id) {
			$mform->freeze('company_id'); // makes it read-only (not editable)
		}

        $mform->addElement('select', 'email_template', get_string('selectemailtemplate','local_learnpeek_email_template'), $email_template);
		// Add rule for required (only if not in edit mode)
		if (!$id) {
		    $mform->addRule('email_template', 'Select Email Template', 'required', null, 'client', false, false);
		}
		// Make it disabled in edit mode
		if ($id) {
			$mform->freeze('email_template'); // makes it read-only (not editable)
		}

        $mform->addElement('text', 'destination_email', get_string('destinationemail','local_learnpeek_email_template'), 'maxlength="254" size="50"');
        $mform->addRule('destination_email', 'Invalid email pattern', 'email', null, 'client', false, false);
        $mform->setType('destination_email', PARAM_MULTILANG); 
        
        $mform->addElement('text', 'email_subject', get_string('subject','local_learnpeek_email_template'));
        $mform->setType('email_subject', PARAM_MULTILANG); 
        $mform->addRule('email_subject', 'Add Email Subject', 'required', null, 'client', false, false);


        // $mform->addElement('editor', 'email_message', "Email Message", 'wrap="virtual" rows="20" cols="50"');
        // $mform->addRule('email_message', 'Add Email Message', 'required', null, 'client', false, false);


        $mform->addElement('editor', 'email_message', get_string('emailtemplate','local_learnpeek_email_template'),null, $this->editoroptions);
        $mform->setType('email_message', PARAM_RAW);
        $mform->addRule('email_message', 'Add Email Message','required', null, 'client');

        $imageoverviewfilesoptions = array(
                                        'maxfiles' => 1,
                                        'maxbytes' => 3 * 1024 * 1024, // 3MB in bytes
                                        'subdirs' => 0,
                                        'accepted_types' => array('.jpg', '.png'),
                                        'context' => $systemcontext
                                    );
        // $mform->addElement('filemanager', 'email_logo', 'Email Footer Logo', null, $imageoverviewfilesoptions);

        $mform->addElement('static', 'username1', ' ', $infohtml);

        $mform->addElement('hidden', 'id', $id);

        $this->add_action_buttons($cancel = true, $submitlabel = get_string('save','local_learnpeek_email_template'));
    }
    function validation($data, $files) {
    	global $CFG, $DB;
        $errors = array();
        return $errors;
    }
}