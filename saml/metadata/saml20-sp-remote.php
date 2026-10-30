<?php

/**
 * SAML 2.0 remote SP metadata for SimpleSAMLphp.
 *
 * See: https://simplesamlphp.org/docs/stable/simplesamlphp-reference-sp-remote
 */

/*
 * Example SimpleSAMLphp SAML 2.0 SP
 */

/*$metadata['https://sso.learnshare.com/shibboleth'] = [
//    'AssertionConsumerService' => 'https://sso.learnshare.com/471/Shibboleth.sso/SAML2/POST-SimpleSign',
    'AssertionConsumerService' => 'https://sso.learnshare.com/471/Shibboleth.sso/SAML2/POST',
    'SingleLogoutService' => 'https://sso.learnshare.com/471/Shibboleth.sso/SLO/Artifact',
    'attributes' => array(
            'NameID',
            'givenName',
            'sn',
            'uid',
            'mail',
            'Email',
            'displayName',
    ),
        
    // Define NameID format
    'NameIDFormat' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:emailAddress',
    'customid' => 'NameID',
];*/


$metadata['https://sso.learnshare.com/shibboleth'] = [
//    'AssertionConsumerService' => 'https://sso.learnshare.com/485/Shibboleth.sso/SAML2/POST-SimpleSign',
    'AssertionConsumerService' => 'https://sso.learnshare.com/485/Shibboleth.sso/SAML2/POST',
    'SingleLogoutService' => 'https://sso.learnshare.com/485/Shibboleth.sso/SLO/Artifact',
    'attributes' => array(
            'NameID',
            'givenName',
            'sn',
            'uid',
            'mail',
            'email',
            'displayName',
    ),
        
    // Define NameID format
    'NameIDFormat' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:emailAddress',
    'attributes.NameFormat' => 'urn:oasis:names:tc:SAML:2.0:attrname-format:basic',
    'customid' => 'NameID',
];


$metadata['https://stagesp.pdhstar.com/wp-content/plugins/miniorange-saml-20-single-sign-on/'] = [
    'AssertionConsumerService' => 'https://stagesp.pdhstar.com/',
    'SingleLogoutService' => 'https://stagesp.pdhstar.com/',
    'attributes' => array(
            'NameID',
            'givenName',
            'sn',
            'uid',
            'mail',
            'Email',
            'displayName',
    ),
    'NameIDFormat' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:emailAddress',
    'customid' => 'NameID',
];

/*
 * This example shows an example config that works with Google Workspace (G Suite / Google Apps) for education.
 * What is important is that you have an attribute in your IdP that maps to the local part of the email address at
 * Google Workspace. In example, if your Google account is foo.com, and you have a user that has an email john@foo.com,
 * then you must properly configure the saml:AttributeNameID authproc-filter with the name of an attribute that for
 * this user has the value of 'john'.
 */
$metadata['google.com'] = [
    'AssertionConsumerService' => 'https://www.google.com/a/g.feide.no/acs',
    'NameIDFormat' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
    'authproc' => [
      1 => [
        'class' => 'saml:AttributeNameID',
        'identifyingAttribute' => 'uid',
        'Format' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
      ],
    ],
    'simplesaml.attributes' => false,
];

$metadata['https://legacy.example.edu'] = [
    'AssertionConsumerService' => 'https://legacy.example.edu/saml/acs',
    /*
     * Currently, SimpleSAMLphp defaults to the SHA-256 hashing algorithm.
     * Uncomment the following option to use SHA-1 for signatures directed
     * at this specific service provider if it does not support SHA-256 yet.
     *
     * WARNING: SHA-1 is disallowed starting January the 1st, 2014.
     * Please refer to the following document for more information:
     * http://csrc.nist.gov/publications/nistpubs/800-131A/sp800-131A.pdf
     */
    //'signature.algorithm' => 'http://www.w3.org/2000/09/xmldsig#rsa-sha1',
];
