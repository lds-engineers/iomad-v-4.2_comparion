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

/**
 * mod_courserating data generator.
 *
 * @package    mod_courserating
 * @category   test
 * @copyright  2013 Ankit Agarwal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * mod_courserating data generator class.
 *
 * @package    mod_courserating
 * @category   test
 * @copyright  2013 Ankit Agarwal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_courserating_generator extends testing_module_generator {

    public function create_instance($record = null, array $options = null) {
        global $CFG;
        require_once($CFG->dirroot.'/mod/courserating/lib.php');
        $record = (object)(array)$record;

        if (!isset($record->anonymous)) {
            $record->anonymous = courserating_ANONYMOUS_YES;
        }
        if (!isset($record->email_notification)) {
            $record->email_notification = 0;
        }
        if (!isset($record->multiple_submit)) {
            $record->multiple_submit = 0;
        }
        if (!isset($record->autonumbering)) {
            $record->autonumbering = 0;
        }
        if (!isset($record->site_after_submit)) {
            $record->site_after_submit = '';
        }
        if (!isset($record->page_after_submit)) {
            $record->page_after_submit = 'This is page after submit';
        }
        if (!isset($record->page_after_submitformat)) {
            $record->page_after_submitformat = FORMAT_MOODLE;
        }
        if (!isset($record->publish_stats)) {
            $record->publish_stats = 0;
        }
        if (!isset($record->timeopen)) {
            $record->timeopen = 0;
        }
        if (!isset($record->timeclose)) {
            $record->timeclose = 0;
        }
        if (!isset($record->timemodified)) {
            $record->timemodified = time();
        }
        if (!isset($record->completionsubmit)) {
            $record->completionsubmit = 0;
        }

        // Hack to bypass draft processing of courserating_add_instance.
        $record->page_after_submit_editor['itemid'] = false;

        return parent::create_instance($record, (array)$options);
    }

    /**
     * Create question.
     *
     * @param array $data Question data
     * @return mixed Question instance
     */
    public function create_question(array $data) {
        global $DB;

        $questiontype = $data['questiontype'] ?? 'textfield';
        $cm = get_coursemodule_from_id('courserating', $data['cmid']);
        $courserating = $DB->get_record('courserating', ['id' => $cm->instance]);

        unset($data['questiontype']);
        unset($data['cmid']);

        if (isset($data['values'])) {
            $data['values'] = $this->format_item_values($questiontype, $data['values']);
        }

        return call_user_func([$this, "create_item_{$questiontype}"], $courserating, $data);
    }

    /**
     * Create response.
     *
     * @param array $data Response data.
     * @return stdClass courserating_completed response instance.
     */
    public function create_response(array $data): stdClass {
        global $DB;

        $userid = $data['userid'];
        $responsenumber = null;
        $cm = get_coursemodule_from_id('courserating', $data['cmid']);
        $courserating = $DB->get_record('courserating', ['id' => $cm->instance]);
        $answers = [];

        if (isset($data['responsenumber']) && trim($data['responsenumber']) !== '') {
            $responsenumber = $data['responsenumber'];
        }

        if (isset($data['anonymous']) && trim($data['anonymous']) !== '') {
            $anonymous = filter_var(trim($data['anonymous']), FILTER_VALIDATE_BOOLEAN);
            $courserating->anonymous = $anonymous ? courserating_ANONYMOUS_YES : courserating_ANONYMOUS_NO;
        }

        unset($data['cmid']);
        unset($data['userid']);
        unset($data['anonymous']);
        unset($data['responsenumber']);

        foreach ($data as $question => $response) {
            $item = $DB->get_record('courserating_item', ['name' => trim($question)], '*', MUST_EXIST);

            $answers["{$item->typ}_{$item->id}"] = $this->get_item_response_value($item, $response);
        }

        $courseratingcompletion = new mod_courserating_completion(
            $courserating,
            $cm,
            $cm->course,
            false,
            null,
            $courserating->anonymous === courserating_ANONYMOUS_YES ? null : $userid,
            $userid
        );

        if (!$courseratingcompletion->can_complete()) {
            throw new coding_exception("User {$userid} cannot complete this courserating activity.");
        }

        if (!$courseratingcompletion->is_open()) {
            throw new coding_exception("This activity is not open.");
        }

        $courseratingcompletion->set_module_viewed();
        $courseratingcompletion->save_response_tmp((object) $answers);
        $courseratingcompletion->save_response();
        $completed = $courseratingcompletion->get_completed();

        if (!is_null($responsenumber)) {
            $DB->update_record('courserating_completed', [
                'id' => $completed->id,
                'random_response' => $responsenumber,
            ]);
        }

        return $completed;
    }

    /**
     * Create info question item.
     *
     * @param object $courserating courserating record
     * @param array $record (optional) to override default values
     * @return int
     */
    public function create_item_info($courserating, $record = array()) {
        global $DB, $CFG;

        require_once($CFG->dirroot.'/mod/courserating/lib.php');

        $itemobj = courserating_get_item_class('info');
        $position = $DB->count_records('courserating_item', array('courserating' => $courserating->id)) + 1;

        $record = (array)$record + array(
            'id' => 0,
            'courserating' => $courserating->id,
            'template' => 0,
            'name' => 'courserating question item ' . $position,
            'label' => 'courserating label ' . $position,
            'presentation' => $itemobj::MODE_COURSE,
            'typ' => 'info',
            'hasvalue' => 0,
            'position' => $position,
            'required' => 0,
            'dependitem' => 0,
            'dependvalue' => '',
            'options' => '',
        );

        $itemobj->set_data((object) $record);
        return $itemobj->save_item();
    }

    /**
     * Create label question item.
     *
     * @param object $courserating courserating record
     * @param array $record (optional) to override default values
     * @return int
     */
    public function create_item_label($courserating, $record = array()) {
        global $DB, $CFG;

        require_once($CFG->dirroot.'/mod/courserating/lib.php');

        $itemobj = courserating_get_item_class('label');
        $position = $DB->count_records('courserating_item', array('courserating' => $courserating->id)) + 1;

        $record = (array)$record + array(
            'id' => 0,
            'courserating' => $courserating->id,
            'template' => 0,
            'name' => 'label',
            'label' => '',
            'presentation' => '',
            'typ' => 'label',
            'hasvalue' => 0,
            'position' => $position,
            'required' => 0,
            'dependitem' => 0,
            'dependvalue' => '',
            'options' => '',
        );

        if (!isset($record['presentation_editor'])) {
            $record['presentation_editor'] = array(
                'text' => "The label $position text goes here",
                'format' => FORMAT_HTML,
                'itemid' => 0
            );
        }

        $itemobj->set_data((object) $record);
        return $itemobj->save_item();
    }

    /**
     * Create multichoice question item.
     *
     * @param object $courserating courserating record
     * @param array $record (optional) to override default values
     * @return int
     */
    public function create_item_multichoice($courserating, $record = array()) {
        global $DB, $CFG;

        require_once($CFG->dirroot.'/mod/courserating/lib.php');

        $itemobj = courserating_get_item_class('multichoice');
        $position = $DB->count_records('courserating_item', array('courserating' => $courserating->id)) + 1;

        $record = (array)$record + array(
            'id' => 0,
            'courserating' => $courserating->id,
            'template' => 0,
            'name' => 'courserating question item ' . $position,
            'label' => 'courserating label ' . $position,
            'presentation' => '',
            'typ' => 'multichoice',
            'hasvalue' => 0,
            'position' => $position,
            'required' => 0,
            'dependitem' => 0,
            'dependvalue' => '',
            'options' => '',
            'subtype' => 'r',
            'horizontal' => 0,
            'hidenoselect' => 1,
            'ignoreempty' => 0,
            'values' => "a\nb\nc\nd\ne"
        );

        $presentation = str_replace("\n", courserating_MULTICHOICE_LINE_SEP, trim($record['values']));

        if ($record['horizontal'] == 1 AND $record['subtype'] != 'd') {
            $presentation .= courserating_MULTICHOICE_ADJUST_SEP.'1';
        }
        $record['presentation'] = $record['subtype'].courserating_MULTICHOICE_TYPE_SEP.$presentation;

        $itemobj->set_data((object) $record);
        return $itemobj->save_item();
    }

    /**
     * Create multichoicerated question item.
     *
     * @param object $courserating courserating record
     * @param array $record (optional) to override default values
     * @return int
     */
    public function create_item_multichoicerated($courserating, $record = array()) {
        global $DB, $CFG;

        require_once($CFG->dirroot.'/mod/courserating/lib.php');

        $itemobj = courserating_get_item_class('multichoicerated');
        $position = $DB->count_records('courserating_item', array('courserating' => $courserating->id)) + 1;

        $record = (array)$record + array(
            'id' => 0,
            'courserating' => $courserating->id,
            'template' => 0,
            'name' => 'courserating question item ' . $position,
            'label' => 'courserating label ' . $position,
            'presentation' => '',
            'typ' => 'multichoicerated',
            'hasvalue' => 0,
            'position' => $position,
            'required' => 0,
            'dependitem' => 0,
            'dependvalue' => '',
            'options' => '',
            'subtype' => 'r',
            'horizontal' => 0,
            'hidenoselect' => 1,
            'ignoreempty' => 0,
            'values' => "0/a\n1/b\n2/c\n3/d\n4/e"
        );

        $itemobj = new courserating_item_multichoicerated();
        $presentation = $itemobj->prepare_presentation_values_save(trim($record['values']),
            courserating_MULTICHOICERATED_VALUE_SEP2, courserating_MULTICHOICERATED_VALUE_SEP);

        if ($record['horizontal'] == 1 AND $record['subtype'] != 'd') {
            $presentation .= courserating_MULTICHOICERATED_ADJUST_SEP.'1';
        }
        $record['presentation'] = $record['subtype'].courserating_MULTICHOICERATED_TYPE_SEP.$presentation;

        $itemobj->set_data((object) $record);
        return $itemobj->save_item();
    }

    /**
     * Create numeric question item.
     *
     * @param object $courserating courserating record
     * @param array $record (optional) to override default values
     * @return int
     */
    public function create_item_numeric($courserating, $record = array()) {
        global $DB, $CFG;

        require_once($CFG->dirroot.'/mod/courserating/lib.php');

        $itemobj = courserating_get_item_class('numeric');
        $position = $DB->count_records('courserating_item', array('courserating' => $courserating->id)) + 1;

        $record = (array)$record + array(
            'id' => 0,
            'courserating' => $courserating->id,
            'template' => 0,
            'name' => 'courserating question item ' . $position,
            'label' => 'courserating label ' . $position,
            'presentation' => '',
            'typ' => 'numeric',
            'hasvalue' => 0,
            'position' => $position,
            'required' => 0,
            'dependitem' => 0,
            'dependvalue' => '',
            'options' => '',
            'rangefrom' => '-',
            'rangeto' => '-',
        );

        if ($record['rangefrom'] === '-' OR $record['rangeto'] === '-') {
            $record['presentation'] = $record['rangefrom'] . '|'. $record['rangeto'];
        } else if ($record['rangefrom'] > $record['rangeto']) {
            $record['presentation'] = $record['rangeto'] . '|'. $record['rangefrom'];
        } else {
            $record['presentation'] = $record['rangefrom'] . '|'. $record['rangeto'];
        }

        $itemobj->set_data((object) $record);
        return $itemobj->save_item();
    }

    /**
     * Create textarea question item.
     *
     * @param object $courserating courserating record
     * @param array $record (optional) to override default values
     * @return int
     */
    public function create_item_textarea($courserating, $record = array()) {
        global $DB, $CFG;

        require_once($CFG->dirroot.'/mod/courserating/lib.php');

        $itemobj = courserating_get_item_class('textarea');
        $position = $DB->count_records('courserating_item', array('courserating' => $courserating->id)) + 1;

        $record = (array)$record + array(
            'id' => 0,
            'courserating' => $courserating->id,
            'template' => 0,
            'name' => 'courserating question item ' . $position,
            'label' => 'courserating label ' . $position,
            'presentation' => '',
            'typ' => 'textarea',
            'hasvalue' => 0,
            'position' => $position,
            'required' => 0,
            'dependitem' => 0,
            'dependvalue' => '',
            'options' => '',
            'itemwidth' => '40',
            'itemheight' => '20',
        );

        $record['presentation'] = $record['itemwidth'] . '|'. $record['itemheight'];

        $itemobj->set_data((object) $record);
        return $itemobj->save_item();
    }

    /**
     * Create textfield question item.
     *
     * @param object $courserating courserating record
     * @param array $record (optional) to override default values
     * @return int
     */
    public function create_item_textfield($courserating, $record = array()) {
        global $DB, $CFG;

        require_once($CFG->dirroot.'/mod/courserating/lib.php');

        $itemobj = courserating_get_item_class('textfield');
        $position = $DB->count_records('courserating_item', array('courserating' => $courserating->id)) + 1;

        $record = (array)$record + array(
            'id' => 0,
            'courserating' => $courserating->id,
            'template' => 0,
            'name' => 'courserating question item ' . $position,
            'label' => 'courserating label ' . $position,
            'presentation' => '',
            'typ' => 'textfield',
            'hasvalue' => 0,
            'position' => $position,
            'required' => 0,
            'dependitem' => 0,
            'dependvalue' => '',
            'options' => '',
            'itemsize' => '20',
            'itemmaxlength' => '30',
        );

        $record['presentation'] = $record['itemsize'] . '|'. $record['itemmaxlength'];

        $itemobj->set_data((object) $record);
        return $itemobj->save_item();
    }

    /**
     * Create pagebreak.
     *
     * @param object $courserating courserating record
     * @return mixed false if there already is a pagebreak on last position or the id of the pagebreak-item
     */
    public function create_item_pagebreak($courserating) {
        global $CFG;
        require_once($CFG->dirroot.'/mod/courserating/lib.php');

        return courserating_create_pagebreak($courserating->id);
    }

    /**
     * Format courserating item values.
     *
     * This method will replace newline characters with the proper line separator for each question type.
     *
     * @param string $questiontype Question types
     * @param string $values Values
     * @return string Formatted values
     */
    protected function format_item_values(string $questiontype, string $values): string {
        global $CFG;

        if (!file_exists($CFG->dirroot.'/mod/courserating/item/'.$questiontype.'/lib.php')) {
            throw new coding_exception("Question type '$questiontype' not found");
        }

        require_once($CFG->dirroot.'/mod/courserating/item/'.$questiontype.'/lib.php');

        $questiontype = strtoupper($questiontype);

        if (defined("courserating_{$questiontype}_LINE_SEP")) {
            return implode(constant("courserating_{$questiontype}_LINE_SEP"), explode('\n', $values));
        }

        return $values;
    }

    /**
     * Given a response to a courserating item, return its corresponding value.
     *
     * @param mixed $record Item record
     * @param string $response Response name
     * @return int|string Response value
     */
    protected function get_item_response_value($record, string $response) {
        if (strpos($record->typ, 'multichoice') === 0) {
            $item = courserating_get_item_class($record->typ);

            return $this->get_choice_item_response_value($item, $record, $response);
        }

        return $response;
    }

    /**
     * Given a response to a courserating choice item, return its corresponding value.
     *
     * @param courserating_item_base $item courserating item
     * @param mixed $record Item record
     * @param string $response Response
     * @param int $offset Choice to start looking from
     * @return int Response choice index
     */
    protected function get_choice_item_response_value(courserating_item_base $item, $record, string $response, int $offset = 1): int {
        $printval = $item->get_printval($record, (object) ['value' => $offset]);

        if (empty($printval)) {
            throw new coding_exception("Value '$offset' not found");
        }

        if ($printval === $response) {
            return $offset;
        }

        return $this->get_choice_item_response_value($item, $record, $response, $offset + 1);
    }
}
