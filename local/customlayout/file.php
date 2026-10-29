<?php

/* 
 * This file is use to retrive files from hidden module directories
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

// disable moodle specific debug messages and any errors in output
define('NO_DEBUG_DISPLAY', true);

require_once('../../config.php');

require_once($CFG->dirroot."/lib/filelib.php");

//require_login();
if (isguestuser()) {
    print_error('noguest');
}

$filename = "";
$contenthash = optional_param('id', 0, PARAM_TEXT);
$forcedownload = optional_param('forcedownload', 0, PARAM_BOOL);

$preview = optional_param('preview', null, PARAM_ALPHANUM);

if( $contenthash == '0' ){
    // use old method
    // relative path must start with '/'
    $relativepath = get_file_argument();
    
    if (!$relativepath) {
        print_error('invalidargorconf');
    } else if ($relativepath[0] != '/') {
        print_error('pathdoesnotstartslash');
    }

    $args = explode('/', ltrim($relativepath, '/'));
    $args = explode('/', ltrim($relativepath, '/'));
    $contenthash = array_shift($args);
    $forcedownload = (int)array_shift($args);
    $filename = array_shift($args);
}

if( $forcedownload ){
    // download file
    $file_download = true;
}
else{
    $file_download = false;
}
$fs = get_file_storage();

$file = $fs->get_file_by_hash( $contenthash );

if( $file ){
    
// ========================================
// finally send the file
// ========================================
//\core\session\manager::write_close(); // Unlock session during file serving.
//send_stored_file($file, null, $CFG->filteruploadedfiles, $forcedownload);

   \core\session\manager::write_close(); // Unlock session during file serving.
    
send_stored_file($file,  0, false, $file_download , array('preview' => $preview) );

//\core\session\manager::write_close(); // Unlock session during file serving.
//send_stored_file($file, 0, false, true, array('preview' => $preview)); // force download - security first!


}
else{
    send_file_not_found();
}