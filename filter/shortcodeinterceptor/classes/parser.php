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
 * Parser for WordPress Shortcoder [sc ...] shortcodes.
 *
 * @package    filter_shortcodeinterceptor
 * @copyright  2026 LearnPeek
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_shortcodeinterceptor;

/**
 * Locates [sc ...] shortcodes in text and parses their attributes.
 *
 * Parsing is a single linear scan with only anchored, non-nested regular expressions, so
 * hostile input cannot cause catastrophic backtracking. The parser knows nothing about
 * YouTube; it only reports attributes to a callback.
 *
 * @package    filter_shortcodeinterceptor
 * @copyright  2026 LearnPeek
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class parser {
    /** @var int Longest shortcode body (between "[sc" and "]") that is considered. */
    const MAX_BODY_LENGTH = 1000;

    /**
     * Replace every well-formed [sc ...] shortcode using the callback.
     *
     * The callback receives the attributes (lower-cased name => value) and returns the
     * replacement string, or null to leave the original shortcode untouched. Shortcodes that
     * are malformed, or that sit inside an HTML tag, are never passed to the callback.
     *
     * @param string $text
     * @param callable $callback function(array $attributes): ?string
     * @return string
     */
    public function replace(string $text, callable $callback): string {
        $result = '';
        $copied = 0; // Everything before this offset is already in $result.
        $offset = 0;

        while (($start = stripos($text, '[sc', $offset)) !== false) {
            $offset = $start + 3;

            if (!preg_match('/\G(?:\s|&nbsp;|\])/i', $text, $m, 0, $offset) || $this->inside_tag($text, $start)) {
                continue;
            }
            $end = strpos($text, ']', $offset);
            if ($end === false) {
                break;
            }
            $body = substr($text, $offset, $end - $offset);
            if (strlen($body) > self::MAX_BODY_LENGTH || strpbrk($body, '<>') !== false) {
                continue;
            }
            $attributes = $this->parse_attributes($body);
            if ($attributes === null) {
                continue;
            }
            $replacement = $callback($attributes);
            if ($replacement !== null) {
                $result .= substr($text, $copied, $start - $copied) . $replacement;
                $copied = $end + 1;
            }
            $offset = $end + 1;
        }

        return $result . substr($text, $copied);
    }

    /**
     * Parse the text between "[sc" and "]" into attributes.
     *
     * Accepts double, single and typographic quotes (and their HTML-entity forms, which rich
     * text editors produce), unquoted values and valueless flags.
     *
     * @param string $body
     * @return array|null lower-cased attribute name => value, or null if malformed
     */
    public function parse_attributes(string $body): ?array {
        $body = str_replace(
            ['&quot;', '&#34;', '&#039;', '&#39;', '&apos;', '&nbsp;', '“', '”', '„', '‘', '’'],
            ['"', '"', "'", "'", "'", ' ', '"', '"', '"', "'", "'"],
            $body
        );
        $length = strlen($body);
        $pos = 0;
        $attributes = [];

        while (true) {
            $pos += strspn($body, " \t\r\n", $pos);
            if ($pos >= $length) {
                return $attributes;
            }
            if (!preg_match('/\G[A-Za-z_][A-Za-z0-9_-]*/', $body, $m, 0, $pos)) {
                return null;
            }
            $name = strtolower($m[0]);
            $pos += strlen($m[0]);
            $value = '';

            $afterspace = $pos + strspn($body, " \t\r\n", $pos);
            if (($body[$afterspace] ?? '') === '=') {
                $pos = $afterspace + 1;
                $pos += strspn($body, " \t\r\n", $pos);
                $quote = $body[$pos] ?? '';
                if ($quote === '"' || $quote === "'") {
                    $close = strpos($body, $quote, $pos + 1);
                    if ($close === false) {
                        return null;
                    }
                    $value = substr($body, $pos + 1, $close - $pos - 1);
                    $pos = $close + 1;
                } else if (preg_match('/\G[^\s"\x27=\x60]+/', $body, $m, 0, $pos)) {
                    $value = $m[0];
                    $pos += strlen($m[0]);
                } else {
                    return null;
                }
            }
            $attributes[$name] = trim($value);
        }
    }

    /**
     * Whether an offset lies inside an HTML tag, e.g. in an attribute value.
     *
     * @param string $text
     * @param int $offset
     * @return bool
     */
    protected function inside_tag(string $text, int $offset): bool {
        $lt = strrpos($text, '<', $offset - strlen($text));
        if ($lt === false) {
            return false;
        }
        $gt = strrpos($text, '>', $offset - strlen($text));
        return $gt === false || $gt < $lt;
    }
}
