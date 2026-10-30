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
 * courserating external API
 *
 * @package    mod_courserating
 * @category   external
 * @copyright  2017 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.3
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");

use mod_courserating\external\courserating_summary_exporter;
use mod_courserating\external\courserating_completedtmp_exporter;
use mod_courserating\external\courserating_item_exporter;
use mod_courserating\external\courserating_valuetmp_exporter;
use mod_courserating\external\courserating_value_exporter;
use mod_courserating\external\courserating_completed_exporter;

/**
 * courserating external functions
 *
 * @package    mod_courserating
 * @category   external
 * @copyright  2017 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.3
 */
class mod_courserating_external extends external_api {

    /**
     * Describes the parameters for get_courseratings_by_courses.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function get_courseratings_by_courses_parameters() {
        return new external_function_parameters (
            array(
                'courseids' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'Course id'), 'Array of course ids', VALUE_DEFAULT, array()
                ),
            )
        );
    }

    /**
     * Returns a list of courseratings in a provided list of courses.
     * If no list is provided all courseratings that the user can view will be returned.
     *
     * @param array $courseids course ids
     * @return array of warnings and courseratings
     * @since Moodle 3.3
     */
    public static function get_courseratings_by_courses($courseids = array()) {
        global $PAGE;

        $warnings = array();
        $returnedcourseratings = array();

        $params = array(
            'courseids' => $courseids,
        );
        $params = self::validate_parameters(self::get_courseratings_by_courses_parameters(), $params);

        $mycourses = array();
        if (empty($params['courseids'])) {
            $mycourses = enrol_get_my_courses();
            $params['courseids'] = array_keys($mycourses);
        }

        // Ensure there are courseids to loop through.
        if (!empty($params['courseids'])) {

            list($courses, $warnings) = external_util::validate_courses($params['courseids'], $mycourses);
            $output = $PAGE->get_renderer('core');

            // Get the courseratings in this course, this function checks users visibility permissions.
            // We can avoid then additional validate_context calls.
            $courseratings = get_all_instances_in_courses("courserating", $courses);
            foreach ($courseratings as $courserating) {

                $context = context_module::instance($courserating->coursemodule);

                // Remove fields that are not from the courserating (added by get_all_instances_in_courses).
                unset($courserating->coursemodule, $courserating->context, $courserating->visible, $courserating->section, $courserating->groupmode,
                        $courserating->groupingid);

                // Check permissions.
                if (!has_capability('mod/courserating:edititems', $context)) {
                    // Don't return the optional properties.
                    $properties = courserating_summary_exporter::properties_definition();
                    foreach ($properties as $property => $config) {
                        if (!empty($config['optional'])) {
                            unset($courserating->{$property});
                        }
                    }
                }
                $exporter = new courserating_summary_exporter($courserating, array('context' => $context));
                $returnedcourseratings[] = $exporter->export($output);
            }
        }

        $result = array(
            'courseratings' => $returnedcourseratings,
            'warnings' => $warnings
        );
        return $result;
    }

    /**
     * Describes the get_courseratings_by_courses return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function get_courseratings_by_courses_returns() {
        return new external_single_structure(
            array(
                'courseratings' => new external_multiple_structure(
                    courserating_summary_exporter::get_read_structure()
                ),
                'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * Utility function for validating a courserating.
     *
     * @param int $courseratingid courserating instance id
     * @param int $courseid courseid course where user completes the courserating (for site courseratings only)
     * @return array containing the courserating, courserating course, context, course module and the course where is being completed.
     * @throws moodle_exception
     * @since  Moodle 3.3
     */
    protected static function validate_courserating($courseratingid, $courseid = 0) {
        global $DB, $USER;

        // Request and permission validation.
        $courserating = $DB->get_record('courserating', array('id' => $courseratingid), '*', MUST_EXIST);
        list($courseratingcourse, $cm) = get_course_and_cm_from_instance($courserating, 'courserating');

        $context = context_module::instance($cm->id);
        self::validate_context($context);

        // Set default completion course.
        $completioncourse = (object) array('id' => 0);
        if ($courseratingcourse->id == SITEID && $courseid) {
            $completioncourse = get_course($courseid);
            self::validate_context(context_course::instance($courseid));

            $courseratingcompletion = new mod_courserating_completion($courserating, $cm, $courseid);
            if (!$courseratingcompletion->check_course_is_mapped()) {
                throw new moodle_exception('cannotaccess', 'mod_courserating');
            }
        }

        return array($courserating, $courseratingcourse, $cm, $context, $completioncourse);
    }

    /**
     * Utility function for validating access to courserating.
     *
     * @param  stdClass   $courserating courserating object
     * @param  stdClass   $course   course where user completes the courserating (for site courseratings only)
     * @param  stdClass   $cm       course module
     * @param  stdClass   $context  context object
     * @throws moodle_exception
     * @return mod_courserating_completion courserating completion instance
     * @since  Moodle 3.3
     */
    protected static function validate_courserating_access($courserating, $course, $cm, $context, $checksubmit = false) {
        $courseratingcompletion = new mod_courserating_completion($courserating, $cm, $course->id);

        if (!$courseratingcompletion->can_complete()) {
            throw new required_capability_exception($context, 'mod/courserating:complete', 'nopermission', '');
        }

        if (!$courseratingcompletion->is_open()) {
            throw new moodle_exception('courserating_is_not_open', 'courserating');
        }

        if ($courseratingcompletion->is_empty()) {
            throw new moodle_exception('no_items_available_yet', 'courserating');
        }

        if ($checksubmit && !$courseratingcompletion->can_submit()) {
            throw new moodle_exception('this_courserating_is_already_submitted', 'courserating');
        }
        return $courseratingcompletion;
    }

