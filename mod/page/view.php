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
 * Page module version information
 *
 * @package mod_page
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot.'/mod/page/lib.php');
require_once($CFG->dirroot.'/mod/page/locallib.php');
require_once($CFG->libdir.'/completionlib.php');

$id      = optional_param('id', 0, PARAM_INT); // Course Module ID
$p       = optional_param('p', 0, PARAM_INT);  // Page instance ID
$inpopup = optional_param('inpopup', 0, PARAM_BOOL);

if ($p) {
    if (!$page = $DB->get_record('page', array('id'=>$p))) {
        throw new \moodle_exception('invalidaccessparameter');
    }
    $cm = get_coursemodule_from_instance('page', $page->id, $page->course, false, MUST_EXIST);

} else {
    if (!$cm = get_coursemodule_from_id('page', $id)) {
        throw new \moodle_exception('invalidcoursemodule');
    }
    $page = $DB->get_record('page', array('id'=>$cm->instance), '*', MUST_EXIST);
}

$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/page:view', $context);

/*Suneet code 
* Trigger page_view() only when no video/audio content exists.
*/
$pagecontent = $page->content ?? '';

$hasvideo = preg_match(
    '/<video\b|<source\b[^>]*type\s*=\s*["\']video\/|\.mp4(?:[?#"\']|$)|\[sc\s+name\s*=\s*["\']youtubevideo["\']/i',
    $pagecontent
);

$hasaudio = preg_match(
    '/<audio\b|<source\b[^>]*type\s*=\s*["\']audio\/|\.mp3(?:[?#"\']|$)/i',
    $pagecontent
);

if (!$hasvideo && !$hasaudio) {
    // Completion and trigger events.
    page_view($page, $course, $cm, $context);

}

$PAGE->set_url('/mod/page/view.php', array('id' => $cm->id));

$options = empty($page->displayoptions) ? [] : (array) unserialize_array($page->displayoptions);

$activityheader = ['hidecompletion' => false];
if (empty($options['printintro'])) {
    $activityheader['description'] = '';
}

if ($inpopup and $page->display == RESOURCELIB_DISPLAY_POPUP) {
    $PAGE->set_pagelayout('popup');
    $PAGE->set_title($course->shortname.': '.$page->name);
    $PAGE->set_heading($course->fullname);
} else {
    $PAGE->add_body_class('limitedwidth');
    $PAGE->set_title($course->shortname.': '.$page->name);
    $PAGE->set_heading($course->fullname);
    $PAGE->set_activity_record($page);
    if (!$PAGE->activityheader->is_title_allowed()) {
        $activityheader['title'] = "";
    }
}
$PAGE->activityheader->set_attrs($activityheader);
echo $OUTPUT->header();
$content = file_rewrite_pluginfile_urls($page->content, 'pluginfile.php', $context->id, 'mod_page', 'content', $page->revision);
$formatoptions = new stdClass;
$formatoptions->noclean = true;
$formatoptions->overflowdiv = true;
$formatoptions->context = $context;
$content = format_text($content, $page->contentformat, $formatoptions);
echo $OUTPUT->box($content, "generalbox center clearfix");

if (!isset($options['printlastmodified']) || !empty($options['printlastmodified'])) {
    $strlastmodified = get_string("lastmodified");
    echo html_writer::div("$strlastmodified: " . userdate($page->timemodified), 'modified');
}
$iscompleted = true;
echo $OUTPUT->footer();
?>

<!--Suneet audio/video tracking code --->

