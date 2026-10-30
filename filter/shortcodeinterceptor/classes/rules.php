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
 * Video ID validation and allow/block rules.
 *
 * @package    filter_shortcodeinterceptor
 * @copyright  2026 LearnPeek
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_shortcodeinterceptor;

/**
 * Evaluates which video IDs may be processed.
 *
 * A non-empty allowlist restricts processing to the listed IDs. The blocklist always wins.
 *
 * @package    filter_shortcodeinterceptor
 * @copyright  2026 LearnPeek
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rules {
    /** @var string[] */
    protected $allowlist;

    /** @var string[] */
    protected $blocklist;

    /**
     * Constructor.
     *
     * @param string[] $allowlist
     * @param string[] $blocklist
     */
    public function __construct(array $allowlist = [], array $blocklist = []) {
        $this->allowlist = $allowlist;
        $this->blocklist = $blocklist;
    }

    /**
     * Whether a string is a well-formed YouTube video ID (11 characters of A-Z a-z 0-9 _ -).
     *
     * @param string $videoid
     * @return bool
     */
    public static function is_valid_video_id(string $videoid): bool {
        return preg_match('/\A[A-Za-z0-9_-]{11}\z/', $videoid) === 1;
    }

    /**
     * Split a setting value (newline, comma, semicolon or space separated) into a list of unique tokens.
     *
     * @param string $value
     * @return string[]
     */
    public static function parse_list(string $value): array {
        return array_values(array_unique(preg_split('/[\s,;]+/', $value, -1, PREG_SPLIT_NO_EMPTY)));
    }

    /**
     * Whether a video may be processed under the configured lists.
     *
     * @param string $videoid
     * @return bool
     */
    public function is_permitted(string $videoid): bool {
        if (in_array($videoid, $this->blocklist, true)) {
            return false;
        }
        return !$this->allowlist || in_array($videoid, $this->allowlist, true);
    }
}