    /**
     * Describes the parameters for get_courserating_access_information.
     *
     * @return external_external_function_parameters
     * @since Moodle 3.3
     */
    public static function get_courserating_access_information_parameters() {
        return new external_function_parameters (
            array(
                'courseratingid' => new external_value(PARAM_INT, 'courserating instance id.'),
                'courseid' => new external_value(PARAM_INT, 'Course where user completes the courserating (for site courseratings only).',
                    VALUE_DEFAULT, 0),
            )
        );
    }

    /**
     * Return access information for a given courserating.
     *
     * @param int $courseratingid courserating instance id
     * @param int $courseid course where user completes the courserating (for site courseratings only)
     * @return array of warnings and the access information
     * @since Moodle 3.3
     * @throws  moodle_exception
     */
    public static function get_courserating_access_information($courseratingid, $courseid = 0) {
        global $PAGE;

        $params = array(
            'courseratingid' => $courseratingid,
            'courseid' => $courseid,
        );
        $params = self::validate_parameters(self::get_courserating_access_information_parameters(), $params);

        list($courserating, $course, $cm, $context, $completioncourse) = self::validate_courserating($params['courseratingid'],
            $params['courseid']);
        $courseratingcompletion = new mod_courserating_completion($courserating, $cm, $completioncourse->id);

        $result = array();
        // Capabilities first.
        $result['canviewanalysis'] = $courseratingcompletion->can_view_analysis();
        $result['cancomplete'] = $courseratingcompletion->can_complete();
        $result['cansubmit'] = $courseratingcompletion->can_submit();
        $result['candeletesubmissions'] = has_capability('mod/courserating:deletesubmissions', $context);
        $result['canviewreports'] = has_capability('mod/courserating:viewreports', $context);
        $result['canedititems'] = has_capability('mod/courserating:edititems', $context);

        // Status information.
        $result['isempty'] = $courseratingcompletion->is_empty();
        $result['isopen'] = $courseratingcompletion->is_open();
        $anycourse = ($course->id == SITEID);
        $result['isalreadysubmitted'] = $courseratingcompletion->is_already_submitted($anycourse);
        $result['isanonymous'] = $courseratingcompletion->is_anonymous();

        $result['warnings'] = [];
        return $result;
    }

    /**
     * Describes the get_courserating_access_information return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function get_courserating_access_information_returns() {
        return new external_single_structure(
            array(
                'canviewanalysis' => new external_value(PARAM_BOOL, 'Whether the user can view the analysis or not.'),
                'cancomplete' => new external_value(PARAM_BOOL, 'Whether the user can complete the courserating or not.'),
                'cansubmit' => new external_value(PARAM_BOOL, 'Whether the user can submit the courserating or not.'),
                'candeletesubmissions' => new external_value(PARAM_BOOL, 'Whether the user can delete submissions or not.'),
                'canviewreports' => new external_value(PARAM_BOOL, 'Whether the user can view the courserating reports or not.'),
                'canedititems' => new external_value(PARAM_BOOL, 'Whether the user can edit courserating items or not.'),
                'isempty' => new external_value(PARAM_BOOL, 'Whether the courserating has questions or not.'),
                'isopen' => new external_value(PARAM_BOOL, 'Whether the courserating has active access time restrictions or not.'),
                'isalreadysubmitted' => new external_value(PARAM_BOOL, 'Whether the courserating is already submitted or not.'),
                'isanonymous' => new external_value(PARAM_BOOL, 'Whether the courserating is anonymous or not.'),
                'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * Describes the parameters for view_courserating.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function view_courserating_parameters() {
        return new external_function_parameters (
            array(
                'courseratingid' => new external_value(PARAM_INT, 'courserating instance id'),
                'moduleviewed' => new external_value(PARAM_BOOL, 'If we need to mark the module as viewed for completion',
                    VALUE_DEFAULT, false),
                'courseid' => new external_value(PARAM_INT, 'Course where user completes the courserating (for site courseratings only).',
                    VALUE_DEFAULT, 0),
            )
        );
    }

    /**
     * Trigger the course module viewed event and update the module completion status.
     *
     * @param int $courseratingid courserating instance id
     * @param bool $moduleviewed If we need to mark the module as viewed for completion
     * @param int $courseid course where user completes the courserating (for site courseratings only)
     * @return array of warnings and status result
     * @since Moodle 3.3
     * @throws moodle_exception
     */
    public static function view_courserating($courseratingid, $moduleviewed = false, $courseid = 0) {

        $params = array('courseratingid' => $courseratingid, 'moduleviewed' => $moduleviewed, 'courseid' => $courseid);
        $params = self::validate_parameters(self::view_courserating_parameters(), $params);
        $warnings = array();

        list($courserating, $course, $cm, $context, $completioncourse) = self::validate_courserating($params['courseratingid'],
            $params['courseid']);
        $courseratingcompletion = new mod_courserating_completion($courserating, $cm, $completioncourse->id);

        // Trigger module viewed event.
        $courseratingcompletion->trigger_module_viewed();
        if ($params['moduleviewed']) {
            if (!$courseratingcompletion->is_open()) {
                throw new moodle_exception('courserating_is_not_open', 'courserating');
            }
            // Mark activity viewed for completion-tracking.
            $courseratingcompletion->set_module_viewed();
        }

        $result = array(
            'status' => true,
            'warnings' => $warnings,
        );
        return $result;
    }

