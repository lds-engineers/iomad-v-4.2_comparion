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

/**
 * Tests for the shortcode parser and the video rules.
 *
 * @package    filter_shortcodeinterceptor
 * @copyright  2026 LearnPeek
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \filter_shortcodeinterceptor\parser
 * @covers     \filter_shortcodeinterceptor\rules
 */
class parser_test extends \basic_testcase {
    /**
     * Attributes are lower-cased by name and quote styles are normalised.
     */
    public function test_parse_attributes(): void {
        $parser = new parser();
        $this->assertSame(['name' => 'a', 'videoid' => 'b c'], $parser->parse_attributes(' name="a" VideoID=\'b c\' '));
        $this->assertSame(['name' => 'a', 'flag' => ''], $parser->parse_attributes('name=a flag'));
        $this->assertSame([], $parser->parse_attributes('   '));
        $this->assertNull($parser->parse_attributes('name="open'));
        $this->assertNull($parser->parse_attributes('=value'));
        $this->assertNull($parser->parse_attributes('name='));
    }

    /**
     * The callback decides what is replaced; null keeps the original.
     */
    public function test_replace(): void {
        $parser = new parser();
        $result = $parser->replace('a [sc id=1] b [sc id=2] c [SC id=3]', function (array $attributes) {
            return $attributes['id'] === '2' ? null : 'X' . $attributes['id'];
        });
        $this->assertSame('a X1 b [sc id=2] c X3', $result);
    }

    /**
     * Video ID validation.
     */
    public function test_video_id_validation(): void {
        $this->assertTrue(rules::is_valid_video_id('wYfjI84o8Cw'));
        $this->assertTrue(rules::is_valid_video_id('-_-_-_-_-_-'));
        $this->assertFalse(rules::is_valid_video_id(''));
        $this->assertFalse(rules::is_valid_video_id("wYfjI84o8Cw\n"));
        $this->assertFalse(rules::is_valid_video_id('wYfjI84o8C '));
        $this->assertFalse(rules::is_valid_video_id('wYfjI84o8Cwx'));
    }

    /**
     * List parsing and allow/block evaluation.
     */
    public function test_rules(): void {
        $this->assertSame(['a', 'b', 'c'], rules::parse_list("a,b\r\n c;a\n"));
        $this->assertSame([], rules::parse_list(" \n "));

        $this->assertTrue((new rules())->is_permitted('x'));
        $this->assertFalse((new rules([], ['x']))->is_permitted('x'));
        $this->assertTrue((new rules(['x']))->is_permitted('x'));
        $this->assertFalse((new rules(['x']))->is_permitted('y'));
        $this->assertFalse((new rules(['x'], ['x']))->is_permitted('x'));
    }
}
