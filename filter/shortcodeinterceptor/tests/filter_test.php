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

namespace filter_shortcodeinterceptor;

use filter_shortcodeinterceptor;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/filter/shortcodeinterceptor/filter.php');

/**
 * Tests for the Shortcode Interceptor filter.
 *
 * @package    filter_shortcodeinterceptor
 * @copyright  2026 LearnPeek
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \filter_shortcodeinterceptor
 */
class filter_test extends \advanced_testcase {
    /** @var string A valid video ID used throughout. */
    const ID = 'wYfjI84o8Cw';

    /** @var string Its privacy-enhanced embed URL. */
    const SRC = 'src="https://www.youtube-nocookie.com/embed/wYfjI84o8Cw"';

    /**
     * Run text through the filter with the given settings.
     *
     * @param string $text
     * @param array $config settings to override
     * @return string
     */
    protected function run_filter(string $text, array $config = []): string {
        $this->resetAfterTest();
        foreach ($config as $name => $value) {
            set_config($name, $value, 'filter_shortcodeinterceptor');
        }
        $filter = new filter_shortcodeinterceptor(\context_system::instance(), []);
        return $filter->filter($text, ['originalformat' => FORMAT_HTML]);
    }

    /**
     * Run text through the filter in embed mode.
     *
     * @param string $text
     * @param array $config extra settings
     * @return string
     */
    protected function embed(string $text, array $config = []): string {
        return $this->run_filter($text, ['mode' => 'embed'] + $config);
    }

    /**
     * Texts that must come back unchanged in every mode.
     *
     * @return array
     */
    public static function untouched_provider(): array {
        return [
            'no shortcode' => ['<p>Hello <b>world</b></p>'],
            'empty string' => [''],
            'other shortcoder template' => ['<p>[sc name="imagegallery" id="123"]</p>'],
            'missing name' => ['[sc VideoID="wYfjI84o8Cw"]'],
            'missing VideoID' => ['[sc name="youtubevideo"]'],
            'empty VideoID' => ['[sc name="youtubevideo" VideoID=""]'],
            'too short VideoID' => ['[sc name="youtubevideo" VideoID="abc"]'],
            'too long VideoID' => ['[sc name="youtubevideo" VideoID="wYfjI84o8Cwx"]'],
            'bad characters in VideoID' => ['[sc name="youtubevideo" VideoID="wYfjI84o8C!"]'],
            'unterminated' => ['[sc name="youtubevideo" VideoID="wYfjI84o8Cw"'],
            'unterminated quote' => ['[sc name="youtubevideo VideoID="wYfjI84o8Cw]'],
            'not the sc tag' => ['[scx name="youtubevideo" VideoID="wYfjI84o8Cw"]'],
            'tag opened inside' => ['[sc name="youtubevideo" <b>VideoID="wYfjI84o8Cw"]'],
            'inside an html attribute' => ['<a title=\'[sc name="youtubevideo" VideoID="wYfjI84o8Cw"]\'>x</a>'],
            'xss in VideoID' => ['[sc name="youtubevideo" VideoID="x\' onload=\'alert(1)"]'],
            'script in VideoID' => ['[sc name="youtubevideo" VideoID="<script>alert(1)</script>"]'],
        ];
    }

    /**
     * Unusable or unrelated text is preserved.
     *
     * @dataProvider untouched_provider
     * @param string $text
     */
    public function test_untouched_text(string $text): void {
        $this->assertSame($text, $this->run_filter($text));
        $this->assertSame($text, $this->embed($text));
    }

