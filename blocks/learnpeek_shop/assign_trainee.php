<?php
require('../../config.php');
require_once($CFG->dirroot . '/blocks/iomad_company_admin/lib.php');
\block_iomad_commerce\helper::require_commerce_enabled();

global $DB, $USER, $PAGE, $OUTPUT;
require_login();
$PAGE->requires->jquery();
// die();
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('base');

$companyid = iomad::get_my_companyid(context_system::instance());
$ucontext = context_user::instance($USER->id);
$ucurrent_user_roles = get_user_roles($ucontext, $USER->id);
$ucurrent_user_roles = array_values($ucurrent_user_roles);
$hascompanyreporterrole = (isset($ucurrent_user_roles[0]) && $ucurrent_user_roles[0]->shortname == 'companyreporter');


$getcompnayadmin = $DB->get_record_sql("SELECT * FROM {company_users} WHERE companyid = ? AND userid =?", array($companyid, $USER->id));



if (!is_siteadmin() && (!$getcompnayadmin || ($getcompnayadmin->managertype != 1 && $getcompnayadmin->managertype != 2)) && !$hascompanyreporterrole) {
    redirect(new moodle_url($CFG->wwwroot . '/my'), 'Access denied', null, \core\output\notification::NOTIFY_ERROR);
    die;
}
$itemid = required_param('itemid', PARAM_INT);
// ---------- Page output ----------
$PAGE->set_title(get_string('assignseatstotrainee','block_learnpeek_shop'));
$PAGE->set_heading(get_string('assignseatstotrainee','block_learnpeek_shop'));
echo $OUTPUT->header();

$lexpirydate = time();

// get all lincenses for same product
// $all_lincenses = $DB->get_records_sql("SELECT cl.*,ii.invoiceableitemid, clf.user_id, clf.invoiceitem_id, count( distinct clu.userid) as usedlicence 
//                                         FROM {companylicense_info} clf 
//                                         INNER JOIN {invoiceitem} ii ON ii.id = clf.invoiceitem_id 
//                                         INNER JOIN {companylicense} as cl ON cl.id = clf.companylicense_id
//                                         LEFT JOIN {companylicense_users} clu on clu.licenseid = cl.id
//                                         WHERE clf.user_id = ? AND ii.invoiceableitemid = ? AND cl.expirydate > ? GROUP BY cl.id", [$USER->id, $itemid, $lexpirydate]);

