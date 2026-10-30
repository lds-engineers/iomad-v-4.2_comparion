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
 * Prints an instance of mod_videotime.
 *
 * @package     mod_videotime
 * @copyright   2021 bdecent gmbh <https://bdecent.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_videotime\output\next_activity_button;
use mod_videotime\videotime_instance;

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

global $USER;

// Course_module ID, or.
$id = optional_param('id', 0, PARAM_INT);

// ... module instance id.
$v  = optional_param('v', 0, PARAM_INT);

if ($id) {
    $cm             = get_coursemodule_from_id('videotime', $id, 0, false, MUST_EXIST);
    $course         = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('videotime', ['id' => $cm->instance], '*', MUST_EXIST);
} else if ($v) {
    $moduleinstance = $DB->get_record('videotime', ['id' => $v], '*', MUST_EXIST);
    $course         = $DB->get_record('course', ['id' => $moduleinstance->course], '*', MUST_EXIST);
    $cm             = get_coursemodule_from_instance('videotime', $moduleinstance->id, $course->id, false, MUST_EXIST);
} else {
    throw new moodle_exception('invalidcoursemodule', 'mod_videotime');
}

$context = context_module::instance($cm->id);
$PAGE->set_context($context);
$PAGE->requires->jquery();
require_login($course, true, $cm);

require_capability('mod/videotime:view', $context);

