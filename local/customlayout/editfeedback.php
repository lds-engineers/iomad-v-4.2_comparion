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

//

// Correct data fetch
$data = $DB->get_record('tool_courserating_rating', ['id' => $id], '*', MUST_EXIST);
$data->page = $page;
$data->perpage = $perpage;
$data->search = $search;


//set API data
$user   = $DB->get_record('user', ['id' => $data->userid]);

$lpcourse_fid = $DB->get_record("customfield_field",array("shortname"=>"learnpress_courseid"));

$lpcoursedata = $DB->get_record_sql("SELECT value FROM {customfield_data} WHERE instanceid = ? AND fieldid =?",[$data->courseid, $lpcourse_fid->id]);

$companydata = $DB->get_record('company_users', ['userid' => $user->id]);

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
    if(empty(trim($lpcoursedata->value))){
        $fromform->timemodified = time();
        $DB->update_record('tool_courserating_rating', $fromform);
        redirect($redirecturl, get_string('changessaved', 'local_customlayout'));
    }

    $wordpresscourseid = $lpcoursedata->value;
    $companyid = $companydata->companyid;
    $ssourl = get_config(null, "main_site_url{$companyid}");

    if($ssourl){
        $ssovalidateurl = rtrim($ssourl, '/') . '/wp-json/learnpeek/v1/receive-review';
        $ssovalidateurl;
        $payload = [
            'rating'            => (int)$fromform->rating,
            'review'            => $fromform->review,
            'status'            => $fromform->status,
            'useremail'         => $user->email,             
            'firstname'         => $user->firstname,             
            'lastname'          => $user->lastname,             
            'lp_courseid'       => $wordpresscourseid,
            'lms_courseid'      => (int)$data->courseid,
            'timecreated'       => $fromform->timecreated,  
            'moodlewsrestformat'=> 'json'
        ];

        $curl = new curl();
        $response = $curl->post($ssovalidateurl, $payload);
        $result = json_decode($response);

        // echo "<pre>";
        // print_r($payload);
        // print_r($response);
        // print_r($result);
        // echo "</pre>";
        // die();
        $fromform->timemodified = time();
        $DB->update_record('tool_courserating_rating', $fromform);
        redirect($redirecturl, get_string('changessaved', 'local_customlayout'));

        // if (!empty($result) && isset($result->status) && $result->status == 1) {
        //     $fromform->timemodified = time();
        //     $DB->update_record('tool_courserating_rating', $fromform);
        //     redirect($redirecturl, get_string('changessaved', 'local_customlayout'));
        // }else if($result->data->status = 404){
        //     $fromform->timemodified = time();
        //     $DB->update_record('tool_courserating_rating', $fromform);
        //     redirect($redirecturl, get_string('changessaved', 'local_customlayout'));
        // } else {
        //     // Failure - show Moodle notification and go to dashboard
        //     $errormessage = !empty($result->message) ? $result->message : 'Something is went wrong!. Please try again later.';
        //     redirect( new moodle_url($redirecturl), $errormessage, 3 , \core\output\notification::NOTIFY_ERROR);
        // }
    }
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