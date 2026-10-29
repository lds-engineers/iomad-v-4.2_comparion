<?php

$config = [
    /*
     * When multiple authentication sources are defined, you can specify one to use by default
     * in order to authenticate users. In order to do that, you just need to name it "default"
     * here. That authentication source will be used by default then when a user reaches the
     * SimpleSAMLphp installation from the web browser, without passing through the API.
     *
     * If you already have named your auth source with a different name, you don't need to change
     * it in order to use it as a default. Just create an alias by the end of this file:
     *
     * $config['default'] = &$config['your_auth_source'];
     */

    // This is a authentication source which handles admin authentication.
    'admin' => [
        // The default is to use core:AdminPassword, but it can be replaced with
        // any authentication source.

        'core:AdminPassword',
        // 'sandeeplds:Sandeep@2024XYZ',
    ],

    'example-wordpress' => [
        // 'sqlauth:SQL',
        'customauth:WordPressAuth',

        // Database connection details
        'dsn' => 'mysql:host=127.0.0.1;port=3306;dbname=learnpeek;charset=utf8',
        'username' => 'root',
        'password' => 'P!ssw0rd$131',

        // Correct SQL query
        'query' => 'SELECT id, id as uid, username, email, password, firstname, lastname 
                FROM mdl_user 
                WHERE username = :username or email = :username',

        'attributes' => array(
            'NameID' => 'email',      // Map SAML NameID to the user's email
            'employeeID' => 'id',     // Map employee ID to a custom attribute
            'givenName' => 'firstname', // Map givenName to user's first name
            'sn' => 'lastname',       // Map sn to user's last name
            'Email' => 'email',       // Map sn to user's last name
            'mail' => 'email',       // Map sn to user's last name
        ), 
    ],
];
