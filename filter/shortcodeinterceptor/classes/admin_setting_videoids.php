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
 * Admin setting for a list of YouTube video IDs.
 *
 * @package    filter_shortcodeinterceptor
 * @copyright  2026 LearnPeek
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_shortcodeinterceptor;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/adminlib.php');

/**
 * Textarea setting that only accepts valid YouTube video IDs.
 *
 * @package    filter_shortcodeinterceptor
 * @copyright  2026 LearnPeek
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_setting_videoids extends \admin_setting_configtextarea {
    /**
     * Reject the value if any entry is not a well-formed video ID.
     *
     * @param string $data
     * @return true|string true if valid, otherwise an error message
     */
    public function validate($data) {
        $invalid = array_filter(rules::parse_list((string) $data), function ($id) {
            return !rules::is_valid_video_id($id);
        });
        if ($invalid) {
            return get_string('invalidvideoids', 'filter_shortcodeinterceptor', s(implode(', ', $invalid)));
        }
        return true;
    }
}
