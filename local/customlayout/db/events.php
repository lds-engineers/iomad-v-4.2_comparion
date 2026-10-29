<?php
$observers = array(
    array(
        'eventname' => '\core\event\course_created',
        'includefile' => '/local/customlayout/classes/eventsobserver.php',
        'callback'  => 'local_customlayout_eventsobserver::course_created',
    ),
    array(
        'eventname' => '\core\event\course_updated',
        'includefile' => '/local/customlayout/classes/eventsobserver.php',
        'callback'  => 'local_customlayout_eventsobserver::course_updated',
    ),
    array(
        'eventname' => '\core\event\course_restored',
        'includefile' => '/local/customlayout/classes/eventsobserver.php',
        'callback'  => 'local_customlayout_eventsobserver::course_restored',
    ),
    array(
        'eventname' => '\core\event\role_assigned',
        'includefile' => '/local/customlayout/classes/eventsobserver.php',
        'callback'  => 'local_customlayout_eventsobserver::role_assigned',
    ),
    array(
        'eventname' => '\core\event\user_loggedin',
        'includefile' => '/local/customlayout/classes/eventsobserver.php',
        'callback'  => 'local_customlayout_eventsobserver::user_loggedin',
    ), 
    array(
        'eventname' => '\core\event\user_loggedinas',
        'includefile' => '/local/customlayout/classes/eventsobserver.php',
        'callback'  => 'local_customlayout_eventsobserver::user_loggedinas',
    ), 

    //event for delete chache

    array(
        'eventname' => '\core\event\course_deleted',
        'includefile' => '/local/customlayout/classes/eventsobserver.php',
        'callback'  => 'local_customlayout_eventsobserver::course_deleted',
    ),

    array(
        'eventname' => '\core\event\course_module_deleted',
        'includefile' => '/local/customlayout/classes/eventsobserver.php',
        'callback'  => 'local_customlayout_eventsobserver::course_module_deleted',
    ),

     array(
        'eventname' => '\core\event\course_section_deleted',
        'includefile' => '/local/customlayout/classes/eventsobserver.php',
        'callback'  => 'local_customlayout_eventsobserver::course_section_deleted',
    ),

    array(
        'eventname' => '\core\event\course_category_deleted',
        'includefile' => '/local/customlayout/classes/eventsobserver.php',
        'callback'  => 'local_customlayout_eventsobserver::course_category_deleted',
    ),

    

    array(
        'eventname' => '\block_iomad_company_admin\event\user_license_assigned',
        'includefile' => '/local/customlayout/classes/eventsobserver.php',
        'callback'  => 'local_customlayout_eventsobserver::user_license_assigned',
    )



);