    /**
     * Variants of a valid shortcode that must all be recognised.
     *
     * @return array
     */
    public static function valid_provider(): array {
        return [
            'double quotes, trailing space' => ['[sc name="youtubevideo" VideoID="wYfjI84o8Cw" ]'],
            'attributes reversed' => ['[sc VideoID="wYfjI84o8Cw" name="youtubevideo"]'],
            'single quotes' => ["[sc name='youtubevideo' VideoID='wYfjI84o8Cw']"],
            'mixed quotes' => ['[sc name=\'youtubevideo\' VideoID="wYfjI84o8Cw"]'],
            'unquoted' => ['[sc name=youtubevideo VideoID=wYfjI84o8Cw]'],
            'extra whitespace' => ["[sc   name = \"youtubevideo\"\n\tVideoID =  \"wYfjI84o8Cw\"   ]"],
            'lower-case attribute names' => ['[sc name="youtubevideo" videoid="wYfjI84o8Cw"]'],
            'html entity quotes' => ['[sc name=&quot;youtubevideo&quot; VideoID=&quot;wYfjI84o8Cw&quot;]'],
            'typographic quotes' => ['[sc name=“youtubevideo” VideoID=“wYfjI84o8Cw”]'],
            'nbsp separators' => ['[sc&nbsp;name="youtubevideo"&nbsp;VideoID="wYfjI84o8Cw"]'],
        ];
    }

    /**
     * Every valid variant is recognised but left alone in pass-through mode.
     *
     * @dataProvider valid_provider
     * @param string $text
     */
    public function test_passthrough_leaves_valid_shortcodes(string $text): void {
        $this->assertSame($text, $this->run_filter($text));
        $this->assertSame($text, $this->run_filter($text, ['mode' => 'passthrough']));
    }

    /**
     * Every valid variant becomes an embed in embed mode.
     *
     * @dataProvider valid_provider
     * @param string $text
     */
    public function test_embed_valid_shortcodes(string $text): void {
        $result = $this->embed("<p>Before</p>$text<p>After</p>");
        $this->assertStringStartsWith('<p>Before</p><iframe ', $result);
        $this->assertStringEndsWith('</iframe><p>After</p>', $result);
        $this->assertStringContainsString(self::SRC, $result);
        $this->assertStringNotContainsString('[sc', $result);
    }

    /**
     * The embed markup is built only from fixed values and the video ID.
     */
    public function test_embed_markup(): void {
        $result = $this->embed('[sc name="youtubevideo" VideoID="wYfjI84o8Cw"]');
        $this->assertStringContainsString(self::SRC, $result);
        $this->assertStringContainsString('width="560"', $result);
        $this->assertStringContainsString('height="315"', $result);
        $this->assertStringContainsString('allowfullscreen="allowfullscreen"', $result);
        $this->assertStringContainsString('loading="lazy"', $result);
    }

    /**
     * Privacy-enhanced mode can be turned off.
     */
    public function test_embed_standard_host(): void {
        $result = $this->embed('[sc name="youtubevideo" VideoID="wYfjI84o8Cw"]', ['privacyenhanced' => 0]);
        $this->assertStringContainsString('src="https://www.youtube.com/embed/wYfjI84o8Cw"', $result);
    }

    /**
     * Size attributes are accepted when numeric and ignored otherwise; nothing else is copied.
     */
    public function test_embed_attribute_handling(): void {
        $result = $this->embed('[sc name="youtubevideo" VideoID="wYfjI84o8Cw" width="800" height="450"]');
        $this->assertStringContainsString('width="800" height="450"', $result);

        $result = $this->embed(
            '[sc name="youtubevideo" VideoID="wYfjI84o8Cw" width="100\' onload=\'alert(1)" height="-5" onclick="x"]'
        );
        $this->assertStringContainsString('width="560" height="315"', $result);
        $this->assertStringNotContainsString('onload', $result);
        $this->assertStringNotContainsString('onclick', $result);

        $result = $this->embed('[sc name="youtubevideo" VideoID="wYfjI84o8Cw" src="https://evil.example/x" class="evil"]');
        $this->assertStringNotContainsString('evil', $result);
    }

