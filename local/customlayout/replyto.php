<?php
require_once('../../config.php');
global $DB, $USER, $CFG;
require_once($CFG->libdir . '/formslib.php');
$id = required_param('id', PARAM_INT); // Review ID
$redirecturl = new moodle_url('/local/customlayout/feedbackstatus.php');

$PAGE->set_url(new moodle_url('/local/customlayout/replyto.php', ['id' => $id]));
$PAGE->set_context(context_system::instance());
$title = get_string('replytoreview', 'local_customlayout');

$PAGE->set_title($title);
$PAGE->set_heading($title);

require_login();
$companyid = iomad::get_my_companyid(context_system::instance());
$getcompnayadmin = $DB->get_record_sql("SELECT * FROM {company_users} WHERE companyid = ? AND userid =?",array($companyid,$USER->id));
if (!is_siteadmin() && (!$getcompnayadmin || $getcompnayadmin->managertype != 1)) {
    redirect(new moodle_url($CFG->wwwroot.'/my'),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
     die;
}

$review = $DB->get_record('tool_courserating_rating', ['id' => $id], '*', MUST_EXIST); 
$user = $DB->get_record('user', ['id' => $review->userid], 'firstname, lastname', MUST_EXIST); // Assumes 'userid' exists

$data = new stdClass();
$data->id = $id;
$data->reply = $review->reply ?? '';
$data->username = fullname($user);
$data->reviewmessage = $review->review ?? ''; 

class edit_review_form extends moodleform {
    public function definition() {
        $mform = $this->_form;
        $custom = $this->_customdata;

        // Show username (read-only)
        $mform->addElement('static', 'username', get_string('user', 'local_customlayout'), $custom->username);

        // Show review message (read-only)
        $mform->addElement('static', 'reviewmessage', get_string('review', 'local_customlayout'), s($custom->reviewmessage));

        // Add reply textarea
        $mform->addElement('textarea', 'reply', get_string('reply', 'local_customlayout'), ['rows' => 5, 'cols' => 60]);
        $mform->setType('reply', PARAM_TEXT);
        $mform->addRule('reply', null, 'required');

        // Hidden review ID
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons(true, get_string('submit', 'local_customlayout'));
    }

    public function validation($data, $files) {
        $errors = [];

        if (empty(trim($data['reply']))) {
            $errors['reply'] = get_string('required', 'local_customlayout');
        }

        return $errors;
    }
}

$form = new edit_review_form(null, $data);

if ($form->is_cancelled()) {
    redirect($redirecturl);
} elseif ($fromform = $form->get_data()) {
    $fromform->timemodified = time();


    $DB->update_record('tool_courserating_rating', $fromform); // ✅ Replace with your table name

    redirect($redirecturl, 'Reply submitted successfully');
}


echo $OUTPUT->header();
// echo $OUTPUT->heading(get_string('editreview', 'local_customlayout'));
$form->set_data($data);
$form->display();
echo $OUTPUT->footer();