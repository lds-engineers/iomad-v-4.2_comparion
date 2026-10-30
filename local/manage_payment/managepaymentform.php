<?php
require_once(__DIR__ . '/../../config.php');
require_login();

if (!is_siteadmin()) {
    redirect(new moodle_url($CFG->wwwroot . '/my'), 'Access denied', null, \core\output\notification::NOTIFY_ERROR);
}
require_capability('moodle/site:config', context_system::instance());

global $PAGE, $OUTPUT, $DB;
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/manage_payment/managepaymentform.php'));
$PAGE->set_title(get_string('selectmultiplecompany','local_manage_payment'));
$PAGE->set_heading(get_string('managepayment','local_manage_payment'));

echo $OUTPUT->header();

$accounts = $DB->get_records('payment_accounts',array("archived"=>0));
$companies = $DB->get_records('company',array("suspended"=>0));
$mappings = $DB->get_records('manage_payment');

// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     $accountname = optional_param('accountid', '', PARAM_TEXT);
//     $companyids = optional_param_array('companyids', [], PARAM_INT);

//     $accountid = 0;
//     foreach ($accounts as $a) {
//         if ($a->name === $accountname) {
//             $accountid = $a->id;
//             break;
//         }
//     }

//     if ($accountid) {
//         $DB->delete_records('manage_payment', ['payment_account_id' => $accountid]);
//         if (!empty($companyids)) {
//             $company_id_csv = implode(',', $companyids);
//             $record = new stdClass();
//             $record->payment_account_id = $accountid;
//             $record->company_id = $company_id_csv;
//             $DB->insert_record('manage_payment', $record);
//         }

//         $existing_company_ids = [];
//         foreach ($companies as $c) {
//             if ((int)($c->paymentaccount) === $accountid) {
//                 $existing_company_ids[] = $c->id;
//             }
//         }

//         $to_remove = array_diff($existing_company_ids, $companyids);
//         foreach ($to_remove as $cid) {
//             $company = $DB->get_record('company', ['id' => $cid], '*', IGNORE_MISSING);
//             if ($company) {
//                 $company->paymentaccount = null; 
//                 $DB->update_record('company', $company);
//             }
//         }

//         foreach ($companyids as $cid) {
//             $company = $DB->get_record('company', ['id' => $cid], '*', IGNORE_MISSING);
//             if ($company) {
//                 $company->paymentaccount = $accountid;
//                 $DB->update_record('company', $company);
//             }
//         }
//     }

//     redirect(new moodle_url('/payment/accounts.php'), 'Changes saved.', null, \core\output\notification::NOTIFY_SUCCESS);
//     exit;
// }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accountname = optional_param('accountid', '', PARAM_TEXT);
    $companyids = optional_param_array('companyids', [], PARAM_INT);

    $accountid = 0;
    foreach ($accounts as $a) {
        if ($a->name === $accountname) {
            $accountid = $a->id;
            break;
        }
    }

    if ($accountid) {
        // Get ALL companies currently assigned to this payment_account_id
        $assigned_companies = $DB->get_records('company', ['paymentaccount' => $accountid]);
        $assigned_ids = array_map(fn($c) => (int) $c->id, $assigned_companies);

        // Convert selected IDs to int just in case
        $companyids = array_map('intval', $companyids);

        // Find unselected (removed) company IDs
        $removed_ids = array_diff($assigned_ids, $companyids);

        // Clear `paymentaccount` for unselected companies
        foreach ($removed_ids as $remove_id) {
            $company = $DB->get_record('company', ['id' => $remove_id], '*', MUST_EXIST);
            $company->paymentaccount = 0; // or 0 if preferred
            $DB->update_record('company', $company);
        }

        // Update selected companies with the current account
        foreach ($companyids as $cid) {
            $company = $DB->get_record('company', ['id' => $cid], '*', MUST_EXIST);
            $company->paymentaccount = $accountid;
            $DB->update_record('company', $company);
        }

        // Update mapping table (optional)
        $DB->delete_records('manage_payment', ['payment_account_id' => $accountid]);
        if (!empty($companyids)) {
            $record = new stdClass();
            $record->payment_account_id = $accountid;
            $record->company_id = implode(',', $companyids);
            $DB->insert_record('manage_payment', $record);
        }

        redirect(new moodle_url('/payment/accounts.php'), 'Changes saved.', null, \core\output\notification::NOTIFY_SUCCESS);
    }
}


$accountnames = [];
foreach ($accounts as $a) {
    $accountnames[$a->id] = $a->name;
}

// Build mapping
$companyAccounts = [];
$accountToCompanies = [];
foreach ($mappings as $map) {
    $company_ids = explode(',', $map->company_id);
    $accname = $accountnames[$map->payment_account_id] ?? null;
    foreach ($company_ids as $cid) {
        $cid = (int)$cid;
        if ($accname) {
            $companyAccounts[$cid] = $accname;
            $accountToCompanies[$accname][] = $cid;
        }
    }
}
?>

<form method="post">
    <div style="margin-bottom: 20px;">
        <label style="font-weight: bold; font-size: 18px;"><?php echo get_string('accountname','local_manage_payment')?></label>
        <select name="accountid" id="accountSelect" class="p-2 rounded w-30">
            <option value=""><?php echo get_string('selectanaccount','local_manage_payment')?> </option>
            <?php foreach ($accountnames as $id => $name): ?>
                <option value="<?= htmlspecialchars($name) ?>"><?= htmlspecialchars($name) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div style="display: flex; align-items: flex-start;">
        <label style="min-width: 150px; font-weight: bold; font-size: 18px; margin-top: 6px;"><?php echo get_string('selectcompany','local_manage_payment')?></label>
        <div style="margin-top: 11px;">
            <?php foreach ($companies as $company): 
                $cid = $company->id;
                $assigned_account = $companyAccounts[$cid] ?? '';
            ?>
                <label style="display: block; margin-bottom: 6px;">
                    <input type="checkbox"
                        name="companyids[]"
                        value="<?= $cid ?>"
                        data-account="<?= $assigned_account ?>"
                        data-companyname="<?= htmlspecialchars($company->name) ?>"
                    >
                    <?= format_string($company->name) ?>
                    <?= $assigned_account ? ' (' . format_string($assigned_account) . ')' : '' ?>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <br>
    <button type="submit" class="btn btn-primary"><?php echo get_string('savechanges','local_manage_payment')?> </button>
    <a href="<?= $CFG->wwwroot ?>/my" class="btn btn-secondary"><?php echo get_string('cancel','local_manage_payment')?></a>
</form>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    const accountToCompanies = <?= json_encode($accountToCompanies) ?>;

    function updateCompanies() {
        const selectedAccount = $('#accountSelect').val();

        $('input[name="companyids[]"]').each(function () {
            const $cb = $(this);
            const companyAccount = $cb.data('account');
            const companyId = parseInt($cb.val());

            if (!selectedAccount) {
                $cb.prop('disabled', false).prop('checked', false);
            } else {
                const allowed = accountToCompanies[selectedAccount] || [];
                if (allowed.includes(companyId) || companyAccount === selectedAccount) {
                    $cb.prop('disabled', false).prop('checked', true);
                } else if (!companyAccount) {
                    $cb.prop('disabled', false).prop('checked', false);
                } else {
                    $cb.prop('disabled', true).prop('checked', false);
                }
            }
        });
    }

    $(document).ready(function () {
        updateCompanies();
        $('#accountSelect').on('change', updateCompanies);
    });
</script>

<?php echo $OUTPUT->footer(); ?>
