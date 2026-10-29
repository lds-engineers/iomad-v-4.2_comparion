<?php
require("../../config.php");
require_once($CFG->libdir . '/formslib.php');
global $DB, $USER, $PAGE, $OUTPUT;

require_login();
$id = optional_param('id', 0, PARAM_INT);
if (!$id) {
    $id = optional_param('id', 0, PARAM_INT, 'post');
}
if (!$id) {
    throw new moodle_exception('missingparam', 'error', '', 'id');
}
$coupon = $DB->get_record('local_coupon', ['id' => $id], '*', MUST_EXIST);

$PAGE->set_url(new moodle_url('/local/learnpeekcoupon/edit.php', ['id' => $id]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Edit Coupon');

class edit_coupon_form extends moodleform {
    function definition() {
        global $DB;
        $mform = $this->_form;

        $mform->addElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'code', get_string('code', 'local_learnpeekcoupon'));
        $mform->setType('code', PARAM_ALPHANUMEXT);
        $mform->addRule('code', null, 'required', null, 'client');

        $mform->addElement('textarea', 'description', get_string('description', 'local_learnpeekcoupon'), 'wrap="virtual" rows="5" cols="50"');
        $mform->setType('description', PARAM_TEXT);

        $mform->addElement('text', 'max_uses', get_string('max_uses', 'local_learnpeekcoupon'));
        $mform->setType('max_uses', PARAM_INT);

        $mform->addElement('text', 'max_uses_user', get_string('max_uses_user', 'local_learnpeekcoupon'));
        $mform->setType('max_uses_user', PARAM_INT);

        $mform->addElement('select', 'type', get_string('type', 'local_learnpeekcoupon'), ['fixed' => 'fixed', 'percent' => 'percent']);

        $mform->addElement('text', 'discount_amount', get_string('discount_amount', 'local_learnpeekcoupon'));
        $mform->setType('discount_amount', PARAM_FLOAT);

        $mform->addElement('text', 'min_amount', get_string('min_amount', 'local_learnpeekcoupon'));
        $mform->setType('min_amount', PARAM_FLOAT);

        $mform->addElement('select', 'status', get_string('status', 'local_learnpeekcoupon'), [1 => 'active', 0 => 'inactive']);

        $mform->addElement('date_time_selector', 'starts_at', get_string('starts_at', 'local_learnpeekcoupon'), ['optional' => true]);
        $mform->addElement('date_time_selector', 'expires_at', get_string('expires_at', 'local_learnpeekcoupon'), ['optional' => true]);

        // $companies = $DB->get_records_menu('company', null, 'name ASC', 'id, name');



        $companyid = iomad::get_my_companyid(context_system::instance());

        $companylists = array();
        $getcompanies = $DB->get_records_sql('SELECT * FROM {company} WHERE suspended = 0 ORDER BY name ASC');

        // If site admin, show all companies + Default Template
        if (is_siteadmin()) {
            $companylists[0] = 'Select Company';
            foreach ($getcompanies as $company) {
                $companylists[$company->id] = $company->name;
            }
        } else {
            // For normal users, only show their own company
            foreach ($getcompanies as $company) { 
                if ($company->id == $companyid) {
                    $companylists[$company->id] = $company->name;
                    break;
                }
            }
        }
        

        $mform->addElement('select', 'companyid', get_string('selectcompany', 'local_learnpeekcoupon'), $companylists);
        $mform->addRule('companyid', null, 'required', null, 'client');

        $mform->addElement('advcheckbox', 'allow_all_users',  get_string('allow_all_users', 'local_learnpeekcoupon'), null, [0, 1]);
       // $mform->addElement('select', 'allowedusers', 'Allowed Users', [], [
       //      'multiple' => 'multiple',
       //      'class' => 'select2',
       //      'size' => 10
       //  ]);
       $mform->addElement('select', 'allowedusers',  get_string('allowedusers', 'local_learnpeekcoupon'), [], [
            'multiple' => 'multiple',
            'class' => 'select2',
        ]);

        $mform->setType('allowedusers', PARAM_RAW);
        $mform->disabledIf('allowedusers[]', 'allow_all_users', 'checked');

        $mform->addElement('advcheckbox', 'allow_all_products', get_string('allow_all_products', 'local_learnpeekcoupon'), null, [0, 1]);
        $mform->addElement('select', 'allowedproducts', get_string('allowedproducts', 'local_learnpeekcoupon'), [], [
            'multiple' => 'multiple',
            'class' => 'select2',
            'size' => 10
        ]);
        $mform->setType('allowedproducts', PARAM_RAW);
        $mform->disabledIf('allowedproducts[]', 'allow_all_products', 'checked');

        $this->add_action_buttons(true, get_string('updatecoupon', 'local_learnpeekcoupon'));
    }
}

$form = new edit_coupon_form();

$defaults = (array)$coupon;
$defaults['allowedusers'] = array_keys($DB->get_records('local_coupon_user', ['couponid' => $id], '', 'userid, id'));
$defaults['allowedproducts'] = array_keys($DB->get_records('local_coupon_product', ['couponid' => $id], '', 'course_shopsetting_id, id'));
$form->set_data($defaults);

if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/learnpeekcoupon/index.php'));
} else if ($data = $form->get_data()) {

    $existing = $DB->get_record('local_coupon', ['code' => $data->code]);
    if ($existing && $existing->id != $data->id) {
        redirect(new moodle_url('/local/learnpeekcoupon/edit.php', ['id' => $id]), 'Coupon code already exists.');
    }

    $update = (object)[
        'id' => $data->id,
        'code' => $data->code,
        'description' => $data->description,
        'max_uses' => $data->max_uses,
        'max_uses_user' => $data->max_uses_user,
        'type' => $data->type,
        'discount_amount' => $data->discount_amount,
        'min_amount' => $data->min_amount,
        'status' => $data->status,
        'starts_at' => $data->starts_at,
        'expires_at' => $data->expires_at,
        'companyid' => $data->companyid,
        'allow_all_users' => $data->allow_all_users,
        'allow_all_products' => $data->allow_all_products,
        'timemodified' => time()
    ];
    $DB->update_record('local_coupon', $update);

    $DB->delete_records('local_coupon_user', ['couponid' => $id]);
    if (!$data->allow_all_users && !empty($_POST['allowedusers'])) {
        foreach ($_POST['allowedusers'] as $userid) {
            $DB->insert_record('local_coupon_user', (object)[
                'couponid' => $id,
                'userid' => (int)$userid,
                'timecreated' => time()
            ]);
        }
    }

    $DB->delete_records('local_coupon_product', ['couponid' => $id]);
    if (!$data->allow_all_products && !empty($_POST['allowedproducts'])) {
        foreach ($_POST['allowedproducts'] as $productid) {
            $DB->insert_record('local_coupon_product', (object)[
                'couponid' => $id,
                'course_shopsetting_id' => (int)$productid,
                'timecreated' => time()
            ]);
        }
    }

    redirect(new moodle_url('/local/learnpeekcoupon/index.php'), "Coupon updated successfully");
}

