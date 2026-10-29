<?php
require_once('../../config.php');
require_once($CFG->libdir . '/authlib.php');

global $CFG, $DB, $USER, $PAGE;

// Collect all necessary parameters
$userid       = required_param('userid', PARAM_INT);
$usertokenkey = required_param('usertokenkey', PARAM_RAW);
$productid    = optional_param('productid', 0, PARAM_INT);
$lunchurl     = required_param('lunchurl', PARAM_RAW);
$sourceurl    = required_param('sourceurl', PARAM_RAW);

$userdata = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

$error = false;
$errormsg = '';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameoremail = required_param('usernameoremail', PARAM_RAW_TRIMMED);
    $password = required_param('password', PARAM_RAW_TRIMMED);

    $user = $DB->get_record('user', ['id' => $userid]);

    $is_match = (
        strtolower($user->username) === strtolower($usernameoremail) ||
        strtolower($user->email) === strtolower($usernameoremail)
    );

    // Try to find the Moodle user by username or email
    // $user = $DB->get_record_sql(
    //     "SELECT * FROM {user} WHERE username = ? OR email = ?",
    //     [$usernameoremail, $usernameoremail]
    // );

    if ($is_match && validate_internal_user_password($user, $password)) {
        // Login success — continue to your main SSO handler

        $newrecord = new stdClass();
        $newrecord->user_id = $userid;
        $newrecord->required_validation = 1;
        $newrecord->created_time = time();

        $insertid = $DB->insert_record('sso_validation', $newrecord);

        $continueurl = new moodle_url('/local/user_registration/access.php', [
            'accesstoken' => $userid,
            'usertokenkey' => $usertokenkey,
            'moodle_productid' => $productid,
            'verified' => 1,
            'lunchurl' => $lunchurl
        ]);
        redirect($continueurl);
        exit;
    } else {
        // Wrong credentials — show inline error
        $error = true;
        $errormsg = 'Invalid username or password. Please try again.';
    }
}

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/verifyuser/verify.php'));
$PAGE->set_pagelayout('login');
$PAGE->set_title('Verify Your Identity');
$PAGE->set_heading('Account Verification');

echo $OUTPUT->header();
?>

<style>
.verify-container {
    max-width: 420px;
    margin: 80px auto;
    background: #fff;
    padding: 30px 25px;
    border-radius: 10px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.1);
}
.verify-container h3 {
    margin-bottom: 20px;
}
.error-box {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
    padding: 10px 15px;
    border-radius: 6px;
    margin-bottom: 15px;
    text-align: left;
}
.btn-back {
    background-color: #6c757d;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 10px;
    width: 100%;
    display: block;
    text-align: center;
    text-decoration: none;
    margin-top: 10px;
}
.btn-back:hover {
    background-color: #5a6268;
    color: #fff;
}
</style>

<div class="verify-container text-center">
    <h3>Verify Your Account</h3>
    <p>Please enter your Moodle credentials to confirm your identity.</p>

    <?php if ($error): ?>
        <div class="error-box">
            <strong>Verification failed!</strong> <?php echo $errormsg; ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="userid" value="<?php echo $userid; ?>">
        <input type="hidden" name="usertokenkey" value="<?php echo $usertokenkey; ?>">
        <input type="hidden" name="productid" value="<?php echo $productid; ?>">
        <input type="hidden" name="lunchurl" value="<?php echo $lunchurl; ?>">
        <input type="hidden" name="sourceurl" value="<?php echo $sourceurl; ?>">

        <div class="form-group text-left mt-3">
            <label for="usernameoremail"><strong>Username or Email</strong></label>
            <input type="text" name="usernameoremail" id="usernameoremail" class="form-control" required>
        </div>

        <div class="form-group text-left mt-3">
            <label for="password"><strong>Password</strong></label>
            <input type="password" name="password" id="password" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary mt-4 w-100">Verify & Continue</button>
    </form>

    <?php if ($error): ?>
        <a href="<?php echo urldecode($sourceurl); ?>" class="btn-back">← Go Back to Previous Site</a>
    <?php endif; ?>
</div>

<?php
echo $OUTPUT->footer();