$PAGE->set_url('/mod/videotime/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($moduleinstance->name));
if (class_exists('core\\output\\activity_header') && empty($moduleinstance->show_description_in_player)) {
    $PAGE->activityheader->set_description('');
}
$PAGE->set_heading(format_string($course->fullname));
$PAGE->add_body_class('limitedwidth');

$edit = optional_param('edit', null, PARAM_BOOL);
if ($edit !== null && confirm_sesskey() && $PAGE->user_allowed_editing()) {
    $USER->editing = $edit;
    redirect($PAGE->url);
}

$moduleinstance = videotime_instance::instance_by_id($moduleinstance->id);
$moduleinstance->setup_page();

 // Completion and trigger events.
videotime_view($moduleinstance, $course, $cm, $moduleinstance->get_context());

$renderer = $PAGE->get_renderer('mod_videotime');

echo $OUTPUT->header();

if (!class_exists('core\\output\\activity_header')) {
    echo $OUTPUT->heading(format_string($moduleinstance->name), 2);
}

foreach (array_keys(core_component::get_plugin_list('videotimeplugin')) as $name) {
    if ($player = component_callback("videotimeplugin_$name", 'embed_player', [$moduleinstance->to_record()], null)) {
        break;
    }
}

if (empty($player)) {
    \core\notification::error(get_string('vimeo_url_missing', 'videotime'));
} else {
    // Render the activity information.
    if (class_exists('\\core_completion\\activity_custom_completion') && !class_exists('core\\output\\activity_header')) {
        $cminfo = cm_info::create($cm);
        $completiondetails = \core_completion\cm_completion_details::get_instance($cminfo, $USER->id);
        $activitydates = \core\activity_dates::get_dates_for_module($cminfo, $USER->id);
        echo $OUTPUT->activity_information($cminfo, $completiondetails, $activitydates);
    }

    echo $renderer->render($moduleinstance);
}
echo $OUTPUT->footer();
?>
<!-- ------------------sushil start ----------------------------------->
<script>
    var id = <?php echo $id;?>;
    var currentvideoid = 0;
    var secondvideoplayed = 0;
    var percentvideoplayed = 0;
    var videotracker = null;
    var playcounter = 0;
    var vimeoPlayerInitialized = false;
    var vimeoPlayerInitializing = false;
    var vimeoInitAttempts = 0;
    var vimeoInitTimer = null;
    var vimeoPlayerInstance = null;

    function initializeVimeoTracking() {

        // Do not initialize the player more than once.
        if (vimeoPlayerInitialized || vimeoPlayerInitializing) {
            return;
        }

        // Find only a real Vimeo iframe.
        var iframe1 = document.querySelector(
            '.vimeo-embed iframe[src*="vimeo.com"], ' +
            '.vimeo-embed iframe[src*="player.vimeo.com"]'
        );

        // Iframe may not have been created yet.
        if (!iframe1 || !iframe1.isConnected) {
            vimeoInitAttempts++;

            if (vimeoInitAttempts <= 30) {
                vimeoInitTimer = window.setTimeout(
                    initializeVimeoTracking,
                    1000
                );
            } else {
                console.warn(
                    '[Vimeo tracking] Vimeo iframe not found after 30 attempts.'
                );
            }

            return;
        }

        // console.log('[Vimeo tracking] Valid iframe found:', iframe1);

        vimeoPlayerInitializing = true;

        require(
            ['https://player.vimeo.com/api/player.js'],
            function(Player) {

                // Recheck the iframe before initializing.
                if (!iframe1.isConnected) {
                    vimeoPlayerInitializing = false;
                    initializeVimeoTracking();
                    return;
                }

                try {
                    var player1 = new Player(iframe1);

                    vimeoPlayerInstance = player1;

                    player1.on('play', startvideotracking);
                    player1.on('pause', onPause111);
                    player1.on('ended', onFinish111);
                    player1.on('progress', onPlayProgress111);

                    player1.getVideoId().then(function(videoid) {
                        currentvideoid = videoid;
                        vimeoPlayerInitialized = true;

                        // console.log(
                        //     '[Vimeo tracking] Initialized successfully:',
                        //     currentvideoid
                        // );
                    }).catch(function(error) {
                        console.error(
                            '[Vimeo tracking] Video ID error:',
                            error
                        );
                    }).finally(function() {
                        vimeoPlayerInitializing = false;
                    });

                } catch (error) {
                    vimeoPlayerInitializing = false;

                    console.error(
                        '[Vimeo tracking] Initialization error:',
                        error
                    );
                }
            },
            function(error) {
                vimeoPlayerInitializing = false;

                console.error(
                    '[Vimeo tracking] Vimeo API loading failed:',
                    error
                );
            }
        );
    }

    // Try immediately; retry only if the iframe is not ready.
    initializeVimeoTracking();


    function onPause111() {
        console.log('paused');
        // status.text('paused');
    }

    function onFinish111() {
        // console.log('Video finished, updating status...');

        $.ajax({
            url: '<?php echo $CFG->wwwroot; ?>/mod/videotime/updated_completion_status_videotime_view.php',
            type: 'POST',
            data: {
                cmid: id,
                sesskey: M.cfg.sesskey
            },
            success: function(response) {
                // console.log("Success:", response);
                if (response.status === 'success') {
                    // Inject the dynamic Next Activity button layout
                    if (response.buttonhtml !== '') {
                        $('#next-activity').html(response.buttonhtml);
                    }

                    //  hide the lock icon
                    // if (response.nextcmid > 0) {
                    //     var targetAnchor = $('.courseindex-link[href="#module-' + response.nextcmid + '"]');
                    //     if (targetAnchor.length > 0) {
                    //         // Find the sibling locked span container right next to the link text and remove it
                    //         targetAnchor.siblings('.courseindex-locked').hide();
                    //     }
                    // }
                }
            },
            error: function(xhr, status, error) {
                console.error("Status:", status);
                console.error("Error:", error);
                console.error("Response:", xhr.responseText);
            }
        });
    }
    function startvideotracking() {
        var modleid = "<?php echo  $id; ?>";
        console.log("startvideotracking- ", playcounter);
        if(playcounter == 0 || (playcounter % 3) == 0){
            console.log("startvideotracking updated at - ", playcounter);
        }
    }

    function onPlayProgress111(data) {
        // console.log(data);
        var vsec= data.seconds;
        secondvideoplayed = vsec;
        percentvideoplayed = data.percent;
        startvideotracking();
        playcounter++;
        var duration= data.duration;
        var src = $('iframe').attr('src'); 
        var modleid = "<?php echo  $id; ?>";
        console.log("vsec- ", vsec);
        if(vsec >= (duration * 0.9)){
        } else {
            console.log("not completed");
        }
    }




    /****  Start YouTube tracking  ********/
    var trackedMoodlePlayers = [];
    var youtubeCompletionSent = false;
    var youtubeTrackingTimer = null;
    var moodleYouTubeObserver = null;

    function initializeMoodleYouTubeTracking() {
        document.querySelectorAll('.video-js.vjs-youtube').forEach(function(el) {
            var player = el.player;

            if (
                !player ||
                typeof player.currentTime !== 'function' ||
                typeof player.duration !== 'function' ||
                typeof player.on !== 'function' ||
                el.dataset.moodleCompletionTracking === '1'
            ) {
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

            player.on('playing', function() {
                // console.log('[Moodle tracking] YouTube playing:', player.id());
                startMoodleYouTubeTracking();
            });

            player.on('pause', function() {
                item.lastTime = null;
                // console.log('[Moodle tracking] YouTube paused:', player.id());
            });

            player.on('ended', function() {
                // console.log('[Moodle tracking] YouTube ended:', player.id());

                var duration = Number(player.duration());
                var currentTime = Number(player.currentTime());

                if (
                    isFinite(duration) &&
                    duration > 0 &&
                    isFinite(currentTime)
                ) {
                    if (
                        item.lastTime !== null &&
                        currentTime > item.lastTime
                    ) {
                        var delta = currentTime - item.lastTime;

                        if (delta > 0 && delta <= 3) {
                            item.intervals.push({
                                start: item.lastTime,
                                end: Math.min(currentTime, duration)
                            });
                        }
                    }

                    item.lastTime = null;

                    trackMoodleYouTubeProgress(item);

                    // Natural video end is also a completion signal.
                    // The percentage check below still handles watched coverage.
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

            if (
                !isFinite(currentTime) ||
                !isFinite(duration) ||
                duration <= 0
            ) {
                return;
            }

            if (player.paused() && currentTime < duration) {
                item.lastTime = null;
                return;
            }

            if (item.lastTime !== null) {
                var delta = currentTime - item.lastTime;

                // Ignore seeking and large playback jumps.
                if (delta > 0 && delta <= 2.5) {
                    item.intervals.push({
                        start: item.lastTime,
                        end: Math.min(currentTime, duration)
                    });
                }
            }

            item.lastTime = currentTime;

            // Merge overlapping intervals to avoid counting replayed sections twice.
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

            // console.log('[Moodle tracking] YouTube progress:', {
            //     player: player.id(),
            //     currentTime: currentTime.toFixed(1),
            //     duration: duration.toFixed(1),
            //     watchedSeconds: watched.toFixed(1),
            //     percentage: percent.toFixed(2) + '%'
            // });

            if (percent >= 95) {
                item.completed = true;

                console.log('[YouTube] Sending completion request:', {
                    cmid: id,
                });

                checkMoodleYouTubeCompletion();
            }
        } catch (error) {
            console.error('[Moodle tracking] YouTube progress error:', error);
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

            console.log('[Moodle tracking] Sending YouTube completion update');

            $.ajax({
                url: '<?php echo $CFG->wwwroot; ?>/mod/videotime/updated_completion_status_videotime_view.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    cmid: Number(id),
                    sesskey: M.cfg.sesskey
                },
                success: function(response) {
                    // console.log('[YouTube] Completion response:', response);

                    if (response.status === 'success' && response.buttonhtml) {
                        $('#next-activity').html(response.buttonhtml);
                    } else {
                        console.warn('[YouTube] No next button returned:', {
                            cmid: id,
                            nextcmid: response.nextcmid,
                            buttonhtml: response.buttonhtml
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('[YouTube] Completion request failed:', {
                        cmid: id,
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });

                    youtubeCompletionSent = false;
                }
            });
        }
    }

    // Detect players that already exist.
    initializeMoodleYouTubeTracking();

    // Detect Video.js players created later by the activity.
    if (!moodleYouTubeObserver) {
        moodleYouTubeObserver = new MutationObserver(function() {
            initializeMoodleYouTubeTracking();
        });

        moodleYouTubeObserver.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    /* END YouTube tracking */
</script>