    /**
     * Several shortcodes in one block, mixed with other content, are handled independently.
     */
    public function test_multiple_and_mixed_content(): void {
        $text = '<h3>Intro</h3><p>[sc name="youtubevideo" VideoID="wYfjI84o8Cw"]</p>'
            . '<p>[sc name="imagegallery" id="123"]</p>'
            . '<div class="x"><a href="http://example.com?a=1&amp;b=2">link</a> '
            . '[sc VideoID=\'abc123XYZ89\' name=\'youtubevideo\']</div>'
            . '<p>[sc name="youtubevideo" VideoID="bad"]</p><ul><li>one</li></ul>';
        $result = $this->embed($text);

        $this->assertSame(2, substr_count($result, '<iframe '));
        $this->assertStringContainsString('/embed/wYfjI84o8Cw"', $result);
        $this->assertStringContainsString('/embed/abc123XYZ89"', $result);
        // Everything that is not a processed shortcode survives byte for byte.
        $this->assertStringContainsString('<h3>Intro</h3>', $result);
        $this->assertStringContainsString('<p>[sc name="imagegallery" id="123"]</p>', $result);
        $this->assertStringContainsString('<a href="http://example.com?a=1&amp;b=2">link</a>', $result);
        $this->assertStringContainsString('<p>[sc name="youtubevideo" VideoID="bad"]</p><ul><li>one</li></ul>', $result);
    }

    /**
     * Running the filter again must not change or re-transform its own output.
     */
    public function test_idempotent(): void {
        $text = '<p>[sc name="youtubevideo" VideoID="wYfjI84o8Cw"] [sc name="imagegallery" id="1"]</p>';
        $once = $this->embed($text);
        $twice = $this->embed($once);
        $this->assertSame($once, $twice);
        $this->assertSame(1, substr_count($twice, '<iframe '));
    }

    /**
     * URL mode turns every valid variant into the plain youtu.be URL.
     *
     * @dataProvider valid_provider
     * @param string $text
     */
    public function test_url_mode_valid_shortcodes(string $text): void {
        $result = $this->run_filter("<p>Before $text after</p>", ['mode' => 'url']);
        $this->assertSame('<p>Before https://youtu.be/wYfjI84o8Cw after</p>', $result);
    }

    /**
     * URL mode keeps unrelated and invalid content, handles several shortcodes, and is idempotent.
     */
    public function test_url_mode_mixed_content(): void {
        $text = '<p>[sc name="youtubevideo" VideoID="wYfjI84o8Cw"]</p><p>[sc name="imagegallery" id="1"]</p>'
            . '<p>[sc VideoID=\'abc123XYZ89\' name=\'youtubevideo\']</p><p>[sc name="youtubevideo" VideoID="bad"]</p>';
        $expected = '<p>https://youtu.be/wYfjI84o8Cw</p><p>[sc name="imagegallery" id="1"]</p>'
            . '<p>https://youtu.be/abc123XYZ89</p><p>[sc name="youtubevideo" VideoID="bad"]</p>';
        $once = $this->run_filter($text, ['mode' => 'url']);
        $this->assertSame($expected, $once);
        $this->assertSame($expected, $this->run_filter($once, ['mode' => 'url']));
    }

    /**
     * URL mode ignores extra attributes, so nothing hostile reaches the output.
     */
    public function test_url_mode_ignores_other_attributes(): void {
        $result = $this->run_filter(
            '[sc name="youtubevideo" VideoID="wYfjI84o8Cw" onclick="x" src="https://evil.example/"]',
            ['mode' => 'url']
        );
        $this->assertSame('https://youtu.be/wYfjI84o8Cw', $result);
    }

    /**
     * URL mode applies the allow and block lists.
     */
    public function test_url_mode_rules(): void {
        $text = '[sc name="youtubevideo" VideoID="wYfjI84o8Cw"] [sc name="youtubevideo" VideoID="abc123XYZ89"]';
        $result = $this->run_filter($text, ['mode' => 'url', 'blocklist' => 'wYfjI84o8Cw']);
        $this->assertSame('[sc name="youtubevideo" VideoID="wYfjI84o8Cw"] https://youtu.be/abc123XYZ89', $result);

        $result = $this->run_filter(
            $text,
            ['mode' => 'url', 'blocklist' => '', 'allowlist' => 'wYfjI84o8Cw', 'blockedbehaviour' => 'notice']
        );
        $this->assertSame(
            'https://youtu.be/wYfjI84o8Cw <span class="filter_shortcodeinterceptor-blocked">This video is unavailable.</span>',
            $result
        );
    }