echo $OUTPUT->header();
$com_id = iomad::get_my_companyid(context_system::instance());
$getcompnayadmin = $DB->get_record_sql("SELECT * FROM {company_users} WHERE companyid = ? AND userid =?",array($com_id,$USER->id));

if(is_siteadmin() || $getcompnayadmin->managertype ==1 && $com_id == $coupon->companyid){
 
$form->display();
?>

<!-- Select2 JS & CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const companySelect = document.querySelector('[name=companyid]');
    const userSelect = document.querySelector('[name="allowedusers[]"]');
    const productSelect = document.querySelector('[name="allowedproducts[]"]');

    const selectedUsers = <?= json_encode($defaults['allowedusers']) ?>;
    const selectedProducts = <?= json_encode($defaults['allowedproducts']) ?>;
    const initialCompanyId = <?= (int)$coupon->companyid ?>;

    function enableSelect2() {
        $(userSelect).select2({ width: '100%', placeholder: 'Select users' });
        $(productSelect).select2({ width: '100%', placeholder: 'Select products' });
    }

    function fetchAndPopulate(companyid, preselect = true) {
        fetch(`../learnpeekcoupon/ajax.php?action=get_data&sesskey=${M.cfg.sesskey}&companyid=${companyid}`)
            .then(response => response.json())
            .then(data => {
                userSelect.innerHTML = '';
                productSelect.innerHTML = '';

                for (const id in data.users) {
                    const option = document.createElement('option');
                    option.value = id;
                    option.textContent = data.users[id];
                    if (preselect && selectedUsers.includes(parseInt(id))) {
                        option.selected = true;
                    }
                    userSelect.appendChild(option);
                }

                for (const id in data.products) {
                    const option = document.createElement('option');
                    option.value = id;
                    option.textContent = data.products[id];
                    if (preselect && selectedProducts.includes(parseInt(id))) {
                        option.selected = true;
                    }
                    productSelect.appendChild(option);
                }

                enableSelect2();
            })
            .catch(error => console.error('Error fetching data:', error));
    }

    companySelect.addEventListener('change', function () {
        if (this.value) {
            fetchAndPopulate(this.value, false);
        } else {
            userSelect.innerHTML = '';
            productSelect.innerHTML = '';
        }
    });

    if (initialCompanyId) {
        fetchAndPopulate(initialCompanyId, true);
    }
});
</script>


<?php
}else{
    redirect(new moodle_url($CFG->wwwroot.'/local/learnpeekcoupon/index.php'),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
}
 echo $OUTPUT->footer(); ?>
