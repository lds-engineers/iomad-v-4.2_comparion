<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) { // Ensure only admins see this
    $settings = new admin_settingpage(
        'local_user_registration_settings',
        get_string('pluginname', 'local_user_registration')
    );

    // Shared secret field
    $settings->add(new admin_setting_configtext(
        'local_user_registration/sso_shared_secret',
        get_string('sso_shared_secret', 'local_user_registration'),
        get_string('sso_shared_secret_desc', 'local_user_registration'),
        '', // default value
        PARAM_RAW_TRIMMED 
    ));

    // SSO Registration stop checkbox
     $settings->add(new admin_setting_configcheckbox(
        'local_user_registration/sso_registration_disable',
        get_string('sso_registration_disable', 'local_user_registration'),
        get_string('sso_registration_disable_desc', 'local_user_registration'),
        0
    ));

    $ADMIN->add('localplugins', $settings);
}