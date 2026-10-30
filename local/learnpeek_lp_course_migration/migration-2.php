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
require_once($CFG->libdir . '/resourcelib.php');


require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->dirroot.'/completion/criteria/completion_criteria_self.php');
require_once($CFG->dirroot.'/completion/criteria/completion_criteria_date.php');
require_once($CFG->dirroot.'/completion/criteria/completion_criteria_unenrol.php');
require_once($CFG->dirroot.'/completion/criteria/completion_criteria_activity.php');
require_once($CFG->dirroot.'/completion/criteria/completion_criteria_duration.php');
require_once($CFG->dirroot.'/completion/criteria/completion_criteria_grade.php');
require_once($CFG->dirroot.'/completion/criteria/completion_criteria_role.php');
require_once($CFG->dirroot.'/completion/criteria/completion_criteria_course.php');
require_once $CFG->libdir.'/gradelib.php';


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
    // // print_r($migratedata);
    // // print_r($mdlcoursedata);
    // echo "======================================================";
    // // print_r($allsections);
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
                            $modulename = "page";
                            // echo "<pre>";
                            // echo "===============page=======================================";
                            // print_r($sectiondata);
                            // echo "</pre>"; 
                            // Get page module info
                            $getpagemdl = $DB->get_record("modules",array("name"=>"page"));
                            if (!$getpagemdl) {
                                throw new moodle_exception('page module not found!');
                            }
                            $pagemodulesid   = $getpagemdl->id;
                            // Create page instance
                            $pagemodule = new stdClass();
                            $pagemodule->course        = $course_id;
                            $pagemodule->name          = $sectiondata->title;
                            $pagemodule->intro         = '';
                            $pagemodule->introformat   = 1;
                            $pagemodule->content       = $sectiondata->description;
                            $pagemodule->contentformat = 1;
                            $pagemodule->display       = 5;
                            $pagemodule->section       = $sectionid;
                            $pagemodule->module        = $pagemodulesid;
                            $pagemodule->modulename    = "page";
                            $pagemodule->visible       = 1;
                            $pagemodule->visibleoncoursepage = 1;
                            $pagemoduledata = add_moduleinfo($pagemodule, $mdlcoursedata);
                            $cmid = $pagemoduledata->coursemodule;
                            // Enable completion tracking
                            $DB->set_field('course_modules', 'completion', 2, ['id' => $cmid]);
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
                        $quiz->rightanswerimmediately   = 1;
                        $quiz->overallfeedbackimmediately   = 1;
                        $quiz->attemptopen   = 1;
                        $quiz->correctnessopen   = 1;
                        $quiz->correctnessopen   = 1;
                        $quiz->marksopen   = 1;
                        $quiz->specificfeedbackopen   = 1;
                        $quiz->generalfeedbackopen   = 1;
                        $quiz->overallfeedbackopen   = 1;
                        $quiz->rightansweropen   = 1;
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
                        $lastactivityid = 0;

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
                            $fractionPerCorrect = $correctCount > 0 ? 1 / $correctCount : 0;

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
                            $qtype = question_bank::get_qtype($question->qtype);
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

    // ------------------- Add Rating Activity -----------------// 
    $getfeedbackmdl = $DB->get_record("modules", ["name" => "courserating"], '*', MUST_EXIST);
    $feedback = new stdClass();
    $feedback->course        = $course_id;
    $feedback->name          = "Feedback";
    $feedback->intro         = "";
    $feedback->introformat   = 1;
    $feedback->section       = $sectionid;
    $feedback->module        = $getfeedbackmdl->id;
    $feedback->modulename    = "courserating";
    $feedback->visible       = 1;
    $feedback->visibleoncoursepage = 1;
    $feedback->page_after_submit = '';
    $feedback->site_after_submit = '';
    $feedback->timemodified = time();


    // Activity completion
    $feedback->completion           = 2; 
    $feedback->completionview       = 1;
    $feedback->completionsubmit     = 1;

    $feedbackmoduledata = add_moduleinfo($feedback, $mdlcoursedata);
    $feedbackcmid = $feedbackmoduledata->coursemodule;

    if ($previous_cmid) {
        $lastactivityid = $previous_cmid;
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

        $DB->set_field('course_modules', 'availability', json_encode($availability), ['id' => $feedbackcmid]);
    }

    // -------------- End ---------------------- //

    // ------------------- Add Custom Certificate Activity -----------------//

    $getcertmdl = $DB->get_record("modules", ["name" => "customcert"], '*', MUST_EXIST);

    $cert = new stdClass();
    $cert->course        = $course_id;
    $cert->name          = "Course Certificate";
    $cert->intro         = "";
    $cert->introformat   = 1;
    $cert->section       = $sectionid; // same section as feedback
    $cert->module        = $getcertmdl->id;
    $cert->modulename    = "customcert";
    $cert->visible       = 1;
    $cert->visibleoncoursepage = 1;

    // Required fields for customcert
    $cert->templateid    = 1; // IMPORTANT: replace with your actual template ID
    $cert->emailstudents = 0;
    $cert->emailteachers = 0;
    $cert->emailothers   = '';
    $cert->savecert      = 1;
    $cert->delivery      = 0;

    // Activity completion: Student must view this activity
    $cert->completion           = 2;
    $cert->completionview       = 1;

    $certmoduledata = add_moduleinfo($cert, $mdlcoursedata);
    $certcmid = $certmoduledata->coursemodule;
    $certinstanceid = $certmoduledata->instance;
    $templateid = $DB->get_field('customcert', 'templateid', ['id' => $certinstanceid]);

    // Restrict: show only after Feedback is completed
    if ($feedbackcmid) {
        $availability = [
            "op" => "&",
            "showc" => [true],
            "c" => [
                [
                    "type" => "completion",
                    "cm" => $feedbackcmid,
                    "e" => 1
                ]
            ]
        ];

        $DB->set_field('course_modules', 'availability', json_encode($availability), ['id' => $certcmid]);
    }

