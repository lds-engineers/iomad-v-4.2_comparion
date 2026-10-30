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
 * Shortcode Interceptor settings.
 *
 * @package    filter_shortcodeinterceptor
 * @copyright  2026 LearnPeek
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $component = 'filter_shortcodeinterceptor';

    $settings->add(new admin_setting_configcheckbox(
        "$component/enabled",
        get_string('enabled', $component),
        get_string('enabled_desc', $component),
        1
    ));

    $settings->add(new admin_setting_configselect(
        "$component/mode",
        get_string('mode', $component),
        get_string('mode_desc', $component),
        'passthrough',
        [
            'passthrough' => get_string('mode_passthrough', $component),
            'url' => get_string('mode_url', $component),
            'embed' => get_string('mode_embed', $component),
        ]
    ));

    $settings->add(new admin_setting_configcheckbox(
        "$component/privacyenhanced",
        get_string('privacyenhanced', $component),
        get_string('privacyenhanced_desc', $component),
        1
    ));

    $settings->add(new \filter_shortcodeinterceptor\admin_setting_videoids(
        "$component/allowlist",
        get_string('allowlist', $component),
        get_string('allowlist_desc', $component),
        '',
        PARAM_RAW,
        30,
        6
    ));

    $settings->add(new \filter_shortcodeinterceptor\admin_setting_videoids(
        "$component/blocklist",
        get_string('blocklist', $component),
        get_string('blocklist_desc', $component),
        '',
        PARAM_RAW,
        30,
        6
    ));

    $settings->add(new admin_setting_configselect(
        "$component/blockedbehaviour",
        get_string('blockedbehaviour', $component),
        get_string('blockedbehaviour_desc', $component),
        'leave',
        [
            'leave' => get_string('blockedbehaviour_leave', $component),
            'notice' => get_string('blockedbehaviour_notice', $component),
        ]
    ));
}
