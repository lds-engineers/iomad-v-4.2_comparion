<?php
require_once('header.php');
require_once($CFG->libdir . "/formslib.php");
require_once("$CFG->dirroot/local/user_registration/user_registration_email.php");
$PAGE->requires->jquery();
$currentHostname = $_SERVER['HTTP_HOST']; // updated variable name
$companyRecord = $DB->get_record_sql("SELECT * FROM {company} WHERE hostname LIKE ?",[$currentHostname]);

$is_hide = false;
if($companyRecord){
    // Get saved registration settings.
    $settings = $DB->get_record_sql(
        "SELECT companies
        FROM {registration_settings}
    ORDER BY id ASC
        LIMIT 1"
    );

    if ($settings && !empty($settings->companies)) {

        $blockedcompanies = json_decode($settings->companies, true);

        if (
            is_array($blockedcompanies) &&
            in_array($companyRecord->shortname, $blockedcompanies, true)
        ) {
            $is_hide = true;
        }
    }
}
if ($is_hide) {
    redirect(new moodle_url($CFG->wwwroot.'/my'),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
    die;
}
$cartData = $DB->get_record_sql("
    SELECT 
        gci.*, 
        COUNT(gci.id) AS cart_item_count, 
        gc.id AS guest_cart_id 
    FROM {guest_cart} gc
    LEFT JOIN {guest_cart_items} gci 
        ON gc.id = gci.guestcartid
    WHERE gc.id = :id
", ['id' => $SESSION->basketid]);



if ($companyRecord) {
    $companyId = $companyRecord->id;
} else {
    redirect(
        $CFG->wwwroot . '/login/index.php',
        'Login with Company URL',
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}
 
if (empty($cartData->cart_item_count) || (int)$cartData->cart_item_count <= 0) {
    redirect(new moodle_url($CFG->wwwroot . '/courses/'),'Cart is empty',null,\core\output\notification::NOTIFY_WARNING);
}

$html = '
<style>

form.mform label {
    font-size: 18px;
    font-weight: normal;
}

form.mform input, 
form.mform select,
form.mform textarea {
    width: 100% !important;
    border-radius: 0px;
    max-width: 100%;
    border-color: #e0e0e0;
}

form.mform input, 
form.mform select{
 min-height:45px;
}

form.mform input[type="submit"]{
  padding:5px 20px;

}
form.mform input[type="radio"] {
  width: auto !important;
}
</style>
</style>


<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
    <div style="text-align:right; flex:1;">
        <p style="margin:0;">Returning customer? 
            <a href="'. $CFG->wwwroot .'/login/index.php" style="color:#0066cc; text-decoration:none;">Click here to login</a>
        </p>  
    </div>
    <div style="margin-left:20px;">
        <a href="'. $CFG->wwwroot .'/courses/guestcartitems.php">
            <button style="background:#eee; border:none; padding:8px 15px; border-radius:5px; font-weight:bold; cursor:pointer;">
                Back to Cart
            </button>
        </a>
    </div>
</div>



';



class guestcheckout extends moodleform {
    public function definition() {
        global $CFG, $DB, $SESSION,$OUTPUT;

        $mform = $this->_form;

        // Determine user type
        $checksheats = $DB->get_record_sql("SELECT MAX(license_allocation) AS max_seat FROM {guest_cart_items} WHERE guestcartid = ?", [$SESSION->basketid]);
        if($checksheats && $checksheats->max_seat > 1){
            $user_type = 'business';
        }else{
            $user_type = 'individual';

        }
        $strrequired = get_string('required');

        // Account Details Header
        $mform->addElement('html', '<div class="head_info"><h3 style="padding:10px 0; margin:22px 0;border-radius:10px;color:#000;">Create Account</h3></div>');

        // Username
        $mform->addElement('text', 'create_user_name', 'User Name');
        $mform->addRule('create_user_name', 'Missing User Name', 'required', null, 'client');
        // $mform->addRule('create_user_name','Must contain only lowercase letters, numbers, underscore, dash, or @','regex','/^[a-z0-9_\-@]+$/','client',false,false);
        $mform->addRule('create_user_name','Must contain only letters (upper/lowercase), numbers, underscore, dash, or @','regex','/^[A-Za-z0-9_\-@]+$/','client',false,false);
        $mform->setType('create_user_name', PARAM_TEXT);
        
        // First Name
        $mform->addElement('text', 'create_first_name', 'First Name');
        $mform->addRule('create_first_name', 'Missing First Name', 'required', null, 'client');
        $mform->setType('create_first_name', PARAM_TEXT);

        // Last Name
        $mform->addElement('text', 'create_last_name', 'Last Name');
        $mform->addRule('create_last_name', 'Missing Last Name', 'required', null, 'client');
        $mform->setType('create_last_name', PARAM_TEXT);


        // Email
        $mform->addElement('text', 'create_email', 'Email', 'maxlength="100" size="50"');
        $mform->addRule('create_email', 'Missing Email', 'required', null, 'client');
         $mform->addRule('create_email', 'Invalid email pattern', 'email', null, 'client', false, false);
        $mform->setType('create_email', PARAM_EMAIL);

        // Account Type Dropdown
        // $mform->addElement('select', 'create_accounttype', 'Account Type', [
        //     'individual' => 'Individual',
        //     'business'   => 'Business',
        // ]);
        // $mform->addRule('create_accounttype', 'Please select an account type', 'required', null, 'client');

        $accounttypearray = array();
        $accounttypearray[] = $mform->createElement('radio', 'create_accounttype', '', 'Individual', 'individual');
        $accounttypearray[] = $mform->createElement('radio', 'create_accounttype', '', 'Business', 'business');
        $mform->addGroup($accounttypearray, 'accounttypegrp', 'Account Type: '.$user_type, array(' '), false);
        $mform->addRule('create_accounttype', 'Please select an account type', 'required', null, 'client');
        $mform->setDefault('create_accounttype', $user_type);

        // Disable radio buttons 
        if ($user_type === 'business') {
            $mform->freeze('accounttypegrp');
            // $mform->addElement('html', $OUTPUT->notification('You have selected multiple seats, so your account type has been automatically set to <strong>Business</strong>..',\core\output\notification::NOTIFY_WARNING));
            $mform->addElement('html', '<div class="alert alert-warning">You have selected multiple seats, so your account type has been automatically set to <strong>Business</strong></div>');
            $mform->addElement('hidden', 'create_accounttype', $user_type);
        }

        // Company field
        $mform->addElement('text', 'company_name', get_string('company', 'block_iomad_company_admin'), 'maxlength="40" size="50"');
        $mform->setType('company_name', PARAM_TEXT);
        $mform->hideIf('company_name', 'create_accounttype', 'eq', 'individual');

        // Password
        $mform->addElement('password', 'create_password', 'Password');
        $mform->addRule('create_password', 'Missing Password', 'required', null, 'client');
        $mform->setType('create_password', PARAM_RAW);

        // Checkout Details Header
        $mform->addElement('html', '<div class="head_info"><h3 style="padding:10px 0; margin:22px 0;border-radius:10px;color:#000;">Billing Details</h3></div>');

        // Billing First Name
        $mform->addElement('text', 'invoice_first_name', 'First Name');
        $mform->addRule('invoice_first_name', 'Missing First Name', 'required', null, 'client');
        $mform->setType('invoice_first_name', PARAM_TEXT);

        // Billing Last Name
        $mform->addElement('text', 'invoice_last_name', 'Last Name');
        $mform->addRule('invoice_last_name', 'Missing Last Name', 'required', null, 'client');
        $mform->setType('invoice_last_name', PARAM_TEXT);

        // Billing Email
        // $mform->addElement('text', 'invoice_email', 'Email', 'maxlength="100" size="50"');
        // $mform->addRule('invoice_email', 'Missing Email', 'required', null, 'client');
        // $mform->setType('invoice_email', PARAM_EMAIL);


        // Address
        $mform->addElement('text', 'invoice_address', get_string('address'), 'maxlength="70" size="50"');
        $mform->addRule('invoice_address', 'Missing Address', 'required', null, 'client');
        $mform->setType('invoice_address', PARAM_TEXT);

        // Address 2
        $mform->addElement('text', 'invoice_address2', '', 'maxlength="70" size="50"');
        $mform->setType('invoice_address2', PARAM_TEXT); 

        // City
        $mform->addElement('text', 'invoice_city', 'City/town');
        $mform->addRule('invoice_city', 'Missing City', 'required', null, 'client');
        $mform->setType('invoice_city', PARAM_TEXT);

        // Zip Postcode
        $mform->addElement('text', 'invoice_zipcode', 'Postcode');
        $mform->addRule('invoice_zipcode', 'Missing Postcode Code', 'required', null, 'client');
        $mform->setType('invoice_zipcode', PARAM_TEXT);
        
        // Country
        $choices = ['' => get_string('selectacountry') . '...'] + get_string_manager()->get_list_of_countries();
        $mform->addElement('select', 'invoice_country', "Select a country", $choices);
        $mform->addRule('invoice_country', 'Please Select a country', 'required', null, 'client');

        // State text (default)
        $mform->addElement('text', 'invoice_state', 'State');
        // $mform->addRule('invoice_state', $strrequired, 'required', null, 'client');changed
        $mform->setType('invoice_state', PARAM_TEXT);
        
        // State as dropdown (for US).
        $usstates = [
            ''   => get_string('selectastate', 'block_iomad_commerce'),
            'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas',
            'CA' => 'California', 'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware',
            'FL' => 'Florida', 'GA' => 'Georgia', 'HI' => 'Hawaii', 'ID' => 'Idaho',
            'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa', 'KS' => 'Kansas',
            'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine', 'MD' => 'Maryland',
            'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi',
            'MO' => 'Missouri', 'MT' => 'Montana', 'NE' => 'Nebraska', 'NV' => 'Nevada',
            'NH' => 'New Hampshire', 'NJ' => 'New Jersey', 'NM' => 'New Mexico', 'NY' => 'New York',
            'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio', 'OK' => 'Oklahoma',
            'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island', 'SC' => 'South Carolina',
            'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah',
            'VT' => 'Vermont', 'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia',
            'WI' => 'Wisconsin', 'WY' => 'Wyoming'
        ];

        $mform->addElement('select', 'invoice_state_select', get_string('state', 'block_iomad_commerce'), $usstates); //
        $mform->setType('invoice_state_select', PARAM_ALPHANUM);
        // $mform->addRule('invoice_state_select', $strrequired, 'required', null, 'client'); 

        // Show/hide rules.
        $mform->hideIf('invoice_state_select', 'invoice_country', 'neq', 'US');
        $mform->hideIf('invoice_state', 'invoice_country', 'eq', 'US');

        // Phone
        $mform->addElement('text', 'invoice_phone', get_string('phone'), 'maxlength="20" size="50"');
        $mform->setType('invoice_phone', PARAM_TEXT);
        $mform->addRule('invoice_phone', 'Missing Phone Number', 'required', null, 'client');

        // Additional Information
        $mform->addElement('textarea', 'ordernotes', 'Order Notes (optional)', 'wrap="virtual" rows="4" cols="50"');
        $mform->setType('ordernotes', PARAM_TEXT);
        $mform->addElement('html', '<script>$(`#id_invoice_state_select_label ~ .form-label-addon`).html(`<div class="text-danger" title="Required"><i class="icon fa fa-exclamation-circle text-danger fa-fw" title="Required" role="img" aria-label="Required"></i></div>`);</script>');

        // Buttons
        $this->add_action_buttons(true, "Continue");
    }
    public function get_data() {
        $data = parent::get_data();
        if (!$data) {
            return false;
        }

        if ($data->invoice_country === 'US') {
            $data->invoice_state = $data->invoice_state_select;
        } else {
            $data->invoice_state = $data->invoice_state;
        }

        if (!empty($data->invoice_address2)) {
            $data->invoice_address = $data->invoice_address . '<br> ' . $data->invoice_address2;
        }

        return $data;
    }
    function validation($data, $files) {
        global $CFG, $DB;
        $errors = [];
        $current_host = $_SERVER['HTTP_HOST'];
        $getcompany = $DB->get_record_sql("SELECT id,hostname FROM {company} WHERE hostname LIKE ?", array($current_host));
        $companyid = $getcompany->id;

        $validatusername = $DB->get_record_sql("SELECT * FROM {user} WHERE email LIKE '".$data['create_user_name']."' OR username = '".$data['create_user_name']."'");
        if($validatusername){
            $errors['create_user_name'] = "User name is alreday registered";
        }

        $validatuseremail = $DB->get_record_sql(
            "SELECT u.id 
            FROM {user} u 
            JOIN {company_users} cu ON cu.userid = u.id 
            WHERE (u.email = :email OR u.username = :username) 
                AND cu.companyid = :companyid",
            [
                'email' => $data['create_email'],
                'username' => $data['create_email'],
                'companyid' => $companyid
            ]
        );

        if ($validatuseremail) {
            $errors['create_email'] = "This email is already registered for your company.";
        }
        

        // $validatuseremail = $DB->get_record_sql("SELECT * FROM {user} WHERE email LIKE '".$data['create_email']."' OR username = '".$data['create_email']."'");
        // if ($validatuseremail) {
        //     $errors['create_email'] = "Email is alreday registered";
        // }

        $uinfofield = $DB->get_record_sql("SELECT * FROM {user_info_field} WHERE shortname  = 'user_company_name'");
        $validatcompanyname = $DB->get_record_sql("SELECT * FROM {user_info_data} WHERE fieldid = ? AND data LIKE ? ",[$uinfofield->id,$data['company_name']]);
        if ($validatcompanyname) {
            $errors['company_name'] = "company name is already used";
        }      

        if ($data['invoice_country'] == "US") {
            if (empty($data['invoice_state_select'])) {
                $errors['invoice_state_select'] = "Please Select State...";
            }
        }
        return $errors;
    }
}

$form = new guestcheckout();

if ($form->is_cancelled()) {
    redirect(new moodle_url($CFG->wwwroot . '/courses/'));
} elseif ($formData = $form->get_data()) {

    $userData = new stdClass();
    $userData->username    = strtolower($formData->create_user_name);
    $userData->firstname   = $formData->create_first_name;
    $userData->lastname    = $formData->create_last_name;
    $userData->email       = strtolower($formData->create_email);
    $userData->company_id  = $companyId;
    $userData->user_company_name  = $formData->company_name;
    $userData->password    = $formData->create_password;
    if($formData->create_accounttype=="business"){
        $userrole = "business";
        $data = create_compnay_business_user($userData);
    }else{
        $userrole = "individual";
        $data = create_individual_user($userData);

    }
    $userId = $data;
    /*** Start Create User in Wordpress*******/
    if($userId){
        $validatecompany = $DB->get_record_sql(
            "SELECT shortname FROM {company} 
                WHERE id = ?",
                [$companyId]
            );

        if ($validatecompany) {
            if (in_array($validatecompany->shortname, ['PDH STAR'])) {

                $wp_siteurl = get_config(null, "main_site_url{$companyId}");

                // WordPress AJAX endpoint
                $ssovalidateurl = rtrim($wp_siteurl, '/') . '/wp-json/custom/v1/register';

                // Get newly created Moodle user
                $moodleUser = $DB->get_record('user', ['id' => $userId], '*',MUST_EXIST);

                //Get and send the child parent user
                $childParentMdlId = 0;
                $childParentWplId = 0;

                $childParent = $DB->get_record(
                    'business_user_child',
                    ['child_id' => $userId]
                );

                if ($childParent) {

                    $childParentMdlId = (int)$childParent->parent_id;

                    $parentRemoteUser = $DB->get_record(
                        'remote_user_info',
                        ['userid' => $childParentMdlId]
                    );

                    if ($parentRemoteUser) {
                        $childParentWplId = (int)$parentRemoteUser->wp_userid;
                    }
                }

                // Business trainers

                $trainerids = "";
                $user_companyname = "";

                if ($userrole == "business") {

                    $businesstrainers = $DB->get_records_sql(
                                    "SELECT u.id
                                       FROM {business_user_child} buc
                                       INNER JOIN {user} u
                                               ON buc.child_id = u.id
                                      WHERE buc.parent_id = ?
                                        AND buc.company_id = ?
                                        AND u.suspended = 0
                                        AND u.deleted = 0",
                                    [$userId, $companyId]
                                );

                    if($businesstrainers){
                        $trainer_ids = array_keys($businesstrainers);
                        $trainerids = implode(',', $trainer_ids);
                    }

                    $user_companyname = $formData->company_name;
                }

                $is_verified = 1;

                // get scret tokenecho
                $ssowstoken = get_config('local_user_registration', 'sso_shared_secret');

                //user registration validation
                $ssoregdisabled = get_config('local_user_registration', 'sso_registration_disable');

                if ($ssoregdisabled) {
                    $disabledregistartion = 1;
                }else{
                    $disabledregistartion = 0;

                }
                
                // Prepare data to send
                $request_data = [
                    'user_firstname'        => $moodleUser->firstname,
                    'user_lastname'         => $moodleUser->lastname,
                    'username'              => strtolower($moodleUser->username),
                    'childParentMdlId'      => $childParentMdlId,
                    'childParentWplId'      => $childParentWplId,
                    'user_email'            => strtolower($moodleUser->email),
                    'lms_userid'            => $moodleUser->id,
                    'lms_password'          => md5($formData->create_password),
                    'userrole'              => $userrole,
                    'user_trainee'          => $trainerids,
                    'user_companyname'      => $user_companyname,
                    'auth_token'            => $ssowstoken,
                    'is_verified'           => $is_verified,
                    'disabled_registartion' => $disabledregistartion,
                    'moodlewsrestformat'=> 'json',
                ];

                $curl = new curl();
                $response = $curl->post($ssovalidateurl, $request_data);
                $result = json_decode($response);
                if (!empty($result) && isset($result->status) && $result->status == 1) {
                    $status = "Account Created IN wordpress";
                }
            }
        }
    }
    /*** End Create User in Wordpress*******/
    $newsessionid = session_id();
    $guestcart = $DB->get_record('guest_cart', ['sessionid' => $newsessionid]);

    $guestCartRecord = new stdClass();
    $guestCartRecord->id        = $guestcart->id;
    $guestCartRecord->userid    = $userId;
    $guestCartRecord->email     = $formData->create_email;
    $guestCartRecord->phone1    = $formData->invoice_phone;
    $guestCartRecord->companyid = $companyRecord->id;
    $guestCartRecord->company   = $companyRecord->name;
    $guestCartRecord->address   = $formData->invoice_address;
    $guestCartRecord->city      = $formData->invoice_city;
    $guestCartRecord->state     = $formData->invoice_state;
    $guestCartRecord->country   = $formData->invoice_country;
    $guestCartRecord->postcode  = $formData->invoice_zipcode;
    $guestCartRecord->firstname = $formData->invoice_first_name;
    $guestCartRecord->lastname  = $formData->invoice_last_name;
    $guestCartRecord->note      = $formData->ordernotes;
    $guestCartUpdated = $DB->update_record('guest_cart', $guestCartRecord);
    if($guestCartUpdated){
        $invoiceRecord = new stdClass();
        $invoiceRecord->userid    = $userId;
        $invoiceRecord->email     = $formData->create_email;
        $invoiceRecord->phone1    = $formData->invoice_phone;
        $invoiceRecord->companyid = $companyRecord->id;
        $invoiceRecord->company   = $companyRecord->shortname;
        $invoiceRecord->address   = $formData->invoice_address;
        $invoiceRecord->city      = $formData->invoice_city;
        $invoiceRecord->state     = $formData->invoice_state;
        $invoiceRecord->country   = $formData->invoice_country;
        $invoiceRecord->postcode  = $formData->invoice_zipcode;
        $invoiceRecord->firstname = $formData->invoice_first_name;
        $invoiceRecord->lastname  = $formData->invoice_last_name;
        $invoiceRecord->date      = time();
        $invoiceId = $DB->insert_record("invoice", $invoiceRecord);
        if ($invoiceId) {

            // add invoice id in guest cart
            $updatechartrecords = new stdClass();
            $updatechartrecords->id        = $guestcart->id;
            $updatechartrecords->invoiceid = $invoiceId;
            $updatedchartrecords = $DB->update_record('guest_cart', $updatechartrecords);

            //apply coupon
            $getcoupon_invoice = $DB->get_record("coupon_invoice",["guestinvoiceid"=>$guestcart->id]);
            if($getcoupon_invoice){
                $updatecouponinvoice = new stdClass();
                $updatecouponinvoice->id        = $getcoupon_invoice->id;
                $updatecouponinvoice->invoiceid = $invoiceId;
                $updatedcouponinvoice = $DB->update_record('coupon_invoice', $updatecouponinvoice);
            }
            $guestCartItems = $DB->get_records_sql("SELECT * FROM {guest_cart_items} WHERE guestcartid = ?", [$guestcart->id]);
            foreach ($guestCartItems as $cartItem) {
                $invoiceItemRecord = new stdClass();
                $invoiceItemRecord->invoiceid              = $invoiceId; // fixed from $invoicerecord->id
                $invoiceItemRecord->invoiceableitemid      = $cartItem->invoiceableitemid;
                $invoiceItemRecord->invoiceableitemtype    = $cartItem->invoiceableitemtype;
                $invoiceItemRecord->quantity               = $cartItem->quantity;
                $invoiceItemRecord->currency               = $cartItem->currency;
                $invoiceItemRecord->price                  = $cartItem->price;
                $invoiceItemRecord->license_allocation     = $cartItem->license_allocation;
                $invoiceItemRecord->license_validlength    = $cartItem->license_validlength;
                $invoiceItemRecord->license_shelflife      = $cartItem->license_shelflife;
                $invoiceItemRecord->groupid                = $cartItem->groupid;
                $invoiceItemRecord->processed              = $cartItem->processed;
                $DB->insert_record("invoiceitem", $invoiceItemRecord);
            }
        }

        if($userId){
          $userdata = $DB->get_record("user", array("id"=>$userId));
          if(!empty($userdata)){
            complete_user_login($userdata);
              \core\session\manager::apply_concurrent_login_limit($userdata->id, session_id());
              $SESSION->basketid = $invoiceId;
            redirect(new moodle_url($CFG->wwwroot.'/blocks/learnpeek_shop/guestcheckout.php'));
          } else {
            redirect(new moodle_url('/'));
          }
            // redirect($CFG->wwwroot.'/login/index.php', 'Individual  user account created successfully', null, \core\output\notification::NOTIFY_SUCCESS);
        }

    }else{
        redirect($CFG->wwwroot.'/courses/guestcartitems.php', 'Records not updated. Please try again', null, \core\output\notification::NOTIFY_SUCCESS);
    }
}


echo $OUTPUT->header();
echo $html;
$form->display();
$SESSION->guestsession_id = session_id();
echo $OUTPUT->footer();
