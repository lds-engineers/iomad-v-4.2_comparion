<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'block/learnpeek_userdashboard:view' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => [
            'student' => CAP_ALLOW,
            'user'    => CAP_ALLOW,
        ],
    ],
];