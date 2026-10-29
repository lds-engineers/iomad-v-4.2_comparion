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
 * @package    local_user_registration
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_user_registration_get_user_key' => [
        'classname'   => 'local_user_registration\external',
        'methodname'  => 'get_user_key',
        'classpath'   => '',
        'description' => 'get user key',
        'type'        => 'write',
        'ajax'        => false,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ]
];
