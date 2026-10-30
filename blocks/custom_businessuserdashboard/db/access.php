<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = array(

    // Control who can ADD the block
    'block/custom_businessuserdashboard:addinstance' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'companydepartmentmanager' => CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'moodle/site:manageblocks'
    ),

    //  Control who can VIEW the block
    'block/custom_businessuserdashboard:view' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'companydepartmentmanager' => CAP_ALLOW
        ),
    ),
);