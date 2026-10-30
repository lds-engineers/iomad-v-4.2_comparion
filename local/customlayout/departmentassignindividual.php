<?php
require_once('../../config.php');
require_once('department_assign_form.php');
global $USER;
require_login();
// $companyid = iomad::get_my_companyid($context);
$departmentid = required_param('id', PARAM_INT);

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/local/customlayout/departmentassignindividual.php?id='.$departmentid);

$context_user = context_user::instance($USER->id);
$current_user_roles = get_user_roles($context_user, $USER->id);
$manag_role = array_column($current_user_roles, 'shortname');
if(!is_siteadmin()){
    if(empty($manag_role)  || in_array('companyreporter', $manag_role)){
        redirect(new moodle_url($CFG->wwwroot.'/my'),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
        die();
    }
}


$getdepartment = $DB->get_record("customdepartment",array("id"=>$departmentid));
$companyid = $getdepartment->companyid;
$businessrecords = $DB->get_records('company_users', ['companyid' => $companyid, 'managertype'=>2]);
$businessusers = [];
foreach ($businessrecords as $br) {
    $u = $DB->get_record('user',['id' => $br->userid, 'deleted' => 0]);
    if($u){
    	$businessusers[$br->userid] = fullname($u);
    }
}

$assignedusers = [];
$potentialusers = [];

if (!empty($departmentid)) {
    $assigned_ids = [];
    $selectedbusinessuser = $DB->get_record('customdepartment', ['id'=>$departmentid]);
    // echo "<pre>";
    // print_r($selectedbusinessuser);
    // echo "</pre>";
    $businessuserid = $selectedbusinessuser->businessuserid;
    $assignments = $DB->get_records('customdepartment_assign', [
        'parent_id' => $businessuserid,
        'department_id' => $departmentid
    ]);
    foreach ($assignments as $assign) {
        $user = $DB->get_record('user', ['id' => $assign->child_id, 'deleted' => 0]);
        if ($user) {
            $assignedusers[$user->id] = fullname($user);
            $assigned_ids[] = $user->id;
        }
    }
    // $childusers = $DB->get_records('business_user_child', ['parent_id' => $businessuserid]);
    // foreach ($childusers as $child) {
    //     if (!in_array($child->child_id, $assigned_ids)) {
    //         $user = $DB->get_record('user', ['id' => $child->child_id, 'deleted' => 0]);
    //         if ($user) {
    //             $potentialusers[$user->id] = fullname($user);
    //         }
    //     }
    // }
    $childusers = $DB->get_records('company_users', ['companyid' => $companyid, 'managertype'=>0]);
    foreach ($childusers as $child) {
        $user = $DB->get_record('user', ['id' => $child->userid, 'deleted' => 0]);
        if ($user) {
            $potentialusers[$user->id] = fullname($user);
        }
    }
}
$departmentstitle =get_string('assigndepartmentusers', 'local_customlayout', $selectedbusinessuser->name);
$PAGE->set_title($departmentstitle);
$PAGE->set_heading($departmentstitle);

echo $OUTPUT->header();

$departmentrecords = $DB->get_records('customdepartment', ['companyid' => $companyid]);
$departments = [];
foreach ($departmentrecords as $d) {
    $departments[$d->id] = $d->name;
}

$context_user = context_user::instance($USER->id);
$current_user_roles = get_user_roles($context_user, $USER->id);
$manag_role = array_column($current_user_roles, 'shortname');

$assignedusers = [];
$form = new department_assign_form(null, [$companyid, $businessusers, $departments, $assignedusers, $departmentid, $potentialusers, $manag_role]);
if ($form->is_cancelled()) {
	$url = $CFG->wwwroot.'/local/customlayout/departmentassign.php';
    redirect($url);
} elseif ($data = $form->get_data()) {
    $users = explode(',', $data->finalassigned);
    $type = $data->type;
    // echo "<pre>";
    // // print_r($_POST);
    // print_r($data);
    // print_r($users);
    // echo "</pre>";
    // die;
	$DB->delete_records('customdepartment_assign', ['department_id' => $data->departmentid, 'is_departmentmanager' => $type]);
    $systemcontext = context_system::instance();
    $systemcontextid = $systemcontext->id;

    $role = $DB->get_record('role', array('shortname'=>'companyreporter'));
    if($type) {
        // $DB->delete_records('role_assignments', ['roleid' => $role->id, 'modifierid' => $USER->id]);
    }

    foreach ($users as $userid) {
        $rec = new stdClass();
        $rec->department_id = $data->departmentid;
        $rec->parent_id = $data->businessuserid;
        $rec->child_id = $userid;
        $rec->is_departmentmanager = $type;
        $rec->moddifiedby = $USER->id;
        $DB->insert_record('customdepartment_assign', $rec);
        if($type) {
            $role = $DB->get_record('role', array('shortname'=>'companyreporter'));
            if($role) {
                if(!$DB->record_exists('role_assignments', array('roleid'=>$role->id,'userid'=>$userid))){
                    $role_assignments_ins = new stdClass();
                    $role_assignments_ins->roleid = $role->id;
                    $role_assignments_ins->contextid = 1;
                    $role_assignments_ins->userid = $userid;
                    $role_assignments_ins->timemodified = time();
                    $role_assignments_ins->modifierid = $USER->id;
                    $role_assignments_ins->component = ''; 
                    $role_assignments_ins->itemid = 0;
                    $role_assignments_ins->sortorder= 0;
                    $DB->insert_record('role_assignments', $role_assignments_ins);
                }
            }
        }
    }
    $url = $CFG->wwwroot.'/local/customlayout/departmentassignindividual.php?id='.$data->id;
    redirect($url, 'Users assigned successfully', 2, \core\output\notification::NOTIFY_SUCCESS);
}
$form->display();

$dpusercontext = context_user::instance($USER->id); // User context
$current_user_roles = get_user_roles($dpusercontext, $USER->id);
$manag_role = array_column($current_user_roles, 'shortname');
// echo "<pre>";
// print_r($manag_role);
// echo "</pre>";
 if($manag_role[0] == "companydepartmentmanager"){
    echo "<style>
    #fitem_id_businessuserid,#fitem_id_departmentid{
	    display:none;
	}
    </style>";
 }
