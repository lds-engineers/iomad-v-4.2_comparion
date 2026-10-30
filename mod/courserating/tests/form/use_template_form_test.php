<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace mod_courserating\form;

/**
 * Tests the confirm use template form
 *
 * @author Peter Dias
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_courserating
 */
final class use_template_form_test extends \advanced_testcase {
    /**
     * Run the basic setup for the test
     */
    public function setup_instance(): array {
        global $DB, $PAGE;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $courserating = $this->getDataGenerator()->create_module('courserating', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('courserating', $courserating->id, $course->id);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');

        $courseratinggenerator = $this->getDataGenerator()->get_plugin_generator('mod_courserating');

        // Create at least one page.
        $courseratinggenerator->create_item_multichoice($courserating, ['values' => "y\nn"]);

        courserating_save_as_template($courserating, 'my template', 0);
        $courseratinggenerator->create_item_multichoice($courserating, ['values' => "0\n1"]);
        courserating_save_as_template($courserating, 'mytemplate2', 1);
        $records = array_keys($DB->get_records('courserating_template', null, 'id ASC'));
        $courseratingparams = [
            'id' => $cm->id,
            'privatetemplate' => $records[0],
            'publictemplate' => $records[1],
        ];
        $PAGE->set_cm($cm);
        $PAGE->set_activity_record($courserating);

        return [$user, $courseratingparams];
    }

    /**
     * Test the form
     *
     * @param string $loginas Which user to log in as
     * @param bool $private Whether we are creating a private template
     * @param bool $expected Whether or not the form should be validated
     * @dataProvider usetemplate_form_provider
     */
    public function test_usetemplate_form(string $loginas, bool $private, bool $expected) {
        [$user, $courserating] = $this->setup_instance();
        switch($loginas) {
            case 'admin':
                $this->setAdminUser();
                break;
            case 'student':
                $this->setUser($user);
                break;
        }

        $data = [
            'id' => $courserating['id'],
            'templateid' => $private ? $courserating['privatetemplate'] : $courserating['publictemplate'],
        ];

        $submitdata = use_template_form::mock_ajax_submit($data);
        if (!$expected) {
            $this->expectException(\moodle_exception::class);
        }
        $form = new use_template_form(null, null, 'post', '', null, true,
            $submitdata, true);
        $form->set_data_for_dynamic_submission();
        if ($expected) {
            $this->assertTrue($form->is_validated());
        }
        $form->process_dynamic_submission();
    }

    /**
     * Provider for the test_usetemplate_form test
     *
     * @return array
     */
    public static function usetemplate_form_provider(): array {
        return [
            'Test submission with a private template as an admin' => [
                'admin', true, true
            ],
            'Test submission with a public template as an admin' => [
                'admin', false, true
            ],
            'Test submission with a public template as a student' => [
                'student', false, false
            ],
            'Test submission with a private template as a student' => [
                'student', true, false
            ],
        ];
    }
}
