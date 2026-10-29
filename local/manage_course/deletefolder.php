<?php
require_once('../../config.php');
global $DB, $CFG, $PAGE, $USER;
require_once($CFG->dirroot . '/mod/zoom/lib.php');
require_once($CFG->dirroot . '/mod/zoom/locallib.php');
require_once($CFG->dirroot . '/mod/zoom/classes/webservice.php');
require_once($CFG->dirroot . '/course/modlib.php');


function deleteFolderAndFiles($folderPath) {
    if (!is_dir($folderPath)) {
        return false;
    }

    $items = scandir($folderPath);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $fullPath = $folderPath . DIRECTORY_SEPARATOR . $item;

        if (is_dir($fullPath)) {
            deleteFolderAndFiles($fullPath);
        } else {
            unlink($fullPath);
        }
    }

    return rmdir($folderPath);
}



$path1 = $CFG->cachedirect . '/cache';
$path2 = $CFG->cachedirect . '/localcache';
 echo "path1: $path1\n";
 echo "path2: $path2\n";
// Delete the folders
if (deleteFolderAndFiles($path1)) {
    echo "Deleted: $path1\n";
} else {
    echo "Failed to delete: $path1\n";
}

if (deleteFolderAndFiles($path2)) {
    echo "Deleted: $path2\n";
} else {
    echo "Failed to delete: $path2\n";
}


?>
