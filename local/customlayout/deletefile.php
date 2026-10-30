<?php
require_once('../../config.php');
require_login(); 
$type = required_param('type', PARAM_TEXT);
$redirecturl = new moodle_url('/local/customlayout/studentslisting.php');
if ($type === "docs") {
    $id = required_param('id', PARAM_INT);
    $imgid = required_param('imgid', PARAM_INT);
    $sql = "UPDATE {lcl_scan_face_recognition}
               SET document = NULL
             WHERE " . $DB->sql_compare_text('id') . " = " . $DB->sql_compare_text(':id');
    $data = $DB->execute($sql, ['id' => $id]);
    if ($file = $DB->get_record('files', ['id' => $imgid])) {
        $fs = get_file_storage();
        $stored_file = $fs->get_file_by_id($imgid);
        if ($stored_file) {
            $stored_file->delete();
            redirect($redirecturl, 'Document deleted', null, \core\output\notification::NOTIFY_SUCCESS);
        } else {
            redirect($redirecturl, "File not found storage", null, \core\output\notification::NOTIFY_ERROR);
        }
    } else {
        redirect($redirecturl, "File not found db", null, \core\output\notification::NOTIFY_ERROR);
    }

} elseif ($type === "img") {
    $id = required_param('id', PARAM_INT);
    $filename = required_param('image', PARAM_TEXT);
    $sql = "UPDATE {lcl_scan_face_recognition}
               SET image = NULL
             WHERE " . $DB->sql_compare_text('id') . " = " . $DB->sql_compare_text(':id');
    $DB->execute($sql, ['id' => $id]);
    $filepath = $CFG->dirroot . "/local/scan_face_recognition/temp/" . basename($filename);
    if (file_exists($filepath)) {
        if (unlink($filepath)) {
            redirect($redirecturl, "Image deleted", null, \core\output\notification::NOTIFY_SUCCESS);
        } else {
            redirect($redirecturl, 'Image delete failed', null, \core\output\notification::NOTIFY_ERROR);
        }
    } else {
        redirect($redirecturl, 'Image not found', null, \core\output\notification::NOTIFY_WARNING);
    }
} else {
    redirect($redirecturl, 'Invalid type', null, \core\output\notification::NOTIFY_WARNING);
}
