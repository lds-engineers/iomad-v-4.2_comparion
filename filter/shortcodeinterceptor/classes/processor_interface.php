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
 * Processor interface.
 *
 * @package    filter_shortcodeinterceptor
 * @copyright  2026 LearnPeek
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_shortcodeinterceptor;

/**
 * Decides what a recognised youtubevideo shortcode becomes.
 *
 * Implement this to add behaviour (URL rewriting, per-video rules, ...) without touching the parser.
 *
 * @package    filter_shortcodeinterceptor
 * @copyright  2026 LearnPeek
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface processor_interface {
    /**
     * Process one shortcode.
     *
     * @param string $videoid validated YouTube video ID
     * @param array $attributes all parsed attributes (lower-cased names, unsanitised values)
     * @return string|null replacement HTML, or null to leave the original shortcode unchanged
     */
    public function process(string $videoid, array $attributes): ?string;
}
