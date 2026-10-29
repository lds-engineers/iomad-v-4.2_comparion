<?php
require_once('../../config.php');
require_login(); 
$id = required_param('id', PARAM_TEXT);
$redirecturl = new moodle_url('/local/customlayout/feedbackstatus.php');
$sql = "SELECT * FROM {tool_courserating_rating} WHERE " . $DB->sql_compare_text('id') . " = " . $DB->sql_compare_text(':id');
if($data = $DB->get_record_sql($sql, ['id' => $id])) {

echo "<pre>";
print_r($data);
echo "</pre>";

}
        
