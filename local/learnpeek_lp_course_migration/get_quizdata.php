<?php
require_once('../../config.php');
require_once($CFG->libdir.'/questionlib.php');
require_once($CFG->dirroot.'/mod/quiz/lib.php');
require_once($CFG->dirroot.'/course/modlib.php');
require_once($CFG->dirroot . '/question/editlib.php');
global $DB, $CFG, $PAGE, $USER;

// $courseid = 17643;
$quiz_id = 15070;
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

$courseid = 384;
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$quizmodule = $DB->get_record('modules', ['name' => 'quiz'], '*', MUST_EXIST);
$quiz = new stdClass();
$quiz->course               = $courseid;
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
$quiz->section              = 1;
$quiz->visibleoncoursepage  = 1;
// add completion restriction
$quiz->completion           = 2;
$quiz->completionview       = 1;
$quiz->completionusegrade   = 1;

if (!empty($gradepass)) {
    // add completion restriction if pass grade are set
    $quiz->completionpassgrade  = 1;
    if($gradepass > 10){
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

echo "<pre>result";
print_r($result);
// echo "=========quiz==================";
// print_r($quiz);
// echo "=========course==================";
// print_r($course);
echo "</pre>";
die();

$quizmoduledata = add_moduleinfo($quiz, $course);

$quizid = $quizmoduledata->instance;
$quizdata = $DB->get_record('quiz', ['id' => $quizid], '*', MUST_EXIST);
// $quizid = 122;
$params['cmid'] =$quizmoduledata->coursemodule;
// $params['cmid'] =4225;
question_build_edit_resources('categories', null, $params);
$quizcontext = context_module::instance($params['cmid']);


$cat = question_get_default_category($quizcontext->id);
$categoryid = $cat->id;
$categorycontextid = $cat->contextid;
// echo "<pre>";
// print_r($quizcontext);
// print_r($cat);
// echo "</pre>";
// die();
    
$qcounter = 1;
foreach ($quiz_data as $item) {
    $question = new stdClass();
    $question->category   = $categoryid;
    $question->name       = "Q".$qcounter;
    $question->questiontext = $item->title;
    $question->questiontextformat = FORMAT_HTML;
    // $question->generalfeedback = "";
    $question->generalfeedbackformat = FORMAT_HTML;
    $question->defaultmark = $item->question_mark;
    $question->hidden = 0;
    $question->createdby = $USER->id;
    $question->status = \core_question\local\bank\question_version_status::QUESTION_STATUS_READY;
    $question->answernumbering = 'none';
    // $question->correctfeedbackformat = 1;

    
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
    $form->courseid = $courseid;
    $form->cmid = $params['cmid'];
    $form->modulename = 'mod_quiz';

    ////////////////////////////////////////////////////////
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
    // if ($form->qtype != "multichoice") {
    //     continue;
    // }

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
            // $answerobj->itemid = rand(10000000, 999999999);
            $form->answer[] = (array)$answerobj;
            // $question->answer[] = $ans->title;

            // $question->fraction[] = $ans->is_true ? 1 : 0;
            if (!empty($ans->is_true)) {
                $form->fraction[] = $fractionPerCorrect;  // 0.5, 0.3333, etc.
            } else {
                $form->fraction[] = 0.0;
            }

            $feedbackobj = new stdClass();
            $feedbackobj->text = '';
            $feedbackobj->format = FORMAT_HTML;
            // $feedbackobj->itemid = rand(10000000, 999999999);
            $form->feedback[] = (array)$feedbackobj;

            $generalfeedbackkobj = new stdClass();
            $generalfeedbackkobj->text = '';
            $generalfeedbackkobj->format = FORMAT_HTML;
            // $generalfeedbackkobj->itemid = rand(10000000, 999999999);
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
            // Moodle requires exactly 2 answers: True / False
            // $question->answer[] = $ans->title;
            // // $question->fraction[] = $ans->is_true ? 1 : 0;
            // $question->feedbackformat[] = FORMAT_HTML;

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

            


            if($ans->is_true){
                $form->correctanswer = ($ans->title == "True") ? 1 : 0;
            }
            $form->penalty = 1;
        }
    }
    ////////////////////////////////////////////////////////    

    // Save Question in Question Bank
    $qtype = question_bank::get_qtype($question->qtype);

    echo "<pre>";
    echo "===================question==================<br>";
    print_r($question);
    echo "===================fromform==================<br>";
    print_r($form);
    echo "</pre>";
    die();

    $newquestion = $qtype->save_question($question, $form);
    question_bank::notify_question_edited($newquestion->id);
    quiz_add_quiz_question($newquestion->id, $quizdata, 0);
    quiz_delete_previews($quiz);
    quiz_update_sumgrades($quiz);
    $qcounter++;
}
echo "QUIZ CREATED SUCCESSFULLY!";