    /**
     * Describes the view_courserating return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function view_courserating_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_BOOL, 'status: true if success'),
                'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * Describes the parameters for get_current_completed_tmp.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function get_current_completed_tmp_parameters() {
        return new external_function_parameters (
            array(
                'courseratingid' => new external_value(PARAM_INT, 'courserating instance id'),
                'courseid' => new external_value(PARAM_INT, 'Course where user completes the courserating (for site courseratings only).',
                    VALUE_DEFAULT, 0),
            )
        );
    }

    /**
     * Returns the temporary completion record for the current user.
     *
     * @param int $courseratingid courserating instance id
     * @param int $courseid course where user completes the courserating (for site courseratings only)
     * @return array of warnings and status result
     * @since Moodle 3.3
     * @throws moodle_exception
     */
    public static function get_current_completed_tmp($courseratingid, $courseid = 0) {
        global $PAGE;

        $params = array('courseratingid' => $courseratingid, 'courseid' => $courseid);
        $params = self::validate_parameters(self::get_current_completed_tmp_parameters(), $params);
        $warnings = array();

        list($courserating, $course, $cm, $context, $completioncourse) = self::validate_courserating($params['courseratingid'],
            $params['courseid']);
        $courseratingcompletion = new mod_courserating_completion($courserating, $cm, $completioncourse->id);

        if ($completed = $courseratingcompletion->get_current_completed_tmp()) {
            $exporter = new courserating_completedtmp_exporter($completed);
            return array(
                'courserating' => $exporter->export($PAGE->get_renderer('core')),
                'warnings' => $warnings,
            );
        }
        throw new moodle_exception('not_started', 'courserating');
    }

    /**
     * Describes the get_current_completed_tmp return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function get_current_completed_tmp_returns() {
        return new external_single_structure(
            array(
                'courserating' => courserating_completedtmp_exporter::get_read_structure(),
                'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * Describes the parameters for get_items.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function get_items_parameters() {
        return new external_function_parameters (
            array(
                'courseratingid' => new external_value(PARAM_INT, 'courserating instance id'),
                'courseid' => new external_value(PARAM_INT, 'Course where user completes the courserating (for site courseratings only).',
                    VALUE_DEFAULT, 0),
            )
        );
    }

    /**
     * Returns the items (questions) in the given courserating.
     *
     * @param int $courseratingid courserating instance id
     * @param int $courseid course where user completes the courserating (for site courseratings only)
     * @return array of warnings and courseratings
     * @since Moodle 3.3
     */
    public static function get_items($courseratingid, $courseid = 0) {
        global $PAGE;

        $params = array('courseratingid' => $courseratingid, 'courseid' => $courseid);
        $params = self::validate_parameters(self::get_items_parameters(), $params);
        $warnings = array();
        $returneditems = array();

        list($courserating, $course, $cm, $context, $completioncourse) = self::validate_courserating($params['courseratingid'],
            $params['courseid']);

        $userhasaccess = true;
        try {
            // Check the user has access to the courserating.
            self::validate_courserating_access($courserating, $completioncourse, $cm, $context, true);
        } catch (moodle_exception $e) {
            $userhasaccess = false;
            $warnings[] = [
                'item' => $courserating->id,
                'warningcode' => clean_param($e->errorcode, PARAM_ALPHANUM),
                'message' => $e->getMessage(),
            ];
        }

        // For consistency with the web behaviour, the items should be returned only when the user can edit or view reports (to
        // include non-editing teachers too).
        $capabilities = [
            'mod/courserating:edititems',
            'mod/courserating:viewreports',
        ];
        if ($userhasaccess || has_any_capability($capabilities, $context)) {
            // Remove previous warnings because, although the user might not have access, they have the proper capability.
            $warnings = [];
            $courseratingstructure = new mod_courserating_structure($courserating, $cm, $completioncourse->id);
            if ($items = $courseratingstructure->get_items()) {
                foreach ($items as $item) {
                    $itemnumber = empty($item->itemnr) ? null : $item->itemnr;
                    unset($item->itemnr);   // Added by the function, not part of the record.
                    $exporter = new courserating_item_exporter($item, array('context' => $context, 'itemnumber' => $itemnumber));
                    $returneditems[] = $exporter->export($PAGE->get_renderer('core'));
                }
            }
        } else if ($userhasaccess) {
            $warnings[] = [
                'item' => $courserating->id,
                'warningcode' => 'nopermission',
                'message' => 'nopermission',
            ];
        }

        $result = array(
            'items' => $returneditems,
            'warnings' => $warnings
        );
        return $result;
    }

    /**
     * Describes the get_items return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function get_items_returns() {
        return new external_single_structure(
            array(
                'items' => new external_multiple_structure(
                    courserating_item_exporter::get_read_structure()
                ),
                'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * Describes the parameters for launch_courserating.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function launch_courserating_parameters() {
        return new external_function_parameters (
            array(
                'courseratingid' => new external_value(PARAM_INT, 'courserating instance id'),
                'courseid' => new external_value(PARAM_INT, 'Course where user completes the courserating (for site courseratings only).',
                    VALUE_DEFAULT, 0),
            )
        );
    }

    /**
     * Starts or continues a courserating submission
     *
     * @param array $courseratingid courserating instance id
     * @param int $courseid course where user completes a courserating (for site courseratings only).
     * @return array of warnings and launch information
     * @since Moodle 3.3
     */
    public static function launch_courserating($courseratingid, $courseid = 0) {
        global $PAGE;

        $params = array('courseratingid' => $courseratingid, 'courseid' => $courseid);
        $params = self::validate_parameters(self::launch_courserating_parameters(), $params);
        $warnings = array();

        list($courserating, $course, $cm, $context, $completioncourse) = self::validate_courserating($params['courseratingid'],
            $params['courseid']);
        // Check we can do a new submission (or continue an existing).
        $courseratingcompletion = self::validate_courserating_access($courserating, $completioncourse, $cm, $context, true);

        $gopage = $courseratingcompletion->get_resume_page();
        if ($gopage === null) {
            $gopage = -1; // Last page.
        }

        $result = array(
            'gopage' => $gopage,
            'warnings' => $warnings
        );
        return $result;
    }

