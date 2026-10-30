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
 * Shortcode Interceptor text filter.
 *
 * @package    filter_shortcodeinterceptor
 * @copyright  2026 LearnPeek
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use filter_shortcodeinterceptor\embed_processor;
use filter_shortcodeinterceptor\parser;
use filter_shortcodeinterceptor\passthrough_processor;
use filter_shortcodeinterceptor\processor_interface;
use filter_shortcodeinterceptor\rules;
use filter_shortcodeinterceptor\url_processor;

/**
 * Finds WordPress Shortcoder "youtubevideo" shortcodes and hands them to a processor.
 *
 * Example: [sc name="youtubevideo" VideoID="wYfjI84o8Cw"]
 *
 * @package    filter_shortcodeinterceptor
 * @copyright  2026 LearnPeek
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_shortcodeinterceptor extends moodle_text_filter {
    /** @var string Value of the "name" attribute this filter reacts to. */
    const SHORTCODE_NAME = 'youtubevideo';

    /**
     * Apply the filter to the text.
     *
     * @param string $text text to be processed
     * @param array $options filter options
     * @return string text after processing
     */
    public function filter($text, array $options = []) {
        if (!is_string($text) || stripos($text, '[sc') === false) {
            return $text;
        }
        if (!isset($options['originalformat'])) {
            // Probably called from format_string(); never inject markup into headings and the like.
            return $text;
        }

        $settings = self::get_settings();
        if (!$settings['enabled']) {
            return $text;
        }

        $processor = self::create_processor($settings);
        return (new parser())->replace($text, function (array $attributes) use ($processor) {
            return self::handle_shortcode($attributes, $processor);
        });
    }

    /**
     * Decide whether a parsed [sc] shortcode is ours and pass it to the processor.
     *
     * @param array $attributes lower-cased attribute name => value
     * @param processor_interface $processor
     * @return string|null replacement HTML, or null to keep the original shortcode
     */
    protected static function handle_shortcode(array $attributes, processor_interface $processor): ?string {
        if (!isset($attributes['name']) || strtolower($attributes['name']) !== self::SHORTCODE_NAME) {
            return null;
        }
        $videoid = $attributes['videoid'] ?? '';
        if (!rules::is_valid_video_id($videoid)) {
            return null;
        }
        return $processor->process($videoid, $attributes);
    }

    /**
     * Build the processor for the configured mode.
     *
     * @param array $settings result of {@see get_settings()}
     * @return processor_interface
     */
    protected static function create_processor(array $settings): processor_interface {
        $rules = new rules(rules::parse_list($settings['allowlist']), rules::parse_list($settings['blocklist']));
        switch ($settings['mode']) {
            case 'embed':
                return new embed_processor($rules, (bool) $settings['privacyenhanced'], $settings['blockedbehaviour']);
            case 'url':
                return new url_processor($rules, $settings['blockedbehaviour']);
            default:
                return new passthrough_processor();
        }
    }

    /**
     * Plugin settings with defaults applied (settings.php defaults only exist after installation).
     *
     * @return array
     */
    protected static function get_settings(): array {
        $config = (array) get_config('filter_shortcodeinterceptor');
        return $config + [
            'enabled' => 1,
            'mode' => 'passthrough',
            'allowlist' => '',
            'blocklist' => '',
            'privacyenhanced' => 1,
            'blockedbehaviour' => 'leave',
        ];
    }
}