$all_lincenses = $DB->get_records_sql("SELECT cl.*,ii.invoiceableitemid, clf.user_id, clf.invoiceitem_id 
                                        FROM {companylicense_info} clf 
                                        INNER JOIN {invoiceitem} ii ON ii.id = clf.invoiceitem_id 
                                        INNER JOIN {companylicense} as cl ON cl.id = clf.companylicense_id
                                        WHERE clf.user_id = ? AND ii.invoiceableitemid = ? AND cl.expirydate > ? GROUP BY cl.id", [$USER->id, $itemid, $lexpirydate]);

$alllicenseids = array_keys($all_lincenses);
$assignedusers = [];
// Get all assigned users for these licenses
if (!empty($alllicenseids)) {
    $licenseidsql = implode(',', array_map('intval', $alllicenseids));

    $assignments = $DB->get_records_sql(
        "SELECT userid, licenseid, issuedate
           FROM {companylicense_users}
          WHERE licenseid IN ($licenseidsql)"
    );

    foreach ($assignments as $a) {
        // One user may have multiple licenses → keep earliest assignment
        if (!isset($assignedusers[$a->userid])) {
            $assignedusers[$a->userid] = $a->issuedate;
        }
    }
}


// echo "<pre>";
// print_r($all_lincenses);
// echo "</pre>";

//=======================================================

$lavailable       = 0;

foreach ($all_lincenses as $alu) {
    $perlicense = $alu->allocation / $alu->humanallocation;
    $licensesused = (int) ceil($alu->used / $perlicense);
    if ($licensesused < $alu->humanallocation) {
        $lavailable = $alu->id;
    }
}
$usedseats = array_sum(array_column($all_lincenses, 'used'));
$humanallocation = array_sum(array_column($all_lincenses, 'humanallocation'));
$totallicence = array_sum(array_column($all_lincenses, 'allocation'));

$perlicense = $totallicence / $humanallocation;
$usedseats = (int) ceil($usedseats / $perlicense);

$remainingseats = max(0, $humanallocation - $usedseats);
//=======================================================

$productinfo = $DB->get_record("course_shopsettings",["id"=>$itemid]);

// echo "<pre>";
// print_r($ucurrent_user_roles);
// echo "</pre>";
$manag_role = array_column($ucurrent_user_roles, 'shortname');
if($manag_role[0] == 'companymanager'){
    $trainees = $DB->get_records_sql("SELECT u.* FROM {company_users} cu 
                                        INNER JOIN {user} u ON cu.userid = u.id 
                                        WHERE u.deleted = 0 AND u.suspended = 0 AND companyid = ?",[$companyid]);
}else if($manag_role[0] == 'companyreporter'){
    $getuserepartments = $DB->get_fieldset_sql("SELECT department_id FROM {customdepartment_assign} WHERE child_id = ? AND is_departmentmanager = ?",array($USER->id,1));
    if(!empty($getuserepartments)){
        $department_ids = implode(",", $getuserepartments);
        $trainees = $DB->get_records_sql("SELECT u.* FROM {customdepartment_assign} cdm 
                                            INNER JOIN {company_users} cu ON  cu.userid = cdm.child_id 
                                            INNER JOIN {user} u ON u.id = cu.userid
                                            WHERE cdm.department_id in (".$department_ids.") AND cu.companyid AND u.deleted = 0 AND u.suspended = 0",array($companyid));
        if(isset($trainees[$USER->id])){
            $trainees[0] = $trainees[$USER->id];
            unset($trainees[$USER->id]);
        }
        ksort($trainees);
    }else{
        $trainees = [];
    }
}else if($manag_role[0] == 'companydepartmentmanager'){

    $trainees = $DB->get_records_sql("SELECT u.*,buc.parent_id,buc.created_date,buc.company_id 
        FROM {business_user_child} as buc 
        INNER JOIN {user} as u ON buc.child_id = u.id
        WHERE parent_id= ? AND company_id = ? AND u.suspended = 0 AND u.deleted=0", array($USER->id, $companyid));
    $trainees[0] = $USER;
    ksort($trainees);
}else{
    $trainees = []; 
}
// echo "<pre>";
// print_r($trainees);
// print_r($getcompnayadmin);
// print_r($hascompanyreporterrole);
// print_r($ucurrent_user_roles);
// print_r($manag_role);
// echo "</pre>";
// $remainingseats = $totallicence - $usedseats;
?>

<div class="row mb-4">
    <div class="col-md-2">
        <div class="card p-3 text-center">
            <strong><?php echo $usedseats; ?></strong><br><?php echo get_string('assignedseats', 'block_learnpeek_shop'); ?> 
        </div>
    </div>
    <div class="col-md-2">
        <div class="card p-3 text-center">
            <strong><?php echo $remainingseats; ?></strong><br><?php echo get_string('remainingseats', 'block_learnpeek_shop'); ?> 
        </div>
    </div>
</div>

<h4><?php echo get_string('product', 'block_learnpeek_shop'); ?>: <span style="color:#0f47ad"><?php echo format_string($productinfo->name); ?></span></h4>

<table class="table table-bordered mt-3">
    <thead>
        <tr>
            <th><?php echo get_string('name', 'block_learnpeek_shop'); ?></th>
            <th><?php echo get_string('email', 'block_learnpeek_shop'); ?></th>
            <th><?php echo get_string('assigneddate', 'block_learnpeek_shop'); ?> </th>
            <th><?php echo get_string('action', 'block_learnpeek_shop'); ?></th>
        </tr>
    </thead>
    <tbody>
<?php
foreach ($trainees as $trainee) {

    $userare = ($trainee->id == $USER->id) ? '<b>(Me)</b>' : '';
    $isassigned = isset($assignedusers[$trainee->id]);
    echo '<tr>
        <td>' . fullname($trainee) . ' '.$userare.'</td>
        <td>' . $trainee->email . '</td>';
            // Assigned Date
    echo '<td>';
        if ($isassigned) {
            echo userdate($assignedusers[$trainee->id], '%Y-%m-%d');
        } else {
            echo '-';
        }
    echo '</td>';

    // Actions
    echo '<td>';
        if ($isassigned) {
            if($remainingseats > 0){
                echo '<button type="button"
                            class="btn btn-primary assign-seat-btn mr-2"
                            data-userid="'.$trainee->id.'"
                            data-itemid="'.$lavailable.'">
                        '.get_string('assignseat', 'block_learnpeek_shop').'
                    </button>';
            }
            echo '<span class="badge" style="font-size: 14px;color: #000;">'.get_string('assigned', 'block_learnpeek_shop').'</span>';
        } else if($remainingseats > 0){
            echo '<button type="button"
                        class="btn btn-primary assign-seat-btn"
                        data-userid="'.$trainee->id.'"
                        data-itemid="'.$lavailable.'">
                    '.get_string('assignseat', 'block_learnpeek_shop').'
                </button>';
        }else{
            echo '<span class="badge badge-danger">'.get_string('noseats', 'block_learnpeek_shop').'</span>';
        }
    echo '</td>';
    echo '</tr>';
}
?>
    </tbody>
</table>
<script>
    $('.assign-seat-btn').on('click', function () {
        var btn = $(this);
        var userid = btn.data('userid');
        var licenseid = btn.data('itemid');

        btn.prop('disabled', true).text('Assigning...');

        // alert(licenseid + '#' + userid);

        submitrequest(userid, licenseid, false, function(res){
            var respose = JSON.parse(res);
            if (respose.status === 'success') {
                 location.reload();
            } else if (respose.status === 'warning') {
                // alert(respose.message);
                if(confirm(`${respose.message}`)){
                    submitrequest(userid, licenseid, true, function(res){
                        var respose = JSON.parse(res);
                        if (respose.status === 'success') {
                            // alert(respose.message);
                             location.reload();
                        } else {
                            // alert(respose.message);
                            btn.prop('disabled', false).text('Assign Seat');
                        }

                    })
                } else {
                    // alert(respose.message);
                    btn.prop('disabled', false).text('Assign Seat');
                }
            } else {
                // alert(respose.message);
                btn.prop('disabled', false).text('Assign Seat');
            }

        })

        
    });
    function submitrequest(userid, licenceid, force, success){
        var ajxurl = '<?php echo $CFG->wwwroot . "/blocks/learnpeek_shop/assigtraineeajax.php"; ?>';
        $.ajax({
            type: "POST",
            url: ajxurl,
            data: {
                action: "assign_trainee_license",
                userid: userid,
                licenseid: licenceid,
                force: force
            },
            success: function (res) {
                success(res);
            },
            
        });
    }
</script>



<?php
echo $OUTPUT->footer();