    /**
     * Describes the launch_courserating return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function launch_courserating_returns() {
        return new external_single_structure(
            array(
                'gopage' => new external_value(PARAM_INT, 'The next page to go (-1 if we were already in the last page). 0 for first page.'),
                'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * Describes the parameters for get_page_items.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function get_page_items_parameters() {
        return new external_function_parameters (
            array(
                'courseratingid' => new external_value(PARAM_INT, 'courserating instance id'),
                'page' => new external_value(PARAM_INT, 'The page to get starting by 0'),
                'courseid' => new external_value(PARAM_INT, 'Course where user completes the courserating (for site courseratings only).',
                    VALUE_DEFAULT, 0),
            )
        );
    }

    /**
     * Get a single courserating page items.
     *
     * @param int $courseratingid courserating instance id
     * @param int $page the page to get starting by 0
     * @param int $courseid course where user completes the courserating (for site courseratings only)
     * @return array of warnings and launch information
     * @since Moodle 3.3
     */
    public static function get_page_items($courseratingid, $page, $courseid = 0) {
        global $PAGE;

        $params = array('courseratingid' => $courseratingid, 'page' => $page, 'courseid' => $courseid);
        $params = self::validate_parameters(self::get_page_items_parameters(), $params);
        $warnings = array();
        $returneditems = array();
        $hasprevpage = false;
        $hasnextpage = false;

        list($courserating, $course, $cm, $context, $completioncourse) = self::validate_courserating($params['courseratingid'],
            $params['courseid']);

        $userhasaccess = true;
        $courseratingcompletion = null;
        try {
            // Check the user has access to the courserating.
            $courseratingcompletion = self::validate_courserating_access($courserating, $completioncourse, $cm, $context, true);
        } catch (moodle_exception $e) {
            $userhasaccess = false;
            $warnings[] = [
                'item' => $courserating->id,
                'warningcode' => str_replace('_', '', $e->errorcode),
                'message' => $e->getMessage(),
            ];
        }

        // For consistency with the web behaviour, the items should be returned only when the user can edit or view reports (to
        // include non-editing teachers too).
        $capabilities = [
            'mod/courserating:edititems',
            'mod/courserating:viewreports',
        ];
        if ($userhasaccess || has_any_capability($capabilities, $context)) {
            // Remove previous warnings because, although the user might not have access, they have the proper capability.
            $warnings = [];

            if ($courseratingcompletion == null) {
                $courseratingcompletion = new mod_courserating_completion($courserating, $cm, $completioncourse->id);
            }

            $page = $params['page'];
            $pages = $courseratingcompletion->get_pages();
            $pageitems = $pages[$page];
            $hasnextpage = $page < count($pages) - 1; // Until we complete this page we can not trust get_next_page().
            $hasprevpage = $page && ($courseratingcompletion->get_previous_page($page, false) !== null);

            foreach ($pageitems as $item) {
                $itemnumber = empty($item->itemnr) ? null : $item->itemnr;
                unset($item->itemnr);   // Added by the function, not part of the record.
                $exporter = new courserating_item_exporter($item, array('context' => $context, 'itemnumber' => $itemnumber));
                $returneditems[] = $exporter->export($PAGE->get_renderer('core'));
            }
        } else if ($userhasaccess) {
            $warnings[] = [
                'item' => $courserating->id,
                'warningcode' => 'nopermission',
                'message' => get_string('nopermission', 'mod_courserating'),
            ];
        }

        $result = array(
            'items' => $returneditems,
            'hasprevpage' => $hasprevpage,
            'hasnextpage' => $hasnextpage,
            'warnings' => $warnings
        );
        return $result;
    }

