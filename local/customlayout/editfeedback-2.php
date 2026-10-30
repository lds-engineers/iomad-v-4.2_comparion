<?php
require_once('../../config.php');
global $DB, $USER, $CFG;
require_once("$CFG->libdir/formslib.php");
require_login();
$companyid = iomad::get_my_companyid(context_system::instance());

$getcompnayadmin = $DB->get_record_sql("SELECT * FROM {company_users} WHERE companyid = ? AND userid =?",array($companyid,$USER->id));
if (!is_siteadmin() && (!$getcompnayadmin || $getcompnayadmin->managertype != 1)) {
    redirect(new moodle_url($CFG->wwwroot.'/my'),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
     die;
}

$id = required_param('id', PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 10, PARAM_INT);
$search = optional_param('search', '', PARAM_TEXT);
$redirecturl = new moodle_url('/local/customlayout/feedbackstatus.php',compact('page', 'perpage', 'search'));
$actionurl = new moodle_url('/local/customlayout/editfeedback.php',compact('page', 'perpage', 'search'));

// Correct data fetch
$data = $DB->get_record('tool_courserating_rating', ['id' => $id], '*', MUST_EXIST);
$data->page = $page;
$data->perpage = $perpage;
$data->search = $search;
// echo "<pre>";
// print_r($data);
// die('stop');
// Form class
class edit_review_form extends moodleform {
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('select', 'rating', get_string('rating', 'local_customlayout'), [
            1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5'
        ]);
        $mform->setType('rating', PARAM_INT);
        $mform->addRule('rating', null, 'required');

        $mform->addElement('textarea', 'review', get_string('review', 'local_customlayout'), ['rows' => 4, 'cols' => 60]);
        $mform->setType('review', PARAM_TEXT);
        $mform->addRule('review', null, 'required');

        $mform->addElement('date_selector', 'timecreated', get_string('timecreated', 'local_customlayout'));
        $mform->setType('timecreated', PARAM_INT);

        $mform->addElement('select', 'status', get_string('status', 'local_customlayout'), [
            0 => get_string('pending', 'local_customlayout'),
            1 => get_string('approved', 'local_customlayout')
        ]);
        $mform->setType('status', PARAM_INT);
        $mform->addRule('status', null, 'required');

        $mform->addElement('hidden', 'id');
        $mform->addElement('hidden', 'page');
        $mform->addElement('hidden', 'perpage');
        $mform->addElement('hidden', 'search');
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons(true, get_string('savechanges', 'local_customlayout'));
    }

    public function validation($data, $files) {
        $errors = [];

        if (!isset($data['rating']) || $data['rating'] === '') {
            $errors['rating'] = get_string('required', 'local_customlayout');
        } else if ($data['rating'] < 1 || $data['rating'] > 5) {
            $errors['rating'] = get_string('invalidrating', 'local_customlayout');
        }

        if (!isset($data['review']) || trim($data['review']) === '') {
            $errors['review'] = get_string('required', 'local_customlayout');
        } else if (strlen($data['review']) > 1000) {
            $errors['review'] = get_string('reviewtoolong', 'local_customlayout');
        }

        if (!isset($data['status']) || !in_array((int)$data['status'], [0, 1])) {
            $errors['status'] = get_string('invalidstatus', 'local_customlayout');
        }

        return $errors;
    }
}

// Process form
$form = new edit_review_form($actionurl, $data);

if ($form->is_cancelled()) {
    redirect($redirecturl);
} else if ($fromform = $form->get_data()) {
    $fromform->timemodified = time();

    // Debug output (optional)
    // echo "<pre>"; print_r($fromform); die('stop');

    $DB->update_record('tool_courserating_rating', $fromform);
    redirect($redirecturl, get_string('changessaved', 'local_customlayout'));
}

$PAGE->set_url(new moodle_url('/local/customlayout/editreview.php', ['id' => $id]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('editreview', 'local_customlayout'));
$PAGE->set_heading(get_string('editreview', 'local_customlayout'));

echo $OUTPUT->header();
// echo $OUTPUT->heading(get_string('editreview', 'local_customlayout'));
$form->set_data($data);
$form->display();
echo $OUTPUT->footer();