    /**
     * The blocklist stops a video; other videos are unaffected.
     */
    public function test_blocklist(): void {
        $text = '[sc name="youtubevideo" VideoID="wYfjI84o8Cw"] [sc name="youtubevideo" VideoID="abc123XYZ89"]';
        $result = $this->embed($text, ['blocklist' => "wYfjI84o8Cw\n"]);
        $this->assertStringContainsString('[sc name="youtubevideo" VideoID="wYfjI84o8Cw"]', $result);
        $this->assertStringContainsString('/embed/abc123XYZ89"', $result);
        $this->assertStringNotContainsString('/embed/wYfjI84o8Cw"', $result);
    }

    /**
     * A non-empty allowlist restricts embedding; the blocklist overrides it.
     */
    public function test_allowlist(): void {
        $text = '[sc name="youtubevideo" VideoID="wYfjI84o8Cw"] [sc name="youtubevideo" VideoID="abc123XYZ89"]';
        $result = $this->embed($text, ['allowlist' => 'abc123XYZ89']);
        $this->assertStringContainsString('[sc name="youtubevideo" VideoID="wYfjI84o8Cw"]', $result);
        $this->assertStringContainsString('/embed/abc123XYZ89"', $result);

        $result = $this->embed($text, ['allowlist' => "wYfjI84o8Cw, abc123XYZ89", 'blocklist' => 'abc123XYZ89']);
        $this->assertStringContainsString('/embed/wYfjI84o8Cw"', $result);
        $this->assertStringContainsString('[sc name="youtubevideo" VideoID="abc123XYZ89"]', $result);
    }

    /**
     * Allow and block lists are ignored in pass-through mode.
     */
    public function test_lists_ignored_in_passthrough(): void {
        $text = '[sc name="youtubevideo" VideoID="wYfjI84o8Cw"]';
        $this->assertSame($text, $this->run_filter($text, ['blocklist' => 'wYfjI84o8Cw']));
    }

    /**
     * A refused video can be replaced by a notice instead of being left as text.
     */
    public function test_blocked_notice(): void {
        $result = $this->embed(
            '[sc name="youtubevideo" VideoID="wYfjI84o8Cw"]',
            ['blocklist' => 'wYfjI84o8Cw', 'blockedbehaviour' => 'notice']
        );
        $this->assertSame('<span class="filter_shortcodeinterceptor-blocked">This video is unavailable.</span>', $result);
    }

    /**
     * Disabling the setting stops all processing.
     */
    public function test_disabled(): void {
        $text = '[sc name="youtubevideo" VideoID="wYfjI84o8Cw"]';
        $this->assertSame($text, $this->embed($text, ['enabled' => 0]));
    }

    /**
     * Without originalformat (format_string) nothing is touched.
     */
    public function test_skipped_without_original_format(): void {
        $this->resetAfterTest();
        set_config('mode', 'embed', 'filter_shortcodeinterceptor');
        $filter = new filter_shortcodeinterceptor(\context_system::instance(), []);
        $text = '[sc name="youtubevideo" VideoID="wYfjI84o8Cw"]';
        $this->assertSame($text, $filter->filter($text));
    }

    /**
     * Large hostile input is handled quickly and unchanged.
     */
    public function test_pathological_input(): void {
        $text = str_repeat('[sc ', 5000) . str_repeat(' name="', 5000) . str_repeat('[', 5000);
        $start = microtime(true);
        $this->assertSame($text, $this->embed($text));
        $this->assertLessThan(5, microtime(true) - $start);
    }
}