    /**
     * Describes the get_page_items return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function get_page_items_returns() {
        return new external_single_structure(
            array(
                'items' => new external_multiple_structure(
                    courserating_item_exporter::get_read_structure()
                ),
                'hasprevpage' => new external_value(PARAM_BOOL, 'Whether is a previous page.'),
                'hasnextpage' => new external_value(PARAM_BOOL, 'Whether there are more pages.'),
                'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * Describes the parameters for process_page.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function process_page_parameters() {
        return new external_function_parameters (
            array(
                'courseratingid' => new external_value(PARAM_INT, 'courserating instance id.'),
                'page' => new external_value(PARAM_INT, 'The page being processed.'),
                'responses' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'name' => new external_value(PARAM_NOTAGS, 'The response name (usually type[index]_id).'),
                            'value' => new external_value(PARAM_RAW, 'The response value.'),
                        )
                    ), 'The data to be processed.', VALUE_DEFAULT, array()
                ),
                'goprevious' => new external_value(PARAM_BOOL, 'Whether we want to jump to previous page.', VALUE_DEFAULT, false),
                'courseid' => new external_value(PARAM_INT, 'Course where user completes the courserating (for site courseratings only).',
                    VALUE_DEFAULT, 0),
            )
        );
    }

    /**
     * Process a jump between pages.
     *
     * @param array $courseratingid courserating instance id
     * @param array $page the page being processed
     * @param array $responses the responses to be processed
     * @param bool $goprevious whether we want to jump to previous page
     * @param int $courseid course where user completes the courserating (for site courseratings only)
     * @return array of warnings and launch information
     * @since Moodle 3.3
     */
    public static function process_page($courseratingid, $page, $responses = [], $goprevious = false, $courseid = 0) {
        global $USER, $SESSION;

        $params = array('courseratingid' => $courseratingid, 'page' => $page, 'responses' => $responses, 'goprevious' => $goprevious,
            'courseid' => $courseid);
        $params = self::validate_parameters(self::process_page_parameters(), $params);
        $warnings = array();
        $siteaftersubmit = $completionpagecontents = '';

        list($courserating, $course, $cm, $context, $completioncourse) = self::validate_courserating($params['courseratingid'],
            $params['courseid']);
        // Check we can do a new submission (or continue an existing).
        $courseratingcompletion = self::validate_courserating_access($courserating, $completioncourse, $cm, $context, true);

        // Create the $_POST object required by the courserating question engine.
        $_POST = array();
        foreach ($responses as $response) {
            // First check if we are handling array parameters.
            if (preg_match('/(.+)\[(.+)\]$/', $response['name'], $matches)) {
                $_POST[$matches[1]][$matches[2]] = $response['value'];
            } else {
                $_POST[$response['name']] = $response['value'];
            }
        }
        // Force fields.
        $_POST['id'] = $cm->id;
        $_POST['courseid'] = $courseid;
        $_POST['gopage'] = $params['page'];
        $_POST['_qf__mod_courserating_complete_form'] = 1;

        // Determine where to go, backwards or forward.
        if (!$params['goprevious']) {
            $_POST['gonextpage'] = 1;   // Even if we are saving values we need this set.
            if ($courseratingcompletion->get_next_page($params['page'], false) === null) {
                $_POST['savevalues'] = 1;   // If there is no next page, it means we are finishing the courserating.
            }
        }

        // Ignore sesskey (deep in some APIs), the request is already validated.
        $USER->ignoresesskey = true;
        courserating_init_courserating_session();
        $SESSION->courserating->is_started = true;

        $courseratingcompletion->process_page($params['page'], $params['goprevious']);
        $completed = $courseratingcompletion->just_completed();
        if ($completed) {
            $jumpto = 0;
            if ($courserating->page_after_submit) {
                $completionpagecontents = $courseratingcompletion->page_after_submit();
            }

            if ($courserating->site_after_submit) {
                $siteaftersubmit = courserating_encode_target_url($courserating->site_after_submit);
            }
        } else {
            $jumpto = $courseratingcompletion->get_jumpto();
        }

        $result = array(
            'jumpto' => $jumpto,
            'completed' => $completed,
            'completionpagecontents' => $completionpagecontents,
            'siteaftersubmit' => $siteaftersubmit,
            'warnings' => $warnings
        );
        return $result;
    }

    /**
     * Describes the process_page return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function process_page_returns() {
        return new external_single_structure(
            array(
                'jumpto' => new external_value(PARAM_INT, 'The page to jump to.'),
                'completed' => new external_value(PARAM_BOOL, 'If the user completed the courserating.'),
                'completionpagecontents' => new external_value(PARAM_RAW, 'The completion page contents.'),
                'siteaftersubmit' => new external_value(PARAM_RAW, 'The link (could be relative) to show after submit.'),
                'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * Describes the parameters for get_analysis.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function get_analysis_parameters() {
        return new external_function_parameters (
            array(
                'courseratingid' => new external_value(PARAM_INT, 'courserating instance id'),
                'groupid' => new external_value(PARAM_INT, 'Group id, 0 means that the function will determine the user group',
                                                VALUE_DEFAULT, 0),
                'courseid' => new external_value(PARAM_INT, 'Course where user completes the courserating (for site courseratings only).',
                    VALUE_DEFAULT, 0),
            )
        );
    }

    /**
     * Retrieves the courserating analysis.
     *
     * @param array $courseratingid courserating instance id
     * @param int $groupid group id, 0 means that the function will determine the user group
     * @param int $courseid course where user completes the courserating (for site courseratings only)
     * @return array of warnings and launch information
     * @since Moodle 3.3
     */
    public static function get_analysis($courseratingid, $groupid = 0, $courseid = 0) {
        global $PAGE;

        $params = array('courseratingid' => $courseratingid, 'groupid' => $groupid, 'courseid' => $courseid);
        $params = self::validate_parameters(self::get_analysis_parameters(), $params);
        $warnings = $itemsdata = array();

        list($courserating, $course, $cm, $context, $completioncourse) = self::validate_courserating($params['courseratingid'],
            $params['courseid']);

        // Check permissions.
        $courseratingstructure = new mod_courserating_structure($courserating, $cm, $completioncourse->id);
        if (!$courseratingstructure->can_view_analysis()) {
            throw new required_capability_exception($context, 'mod/courserating:viewanalysepage', 'nopermission', '');
        }

        if (!empty($params['groupid'])) {
            $groupid = $params['groupid'];
            // Determine is the group is visible to user.
            if (!groups_group_visible($groupid, $course, $cm)) {
                throw new moodle_exception('notingroup');
            }
        } else {
            // Check to see if groups are being used here.
            if ($groupmode = groups_get_activity_groupmode($cm)) {
                $groupid = groups_get_activity_group($cm);
                // Determine is the group is visible to user (this is particullary for the group 0 -> all groups).
                if (!groups_group_visible($groupid, $course, $cm)) {
                    throw new moodle_exception('notingroup');
                }
            } else {
                $groupid = 0;
            }
        }

        // Summary data.
        $summary = new mod_courserating\output\summary($courseratingstructure, $groupid);
        $summarydata = $summary->export_for_template($PAGE->get_renderer('core'));

        $checkanonymously = true;
        if ($groupid > 0 AND $courserating->anonymous == courserating_ANONYMOUS_YES) {
            $completedcount = $courseratingstructure->count_completed_responses($groupid);
            if ($completedcount < courserating_MIN_ANONYMOUS_COUNT_IN_GROUP) {
                $checkanonymously = false;
            }
        }

        if ($checkanonymously) {
            // Get the items of the courserating.
            $items = $courseratingstructure->get_items(true);
            foreach ($items as $item) {
                $itemobj = courserating_get_item_class($item->typ);
                $itemnumber = empty($item->itemnr) ? null : $item->itemnr;
                unset($item->itemnr);   // Added by the function, not part of the record.
                $exporter = new courserating_item_exporter($item, array('context' => $context, 'itemnumber' => $itemnumber));

                $itemsdata[] = array(
                    'item' => $exporter->export($PAGE->get_renderer('core')),
                    'data' => $itemobj->get_analysed_for_external($item, $groupid),
                );
            }
        } else {
            $warnings[] = array(
                'item' => 'courserating',
                'itemid' => $courserating->id,
                'warningcode' => 'insufficientresponsesforthisgroup',
                'message' => s(get_string('insufficient_responses_for_this_group', 'courserating'))
            );
        }

        $result = array(
            'completedcount' => $summarydata->completedcount,
            'itemscount' => $summarydata->itemscount,
            'itemsdata' => $itemsdata,
            'warnings' => $warnings
        );
        return $result;
    }

