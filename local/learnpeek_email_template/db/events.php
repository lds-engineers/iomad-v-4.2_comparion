<?php

$observers = array(
    
    array(
        'eventname' => '\core\event\course_completed',
        'includefile' => 'local/learnpeek_email_template/observer.php',
        'callback' => 'course_completed_email_template',
        'internal' => false 
     ),
    array(
        'eventname'   => '\core\event\user_enrolment_created',
        'includefile' => 'local/learnpeek_email_template/observer.php',
        'callback'    => 'user_enrollment_status',
        'internal'    => false,
        'priority'    => 9999,
    )
);

?>