<script>
    require(['jquery'], function($) {
        var cmid = <?php echo (int)$id; ?>;
        var completionSent = false;
        var requiredPercent = 99;

        /**
         * Check the media source type and track HTML5 audio/video.
         */
        $('video, audio').each(function() {
            var media = this;
            var $media = $(media);

            // Identify the media type from its source tag.
            var sourceTypes = [];

            $media.find('source').each(function() {
                sourceTypes.push(
                    (this.type || '').toLowerCase().split(';')[0].trim()
                );
            });

            // Also support media elements whose source is set directly.
            if (media.currentSrc && sourceTypes.length === 0) {
                var url = media.currentSrc.split('?')[0].toLowerCase();

                if (url.endsWith('.mp4')) {
                    sourceTypes.push('video/mp4');
                } else if (url.endsWith('.mp3')) {
                    sourceTypes.push('audio/mpeg');
                }
            }

            var isVideo = sourceTypes.some(function(type) {
                return type.indexOf('video/') === 0;
            });

            var isAudio = sourceTypes.some(function(type) {
                return type === 'audio/mp3' ||
                       type === 'audio/mpeg' ||
                       type.indexOf('audio/') === 0;
            });

            // Ignore media types that are not supported by this tracker.
            if ((media.tagName === 'VIDEO' && !isVideo) ||
                (media.tagName === 'AUDIO' && !isAudio)) {
                return;
            }

            // Keep separate tracking state for each media element.
            var mediaCompleted = false;

            function checkMediaProgress() {
                if (mediaCompleted || completionSent) {
                    return;
                }

                var duration = media.duration;

                if (!isFinite(duration) || duration <= 0) {
                    return;
                }

                // Count actual played ranges rather than currentTime.
                var playedSeconds = 0;

                for (var i = 0; i < media.played.length; i++) {
                    playedSeconds +=
                        media.played.end(i) - media.played.start(i);
                }

                var percentPlayed = (playedSeconds / duration) * 100;

                // console.log('Page media progress:', {
                //     type: media.tagName.toLowerCase(),
                //     duration: duration,
                //     playedSeconds: playedSeconds,
                //     percentPlayed: percentPlayed
                // });

                if (percentPlayed >= requiredPercent) {
                    mediaCompleted = true;
                    completionSent = true;
                    updatePageCompletion();
                }
                <?php
                if(!$iscompleted){
                    ?>
                else{
                    $('#next-activity').html('');
                }
                    <?php
                }
                ?>
            }

            $media.on(
                'loadedmetadata timeupdate ended',
                checkMediaProgress
            );
        });

        /**
         * Update Moodle completion and refresh the Next Activity button.
         */
        function updatePageCompletion() {
            $.ajax({
                url: '<?php echo $CFG->wwwroot; ?>/mod/page/updated_completion_status_page_view.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    cmid: cmid,
                    sesskey: M.cfg.sesskey
                },
                success: function(response) {
                    if (response.status === 'success') {
                        if (response.buttonhtml !== undefined &&
                            response.buttonhtml !== '') {
                            $('#next-activity').html(response.buttonhtml);
                        }

                        console.log('Page media completion updated.');
                    } else {
                        // Allow a retry if the server rejected the request.
                        completionSent = false;
                        console.error(
                            'Completion update failed:',
                            response.message || response
                        );
                    }
                },
                error: function(xhr, status, error) {
                    completionSent = false;

                    console.error('Completion request failed:', error);
                    console.error(xhr.responseText);
                }
            });
        }


        /* YouTube completion tracking */

        var trackedMoodlePlayers = [];
        var youtubeCompletionSent = false;
        var youtubeTrackingTimer = null;

        function initializeMoodleYouTubeTracking() {
            document.querySelectorAll('.video-js.vjs-youtube').forEach(function(el) {
                var player = el.player;

                if (!player ||
                    typeof player.currentTime !== 'function' ||
                    typeof player.duration !== 'function' ||
                    el.dataset.moodleCompletionTracking === '1') {
                    return;
                }

                el.dataset.moodleCompletionTracking = '1';

                var item = {
                    player: player,
                    intervals: [],
                    lastTime: null,
                    completed: false
                };

                trackedMoodlePlayers.push(item);

                // console.log('[Moodle tracking] Attached:', player.id());

                player.on('playing', function() {
                    startMoodleYouTubeTracking();
                });

                player.on('pause', function() {
                    item.lastTime = null;
                });

                player.on('ended', function() {
                    // console.log('[Moodle tracking] Video ended');

                    var duration = Number(player.duration());

                    if (isFinite(duration) && duration > 0) {
                        // Record the final observed playback interval.
                        var currentTime = Number(player.currentTime());

                        if (item.lastTime !== null &&
                            isFinite(currentTime) &&
                            currentTime > item.lastTime) {

                            var delta = currentTime - item.lastTime;

                            if (delta <= 3) {
                                item.intervals.push({
                                    start: item.lastTime,
                                    end: Math.min(currentTime, duration)
                                });
                            }
                        }

                        item.lastTime = null;

                        // Recalculate progress after recording the final interval.
                        trackMoodleYouTubeProgress(item);

                        // If playback reached the end, finalize completion.
                        if (currentTime >= duration - 1) {
                            item.completed = true;
                            checkMoodleYouTubeCompletion();
                        }
                    }
                });
            });
        }

        function startMoodleYouTubeTracking() {
            if (youtubeTrackingTimer !== null) {
                return;
            }

            youtubeTrackingTimer = window.setInterval(function() {
                trackedMoodlePlayers.forEach(function(item) {
                    trackMoodleYouTubeProgress(item);
                });
            }, 1000);
        }

        function trackMoodleYouTubeProgress(item) {
            if (item.completed) {
                return;
            }

            try {
                var player = item.player;

                if (!player || player.isDisposed()) {
                    return;
                }

                var currentTime = Number(player.currentTime());
                var duration = Number(player.duration());

                if (!isFinite(currentTime) ||
                    !isFinite(duration) ||
                    duration <= 0) {
                    return;
                }

                if (player.paused() && currentTime < duration) {
                    item.lastTime = null;
                    return;
                }

                if (item.lastTime !== null) {
                    var delta = currentTime - item.lastTime;

                    // Ignore forward seeking and large timing jumps.
                    if (delta > 0 && delta <= 2.5) {
                        item.intervals.push({
                            start: item.lastTime,
                            end: currentTime
                        });
                    }
                }

                item.lastTime = currentTime;

                // Merge overlapping watched intervals so replaying
                // the same section does not inflate the percentage.
                var ranges = item.intervals.slice().sort(function(a, b) {
                    return a.start - b.start;
                });

                var watched = 0;
                var start = null;
                var end = null;

                ranges.forEach(function(range) {
                    if (start === null) {
                        start = range.start;
                        end = range.end;
                    } else if (range.start <= end) {
                        end = Math.max(end, range.end);
                    } else {
                        watched += end - start;
                        start = range.start;
                        end = range.end;
                    }
                });

                if (start !== null) {
                    watched += end - start;
                }

                var percent = Math.min(100, watched / duration * 100);

                console.log('[Moodle tracking] YouTube progress:', {
                    player: player.id(),
                    currentTime: currentTime.toFixed(1),
                    duration: duration.toFixed(1),
                    watchedSeconds: watched.toFixed(1),
                    percentage: percent.toFixed(2) + '%'
                });

                if (percent >= 95) {
                    item.completed = true;
                    checkMoodleYouTubeCompletion();
                }
            } catch (error) {
                console.error('[Moodle tracking] Progress error:', error);
            }
        }

        function checkMoodleYouTubeCompletion() {
            if (
                trackedMoodlePlayers.length > 0 &&
                trackedMoodlePlayers.every(function(item) {
                    return item.completed;
                }) &&
                !youtubeCompletionSent
            ) {
                youtubeCompletionSent = true;

                console.log('[Moodle tracking] Sending completion update');

                updatePageCompletion();
            }
        }

        // Initialize existing players and detect lazy-loaded players.
        initializeMoodleYouTubeTracking();

        var moodleYouTubeObserver = new MutationObserver(function() {
            initializeMoodleYouTubeTracking();
        });

        moodleYouTubeObserver.observe(document.body, {
            childList: true,
            subtree: true
        });

    });
</script>

