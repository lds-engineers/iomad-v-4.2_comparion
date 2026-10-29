<?php
require_once($CFG->dirroot . '/lib/formslib.php');
global $DB, $USER;

class migrate_lp_courses extends moodleform {
    public function definition() {
        global $CFG, $DB;
        $mform = $this->_form;
        $categories = $DB->get_records_sql("SELECT id, name FROM {course_categories} ORDER BY name ASC");
        $categoryoptions = array('' => 'Select Category');
        foreach ($categories as $cat) {
            $categoryoptions[$cat->id] = $cat->name;
        }

        $mform->addElement('select', 'categoryid', get_string('selectcategory','local_learnpeek_lp_course_migration'), $categoryoptions);
        $mform->addRule('categoryid', get_string('selectcategory','local_learnpeek_lp_course_migration'), 'required', null, 'client',false, false);

        $learnpress_courses = [
            'SST Courses' => [
                ['id' => '', 'name' =>  get_string('selectlearnPresscourse','local_learnpeek_lp_course_migration')],
                ['id' => 15068, 'name' => '2-Hour Drug and Alcohol Awareness SST-302 - #15068'],
                ['id' => 17342, 'name' => '2-Hour Drug and Alcohol Awareness SST-302 (Spanish) - #17342'],
                ['id' => 15968, 'name' => '2-Hour Drug and Alcohol Awareness (Russian) SST-302 - #15968'],
                ['id' => 17418, 'name' => '8-Hour Fall Prevention SST-307 (Spanish) - #17418'],
                ['id' => 16072, 'name' => '8-Hour Fall Prevention (Russian) SST-307 - #16072'],
                ['id' => 36714, 'name' => 'CE-102 Sight Distance Guidelines: 2 PDH - #36714'],
                ['id' => 16634, 'name' => '4-Hour Fall Prevention SST-305 - #16634'],
            ],
            'HAZWOPER' => [
                ['id' => 19448, 'name' => '40-Hour HAZWOPER Training - #19448'],
                ['id' => 19501, 'name' => '24-Hour HAZWOPER Training - #19501'],
            ],
            'COMPLIANCE CATEGORY' => [
                ['id' => 17930, 'name' => 'Bloodborne Pathogens - #17930'],
                ['id' => 17788, 'name' => 'Excavation Safety in Construction (Spanish) - #17788'],
                ['id' => 17746, 'name' => 'Excavation Safety in Construction - #17746'],
                ['id' => 17575, 'name' => 'OSHA Respiratory Protection - #17575'],
                ['id' => 17133, 'name' => '4 Hour OSHA Scaffold Training - #17133'],
                ['id' => 16956, 'name' => '4 Hour Fall Protection for Construction - #16956'],
                ['id' => 16747, 'name' => 'Confined Space Entry for Construction - #16747'],
                ['id' => 17959, 'name' => 'Radiofrequency (RF) Safety Training - #17959'],
                ['id' => 17695, 'name' => 'MEWP: Aerial and Scissor Lifts (Spanish) - #17695'],
                ['id' => 17643, 'name' => 'MEWP: Aerial and Scissor Lifts - #17643'],
            ],
            'PDF Courses' => [
                ['id' => 37408, 'name' => 'CE-105 Reducing Wrong-Way Crashes on Freeways: 6PDH - #37408'],
                ['id' => 36714, 'name' => 'CE-102 Sight Distance Guidelines: 2 PDH - #36714'],
                ['id' => 14950, 'name' => 'LS-006 Data Acquisition Using LiDAR 1 PDH - #14950'],
                ['id' => 14903, 'name' => 'OG-013 ExxonMobil Baton Rouge Refinery Disaster: 3 PDH - #14903'],
                ['id' => 14826, 'name' => 'CE-090 Foundations in Coastal Regions: 3 PDH - #14826'],
                // ['id' => , 'name' => ' - #'],
                
            ]
        ];

        $courseoptions = [];
        foreach ($learnpress_courses as $category_name => $courses) {
            $group = [];
            foreach ($courses as $course) {
                $group[$course['id']] = $course['name'];
            }
            $courseoptions[$category_name] = $group;
        }

        $mform->addElement('selectgroups', 'lp_courseid', get_string('selectlearnPresscourse','local_learnpeek_lp_course_migration'), $courseoptions);
        $mform->addRule('lp_courseid', 'Please select a LearnPress course', 'required', null, 'client');

        // $mform->addElement('text', 'courseid', 'Learnpress Course ID', array('size' => '5'));
        // $mform->setType('courseid', PARAM_INT);
        // $mform->addRule('courseid', 'Add Learnpress Course Id', 'required', null, 'client');
        // $mform->addRule('courseid', 'Must be an integer', 'numeric', null, 'client');

        $this->add_action_buttons(true, get_string('migrate','local_learnpeek_lp_course_migration'));
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (empty($data['categoryid'])) {
            $errors['categoryid'] = 'Please select a course category.';
        }
        // if (empty($data['courseid']) || !is_numeric($data['courseid'])) {
        //     $errors['courseid'] = 'Please enter a valid integer Course ID.';
        // }

        return $errors;
    }
}
