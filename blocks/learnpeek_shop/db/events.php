<?php

$observers = array(
	array(
        'eventname' => '\core\event\group_created',
        'includefile' => 'blocks/learnpeek_shop/observer.php',
        'callback' => 'add_group_seats',
        'internal' => false 
    ),
    array(
        'eventname' => '\core\event\group_updated',
        'includefile' => 'blocks/learnpeek_shop/observer.php',
        'callback' => 'update_group_seats',
        'internal' => false 
    ),
    array(
        'eventname' => '\core\event\user_loggedin',
        'includefile' => 'blocks/learnpeek_shop/observer.php',
        'callback'  => 'user_loggedin',
        'internal' => false
    ),
    array(
        'eventname' => '\core\event\user_password_updated',
        'includefile' => 'blocks/learnpeek_shop/observer.php',
        'callback'  => 'user_password_updated',
        'internal' => false
    ),
);