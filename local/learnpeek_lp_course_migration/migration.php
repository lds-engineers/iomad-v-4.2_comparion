<?php
require_once('../../config.php');
global $DB, $CFG, $PAGE, $USER;
require_once('../../lib/filelib.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->libdir.'/questionlib.php');
require_once($CFG->dirroot.'/mod/quiz/lib.php');
require_once($CFG->dirroot . '/question/editlib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

require_login();
if (!is_siteadmin()) {
    redirect(new moodle_url($CFG->wwwroot.'/my'),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
     die;
}

function isPdfUrl($url) {
    if(empty($url)){
        return false;
    }
    // Check Content-Type
    $headers = @get_headers($url, 1);
    if ($headers !== false && isset($headers['Content-Type'])) {
        $type = is_array($headers['Content-Type']) 
                ? end($headers['Content-Type']) 
                : $headers['Content-Type'];
 
        if (stripos($type, 'application/pdf') !== false) {
            return true;
        }
    }
 
    // Fallback: check first bytes of file
    $context = stream_context_create([
        'http' => ['method' => 'GET', 'header' => "Range: bytes=0-4"]
    ]);
 
    $data = @file_get_contents($url, false, $context);
 
    return $data !== false && str_starts_with($data, "%PDF-");
}
$id = required_param('migrateid', PARAM_INT);
$migratedata = $DB->get_record_sql("SELECT * FROM {course_migration} WHERE id = ? AND status = 0",array($id));
if($migratedata->courseid){
    $course_id = $migratedata->courseid;
    $mdlcoursedata = $DB->get_record("course", array("id" => $course_id));
    $coursedata = json_decode($migratedata->lp_coursedata);
    $allsections = $coursedata->sections;
    // echo "<pre>";
    // print_r($mdlcoursedata);
    // echo "======================================================";
    // print_r($allsections);
    // echo "</pre>"; 
    // die();

    $previous_cmid = null; 
    foreach ($allsections as $section) {
        if($section->id){
            if($sectdata = course_create_section($course_id, 0, false)){
                $sectdata->name  = $section->name;
                $DB->update_record("course_sections", $sectdata);

                $sectionid = $sectdata->section;
                $sectionsdata = $section->items;
                // $previous_cmid = null; 

                foreach ($sectionsdata as $sectiondata) {
                    if($sectiondata->type == "lp_lesson"){
                        $pdf_url = preg_replace('/(\.com|\.net|\.org)(?!\/)/', '$1/', $sectiondata->description);
                        $pdf_url = trim(preg_replace('/[\r\n\t\\\\ ]+/', '', $pdf_url));
                        $pdf_url = preg_replace('/\.pdf.*/i', '.pdf', $pdf_url);
                        $pdf_url = preg_match('/https?:\/\/[^\s]+/i', $pdf_url,$matches);
                        $pdf_url = $matches[0];
                        // $isPDF = preg_match('/\.pdf(\?.*)?$/i', $pdf_url);
                        $isPDF = isPdfUrl($pdf_url);

                        // echo "<pre>";
                        // // print_r($sectiondata);
                        // // echo "get url = $sectiondata->description <br>";
                        // echo "url = $pdf_url <br>";
                        // print_r($isPDF);
                        // echo "</pre>";
                        // die();
                        $description = $sectiondata->description;
                        $description = preg_match('/vimeoid="(\d+)"/i', $sectiondata->description, $matches);
                        $vimeoid = $matches[1];

                        if(!empty($vimeoid)){
                            $vimeo_url = "https://vimeo.com/".$vimeoid;
                            $modulename = "videotime";
                            // echo "<pre>";
                            // echo "===============videotime=======================================";
                            // print_r($sectiondata);
                            // echo "</pre>"; 
                            

                            // Get Videotime module info
                            $getvideotimemdl = $DB->get_record("modules",array("name"=>"videotime"));
                            if (!$getvideotimemdl) {
                                throw new moodle_exception('Videotime module not found!');
                            }
                            $videomodulesid   = $getvideotimemdl->id;
                            // Create Videotime instance
                            $videomodule = new stdClass();
                            $videomodule->course        = $course_id;
                            $videomodule->name          = $sectiondata->title;
                            $videomodule->intro         = '';
                            $videomodule->introformat   = 1;
                            $videomodule->vimeo_url     = $vimeo_url;
                            $videomodule->video_description_format  = 1;
                            $videomodule->section       = $sectionid;
                            $videomodule->module        = $videomodulesid;
                            $videomodule->modulename    = $modulename;
                            $videomodule->visible       = 1;
                            $videomodule->visibleoncoursepage = 1;
                            $videomoduledata = add_moduleinfo($videomodule, $mdlcoursedata);
                            $cmid = $videomoduledata->coursemodule;
                            // Enable completion tracking

                            $DB->set_field('course_modules', 'completion', 2, ['id' => $cmid]); 
                            // Require view to complete the activity
                            $DB->set_field('course_modules', 'completionview', 1, ['id' => $cmid]);
                            // Add availability restriction
                            if ($previous_cmid) {
                                $availability = [
                                    "op" => "&",
                                    "showc" => [true],
                                    "c" => [
                                        [
                                            "type" => "completion",
                                            "cm" => $previous_cmid,
                                            "e" => 1 // must be completed
                                        ]
                                    ]
                                ];
                                $availabilityjson = json_encode($availability);
                                $DB->set_field('course_modules', 'availability', $availabilityjson, ['id' => $cmid]);
                            }
                            $previous_cmid = $cmid;
                        }elseif(!empty($isPDF)){
                            $tempfile = download_file_content($pdf_url);

                            if (!$tempfile) {
                                throw new moodle_exception('Could not download PDF: ' . $pdf_url);
                            }

                            $filename = basename(parse_url($pdf_url, PHP_URL_PATH));

                            $fs = get_file_storage();

                            $getresourcemdl = $DB->get_record("modules", ["name" => "resource"]);
                            if (!$getresourcemdl) {
                                throw new moodle_exception('Resource module not found!');
                            }
                            $resourcemoduleid = $getresourcemdl->id;

                            $resource = new stdClass();
                            $resource->course        = $course_id;
                            $resource->name          = $sectiondata->title;
                            $resource->intro         = '';
                            $resource->introformat   = 1;
                            $resource->section       = $sectionid;
                            $resource->module        = $resourcemoduleid;
                            $resource->modulename    = "resource";
                            $resource->visible       = 1;
                            $resource->visibleoncoursepage = 1;
                            $resource->display       = RESOURCELIB_DISPLAY_EMBED;
                            // $resource->popupwidth    = 0;
                            // $resource->popupheight   = 0;

                            // $resource->files = [$storedfile->get_id()];

                            $resourcedata = add_moduleinfo($resource, $mdlcoursedata);
                            $cmid = $resourcedata->coursemodule;

                            $DB->set_field('course_modules', 'completion', 2, ['id' => $cmid]);
                            $DB->set_field('course_modules', 'completionview', 1, ['id' => $cmid]);

                            $file_record = array(
                                'contextid' => context_module::instance($cmid)->id,
                                'component' => 'mod_resource',
                                'filearea'  => 'content',
                                'itemid'    => 0,
                                'filepath'  => '/',
                                'filename'  => $filename,
                                'userid'    => $USER->id,
                                'timecreated' => time(),
                                'timemodified' => time(),
                            );
                            $storedfile = $fs->create_file_from_string($file_record, $tempfile);

                            if (!$storedfile) {
                                throw new moodle_exception('Failed to store PDF file in Moodle');
                            }

                            if ($previous_cmid) {
                                $availability = [
                                    "op" => "&",
                                    "showc" => [true],
                                    "c" => [
                                        [
                                            "type" => "completion",
                                            "cm" => $previous_cmid,
                                            "e" => 1
                                        ]
                                    ]
                                ];
                                $DB->set_field('course_modules', 'availability', json_encode($availability), ['id' => $cmid]);
                            }
                            $previous_cmid = $cmid;
                        }else{
                            // $modulename = "page";
                            // // echo "<pre>";
                            // // echo "===============page=======================================";
                            // // print_r($sectiondata);
                            // // echo "</pre>"; 
                            // // Get page module info
                            // $getpagemdl = $DB->get_record("modules",array("name"=>"page"));
                            // if (!$getpagemdl) {
                            //     throw new moodle_exception('page module not found!');
                            // }
                            // $pagemodulesid   = $getpagemdl->id;
                            // // Create page instance
                            // $pagemodule = new stdClass();
                            // $pagemodule->course        = $course_id;
                            // $pagemodule->name          = $sectiondata->title;
                            // $pagemodule->intro         = '';
                            // $pagemodule->introformat   = 1;
                            // $pagemodule->content       = $sectiondata->description;
                            // $pagemodule->contentformat = 1;
                            // $pagemodule->display       = 5;
                            // $pagemodule->section       = $sectionid;
                            // $pagemodule->module        = $pagemodulesid;
                            // $pagemodule->modulename    = "page";
                            // $pagemodule->visible       = 1;
                            // $pagemodule->visibleoncoursepage = 1;
                            // $pagemoduledata = add_moduleinfo($pagemodule, $mdlcoursedata);
                            // $cmid = $pagemoduledata->coursemodule;
                            // // Enable completion tracking
                            // $DB->set_field('course_modules', 'completion', 2, ['id' => $cmid]);
                            // $DB->set_field('course_modules', 'completionview', 1, ['id' => $cmid]); 
                            // // Add availability restriction
                            // if ($previous_cmid) {
                            //     $availability = [
                            //         "op" => "&",
                            //         "showc" => [true],
                            //         "c" => [
                            //             [
                            //                 "type" => "completion",
                            //                 "cm" => $previous_cmid,
                            //                 "e" => 1 // must be completed
                            //             ]
                            //         ]
                            //     ];
                            //     $availabilityjson = json_encode($availability);
                            //     $DB->set_field('course_modules', 'availability', $availabilityjson, ['id' => $cmid]);
                            // }
                            // $previous_cmid = $cmid;

                            //===================update code for mp3 ==========//

                            // // Create a Moodle Page activity.
                            // $modulename = "page";

                            // // Get the original lesson description.
                            // $description = $sectiondata->description;

                            // // --------------------------------------------------
                            // // 1. Resize images to fit the screen.
                            // // --------------------------------------------------
                            // $description = preg_replace_callback(
                            //     '/<img\b[^>]*>/i',
                            //     function ($matches) {
                            //         $img = $matches[0];

                            //         $responsive_style = 'max-height:70vh;max-width:100%;height:auto;width:auto;';

                            //         if (preg_match('/\bstyle\s*=\s*(["\'])(.*?)\1/i', $img, $style_match)) {
                            //             // Preserve existing styles and append responsive styles.
                            //             $existing_style = rtrim(trim($style_match[2]), ';');
                            //             $new_style = ($existing_style !== '' ? $existing_style . ';' : '')
                            //                 . $responsive_style;

                            //             $img = preg_replace(
                            //                 '/\bstyle\s*=\s*(["\'])(.*?)\1/i',
                            //                 'style="' . $new_style . '"',
                            //                 $img,
                            //                 1
                            //             );
                            //         } else {
                            //             // Add responsive styles if no style attribute exists.
                            //             $img = preg_replace(
                            //                 '/<img\b/i',
                            //                 '<img style="' . $responsive_style . '"',
                            //                 $img,
                            //                 1
                            //             );
                            //         }

                            //         return $img;
                            //     },
                            //     $description
                            // );

                            // // Extract the MP3 URL from the WordPress audio shortcode.
                            // $mp3url = '';
                            // if (preg_match(
                            //     '/\[audio\b[^\]]*mp3=["\']([^"\']+\.mp3(?:\?[^"\']*)?)["\'][^\]]*\](?:\[\/audio\])?/i',
                            //     $description,
                            //     $matches
                            // )) {
                            //     $mp3url = trim($matches[1]);
                            // }

                            // // Get Page module information.
                            // $getpagemdl = $DB->get_record(
                            //     "modules",
                            //     ["name" => "page"],
                            //     '*',
                            //     MUST_EXIST
                            // );

                            // $pagemodule = new stdClass();
                            // $pagemodule->course = $course_id;
                            // $pagemodule->name = $sectiondata->title;
                            // $pagemodule->intro = '';
                            // $pagemodule->introformat = FORMAT_HTML;
                            // $pagemodule->content = $description;
                            // $pagemodule->contentformat = FORMAT_HTML;
                            // $pagemodule->display = 5;
                            // $pagemodule->section = $sectionid;
                            // $pagemodule->module = $getpagemdl->id;
                            // $pagemodule->modulename = "page";
                            // $pagemodule->visible = 1;
                            // $pagemodule->visibleoncoursepage = 1;

                            // // --------------------------------------------------
                            // // 4. Download MP3 and replace the shortcode.
                            // // --------------------------------------------------
                            // $filename = '';
                            // $audiofile = false;

                            // if (!empty($mp3url)) {
                            //     // Download the MP3 file from WordPress.
                            //     $audiofile = download_file_content($mp3url);

                            //     if ($audiofile === false || $audiofile === '') {
                            //         throw new moodle_exception(
                            //             'Could not download MP3 file: ' . $mp3url
                            //         );
                            //     }

                            //     // Extract and sanitize the original filename.
                            //     $filename = basename(parse_url($mp3url, PHP_URL_PATH));
                            //     $filename = clean_filename($filename);

                            //     if (
                            //         empty($filename) ||
                            //         strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'mp3'
                            //     ) {
                            //         throw new moodle_exception(
                            //             'Invalid MP3 filename: ' . $mp3url
                            //         );
                            //     }

                            //     // Replace the WordPress audio shortcode with an HTML5 audio player.
                            //     $audioplayer = '<audio controls autoplay preload="metadata" style="width:100%;">'
                            //         . '<source src="@@PLUGINFILE@@/' . s($filename)
                            //         . '" type="audio/mpeg">'
                            //         . 'Your browser does not support the audio element.'
                            //         . '</audio>';

                            //     $description = preg_replace(
                            //         '/\[audio\b[^\]]*\](?:\[\/audio\])?/i',
                            //         $audioplayer,
                            //         $description,
                            //         1
                            //     );

                            //     $pagemodule->content = $description;
                            // }

                            // // Create the Moodle Page activity.
                            // $pagemoduledata = add_moduleinfo($pagemodule, $mdlcoursedata);
                            // $cmid = $pagemoduledata->coursemodule;

                            // // Store the MP3 in this Page activity's Moodle file storage.
                            // if (!empty($mp3url)) {
                            //     $fs = get_file_storage();
                            //     $context = context_module::instance($cmid);

                            //     $file_record = [
                            //         'contextid' => $context->id,
                            //         'component' => 'mod_page',
                            //         'filearea' => 'content',
                            //         'itemid' => 0,
                            //         'filepath' => '/',
                            //         'filename' => $filename,
                            //         'userid' => $USER->id,
                            //         'timecreated' => time(),
                            //         'timemodified' => time(),
                            //     ];

                            //     $storedfile = $fs->create_file_from_string(
                            //         $file_record,
                            //         $audiofile
                            //     );

                            //     if (!$storedfile) {
                            //         throw new moodle_exception(
                            //             'Failed to store MP3 file in Moodle.'
                            //         );
                            //     }
                            // }

                            // // Enable completion tracking.
                            // $DB->set_field(
                            //     'course_modules',
                            //     'completion',
                            //     COMPLETION_TRACKING_AUTOMATIC,
                            //     ['id' => $cmid]
                            // );

                            // $DB->set_field(
                            //     'course_modules',
                            //     'completionview',
                            //     1,
                            //     ['id' => $cmid]
                            // );

                            // // Require the previous activity to be completed first.
                            // if ($previous_cmid) {
                            //     $availability = [
                            //         'op' => '&',
                            //         'showc' => [true],
                            //         'c' => [
                            //             [
                            //                 'type' => 'completion',
                            //                 'cm' => $previous_cmid,
                            //                 'e' => 1
                            //             ]
                            //         ]
                            //     ];

                            //     $DB->set_field(
                            //         'course_modules',
                            //         'availability',
                            //         json_encode($availability),
                            //         ['id' => $cmid]
                            //     );
                            // }

                            // $previous_cmid = $cmid;

                            ///==============================================
                            // Create a Moodle Page activity.
                            $modulename = "page";

                            // Get the original lesson description.
                            $description = $sectiondata->description;

                            // Keep downloaded assets unique by source URL.
                            $assets = [];

                            // --------------------------------------------------
                            // 1. Helper: download files from approved domains.
                            // --------------------------------------------------
                            $downloadAndPrepareAsset = function ($url) use (&$assets) {
                                $url = html_entity_decode(trim($url), ENT_QUOTES | ENT_HTML5, 'UTF-8');

                                if (!filter_var($url, FILTER_VALIDATE_URL)) {
                                    return false;
                                }

                                $host = strtolower((string) parse_url($url, PHP_URL_HOST));

                                // Download only files hosted on the specified domains.
                                $allowed_hosts = [
                                    'pdhstar.com',
                                    'www.pdhstar.com',
                                ];

                                if (!in_array($host, $allowed_hosts, true)) {
                                    return false;
                                }

                                // Reuse the download for the exact same source URL.
                                if (isset($assets[$url])) {
                                    return $assets[$url]['filename'];
                                }

                                // Get the original filename.
                                $path = (string) parse_url($url, PHP_URL_PATH);
                                $original_filename = clean_filename(basename($path));

                                if (empty($original_filename) || $original_filename === '.') {
                                    $original_filename = 'asset';
                                }

                                // Add a URL hash to prevent different files with the same
                                // original filename from colliding in Moodle file storage.
                                $filename = substr(sha1($url), 0, 12) . '_' . $original_filename;
                                $filename = clean_filename($filename);

                                // Download the source file.
                                $filecontent = download_file_content($url);

                                if ($filecontent === false || $filecontent === '') {
                                    throw new moodle_exception(
                                        'Could not download source file: ' . $url
                                    );
                                }

                                $assets[$url] = [
                                    'filename' => $filename,
                                    'content' => $filecontent,
                                ];

                                return $filename;
                            };

                            // --------------------------------------------------
                            // 2. Extract the MP3 URL from the audio shortcode.
                            // --------------------------------------------------
                            $mp3url = '';

                            if (preg_match(
                                '/\[audio\b[^\]]*mp3=["\']([^"\']+\.mp3(?:\?[^"\']*)?)["\'][^\]]*\](?:\[\/audio\])?/i',
                                $description,
                                $matches
                            )) {
                                $mp3url = trim($matches[1]);
                            }

                            // --------------------------------------------------
                            // 3. Download the MP3 and replace its shortcode.
                            // --------------------------------------------------
                            if (!empty($mp3url)) {
                                $audiofilename = $downloadAndPrepareAsset($mp3url);

                                if ($audiofilename === false) {
                                    throw new moodle_exception(
                                        'The MP3 URL is not hosted on an approved domain: ' . $mp3url
                                    );
                                }

                                $audioplayer = '<audio controls autoplay preload="metadata" style="width:100%;">'
                                    . '<source src="@@PLUGINFILE@@/' . s($audiofilename)
                                    . '" type="audio/mpeg">'
                                    . 'Your browser does not support the audio element.'
                                    . '</audio>';

                                $description = preg_replace(
                                    '/\[audio\b[^\]]*\](?:\[\/audio\])?/i',
                                    $audioplayer,
                                    $description,
                                    1
                                );
                            }

                            // --------------------------------------------------
                            // 4. Download matching files referenced in HTML.
                            //    Handles src, href and poster attributes.
                            // --------------------------------------------------
                            $description = preg_replace_callback(
                                '/\b(src|href|poster)\s*=\s*(["\'])(.*?)\2/i',
                                function ($matches) use ($downloadAndPrepareAsset) {
                                    $attribute = $matches[1];
                                    $quote = $matches[2];
                                    $url = html_entity_decode(
                                        $matches[3],
                                        ENT_QUOTES | ENT_HTML5,
                                        'UTF-8'
                                    );

                                    $filename = $downloadAndPrepareAsset($url);

                                    // Keep URLs from other domains unchanged.
                                    if ($filename === false) {
                                        return $matches[0];
                                    }

                                    return $attribute . '=' . $quote
                                        . '@@PLUGINFILE@@/' . s($filename)
                                        . $quote;
                                },
                                $description
                            );

                            // --------------------------------------------------
                            // 5. Resize images to fit the screen.
                            // --------------------------------------------------
                            $description = preg_replace_callback(
                                '/<img\b[^>]*>/i',
                                function ($matches) {
                                    $img = $matches[0];

                                    $responsive_style =
                                        'max-height:70vh;max-width:100%;height:auto;width:auto;';

                                    if (preg_match('/\bstyle\s*=\s*(["\'])(.*?)\1/i', $img, $style_match)) {
                                        $existing_style = rtrim(trim($style_match[2]), ';');

                                        $new_style = ($existing_style !== ''
                                            ? $existing_style . ';'
                                            : '') . $responsive_style;

                                        $img = preg_replace(
                                            '/\bstyle\s*=\s*(["\'])(.*?)\1/i',
                                            'style="' . $new_style . '"',
                                            $img,
                                            1
                                        );
                                    } else {
                                        $img = preg_replace(
                                            '/<img\b/i',
                                            '<img style="' . $responsive_style . '"',
                                            $img,
                                            1
                                        );
                                    }

                                    return $img;
                                },
                                $description
                            );

                            // --------------------------------------------------
                            // 6. Configure the Moodle Page activity.
                            // --------------------------------------------------
                            $getpagemdl = $DB->get_record(
                                'modules',
                                ['name' => 'page'],
                                '*',
                                MUST_EXIST
                            );

                            $pagemodule = new stdClass();
                            $pagemodule->course = $course_id;
                            $pagemodule->name = $sectiondata->title;
                            $pagemodule->intro = '';
                            $pagemodule->introformat = FORMAT_HTML;
                            $pagemodule->content = $description;
                            $pagemodule->contentformat = FORMAT_HTML;
                            $pagemodule->display = 5;
                            $pagemodule->section = $sectionid;
                            $pagemodule->module = $getpagemdl->id;
                            $pagemodule->modulename = 'page';
                            $pagemodule->visible = 1;
                            $pagemodule->visibleoncoursepage = 1;

                            // --------------------------------------------------
                            // 7. Create the Moodle Page activity.
                            // --------------------------------------------------
                            $pagemoduledata = add_moduleinfo($pagemodule, $mdlcoursedata);
                            $cmid = $pagemoduledata->coursemodule;

                            // --------------------------------------------------
                            // 8. Store downloaded assets in Moodle file storage.
                            // --------------------------------------------------
                            if (!empty($assets)) {
                                $fs = get_file_storage();
                                $context = context_module::instance($cmid);

                                foreach ($assets as $asset) {
                                    $file_record = [
                                        'contextid' => $context->id,
                                        'component' => 'mod_page',
                                        'filearea' => 'content',
                                        'itemid' => 0,
                                        'filepath' => '/',
                                        'filename' => $asset['filename'],
                                        'userid' => $USER->id,
                                        'timecreated' => time(),
                                        'timemodified' => time(),
                                    ];

                                    // Avoid duplicate creation if this filename already exists
                                    // in this activity's content file area.
                                    if (!$fs->file_exists(
                                        $context->id,
                                        'mod_page',
                                        'content',
                                        0,
                                        '/',
                                        $asset['filename']
                                    )) {
                                        $storedfile = $fs->create_file_from_string(
                                            $file_record,
                                            $asset['content']
                                        );

                                        if (!$storedfile) {
                                            throw new moodle_exception(
                                                'Failed to store file in Moodle: ' . $asset['filename']
                                            );
                                        }
                                    }
                                }
                            }

                            // --------------------------------------------------
                            // 9. Enable completion tracking.
                            // --------------------------------------------------
                            $DB->set_field(
                                'course_modules',
                                'completion',
                                COMPLETION_TRACKING_AUTOMATIC,
                                ['id' => $cmid]
                            );

                            $DB->set_field(
                                'course_modules',
                                'completionview',
                                1,
                                ['id' => $cmid]
                            );

                            // --------------------------------------------------
                            // 10. Require the previous activity to be completed.
                            // --------------------------------------------------
                            if (!empty($previous_cmid)) {
                                $availability = [
                                    'op' => '&',
                                    'showc' => [true],
                                    'c' => [
                                        [
                                            'type' => 'completion',
                                            'cm' => $previous_cmid,
                                            'e' => 1,
                                        ],
                                    ],
                                ];

                                $DB->set_field(
                                    'course_modules',
                                    'availability',
                                    json_encode($availability),
                                    ['id' => $cmid]
                                );
                            }

                            // Save the current activity for the next restriction.
                            $previous_cmid = $cmid;
                        }
                    }
                    if($sectiondata->type == "lp_quiz"){
                        $quiz_id = $sectiondata->id;
                        $wp_siteurl = "https://pdhstar.com";
                        $migartionurl = rtrim($wp_siteurl, '/') . '/wp-json/custom/v1/lpcoursemigrationquiz';
                        $request_data = [
                            'lp_quiz_id'    => $quiz_id,
                            'moodlewsrestformat'=> 'json'
                        ];
                        $curl = new curl();
                        $response = $curl->post($migartionurl, $request_data);
                        $result = json_decode($response);
                        $quiz_data = $result->quiz_data;
                        if($result->quiz_passing_grade){
                            $gradepass = $result->quiz_passing_grade / 10;
                        }else{
                            $gradepass = $result->quiz_passing_grade;
                        }
                        $quizmodule = $DB->get_record('modules', ['name' => 'quiz'], '*', MUST_EXIST);
                        $quiz = new stdClass();
                        $quiz->course               = $course_id;
                        $quiz->name                 = $result->title;
                        $quiz->intro                = "";
                        $quiz->introformat          = 1;
                        $quiz->visible              = 1;
                        $quiz->preferredbehaviour   = 'deferredfeedback';
                        $quiz->module               = $quizmodule->id;
                        $quiz->modulename           = 'quiz';
                        $quiz->grade                = 10;
                        $quiz->gradepass            = $gradepass;
                        $quiz->questionsperpage     = 10;
                        $quiz->navmethod            = 'sequential';
                        $quiz->quizpassword         = '';
                        $quiz->subnet               = '';
                        $quiz->browsersecurity      = '-';
                        $quiz->section              = $sectionid;
                        $quiz->visibleoncoursepage  = 1;
                        // add completion restriction
                        $quiz->completion           = 2;
                        $quiz->completionview       = 1;
                        $quiz->completionusegrade   = 1;

                        // === Add Review Options ===
                        $quiz->attemptimmediately   = 1;
                        $quiz->correctnessimmediately   = 1;
                        $quiz->marksimmediately   = 1;
                        $quiz->specificfeedbackimmediately   = 1;
                        $quiz->generalfeedbackimmediately   = 1;
                        // $quiz->rightanswerimmediately   = 1;
                        $quiz->rightanswerimmediately = 0;
                        $quiz->overallfeedbackimmediately   = 1;
                        $quiz->attemptopen   = 1;
                        $quiz->correctnessopen   = 1;
                        $quiz->correctnessopen   = 1;
                        $quiz->marksopen   = 1;
                        $quiz->specificfeedbackopen   = 1;
                        $quiz->generalfeedbackopen   = 1;
                        $quiz->overallfeedbackopen   = 1;
                        // $quiz->rightansweropen   = 1;
                        $quiz->rightansweropen   = 0;

                        // === Correct Review Options Setup ===

                        // // Show only attempt (optional)
                        // $quiz->reviewattempt = QUIZ_REVIEW_IMMEDIATELY | QUIZ_REVIEW_OPEN;

                        // // Hide correctness (removes "Your answer is incorrect")
                        // $quiz->reviewcorrectness = 0;

                        // // Hide marks (optional)
                        // $quiz->reviewmarks = 0;

                        // // Hide all feedback
                        // $quiz->reviewspecificfeedback = 0;
                        // $quiz->reviewgeneralfeedback = 0;
                        // $quiz->reviewoverallfeedback = 0;

                        // // IMPORTANT: Hide "Right answer"
                        // $quiz->reviewrightanswer = 0;
                        // === End Review Options ===
                        if (!empty($gradepass)) {
                            // add completion restriction if pass grade are set
                            $quiz->completionpassgrade  = 1;

                            if($gradepass < 10){
                                $boundary = ($gradepass * 10) . '%';   // For quiz grade = 10
                                // $quiz->boundary_repeats = 1;
                                $quiz->feedbacktext = [
                                    0 => [
                                        'text' => "You have scored above the minimum required and you have passed the quiz and can proceed to the next module.",
                                        'format' => 1
                                    ],
                                    1 => [
                                        'text' => "You have scored below the minimum required and you have failed the quiz. You will have to redo the quiz until you achieve a passing score of {$boundary}. There is no limit as to how many times you can attempt the quiz.",
                                        'format' => 1
                                    ]
                                ];
                                $quiz->feedbackboundaries = [
                                    0 => $boundary
                                ];
                            }
                        }
                        // === Add Review Options (Immediately + Later While Open) ===
                        // $allowed = QUIZ_REVIEW_IMMEDIATELY | QUIZ_REVIEW_OPEN;

                        // $quiz->reviewattempt          = $allowed;
                        // $quiz->reviewcorrectness      = $allowed;
                        // $quiz->reviewmarks            = $allowed;
                        // $quiz->reviewspecificfeedback = $allowed;
                        // $quiz->reviewgeneralfeedback  = $allowed;
                        // $quiz->reviewrightanswer      = $allowed;
                        // $quiz->reviewoverallfeedback  = $allowed;
                        // ===========================================================
                        $quizmoduledata = add_moduleinfo($quiz, $mdlcoursedata);
                        $cmid = $quizmoduledata->coursemodule;
                        // Add availability restriction
                        if ($previous_cmid) {
                            $availability = [
                                "op" => "&",
                                "showc" => [true],
                                "c" => [
                                    [
                                        "type" => "completion",
                                        "cm" => $previous_cmid,
                                        "e" => 1 // must be completed
                                    ]
                                ]
                            ];
                            $availabilityjson = json_encode($availability);
                            $DB->set_field('course_modules', 'availability', $availabilityjson, ['id' => $cmid]);
                        }
                        $previous_cmid = $cmid;

                        $quizid = $quizmoduledata->instance;
                        $quizdata = $DB->get_record('quiz', ['id' => $quizid], '*', MUST_EXIST);
                        $params['cmid'] =$quizmoduledata->coursemodule;
                        question_build_edit_resources('categories', null, $params);
                        $quizcontext = context_module::instance($params['cmid']);


                        $cat = question_get_default_category($quizcontext->id);
                        $categoryid = $cat->id;
                        $categorycontextid = $cat->contextid;
                        $qcounter = 1;
                        foreach ($quiz_data as $item) {
                            $question = new stdClass();
                            $question->category   = $categoryid;
                            $question->name       = "Q".$qcounter;
                            $question->questiontext = $item->title;
                            $question->questiontextformat = FORMAT_HTML;
                            $question->generalfeedbackformat = FORMAT_HTML;
                            $question->defaultmark = $item->question_mark;
                            $question->hidden = 0;
                            $question->createdby = $USER->id;
                            $question->status = \core_question\local\bank\question_version_status::QUESTION_STATUS_READY;
                            $question->answernumbering = 'none';

                            if ($item->question_type == 'single_choice') {
                                $question->qtype = "multichoice";
                                $question->single = 1; // radio button
                            }
                            else if ($item->question_type == 'multi_choice') {
                                $question->qtype = "multichoice";
                                $question->single = 0; // checkbox
                            }
                            else if ($item->question_type == 'true_or_false') {
                                $question->qtype = "truefalse";
                            }else{
                                continue;
                            }
                            $form = new stdClass();
                            $form->category = $categoryid . ',' . $categorycontextid;
                            $form->name = $question->name;
                            $form->questiontext['text'] = $question->questiontext;
                            $form->status = $question->status;
                            $form->defaultmark = $question->defaultmark;
                            // $form->generalfeedback = $question->generalfeedback;
                            $form->qtype = $question->qtype;
                            $form->showstandardinstruction = 1;
                            $form->courseid = $course_id;
                            $form->cmid = $params['cmid'];
                            $form->modulename = 'mod_quiz';

                            // Prepare answers
                            if ($form->qtype == "multichoice") {
                                $form->answer = [];
                                $form->fraction = [];
                                $form->feedback = [];
                                $form->feedbackformat = [];
                                $form->correctfeedback = [];
                                $form->partiallycorrectfeedback = [];
                                $form->incorrectfeedback = [];

                            }else if($form->qtype == "truefalse"){
                                $form->feedbacktrue = [];
                                $form->feedbackfalse = [];

                            }
                            $form->generalfeedback = [];

                            // multy option are currect 
                            $correctCount = 0;
                            foreach ($item->answers as $ans) {
                                if (!empty($ans->is_true)) {
                                    $correctCount++;
                                }
                            }
                            // $fractionPerCorrect = $correctCount > 0 ? 1 / $correctCount : 0;
                            // suneet updated 02feb2026
                            $isSingle = ($form->qtype === 'multichoice' && !empty($question->single));
                            if ($isSingle) {
                                $fractionPerCorrect = 1.0;   //  REQUIRED
                            } else {
                                $fractionPerCorrect = $correctCount > 0 ? 1 / $correctCount : 0;
                            }

                            foreach ($item->answers as $ans) {
                                if ($form->qtype == "multichoice") {
                                    $answerobj = new stdClass();
                                    $answerobj->text = trim($ans->title);
                                    $answerobj->format = FORMAT_HTML;
                                    $form->answer[] = (array)$answerobj;
                                    if (!empty($ans->is_true)) {
                                        $form->fraction[] = $fractionPerCorrect;
                                    } else {
                                        $form->fraction[] = 0.0;
                                    }

                                    $feedbackobj = new stdClass();
                                    $feedbackobj->text = '';
                                    $feedbackobj->format = FORMAT_HTML;
                                    $form->feedback[] = (array)$feedbackobj;

                                    $generalfeedbackkobj = new stdClass();
                                    $generalfeedbackkobj->text = '';
                                    $generalfeedbackkobj->format = FORMAT_HTML;
                                    $form->generalfeedback = (array)$generalfeedbackkobj;

                                    $correctfeedbackkobj = new stdClass();
                                    $correctfeedbackkobj->text = 'Your answer is correct.';
                                    $correctfeedbackkobj->format = FORMAT_HTML;
                                    $form->correctfeedback = (array)$correctfeedbackkobj;

                                    $partiallycorrectfeedbackobj = new stdClass();
                                    $partiallycorrectfeedbackobj->text = 'Your answer is partially correct.';
                                    $partiallycorrectfeedbackobj->format = FORMAT_HTML;
                                    $form->partiallycorrectfeedback = (array)$partiallycorrectfeedbackobj;

                                    $incorrectfeedbackobj = new stdClass();
                                    $incorrectfeedbackobj->text = 'Your answer is incorrect.';
                                    $incorrectfeedbackobj->format = FORMAT_HTML;
                                    $form->incorrectfeedback = (array)$incorrectfeedbackobj;
                                    $form->feedbackformat[] = FORMAT_HTML;

                                    $form->single = $question->single ?? null;  // only multichoice
                                    $form->shuffleanswers = 0;
                                    $form->answernumbering = $question->answernumbering;

                                }else if ($question->qtype == "truefalse") {
                                    $generalfeedbackkobj = new stdClass();
                                    $generalfeedbackkobj->text = '';
                                    $generalfeedbackkobj->format = FORMAT_HTML;
                                    $form->generalfeedback = (array)$generalfeedbackkobj;

                                    $feedbacktrueobj = new stdClass();
                                    $feedbacktrueobj->text = '';
                                    $feedbacktrueobj->format = FORMAT_HTML;
                                    $form->feedbacktrue = (array)$feedbacktrueobj;

                                    $feedbackfalseobj = new stdClass();
                                    $feedbackfalseobj->text = '';
                                    $feedbackfalseobj->format = FORMAT_HTML;
                                    $form->feedbackfalse = (array)$feedbackfalseobj;

                                    // $form->correctanswer = $ans->is_true ? 1 : 0;
                                    if($ans->is_true){
                                        $form->correctanswer = ($ans->order == 1) ? 1 : 0;
                                    }
                                    $form->penalty = 1;
                                }
                            }

                            // Save Question in Question Bank
                            // echo "<pre>";
                            // print_r($question);
                            $qtype = question_bank::get_qtype($question->qtype);
                            // print_r($question);
                            // echo "</pre>";
                            $newquestion = $qtype->save_question($question, $form);
                            question_bank::notify_question_edited($newquestion->id);
                            quiz_add_quiz_question($newquestion->id, $quizdata, 0);
                            quiz_delete_previews($quiz);
                            quiz_update_sumgrades($quiz);
                            $qcounter++;
                        }
                    }
                }
                rebuild_course_cache($course_id, true);
            }
        }
    }
    $updatedmigration = new stdClass();
    $updatedmigration->id = $id;
    $updatedmigration->status = 1;
    $updatedmigration->updated_date = time();
    $updatedmigrationdata = $DB->update_record('course_migration', $updatedmigration);
    redirect(new moodle_url($CFG->wwwroot.'/local/learnpeek_lp_course_migration/lms_course_migration.php'),'Course migration completed successfully with completion restrictions. <a href="'.$CFG->wwwroot.'/course/view.php?id='.$course_id.'"> Course Link </a> ', null, \core\output\notification::NOTIFY_SUCCESS);
}else{
     redirect(new moodle_url($CFG->wwwroot.'/local/learnpeek_lp_course_migration/lms_course_migration.php'),'Invalid migration ID or course already migrated.', null, \core\output\notification::NOTIFY_ERROR);
}
