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
 * Strings for component 'filter_shortcodeinterceptor', language 'en'.
 *
 * @package    filter_shortcodeinterceptor
 * @copyright  2026 LearnPeek
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['allowlist'] = 'Allowed video IDs';
$string['allowlist_desc'] = 'Optional. One YouTube video ID per line (commas and spaces also work). If not empty, only these videos are processed in URL and embed modes. Other videos are handled as set in "When a video is refused". Has no effect in pass-through mode.';
$string['blockedbehaviour'] = 'When a video is refused';
$string['blockedbehaviour_desc'] = 'What happens in URL and embed modes to a video that is not allowed or is blocked.';
$string['blockedbehaviour_leave'] = 'Leave the shortcode unchanged';
$string['blockedbehaviour_notice'] = 'Replace it with a "video unavailable" notice';
$string['blocklist'] = 'Blocked video IDs';
$string['blocklist_desc'] = 'Optional. One YouTube video ID per line. Blocked videos are never embedded, even if they are in the allowed list. Has no effect in pass-through mode.';
$string['enabled'] = 'Process shortcodes';
$string['enabled_desc'] = 'If disabled, the filter leaves all text unchanged even when it is switched on in Manage filters.';
$string['filtername'] = 'Shortcode Interceptor';
$string['invalidvideoids'] = 'These are not valid YouTube video IDs (11 characters: letters, digits, "-" and "_"): {$a}';
$string['mode'] = 'Processing mode';
$string['mode_desc'] = 'Pass-through recognises [sc name="youtubevideo" VideoID="..."] shortcodes but leaves the text exactly as it is, so existing content does not change. URL replaces them with the plain URL https://youtu.be/VideoID, which the Moodle URL and multimedia filters can turn into a link or player. Embed replaces them with a YouTube iframe, which only survives where Moodle does not clean the text (trusted content); see the README.';
$string['mode_embed'] = 'Transform to YouTube embed';
$string['mode_passthrough'] = 'Pass-through (leave unchanged)';
$string['mode_url'] = 'Replace with YouTube URL (https://youtu.be/VideoID)';
$string['privacy:metadata'] = 'The Shortcode Interceptor filter does not store any personal data.';
$string['privacyenhanced'] = 'Privacy-enhanced embeds';
$string['privacyenhanced_desc'] = 'Use youtube-nocookie.com so YouTube sets no tracking cookies until the video is played.';
$string['videotitle'] = 'YouTube video';
$string['videounavailable'] = 'This video is unavailable.';
