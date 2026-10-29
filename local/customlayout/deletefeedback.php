<?php
require_once('../../config.php');
require_login(); 
$id = required_param('id', PARAM_TEXT);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 10, PARAM_INT);
$search = optional_param('search', '', PARAM_TEXT);
$redirecturl = new moodle_url('/local/customlayout/feedbackstatus.php',compact('page', 'perpage','search'));
$sql = "DELETE FROM {tool_courserating_rating} WHERE " . $DB->sql_compare_text('id') . " = " . $DB->sql_compare_text(':id');
if($data = $DB->execute($sql, ['id' => $id])) {
	redirect($redirecturl, 'Feedback Deleted', null, \core\output\notification::NOTIFY_SUCCESS);
} else {
	redirect($redirecturl, "Something is went wrong!", null, \core\output\notification::NOTIFY_ERROR);
}
        