    /**
     * Describes the get_analysis return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function get_analysis_returns() {
        return new external_single_structure(
            array(
            'completedcount' => new external_value(PARAM_INT, 'Number of completed submissions.'),
            'itemscount' => new external_value(PARAM_INT, 'Number of items (questions).'),
            'itemsdata' => new external_multiple_structure(
                new external_single_structure(
                    array(
                        'item' => courserating_item_exporter::get_read_structure(),
                        'data' => new external_multiple_structure(
                            new external_value(PARAM_RAW, 'The analysis data (can be json encoded)')
                        ),
                    )
                )
            ),
            'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * Describes the parameters for get_unfinished_responses.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function get_unfinished_responses_parameters() {
        return new external_function_parameters (
            array(
                'courseratingid' => new external_value(PARAM_INT, 'courserating instance id.'),
                'courseid' => new external_value(PARAM_INT, 'Course where user completes the courserating (for site courseratings only).',
                    VALUE_DEFAULT, 0),
            )
        );
    }

    /**
     * Retrieves responses from the current unfinished attempt.
     *
     * @param array $courseratingid courserating instance id
     * @param int $courseid course where user completes the courserating (for site courseratings only)
     * @return array of warnings and launch information
     * @since Moodle 3.3
     */
    public static function get_unfinished_responses($courseratingid, $courseid = 0) {
        global $PAGE;

        $params = array('courseratingid' => $courseratingid, 'courseid' => $courseid);
        $params = self::validate_parameters(self::get_unfinished_responses_parameters(), $params);
        $warnings = $itemsdata = array();

        list($courserating, $course, $cm, $context, $completioncourse) = self::validate_courserating($params['courseratingid'],
            $params['courseid']);
        $courseratingcompletion = new mod_courserating_completion($courserating, $cm, $completioncourse->id);

        $responses = array();
        $unfinished = $courseratingcompletion->get_unfinished_responses();
        foreach ($unfinished as $u) {
            $exporter = new courserating_valuetmp_exporter($u);
            $responses[] = $exporter->export($PAGE->get_renderer('core'));
        }

        $result = array(
            'responses' => $responses,
            'warnings' => $warnings
        );
        return $result;
    }

    /**
     * Describes the get_unfinished_responses return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function get_unfinished_responses_returns() {
        return new external_single_structure(
            array(
            'responses' => new external_multiple_structure(
                courserating_valuetmp_exporter::get_read_structure()
            ),
            'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * Describes the parameters for get_finished_responses.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function get_finished_responses_parameters() {
        return new external_function_parameters (
            array(
                'courseratingid' => new external_value(PARAM_INT, 'courserating instance id.'),
                'courseid' => new external_value(PARAM_INT, 'Course where user completes the courserating (for site courseratings only).',
                    VALUE_DEFAULT, 0),
            )
        );
    }

    /**
     * Retrieves responses from the last finished attempt.
     *
     * @param array $courseratingid courserating instance id
     * @param int $courseid course where user completes the courserating (for site courseratings only)
     * @return array of warnings and the responses
     * @since Moodle 3.3
     */
    public static function get_finished_responses($courseratingid, $courseid = 0) {
        global $PAGE;

        $params = array('courseratingid' => $courseratingid, 'courseid' => $courseid);
        $params = self::validate_parameters(self::get_finished_responses_parameters(), $params);
        $warnings = $itemsdata = array();

        list($courserating, $course, $cm, $context, $completioncourse) = self::validate_courserating($params['courseratingid'],
            $params['courseid']);
        $courseratingcompletion = new mod_courserating_completion($courserating, $cm, $completioncourse->id);

        $responses = array();
        // Load and get the responses from the last completed courserating.
        $courseratingcompletion->find_last_completed();
        $unfinished = $courseratingcompletion->get_finished_responses();
        foreach ($unfinished as $u) {
            $exporter = new courserating_value_exporter($u);
            $responses[] = $exporter->export($PAGE->get_renderer('core'));
        }

        $result = array(
            'responses' => $responses,
            'warnings' => $warnings
        );
        return $result;
    }

