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
 * Base class for processors that honour the allow/block rules.
 *
 * @package    filter_shortcodeinterceptor
 * @copyright  2026 LearnPeek
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_shortcodeinterceptor;

/**
 * Applies the allow/block rules, then lets the subclass render permitted videos.
 *
 * @package    filter_shortcodeinterceptor
 * @copyright  2026 LearnPeek
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class rule_aware_processor implements processor_interface {
    /** @var rules */
    protected $rules;

    /** @var string 'leave' keeps a refused shortcode as is, 'notice' replaces it with a notice. */
    protected $blockedbehaviour;

    /**
     * Constructor.
     *
     * @param rules $rules
     * @param string $blockedbehaviour 'leave' or 'notice'
     */
    public function __construct(rules $rules, string $blockedbehaviour = 'leave') {
        $this->rules = $rules;
        $this->blockedbehaviour = $blockedbehaviour;
    }

    /**
     * Refuse the video according to the rules, or render it.
     *
     * @param string $videoid validated video ID
     * @param array $attributes parsed attributes
     * @return string|null
     */
    public function process(string $videoid, array $attributes): ?string {
        if (!$this->rules->is_permitted($videoid)) {
            if ($this->blockedbehaviour === 'notice') {
                return \html_writer::span(
                    get_string('videounavailable', 'filter_shortcodeinterceptor'),
                    'filter_shortcodeinterceptor-blocked'
                );
            }
            return null;
        }
        return $this->render($videoid, $attributes);
    }

    /**
     * Produce the replacement for a permitted video.
     *
     * @param string $videoid validated video ID
     * @param array $attributes parsed attributes
     * @return string replacement HTML
     */
    abstract protected function render(string $videoid, array $attributes): string;
}
