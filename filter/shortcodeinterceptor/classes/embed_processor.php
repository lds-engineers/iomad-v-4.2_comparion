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
 * Embed processor.
 *
 * @package    filter_shortcodeinterceptor
 * @copyright  2026 LearnPeek
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_shortcodeinterceptor;

/**
 * Replaces a shortcode with a YouTube iframe.
 *
 * The iframe host is fixed; only the validated video ID and two clamped numbers are inserted.
 * No other shortcode attribute reaches the markup.
 *
 * @package    filter_shortcodeinterceptor
 * @copyright  2026 LearnPeek
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class embed_processor extends rule_aware_processor {
    /** @var string Standard YouTube embed host. */
    const HOST = 'https://www.youtube.com';

    /** @var string Privacy-enhanced (no cookies until play) embed host. */
    const HOST_PRIVACY = 'https://www.youtube-nocookie.com';

    /** @var int Default player width. */
    const DEFAULT_WIDTH = 560;

    /** @var int Default player height. */
    const DEFAULT_HEIGHT = 315;

    /** @var bool */
    protected $privacyenhanced;

    /**
     * Constructor.
     *
     * @param rules $rules
     * @param bool $privacyenhanced use the youtube-nocookie.com host
     * @param string $blockedbehaviour 'leave' or 'notice'
     */
    public function __construct(rules $rules, bool $privacyenhanced = true, string $blockedbehaviour = 'leave') {
        parent::__construct($rules, $blockedbehaviour);
        $this->privacyenhanced = $privacyenhanced;
    }

    /**
     * Build the embed markup.
     *
     * @param string $videoid validated video ID
     * @param array $attributes parsed attributes; only width and height are used
     * @return string
     */
    protected function render(string $videoid, array $attributes): string {
        $host = $this->privacyenhanced ? self::HOST_PRIVACY : self::HOST;
        return \html_writer::tag('iframe', '', [
            'class' => 'filter_shortcodeinterceptor-video',
            'src' => $host . '/embed/' . $videoid,
            'width' => self::clamp($attributes['width'] ?? '', self::DEFAULT_WIDTH),
            'height' => self::clamp($attributes['height'] ?? '', self::DEFAULT_HEIGHT),
            'title' => get_string('videotitle', 'filter_shortcodeinterceptor'),
            'loading' => 'lazy',
            'referrerpolicy' => 'strict-origin-when-cross-origin',
            'allow' => 'accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture; fullscreen',
            'allowfullscreen' => 'allowfullscreen',
        ]);
    }

    /**
     * Accept a plain integer between 1 and 4096, otherwise use the default.
     *
     * @param string $value
     * @param int $default
     * @return int
     */
    protected static function clamp(string $value, int $default): int {
        if (preg_match('/\A[0-9]{1,4}\z/', $value) && (int) $value >= 1 && (int) $value <= 4096) {
            return (int) $value;
        }
        return $default;
    }
}