// ------------------- End Custom Certificate -----------------//
// ------------------- Set Certificate Template ---------//

    $customcert_tempid = 0;
    $lp_courseid = $migratedata->lp_courseid;
    $checktemplate = "SELECT * FROM {customcert_lp_migration} WHERE FIND_IN_SET(:courseid, lp_courseid)";
    $params = ['courseid' => $lp_courseid];
    $migratedtemplate = $DB->get_record_sql($checktemplate, $params);
    if ($migratedtemplate) {
        $customcert_tempid = $migratedtemplate->customcert_tempid;
    }
    $customcert_tempid;
    if(!empty($customcert_tempid) && !empty($templateid)){
        $tid = $templateid;
        $ltid =  $customcert_tempid;


        $template = $DB->get_record('customcert_templates', ['id' => $tid], '*', MUST_EXIST);
        $template = new \mod_customcert\template($template);

        $loadtemplate = $DB->get_record('customcert_templates', ['id' => $ltid], '*', MUST_EXIST);
        $loadtemplate = new \mod_customcert\template($loadtemplate);

        if ($pages = $DB->get_records('customcert_pages', ['templateid' => $template->get_id()])) {
        foreach ($pages as $page) {
                $template->delete_page($page->id, false);
            }
        }

        // Copy the items across.
        $loadtemplate->copy_to_template($template);



        // write code here
    }

// ------------------- End Certificate Template -----------------//

