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
 * URL processor.
 *
 * @package    filter_shortcodeinterceptor
 * @copyright  2026 LearnPeek
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_shortcodeinterceptor;

/**
 * Replaces a shortcode with the plain URL https://youtu.be/VIDEOID.
 *
 * Moodle's own "Convert URLs into links" and "Multimedia plugins" filters can then turn that URL
 * into a link and player. The URL is built from a fixed host and the validated video ID only.
 *
 * @package    filter_shortcodeinterceptor
 * @copyright  2026 LearnPeek
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class url_processor extends rule_aware_processor {
    /** @var string Fixed URL prefix. */
    const URL_PREFIX = 'https://youtu.be/';

    /**
     * Produce the URL.
     *
     * @param string $videoid validated video ID
     * @param array $attributes unused
     * @return string
     */
    protected function render(string $videoid, array $attributes): string {
        return self::URL_PREFIX . $videoid;
    }
}