    /**
     * Describes the get_finished_responses return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function get_finished_responses_returns() {
        return new external_single_structure(
            array(
            'responses' => new external_multiple_structure(
                courserating_value_exporter::get_read_structure()
            ),
            'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * Describes the parameters for get_non_respondents.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function get_non_respondents_parameters() {
        return new external_function_parameters (
            array(
                'courseratingid' => new external_value(PARAM_INT, 'courserating instance id'),
                'groupid' => new external_value(PARAM_INT, 'Group id, 0 means that the function will determine the user group.',
                                                VALUE_DEFAULT, 0),
                'sort' => new external_value(PARAM_ALPHA, 'Sort param, must be firstname, lastname or lastaccess (default).',
                                                VALUE_DEFAULT, 'lastaccess'),
                'page' => new external_value(PARAM_INT, 'The page of records to return.', VALUE_DEFAULT, 0),
                'perpage' => new external_value(PARAM_INT, 'The number of records to return per page.', VALUE_DEFAULT, 0),
                'courseid' => new external_value(PARAM_INT, 'Course where user completes the courserating (for site courseratings only).',
                    VALUE_DEFAULT, 0),
            )
        );
    }

    /**
     * Retrieves a list of students who didn't submit the courserating.
     *
     * @param int $courseratingid courserating instance id
     * @param int $groupid Group id, 0 means that the function will determine the user group'
     * @param str $sort sort param, must be firstname, lastname or lastaccess (default)
     * @param int $page the page of records to return
     * @param int $perpage the number of records to return per page
     * @param int $courseid course where user completes the courserating (for site courseratings only)
     * @return array of warnings and users ids
     * @since Moodle 3.3
     */
    public static function get_non_respondents($courseratingid, $groupid = 0, $sort = 'lastaccess', $page = 0, $perpage = 0,
            $courseid = 0) {

        global $CFG;
        require_once($CFG->dirroot . '/mod/courserating/lib.php');

        $params = array('courseratingid' => $courseratingid, 'groupid' => $groupid, 'sort' => $sort, 'page' => $page,
            'perpage' => $perpage, 'courseid' => $courseid);
        $params = self::validate_parameters(self::get_non_respondents_parameters(), $params);
        $warnings = $nonrespondents = array();

        list($courserating, $course, $cm, $context, $completioncourse) = self::validate_courserating($params['courseratingid'],
            $params['courseid']);
        $courseratingcompletion = new mod_courserating_completion($courserating, $cm, $completioncourse->id);
        $completioncourseid = $courseratingcompletion->get_courseid();

        if ($courserating->anonymous != courserating_ANONYMOUS_NO || $courserating->course == SITEID) {
            throw new moodle_exception('anonymous', 'courserating');
        }

        // Check permissions.
        require_capability('mod/courserating:viewreports', $context);

        if (!empty($params['groupid'])) {
            $groupid = $params['groupid'];
            // Determine is the group is visible to user.
            if (!groups_group_visible($groupid, $course, $cm)) {
                throw new moodle_exception('notingroup');
            }
        } else {
            // Check to see if groups are being used here.
            if ($groupmode = groups_get_activity_groupmode($cm)) {
                $groupid = groups_get_activity_group($cm);
                // Determine is the group is visible to user (this is particullary for the group 0 -> all groups).
                if (!groups_group_visible($groupid, $course, $cm)) {
                    throw new moodle_exception('notingroup');
                }
            } else {
                $groupid = 0;
            }
        }

        if ($params['sort'] !== 'firstname' && $params['sort'] !== 'lastname' && $params['sort'] !== 'lastaccess') {
            throw new invalid_parameter_exception('Invalid sort param, must be firstname, lastname or lastaccess.');
        }

        // Check if we are page filtering.
        if ($params['perpage'] == 0) {
            $page = $params['page'];
            $perpage = courserating_DEFAULT_PAGE_COUNT;
        } else {
            $perpage = $params['perpage'];
            $page = $perpage * $params['page'];
        }
        $users = courserating_get_incomplete_users($cm, $groupid, $params['sort'], $page, $perpage, true);
        foreach ($users as $user) {
            $nonrespondents[] = [
                'courseid' => $completioncourseid,
                'userid'   => $user->id,
                'fullname' => fullname($user),
                'started'  => $user->courseratingstarted
            ];
        }

        $result = array(
            'users' => $nonrespondents,
            'total' => courserating_count_incomplete_users($cm, $groupid),
            'warnings' => $warnings
        );
        return $result;
    }

