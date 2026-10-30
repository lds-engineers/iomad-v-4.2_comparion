<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin strings are defined here.
 *
 * @package     local_user_registration
 * @category    string
 * @copyright   2023 suneet Sharma <suneet@lds-international.in>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'User Registration/SSO';
$string['sso_shared_secret'] = 'SSO Wstoken';
$string['sso_shared_secret_desc'] = 'A secret key shared between Moodle and WordPress used to securely authenticate SSO requests.';

$string['sso_registration_disable'] = 'Disable SSO registration';
$string['sso_registration_disable_desc'] = 'When enabled, user accounts will not be created via SSO.';

$string['register_as_individual'] = 'Register as Individual';
$string['individual_text'] = 'Register as a single user to sign up for courses for yourself or another person';
$string['register_as_business'] = 'Register as Business';
$string['business_text'] = 'Register as a company to add/manage multiple employees, assign and track their training';
$string['register_your_account'] = 'REGISTER YOUR ACCOUNT';
$string['choose_account_type'] = 'CHOOSE ACCOUNT TYPE TO CREATE';

// individual_registration.php

$string['individual_account_registration'] = 'Registration Form - Individual';
$string['individual_account_registration_heading'] = 'Individual Account Registration';
$string['login_with_company_url'] = 'Login with Company URL';
$string['something_wrong'] = 'Something is wrong!';
$string['individual_account_title'] = 'Individual account';
$string['individual_account_description'] = 'Register as a single user and sign up for online training for yourself or another person. Individual account where you can sign up for single or packaged courses, download and print your Certificates of Completion, or sign up for live events/webinars and in-person courses.';
$string['register_as_business_text'] = 'If you would like to register as a business user and be able to bulk purchase and assign training to your employees, please <a href="{$a}/local/user_registration/business_registration.php">click here</a>.';


// individual_registrationform.php
$string['select_company'] = 'Select Company';
$string['create_account'] = 'Create an Account';
$string['user_name'] = 'User Name';
$string['missing_user_name'] = 'Missing User Name';
$string['user_name_regex'] = 'Must contain only letters (upper/lowercase), numbers, underscore, dash, or @';
$string['first_name'] = 'First Name';
$string['missing_first_name'] = 'Missing First Name';
$string['last_name'] = 'Last Name';
$string['missing_last_name'] = 'Missing Last Name';
$string['email_address'] = 'Email Address';
$string['missing_email'] = 'Missing Email';
$string['invalid_email'] = 'Invalid email pattern';
$string['password'] = 'Password';
$string['missing_password'] = 'Missing Password';
$string['password_min_length'] = 'Password must be at least 6 characters long';
$string['confirm_password'] = 'Confirm Password';
$string['missing_confirm_password'] = 'Missing Confirm Password';
$string['sign_up'] = 'Sign Up';
$string['username_already_registered'] = 'User name is already registered';
$string['email_already_registered'] = 'This email is already registered for your company.';
$string['passwords_not_same'] = 'Passwords should be the same.';

// business_registration.php
$string['registration_form_business'] = 'Business Registration Form';
$string['business_account_registration'] = 'Business Account Registration';
$string['login_with_company_url'] = 'Login with Company URL';
$string['access_denied'] = 'Access denied';
$string['something_wrong'] = 'Something is wrong!';
$string['business_account'] = 'Business Account';
$string['business_account_description'] = 'Register as a company administrator and be able to add/manage multiple employees, bulk purchase training courses, assign and track employee training, including yourself. You can purchase and assign single or packaged courses, download and print Certificates of Completion, or sign your team up for live events/webinars and in-person courses.';
$string['individual_account_redirect_text'] = 'If you would like to register as an individual user and sign up for online training for yourself or another person who you are registering, please';
$string['click_here'] = 'click here';

// business_registrationform.php
$string['user_company_name'] = 'Company Name';
$string['missing_company_name'] = 'Company name is required';
$string['managewhitelabeladmin'] = 'Manage White Label Admin';
$string['createwhitelabeladmin'] = 'Create White Label Admin';
$string['fullname'] = 'Full Name';
$string['email'] = 'Email';
$string['createwhitelabeladmin'] = 'Create White label Admin Account';
$string['submit'] = 'Submit';
$string['createindividualaccount'] = 'Create Individual Account';
$string['createaccountformindividual'] = 'Create Account Form - Individual';
$string['createaccountformbusiness'] = 'Create Account Form-Business';
$string['createbusinessaccount'] = 'Create Business Account'; 
$string['missing_company_name'] = 'Missing Company Name ';