// // All individual users 
// $allindividualusers = get_string('allindividualusers', 'local_customlayout');
// // Assigned sers
// $assignedusers= get_string('assignedusers','local_customlayout');


echo $OUTPUT->footer();
?>

<script>
document.getElementById('add').onclick = function () {
    moveOptions('potentialusers', 'assignedusers');
};
document.getElementById('remove').onclick = function () {
    moveOptions('assignedusers', 'potentialusers');
};
function moveOptions(fromID, toID) {
    let from = document.getElementById(fromID);
    let to = document.getElementById(toID);
    let selected = [...from.options].filter(option => option.selected);
    for (let opt of selected) {
        to.add(opt);
    }
    updateFinal();
}
function updateFinal() {
    let assigned = document.getElementById('assignedusers');
    let final = [...assigned.options].map(o => o.value);
    document.querySelector('[name="finalassigned"]').value = final.join(',');
}
</script>
<script>
function loadUserBoxes(businessuserid, departmentid, type) {
    console.log('message', type);
    fetch(`ajax/ajaxloadusers.php?businessuserid=${businessuserid}&departmentid=${departmentid}&type=${type}`)
        .then(res => res.json())
        .then(data => {
            const potential = document.getElementById('potentialusers');
            const assigned = document.getElementById('assignedusers');
            potential.innerHTML = '';
            assigned.innerHTML = '';
            for (const id in data.potential) {
                const opt = document.createElement('option');
                opt.value = id;
                opt.text = data.potential[id];
                potential.appendChild(opt);
            }
            for (const id in data.assigned) {
                const opt = document.createElement('option');
                opt.value = id;
                opt.text = data.assigned[id];
                assigned.appendChild(opt);
            }
            document.getElementById('potential-label').innerText = `<?=get_string('allindividualusers', 'local_customlayout') ?>(${Object.keys(data.potential).length})`;
            document.getElementById('assigned-label').innerText = `<?=get_string('assignedusers', 'local_customlayout')?>(${Object.keys(data.assigned).length})`;
        });
}
document.addEventListener('DOMContentLoaded', function () {
    const businessSelect = document.querySelector('[name="businessuserid"]');
    const deptSelect = document.querySelector('[name="departmentid"]');
    const type = document.querySelector('[name="type"]');
    function reload() {
        const bid = businessSelect.value;
        const did = deptSelect.value;
        const typeid = type.value;
        if (bid && did && typeid) {
            loadUserBoxes(bid, did, typeid);
        }
    }
    businessSelect.addEventListener('change', reload);
    deptSelect.addEventListener('change', reload);
    type.addEventListener('change', reload);
    reload();
});
</script>
<script type="text/javascript">
function myFunctionToDoSomething() {
    var type = $("#id_type").val();
    var departmentid = $("#id_departmentid").val();
}
</script>
<style type="text/css">
select option[disabled] {
    font-weight: bold;
    color: #333;
}
.dual-select-wrapper {
    display: flex;
    gap: 30px;
    align-items: flex-start;
    margin-top: 20px;
}

.select-box-column {
    flex: 1;
    min-width: 300px;
}

.box-label {
    font-weight: bold;
    margin-bottom: 6px;
}

.select-box-column select {
    width: 100% !important;
    height: 350px !important;
    box-sizing: border-box;
}

.button-column {
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 10px;
    min-width: 80px;
}
.mform .form-inline .form-control, #potentialusers, #assignedusers {
    min-width: -webkit-fill-available !important;
}
</style>
