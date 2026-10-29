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
 * courserating external functions and service definitions.
 *
 * @package    mod_courserating
 * @category   external
 * @copyright  2017 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.3
 */

defined('MOODLE_INTERNAL') || die;

$functions = array(

    'mod_courserating_get_courseratings_by_courses' => array(
        'classname'     => 'mod_courserating_external',
        'methodname'    => 'get_courseratings_by_courses',
        'description'   => 'Returns a list of courseratings in a provided list of courses, if no list is provided all courseratings that
                            the user can view will be returned.',
        'type'          => 'read',
        'capabilities'  => 'mod/courserating:view',
        'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),
    'mod_courserating_get_courserating_access_information' => array(
        'classname'     => 'mod_courserating_external',
        'methodname'    => 'get_courserating_access_information',
        'description'   => 'Return access information for a given courserating.',
        'type'          => 'read',
        'capabilities'  => 'mod/courserating:view',
        'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),
    'mod_courserating_view_courserating' => array(
        'classname'     => 'mod_courserating_external',
        'methodname'    => 'view_courserating',
        'description'   => 'Trigger the course module viewed event and update the module completion status.',
        'type'          => 'write',
        'capabilities'  => 'mod/courserating:view',
        'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
    ),
    'mod_courserating_get_current_completed_tmp' => array(
        'classname'     => 'mod_courserating_external',
        'methodname'    => 'get_current_completed_tmp',
        'description'   => 'Returns the temporary completion record for the current user.',
        'type'          => 'read',
        'capabilities'  => 'mod/courserating:view',
        'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
    ),
    'mod_courserating_get_items' => array(
        'classname'     => 'mod_courserating_external',
        'methodname'    => 'get_items',
        'description'   => 'Returns the items (questions) in the given courserating.',
        'type'          => 'read',
        'capabilities'  => 'mod/courserating:view',
        'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
    ),
    'mod_courserating_launch_courserating' => array(
        'classname'     => 'mod_courserating_external',
        'methodname'    => 'launch_courserating',
        'description'   => 'Starts or continues a courserating submission.',
        'type'          => 'write',
        'capabilities'  => 'mod/courserating:complete',
        'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
    ),
    'mod_courserating_get_page_items' => array(
        'classname'     => 'mod_courserating_external',
        'methodname'    => 'get_page_items',
        'description'   => 'Get a single courserating page items.',
        'type'          => 'read',
        'capabilities'  => 'mod/courserating:complete',
        'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
    ),
    'mod_courserating_process_page' => array(
        'classname'     => 'mod_courserating_external',
        'methodname'    => 'process_page',
        'description'   => 'Process a jump between pages.',
        'type'          => 'write',
        'capabilities'  => 'mod/courserating:complete',
        'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
    ),
    'mod_courserating_get_analysis' => array(
        'classname'     => 'mod_courserating_external',
        'methodname'    => 'get_analysis',
        'description'   => 'Retrieves the courserating analysis.',
        'type'          => 'read',
        'capabilities'  => 'mod/courserating:viewanalysepage',
        'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
    ),
    'mod_courserating_get_unfinished_responses' => array(
        'classname'     => 'mod_courserating_external',
        'methodname'    => 'get_unfinished_responses',
        'description'   => 'Retrieves responses from the current unfinished attempt.',
        'type'          => 'read',
        'capabilities'  => 'mod/courserating:view',
        'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
    ),
    'mod_courserating_get_finished_responses' => array(
        'classname'     => 'mod_courserating_external',
        'methodname'    => 'get_finished_responses',
        'description'   => 'Retrieves responses from the last finished attempt.',
        'type'          => 'read',
        'capabilities'  => 'mod/courserating:view',
        'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
    ),
    'mod_courserating_get_non_respondents' => array(
        'classname'     => 'mod_courserating_external',
        'methodname'    => 'get_non_respondents',
        'description'   => 'Retrieves a list of students who didn\'t submit the courserating.',
        'type'          => 'read',
        'capabilities'  => 'mod/courserating:viewreports',
        'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
    ),
    'mod_courserating_get_responses_analysis' => array(
        'classname'     => 'mod_courserating_external',
        'methodname'    => 'get_responses_analysis',
        'description'   => 'Return the courserating user responses analysis.',
        'type'          => 'read',
        'capabilities'  => 'mod/courserating:viewreports',
        'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
    ),
    'mod_courserating_get_last_completed' => array(
        'classname'     => 'mod_courserating_external',
        'methodname'    => 'get_last_completed',
        'description'   => 'Retrieves the last completion record for the current user.',
        'type'          => 'read',
        'capabilities'  => 'mod/courserating:view',
        'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
    ),
);
