# Shortcode Interceptor (`filter_shortcodeinterceptor`)

A Moodle text filter that recognises WordPress **Shortcoder** YouTube shortcodes in Moodle content:

```
[sc name="youtubevideo" VideoID="wYfjI84o8Cw" ]
```

Content migrated from WordPress often contains these. Moodle does not run WordPress Shortcoder templates, so
they show up as literal text. This plugin finds them, validates the video ID, and (optionally) turns them into
a YouTube player.

**Recognising a shortcode is not the same as embedding a video.** The filter always *recognises* matching
shortcodes. Whether it *changes* anything depends on the processing mode:

| Mode | Result |
| --- | --- |
| **Pass-through** (default) | Text is left exactly as it is. Nothing changes for existing courses. |
| **Replace with YouTube URL** | The shortcode is replaced by the plain URL `https://youtu.be/<VideoID>`. |
| **Transform to YouTube embed** | The shortcode is replaced by a YouTube `<iframe>` (see "Limitations"). |

**URL mode** is the recommended way to get videos showing in normal (cleaned) course content: the filter only
writes text, so Moodle's cleaning has nothing to remove. Enable Moodle's own **Convert URLs into links** and
**Multimedia plugins** filters *below* Shortcode Interceptor in Manage filters, and the URL becomes a link
and, with the YouTube media player enabled, a player. Without those filters the visitor sees the plain URL.

## Compatibility

Developed against and verified (by reading source) for **Moodle 4.2.x** (IOMAD 4.2.6, build 20240212),
which is the classic `filter.php` + `moodle_text_filter` API. `version.php` requires Moodle 4.2.0
(`2023042400`). Other versions have not been tested. Moodle 4.5+ adds a namespaced `\core_filters\text_filter`
base class; the old class still works there but this has not been verified.

## Installation

1. Copy this directory to `<moodle>/filter/shortcodeinterceptor/`.
2. As an administrator, go to **Site administration > Notifications** and complete the plugin installation.
3. Purge caches (**Site administration > Development > Purge caches**) so already-rendered text is re-filtered.

No Moodle core files are modified, and the plugin stores no personal data.

## Enabling and configuring

1. **Site administration > Plugins > Filters > Manage filters**: set *Shortcode Interceptor* to **On** (or "Off,
   but available" to enable per course/activity).
2. **Site administration > Plugins > Filters > Shortcode Interceptor** for settings:

| Setting | Default | Meaning |
| --- | --- | --- |
| Process shortcodes | on | Master switch for the plugin's behaviour. |
| Processing mode | Pass-through | Pass-through leaves text alone; Transform replaces with an embed. |
| Privacy-enhanced embeds | on | Embed mode only: use `youtube-nocookie.com` instead of `youtube.com`. |
| Allowed video IDs | empty | If not empty, only these IDs are embedded. One per line. |
| Blocked video IDs | empty | These IDs are never embedded; wins over the allowed list. |
| When a video is refused | Leave unchanged | Keep the shortcode text, or show "This video is unavailable." |

The ID lists are validated on save; anything that is not an 11-character YouTube ID is rejected. The lists and
the refusal setting apply in URL and embed modes. Nothing is ever removed silently: a refused video keeps its
shortcode unless you choose the notice.

## Testing a shortcode

1. Set the mode to *Replace with YouTube URL* (or *Transform to YouTube embed*).
2. Create a **Page** (or Label) as an administrator with this in the HTML view:
   `[sc name="youtubevideo" VideoID="wYfjI84o8Cw"]`
3. View it. You should see `https://youtu.be/wYfjI84o8Cw` (as a link or player if the URL and multimedia filters are on), or a player in embed mode. If you see the shortcode text, see Troubleshooting.

Recognised variations: any attribute order, single/double/typographic quotes (and their `&quot;` forms), unquoted
values, extra whitespace or `&nbsp;`, and any case in attribute names. Optional `width` and `height` (1-4096)
are honoured; no other attribute reaches the output. Other Shortcoder templates, e.g.
`[sc name="imagegallery" id="123"]`, are ignored.

## Filter ordering

Moodle applies filters in the order shown in **Manage filters**, top to bottom, and each filter receives the
output of the one above it. This filter only reacts to the text `[sc ...]`, so it rarely conflicts, but:

- A filter placed **above** it that rewrites or wraps the shortcode text would stop it matching. Move
  Shortcode Interceptor above such filters.
- Filters placed **below** it receive the generated `<iframe>` rather than the shortcode.
- Filters run *before* Moodle's HTML cleaning, regardless of order (see Limitations).

It does nothing when called without a text format (for example `format_string()` for headings and names).

## Limitations

- **Moodle's HTML cleaning runs after filters.** `format_text()` applies filters first and then `clean_text()`
  (HTMLPurifier) to any content that is not trusted. HTMLPurifier removes `<iframe>`, so in embed mode the
  video only appears where the text is not cleaned: content written by users with the *trusted content*
  capability when "Enable trusted content" is on, or Moodle areas that pass `noclean`. For other content the
  iframe is dropped and the visitor sees nothing, which is why pass-through is the default. This plugin does not
  and will not bypass that check.
- Only the `youtubevideo` template is supported; shortcodes spanning HTML tags or containing `<`/`>`/`]` in a
  value are treated as malformed and left alone.
- A shortcode inside an HTML tag (such as an attribute value) is left alone. Text inside `<pre>` or `<code>`
  *is* processed.
- Shortcodes are matched by the literal text; shortcodes in a database field that is never rendered through
  `format_text()` are not touched.

## Troubleshooting

- *Shortcode text still shows:* the filter is off for that context; mode is pass-through; the ID is not exactly
  11 valid characters; the video is blocked or not on the allowlist; or the editor changed the quotes in an
  unsupported way (view the HTML source).
- *Blank space instead of the player:* the content is cleaned by Moodle (see Limitations).
- *Old content unchanged after a change:* purge caches; filtered text is cached.

## Extending

Implement `\filter_shortcodeinterceptor\processor_interface::process(string $videoid, array $attributes): ?string`
(return HTML, or `null` to keep the original) and return it from `create_processor()` in `filter.php`. Parsing
(`classes/parser.php`), video ID validation and allow/block evaluation (`classes/rules.php`) and output
generation (`classes/url_processor.php`, `classes/embed_processor.php`, sharing `classes/rule_aware_processor.php`) are separate, so per-video rules, URL transforms or different
iframe attributes can be added without touching the parser.

## Running the tests

With a configured Moodle PHPUnit environment (see Moodle's *PHPUnit* developer docs), from the Moodle root:

```
php admin/tool/phpunit/cli/init.php
vendor/bin/phpunit --testsuite filter_shortcodeinterceptor_testsuite
# or, without a testsuite entry:
vendor/bin/phpunit filter/shortcodeinterceptor/tests/filter_test.php
vendor/bin/phpunit filter/shortcodeinterceptor/tests/parser_test.php
```

(If the testsuite is not listed, run `php admin/tool/phpunit/cli/util.php --buildcomponentconfigs` first.)
Coding style: `vendor/bin/phpcs --standard=moodle filter/shortcodeinterceptor` with `moodlehq/moodle-cs`.

## License

GNU GPL v3 or later.
