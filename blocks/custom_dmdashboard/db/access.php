<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = array(

    // Control who can ADD the block
    'block/custom_dmdashboard:addinstance' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'companyreporter' => CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'moodle/site:manageblocks'
    ),

    //  Control who can VIEW the block
    'block/custom_dmdashboard:view' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'companyreporter' => CAP_ALLOW
        ),
    ),
);