    /**
     * Describes the get_non_respondents return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function get_non_respondents_returns() {
        return new external_single_structure(
            array(
                'users' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'courseid' => new external_value(PARAM_INT, 'Course id'),
                            'userid' => new external_value(PARAM_INT, 'The user id'),
                            'fullname' => new external_value(PARAM_TEXT, 'User full name'),
                            'started' => new external_value(PARAM_BOOL, 'If the user has started the attempt'),
                        )
                    )
                ),
                'total' => new external_value(PARAM_INT, 'Total number of non respondents'),
                'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * Describes the parameters for get_responses_analysis.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function get_responses_analysis_parameters() {
        return new external_function_parameters (
            array(
                'courseratingid' => new external_value(PARAM_INT, 'courserating instance id'),
                'groupid' => new external_value(PARAM_INT, 'Group id, 0 means that the function will determine the user group',
                                                VALUE_DEFAULT, 0),
                'page' => new external_value(PARAM_INT, 'The page of records to return.', VALUE_DEFAULT, 0),
                'perpage' => new external_value(PARAM_INT, 'The number of records to return per page', VALUE_DEFAULT, 0),
                'courseid' => new external_value(PARAM_INT, 'Course where user completes the courserating (for site courseratings only).',
                    VALUE_DEFAULT, 0),
            )
        );
    }

    /**
     * Return the courserating user responses.
     *
     * @param int $courseratingid courserating instance id
     * @param int $groupid Group id, 0 means that the function will determine the user group
     * @param int $page the page of records to return
     * @param int $perpage the number of records to return per page
     * @param int $courseid course where user completes the courserating (for site courseratings only)
     * @return array of warnings and users attemps and responses
     * @throws moodle_exception
     * @since Moodle 3.3
     */
    public static function get_responses_analysis($courseratingid, $groupid = 0, $page = 0, $perpage = 0, $courseid = 0) {

        $params = array('courseratingid' => $courseratingid, 'groupid' => $groupid, 'page' => $page, 'perpage' => $perpage,
            'courseid' => $courseid);
        $params = self::validate_parameters(self::get_responses_analysis_parameters(), $params);
        $warnings = $itemsdata = array();

        list($courserating, $course, $cm, $context, $completioncourse) = self::validate_courserating($params['courseratingid'],
            $params['courseid']);

        // Check permissions.
        require_capability('mod/courserating:viewreports', $context);

        if (!empty($params['groupid'])) {
            $groupid = $params['groupid'];
            // Determine is the group is visible to user.
            if (!groups_group_visible($groupid, $course, $cm)) {
                throw new moodle_exception('notingroup');
            }
        } else {
            // Check to see if groups are being used here.
            if ($groupmode = groups_get_activity_groupmode($cm)) {
                $groupid = groups_get_activity_group($cm);
                // Determine is the group is visible to user (this is particullary for the group 0 -> all groups).
                if (!groups_group_visible($groupid, $course, $cm)) {
                    throw new moodle_exception('notingroup');
                }
            } else {
                $groupid = 0;
            }
        }

        $courseratingstructure = new mod_courserating_structure($courserating, $cm, $completioncourse->id);
        $responsestable = new mod_courserating_responses_table($courseratingstructure, $groupid);
        // Ensure responses number is correct prior returning them.
        $courseratingstructure->shuffle_anonym_responses();
        $anonresponsestable = new mod_courserating_responses_anon_table($courseratingstructure, $groupid);

        $result = array(
            'attempts'          => $responsestable->export_external_structure($params['page'], $params['perpage']),
            'totalattempts'     => $responsestable->get_total_responses_count(),
            'anonattempts'      => $anonresponsestable->export_external_structure($params['page'], $params['perpage']),
            'totalanonattempts' => $anonresponsestable->get_total_responses_count(),
            'warnings'       => $warnings
        );
        return $result;
    }

    /**
     * Describes the get_responses_analysis return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function get_responses_analysis_returns() {
        $responsestructure = new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'Response id'),
                    'name' => new external_value(PARAM_RAW, 'Response name'),
                    'printval' => new external_value(PARAM_RAW, 'Response ready for output'),
                    'rawval' => new external_value(PARAM_RAW, 'Response raw value'),
                )
            )
        );

        return new external_single_structure(
            array(
                'attempts' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'Completed id'),
                            'courseid' => new external_value(PARAM_INT, 'Course id'),
                            'userid' => new external_value(PARAM_INT, 'User who responded'),
                            'timemodified' => new external_value(PARAM_INT, 'Time modified for the response'),
                            'fullname' => new external_value(PARAM_TEXT, 'User full name'),
                            'responses' => $responsestructure
                        )
                    )
                ),
                'totalattempts' => new external_value(PARAM_INT, 'Total responses count.'),
                'anonattempts' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'Completed id'),
                            'courseid' => new external_value(PARAM_INT, 'Course id'),
                            'number' => new external_value(PARAM_INT, 'Response number'),
                            'responses' => $responsestructure
                        )
                    )
                ),
                'totalanonattempts' => new external_value(PARAM_INT, 'Total anonymous responses count.'),
                'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * Describes the parameters for get_last_completed.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function get_last_completed_parameters() {
        return new external_function_parameters (
            array(
                'courseratingid' => new external_value(PARAM_INT, 'courserating instance id'),
                'courseid' => new external_value(PARAM_INT, 'Course where user completes the courserating (for site courseratings only).',
                    VALUE_DEFAULT, 0),
            )
        );
    }

    /**
     * Retrieves the last completion record for the current user.
     *
     * @param int $courseratingid courserating instance id
     * @return array of warnings and the last completed record
     * @since Moodle 3.3
     * @throws moodle_exception
     */
    public static function get_last_completed($courseratingid, $courseid = 0) {
        global $PAGE;

        $params = array('courseratingid' => $courseratingid, 'courseid' => $courseid);
        $params = self::validate_parameters(self::get_last_completed_parameters(), $params);
        $warnings = array();

        list($courserating, $course, $cm, $context, $completioncourse) = self::validate_courserating($params['courseratingid'],
            $params['courseid']);
        $courseratingcompletion = new mod_courserating_completion($courserating, $cm, $completioncourse->id);

        if ($courseratingcompletion->is_anonymous()) {
             throw new moodle_exception('anonymous', 'courserating');
        }
        if ($completed = $courseratingcompletion->find_last_completed()) {
            $exporter = new courserating_completed_exporter($completed);
            return array(
                'completed' => $exporter->export($PAGE->get_renderer('core')),
                'warnings' => $warnings,
            );
        }
        throw new moodle_exception('not_completed_yet', 'courserating');
    }

    /**
     * Describes the get_last_completed return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function get_last_completed_returns() {
        return new external_single_structure(
            array(
                'completed' => courserating_completed_exporter::get_read_structure(),
                'warnings' => new external_warnings(),
            )
        );
    }
}
