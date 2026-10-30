<?php
require_once('../../config.php');
require_login();

global $DB;

$id = required_param('id', PARAM_INT);

if ($user = $DB->get_record('user', ['id' => $id], '*', IGNORE_MISSING)) {
    $user->suspended = $user->suspended ? 0 : 1;

    if ($DB->update_record('user', $user)) {
        // Success
        redirect(
            new moodle_url('/local/customlayout/userslisting.php'),
            "success",
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } else {
        // Update failed
        redirect(
            new moodle_url('/local/customlayout/userslisting.php'),
            "Error",
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }
} else {
    // User not found
    redirect(
        new moodle_url('/local/customlayout/userslisting.php'),
        'user_not_found',
        null,
        \core\output\notification::NOTIFY_WARNING
    );
}
