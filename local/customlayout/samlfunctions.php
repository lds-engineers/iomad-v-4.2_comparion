<?php
require_once("../../config.php");
function saml_user_login(
    $username,
    $password,
) {
    global $CFG, $DB, $PAGE, $SESSION;
    require_once("$CFG->libdir/authlib.php");
    if ($user = get_complete_user_data('username', $username, $CFG->mnet_localhost_id)) {
        // we have found the user

    } else if (!empty($CFG->authloginviaemail)) {
        if ($email = clean_param($username, PARAM_EMAIL)) {
            $select = "mnethostid = :mnethostid AND LOWER(email) = LOWER(:email) AND deleted = 0";
            $params = array('mnethostid' => $CFG->mnet_localhost_id, 'email' => $email);
            $users = $DB->get_records_select('user', $select, $params, 'id', 'id', 0, 2);
            if (count($users) === 1) {
                // Use email for login only if unique.
                $user = reset($users);
                $user = get_complete_user_data('id', $user->id);
                $username = $user->username;
            }
            unset($users);
        }
    }
    $authsenabled = get_enabled_auth_plugins();

    if ($user) {
        // Use manual if auth not set.
        $auth = empty($user->auth) ? 'manual' : $user->auth;

        if (in_array($user->auth, $authsenabled)) {
            $authplugin = get_auth_plugin($user->auth);
            $authplugin->pre_user_login_hook($user);
        }

        // IOMAD: if we have a SESSION for the company
        // Check that it matches the user's actual company.
        $companysuspended = false;
        if (!empty($SESSION->currenteditingcompany)) {
            if (company::check_user_suspended($SESSION->currenteditingcompany, $user->id)) {
                $companysuspended = true;
            }
        }

        if (!empty($user->suspended) ||
            $companysuspended) {
            $failurereason = AUTH_LOGIN_SUSPENDED;

            // Trigger login failed event.
            $event = \core\event\user_login_failed::create(array('userid' => $user->id,
                    'other' => array('username' => $username, 'reason' => $failurereason)));
            $event->trigger();
            error_log('[client '.getremoteaddr()."]  $CFG->wwwroot  Suspended Login:  $username  ".$_SERVER['HTTP_USER_AGENT']);
			// var_dump("52");
			// die;
            return false;
        }
        if ($auth=='nologin' or !is_enabled_auth($auth)) {
            // Legacy way to suspend user.
            $failurereason = AUTH_LOGIN_SUSPENDED;

            // Trigger login failed event.
            $event = \core\event\user_login_failed::create(array('userid' => $user->id,
                    'other' => array('username' => $username, 'reason' => $failurereason)));
            $event->trigger();
            error_log('[client '.getremoteaddr()."]  $CFG->wwwroot  Disabled Login:  $username  ".$_SERVER['HTTP_USER_AGENT']);
			// var_dump("64");
			// die;
            return false;
        }
        $auths = array($auth);

    } else {
        // Check if there's a deleted record (cheaply), this should not happen because we mangle usernames in delete_user().
        if ($DB->get_field('user', 'id', array('username' => $username, 'mnethostid' => $CFG->mnet_localhost_id,  'deleted' => 1))) {
            $failurereason = AUTH_LOGIN_NOUSER;

            // Trigger login failed event.
            $event = \core\event\user_login_failed::create(array('other' => array('username' => $username,
                    'reason' => $failurereason)));
            $event->trigger();
            error_log('[client '.getremoteaddr()."]  $CFG->wwwroot  Deleted Login:  $username  ".$_SERVER['HTTP_USER_AGENT']);
			// var_dump("81");
			// die;
            return false;
        }

        // User does not exist.
        $auths = $authsenabled;
        $user = new stdClass();
        $user->id = 0;
    }

    if ($ignorelockout) {
        // Some other mechanism protects against brute force password guessing, for example login form might include reCAPTCHA
        // or this function is called from a SSO script.
    } else if ($user->id) {
        // Verify login lockout after other ways that may prevent user login.
        if (login_is_lockedout($user)) {
            $failurereason = AUTH_LOGIN_LOCKOUT;

            // Trigger login failed event.
            $event = \core\event\user_login_failed::create(array('userid' => $user->id,
                    'other' => array('username' => $username, 'reason' => $failurereason)));
            $event->trigger();

            error_log('[client '.getremoteaddr()."]  $CFG->wwwroot  Login lockout:  $username  ".$_SERVER['HTTP_USER_AGENT']);
            $SESSION->loginerrormsg = get_string('accountlocked', 'admin');
			// var_dump("107");
			// die;

            return false;
        }
    } else {
        // We can not lockout non-existing accounts.
    }

    foreach ($auths as $auth) {
        $authplugin = get_auth_plugin($auth);

        // On auth fail fall through to the next plugin.
        if (!$authplugin->user_login($username, $password)) {
            continue;
        }

        // Before performing login actions, check if user still passes password policy, if admin setting is enabled.
        if (!empty($CFG->passwordpolicycheckonlogin)) {
            $errmsg = '';
            $passed = check_password_policy($password, $errmsg, $user);
            if (!$passed) {
                // First trigger event for failure.
                $failedevent = \core\event\user_password_policy_failed::create_from_user($user);
                $failedevent->trigger();

                // If able to change password, set flag and move on.
                if ($authplugin->can_change_password()) {
                    // Check if we are on internal change password page, or service is external, don't show notification.
                    $internalchangeurl = new moodle_url('/login/change_password.php');
                    if (!($PAGE->has_set_url() && $internalchangeurl->compare($PAGE->url)) && $authplugin->is_internal()) {
                        \core\notification::error(get_string('passwordpolicynomatch', '', $errmsg));
                    }
                    set_user_preference('auth_forcepasswordchange', 1, $user);
                } else if ($authplugin->can_reset_password()) {
                    // Else force a reset if possible.
                    \core\notification::error(get_string('forcepasswordresetnotice', '', $errmsg));
                    redirect(new moodle_url('/login/forgot_password.php'));
                } else {
                    $notifymsg = get_string('forcepasswordresetfailurenotice', '', $errmsg);
                    // If support page is set, add link for help.
                    if (!empty($CFG->supportpage)) {
                        $link = \html_writer::link($CFG->supportpage, $CFG->supportpage);
                        $link = \html_writer::tag('p', $link);
                        $notifymsg .= $link;
                    }

                    // If no change or reset is possible, add a notification for user.
                    \core\notification::error($notifymsg);
                }
            }
        }

        // Successful authentication.
        if ($user->id) {
            // User already exists in database.
            if (empty($user->auth)) {
                // For some reason auth isn't set yet.
                $DB->set_field('user', 'auth', $auth, array('id' => $user->id));
                $user->auth = $auth;
            }

            // If the existing hash is using an out-of-date algorithm (or the legacy md5 algorithm), then we should update to
            // the current hash algorithm while we have access to the user's password.
            update_internal_user_password($user, $password);

            if ($authplugin->is_synchronised_with_external()) {
                // Update user record from external DB.
                $user = update_user_record_by_id($user->id);
            }
        } else {
            // The user is authenticated but user creation may be disabled.
            if (!empty($CFG->authpreventaccountcreation)) {
                $failurereason = AUTH_LOGIN_UNAUTHORISED;

                // Trigger login failed event.
                $event = \core\event\user_login_failed::create(array('other' => array('username' => $username,
                        'reason' => $failurereason)));
                $event->trigger();

                error_log('[client '.getremoteaddr()."]  $CFG->wwwroot  Unknown user, can not create new accounts:  $username  ".
                        $_SERVER['HTTP_USER_AGENT']);
				// var_dump("182");
				// die;

                return false;
            } else {
                // $user = create_user_record($username, $password, $auth);
            }
        }

        $authplugin->sync_roles($user);

        foreach ($authsenabled as $hau) {
            $hauth = get_auth_plugin($hau);
            $hauth->user_authenticated_hook($user, $username, $password);
        }

        if (empty($user->id)) {
        	// var_dump("196");
        	// die;
            $failurereason = AUTH_LOGIN_NOUSER;
            // Trigger login failed event.
            $event = \core\event\user_login_failed::create(array('other' => array('username' => $username,
                    'reason' => $failurereason)));
            $event->trigger();
            return false;
        }

        if (!empty($user->suspended)) {
        	// var_dump("207");
        	// die;
            // Just in case some auth plugin suspended account.
            $failurereason = AUTH_LOGIN_SUSPENDED;
            // Trigger login failed event.
            $event = \core\event\user_login_failed::create(array('userid' => $user->id,
                    'other' => array('username' => $username, 'reason' => $failurereason)));
            $event->trigger();
            error_log('[client '.getremoteaddr()."]  $CFG->wwwroot  Suspended Login:  $username  ".$_SERVER['HTTP_USER_AGENT']);
            return false;
        }

        login_attempt_valid($user);
        $failurereason = AUTH_LOGIN_OK;
    	// var_dump("221");
    	// die;
        return $user;
    }

    // Failed if all the plugins have failed.
    if (debugging('', DEBUG_ALL)) {
        error_log('[client '.getremoteaddr()."]  $CFG->wwwroot  Failed Login:  $username  ".$_SERVER['HTTP_USER_AGENT']);
    }

    if ($user->id) {
        login_attempt_failed($user);
        $failurereason = AUTH_LOGIN_FAILED;
        // Trigger login failed event.
        $event = \core\event\user_login_failed::create(array('userid' => $user->id,
                'other' => array('username' => $username, 'reason' => $failurereason)));
        $event->trigger();
    } else {
        $failurereason = AUTH_LOGIN_NOUSER;
        // Trigger login failed event.
        $event = \core\event\user_login_failed::create(array('other' => array('username' => $username,
                'reason' => $failurereason)));
        $event->trigger();
    }

    return false;
}