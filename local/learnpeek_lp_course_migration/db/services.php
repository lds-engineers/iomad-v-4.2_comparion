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
 * Web service for mod customcert.
 *
 * @package    local_learnpeek_lp_course_migration
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_learnpeek_lp_course_migration_get_user_key' => [
        'classname'   => 'local_learnpeek_lp_course_migration\external',
        'methodname'  => 'get_user_key',
        'classpath'   => '',
        'description' => 'get user key for get lepress enrooled course',
        'type'        => 'write',
        'ajax'        => false,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
    'local_learnpeek_lp_user_migration' => [
        'classname'   => 'local_learnpeek_lp_course_migration\external',
        'methodname'  => 'migrate_user',
        'classpath'   => '',
        'description' => 'Migrate user data for LearnPress',
        'type'        => 'write',
        'ajax'        => false,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
];
