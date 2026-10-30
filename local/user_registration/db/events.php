<?php
$observers = array(
	array(
        'eventname' => '\core\event\user_loggedout',
        'includefile' => '/local/user_registration/observer.php',
        'callback' => 'wordpresslogout',
        'internal' => true
    ),

    array(
        'eventname' => '\core\event\course_completed',
        'includefile' => '/local/user_registration/observer.php',
        'callback' => 'course_completed_genratecertificate',
        'internal'    => false,
        'priority'    => 9999
    )

);