// ------------------- COURSE COMPLETION SETUP ------------------- //

    require_once($CFG->libdir . '/completionlib.php');

    $courseinfo = $DB->get_record("course", array("id" => $course_id));

    $data = new stdClass();
    $data->id = $course_id;
    $data->overall_aggregation = 1;
    $data->mform_isexpanded_id_activitiescompleted = 1;
    $data->checkbox_controller1 = 0;
    $data->criteria_activity = [
        $lastactivityid => 1,
        $feedbackcmid => 1,
    ];
    $data->activity_aggregation = 1;
    $data->criteria_course = 0;
    $data->course_aggregation = 1;
    $data->criteria_date_value = time();
    $data->criteria_grade_value = 0.00000;
    $data->role_aggregation = 1;
    $data->submitbutton = 'Save changes';
    // Loop through each criteria type and run its update_config() method.
    global $COMPLETION_CRITERIA_TYPES;
    foreach ($COMPLETION_CRITERIA_TYPES as $type) {
        $class = 'completion_criteria_'.$type;
        $criterion = new $class();
        $criterion->update_config($data);
    }

    // Handle overall aggregation.
    $aggdata = array(
        'course'        => $data->id,
        'criteriatype'  => null
    );
    $aggregation = new completion_aggregation($aggdata);
    $aggregation->setMethod($data->overall_aggregation);
    $aggregation->save();

    // Handle activity aggregation.
    if (empty($data->activity_aggregation)) {
        $data->activity_aggregation = 0;
    }

    $aggdata['criteriatype'] = COMPLETION_CRITERIA_TYPE_ACTIVITY;
    $aggregation = new completion_aggregation($aggdata);
    $aggregation->setMethod($data->activity_aggregation);
    $aggregation->save();

    // Handle course aggregation.
    if (empty($data->course_aggregation)) {
        $data->course_aggregation = 0;
    }

    $aggdata['criteriatype'] = COMPLETION_CRITERIA_TYPE_COURSE;
    $aggregation = new completion_aggregation($aggdata);
    $aggregation->setMethod($data->course_aggregation);
    $aggregation->save();

    // Handle role aggregation.
    if (empty($data->role_aggregation)) {
        $data->role_aggregation = 0;
    }

    $aggdata['criteriatype'] = COMPLETION_CRITERIA_TYPE_ROLE;
    $aggregation = new completion_aggregation($aggdata);
    $aggregation->setMethod($data->role_aggregation);
    $aggregation->save();

    // Trigger an event for course module completion changed.
    $event = \core\event\course_completion_updated::create(
            array(
                'courseid' => $course_id,
                'context' => context_course::instance($course_id)
                )
            );
    $event->trigger();





//////////////new updated ===================================

// // Enable completion in course
// $courseupdate = new stdClass();
// $courseupdate->id = $course_id;
// $courseupdate->enablecompletion = 1;
// $DB->update_record('course', $courseupdate);

// // Remove old criteria (important)
// $DB->delete_records('course_completion_criteria', ['course' => $course_id]);

// // Add LAST ACTIVITY criteria
// if (!empty($lastactivityid)) {
//     $criteria1 = new stdClass();
//     $criteria1->course = $course_id;
//     $criteria1->criteriatype = COMPLETION_CRITERIA_TYPE_ACTIVITY;
//     $criteria1->moduleinstance = $lastactivityid;
//     $criteria1->timecreated = time();

//     $DB->insert_record('course_completion_criteria', $criteria1);
// }

// // Add FEEDBACK criteria
// if (!empty($feedbackcmid)) {
//     $criteria2 = new stdClass();
//     $criteria2->course = $course_id;
//     $criteria2->criteriatype = COMPLETION_CRITERIA_TYPE_ACTIVITY;
//     $criteria2->moduleinstance = $feedbackcmid;
//     $criteria2->timecreated = time();

//     $DB->insert_record('course_completion_criteria', $criteria2);
// }

// // Reset completion system
// $completion = new completion_info(get_course($course_id));
// $completion->reset_all_state(null);

// ------------------- END COURSE COMPLETION ------------------- //




    $updatedmigration = new stdClass();
    $updatedmigration->id = $id;
    $updatedmigration->status = 1;
    $updatedmigration->updated_date = time();
    $updatedmigrationdata = $DB->update_record('course_migration', $updatedmigration);
    // die();
    redirect(new moodle_url($CFG->wwwroot.'/local/learnpeek_lp_course_migration/lms_course_migration.php'),'Course migration completed successfully with completion restrictions. <a href="'.$CFG->wwwroot.'/course/view.php?id='.$course_id.'"> Course Link </a> ', null, \core\output\notification::NOTIFY_SUCCESS);
}else{
     redirect(new moodle_url($CFG->wwwroot.'/local/learnpeek_lp_course_migration/lms_course_migration.php'),'Invalid migration ID or course already migrated.', null, \core\output\notification::NOTIFY_ERROR);
}
