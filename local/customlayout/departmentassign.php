<?php
require_once('../../config.php');
require_once($CFG->libdir . '/tablelib.php');
require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/local/customlayout/departmentassign.php');
$PAGE->set_title(get_string('managedepartments', 'local_customlayout'));
$PAGE->set_heading(get_string('managedepartments', 'local_customlayout'));
$PAGE->navbar->add(get_string('managedepartments', 'local_customlayout'), new moodle_url('/local/customlayout/departmentassign.php'));

$context_user = context_user::instance($USER->id);
$current_user_roles = get_user_roles($context_user, $USER->id);
$manag_role = array_column($current_user_roles, 'shortname');
if(!is_siteadmin()){
	if(empty($manag_role)  || in_array('companyreporter', $manag_role)){
		redirect(new moodle_url($CFG->wwwroot.'/my'),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
		die();
	}
}

echo $OUTPUT->header();

$companyid = iomad::get_my_companyid(context_system::instance());
$companies = $DB->get_records_menu('company', null, 'name ASC', 'id, name');

echo '<div class="mt-2">';
echo '<div class="row justify-content-center">';
echo '<div class="col-md-12">';
echo '<div class="card">';
echo '<div class="card-body">';
echo '<form id="assign-department-form">';

if (is_siteadmin()) {
    ?>
        <div class="mb-3">
            <label for="company" class="form-label"><?php echo get_string('company','local_customlayout')?></label>
            <select id="company" name="company" class="form-control">
                <option value=""><?php echo get_string('selectcompany','local_customlayout')?></option>
                <?php foreach ($companies as $id => $name): ?>
                    <option value="<?= $id ?>"><?= format_string($name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

		<!--         <div class="mb-3">
            <label for="manager" class="form-label">Whitelabel Admin</label>
            <select id="manager" name="manager" class="form-control" disabled>
                <option value="">Select Whitelabel Admin</option>
            </select>
        </div> -->

        <div class="mb-3">
            <label for="deptmanager" class="form-label"><?php get_string('businessusers','local_customlayout')?></label>
            <select id="deptmanager" name="deptmanager" class="form-control" disabled>
                <option value=""><?php get_string('selectbusinessusers','local_customlayout')?></option>
            </select>
        </div>

           <div class="mb-3">
           <label for="new-department" class="form-label"><?php echo get_string('addnewdepartment','local_customlayout'); ?></label>
           <div class="input-group flex-wrap">
                <input type="text" d="new-department" class="form-control"placeholder="<?php echo get_string('enterdepartmentname','local_customlayout'); ?>">
                <button type="button" id="add-department" class="btn btn-outline-primary mx-2"><?php echo get_string('add','local_customlayout'); ?></button>
           </div>
        </div>

        <div class="mb-3">
            <label for="departments" class="form-label"><?php echo get_string('departments','local_customlayout');?></label>
            <ul id="departments" class="list-group">
                <!-- Departments will be populated via JavaScript -->
            </ul>
        </div>

    <?php
} elseif (in_array('companymanager', $manag_role)) {
    $businessList = [];
    $company_users = $DB->get_records('company_users', ['companyid'=>$companyid,'managertype'=>2]);
    // echo "<pre>";
    // echo $companyid;
    // print_r($company_users);
    // echo "</pre>";
    foreach ($company_users as $user) {

        if (!isset($businessList[$user->userid])) {
            $usersdata = $DB->get_record('user', ['id' => $user->userid, 'deleted' => 0]);
            if ($usersdata) {
                $fullname = fullname($usersdata);
                $businessList[$user->userid] = [
                    'id' => $usersdata->id,
                    'name' => $fullname
                ];
            }
        }
    }
    ?>
        <div class="mb-3">
            <label for="deptmanager" class="form-label"><?php echo get_string('businessusers','local_customlayout')?></label>
            <select id="deptmanager" name="deptmanager" class="form-control">
                <option value=""><?php echo get_string('selectbusinessusers','local_customlayout')?></option>
                <?php if($businessList) { 
                        foreach($businessList as $key => $data) {
                ?>
                    <option value="<?php echo $data['id']; ?>"><?php echo $data['name']; ?></option>
                <?php }
                } ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="new-department" class="form-label"><?php echo get_string('addnewdepartment','local_customlayout')?></label>
            <div class="input-group flex-wrap">
                <input type="text" id="new-department" class="form-control" placeholder=<?php echo get_string('enterdepartmentname','local_customlayout')?>>
                <button type="button" id="add-department" class="btn btn-outline-primary mx-2"><?php echo get_string('add','local_customlayout')?></button>
            </div>
        </div>
       

        <div class="mb-3">
            <label for="departments" class="form-label"><?php echo get_string('departments','local_customlayout')?></label>
            <ul id="departments" class="list-group">
                <!-- Departments will be populated via JavaScript -->
            </ul>
        </div>
        <input type='hidden' value='<?php echo $USER->id; ?>' name='mid' id="mid">
        <input type='hidden' value='<?php echo $companyid; ?>' name='cid' id="cid">
    <?php
} elseif (in_array('companydepartmentmanager', $manag_role) || in_array('companyreporter', $manag_role)) {
    if(in_array('companyreporter', $manag_role)) {
        $departmentList = [];
        $sql = "SELECT c.* FROM {customdepartment_assign} ca 
            INNER JOIN {customdepartment} AS c ON ca.department_id = c.id 
            WHERE ca.child_id =:child_id AND ca.is_departmentmanager = 1";
        $departments = $DB->get_records_sql($sql,['child_id'=>$USER->id]);
        foreach ($departments as $department) {
            $departmentList[] = [
                'id' => $department->id,
                'name' => $department->name
            ] ;
        }
    } else {
        $departments = $DB->get_records('customdepartment', ['businessuserid' => $USER->id]);
        $departmentList = [];
        foreach ($departments as $department) {
            $departmentList[] = [
                'id' => $department->id,
                'name' => $department->name
            ] ;
        }
    }
    ?>
        <!-- <div class="mb-3">
            <label for="new-department" class="form-label">Add New Department</label>
            <div class="input-group flex-wrap">
                <input type="text" id="new-department" class="form-control" placeholder="Enter department name">
                <button type="button" id="add-department" class="btn btn-outline-primary mx-2">Add</button>
            </div>
        </div> -->

        <div class="mb-3">
            <label for="new-department" class="form-label"><?php echo get_string('addnewdepartment', 'local_customlayout'); ?></label>
            <div class="input-group flex-wrap">
                <input type="text" id="new-department" class="form-control"placeholder="<?php echo get_string('enterdepartmentname', 'local_customlayout'); ?>">
                <button type="button" id="add-department" class="btn btn-outline-primary mx-2"><?php echo get_string('add', 'local_customlayout'); ?></button>
            </div>
        </div>

        <div class="mb-3">
            <label for="departments" class="form-label"><?php echo get_string('departments', 'local_customlayout'); ?></label>
            <ul id="departments" class="list-group">
              <?php foreach ($departmentList as $dept): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                    <span><?= $dept['name'] ?></span>
                    <div>
                        <button type="button" class="btn btn-sm btn-primary manage-dept" data-id="<?= $dept['id'] ?>"><?= get_string('manage_users', 'local_customlayout') ?></button>
                        <button type="button" class="btn btn-sm btn-warning edit-dept" data-id="<?= $dept['id'] ?>" data-name="<?= $dept['name'] ?>"><?= get_string('edit', 'local_customlayout') ?></button>
                        <button type="button"class="btn btn-sm btn-danger delete-dept my-2" data-id="<?= $dept['id'] ?>"> <?= get_string('delete', 'local_customlayout') ?></button>
                    </div>
                </li>
              <?php endforeach; ?>
            </ul>
        </div>
        <input type='hidden' value='<?php echo $USER->id; ?>' name='bid'>
        <input type='hidden' value='<?php echo $companyid; ?>' name='cid'>
    <?php
}

echo "<input type='hidden' value='$CFG->wwwroot/local/customlayout/departmentassignindividual.php' name='aid'></form></div></div></div></div>";
echo $OUTPUT->footer();

if (is_siteadmin()) {
    ?>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const companySelect = document.getElementById('company');
    // const managerSelect = document.getElementById('manager');
    const deptManagerSelect = document.getElementById('deptmanager');
    const departmentSelect = document.getElementById('departments');
    const addDepartmentButton = document.getElementById('add-department');
    const newDepartmentInput = document.getElementById('new-department');
    // companySelect?.addEventListener('change', function () {
    //     const companyId = this.value;
    //     if (!companyId) return;
    //     fetch(`ajax/get_managers.php?companyid=${companyId}`)
    //         .then(res => res.json())
    //         .then(data => {
    //             managerSelect.innerHTML = '<option value="">Select Whitelabel Admin</option>';
    //             data.forEach(manager => {
    //                 const opt = document.createElement('option');
    //                 opt.value = manager.id;
    //                 opt.textContent = manager.name;
    //                 managerSelect.appendChild(opt);
    //             });
    //             managerSelect.disabled = false;
    //             deptManagerSelect.innerHTML = '<option value="">Select Business Users</option>';
    //             deptManagerSelect.disabled = true;
    //             departmentSelect.innerHTML = '';
    //             departmentSelect.disabled = true;
    //         });
    // });
    companySelect?.addEventListener('change', function () {
        const companyId = this.value;
        // const companyId = $("#company").val(); 
        if (!companyId) return;
        fetch(`ajax/get_deptmanagers.php?companyId=${companyId}`)
            .then(res => res.json())
            .then(data => {
                deptManagerSelect.innerHTML = '<option value="">Select Business Users</option>';
                data.forEach(dm => {
                    const opt = document.createElement('option');
                    opt.value = dm.id;
                    opt.textContent = dm.name;
                    deptManagerSelect.appendChild(opt);
                });
                deptManagerSelect.disabled = false;
                departmentSelect.innerHTML = '';
                departmentSelect.disabled = true;
            });
    });
    deptManagerSelect?.addEventListener('change', function () {
        const deptManagerId = this.value;
        if (!deptManagerId) return;
        fetch(`ajax/get_departments.php?deptmanagerid=${deptManagerId}`)
            .then(res => res.json())
            .then(data => {
                departmentSelect.innerHTML = '';
                data.forEach(dep => {
                    const li = document.createElement('li');
                    li.className = 'list-group-item d-flex justify-content-between align-items-center flex-wrap';
                    li.innerHTML = `
                        <span class="dept-name" data-id="${dep.id}">${dep.name}</span>
                        <div>
                            <button type="button" class="btn btn-sm btn-primary me-1 manage-dept my-2" data-id="${dep.id}">Manage Individual Users</button>
                            <button type="button" class="btn btn-sm btn-warning me-1 edit-dept my-2" data-id="${dep.id}" data-name="${dep.name}">Edit</button>
                            <button type="button" class="btn btn-sm btn-danger delete-dept my-2" data-id="${dep.id}">Delete</button>
                        </div>
                    `;
                    departmentSelect.appendChild(li);
                });
                departmentSelect.disabled = true;
            });
    });
    addDepartmentButton?.addEventListener('click', function () {
        const deptName = newDepartmentInput.value.trim();
        const deptManagerId = deptManagerSelect?.value ?? $('input[name="bid"]').val();
        const companyid = $('#company').val();
        if (!deptName || !deptManagerId) {
            alert('Please enter a department name and ensure business user is selected.');
            return;
        }
        fetch('ajax/add_department.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                name: deptName,
                deptmanagerid: deptManagerId,
                companyid: companyid
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const li = document.createElement('li');
                li.className = 'list-group-item d-flex justify-content-between align-items-center flex-wrap';
                li.innerHTML = `
                    <span class="dept-name" data-id="${data.id}">${data.name}</span>
                    <div>
                        <button type="button" class="btn btn-sm btn-primary me-1 manage-dept my-2" data-id="${data.id}">Manage Individual Users</button>
                        <button type="button" class="btn btn-sm btn-warning me-1 edit-dept my-2" data-id="${data.id}" data-name="${data.name}">Edit</button>
                        <button type="button" class="btn btn-sm btn-danger delete-dept my-2" data-id="${data.id}">Delete</button>
                    </div>
                `;
                departmentSelect.appendChild(li);
                departmentSelect.disabled = true;
                newDepartmentInput.value = '';
            } else {
                alert(data.message || 'Failed to add department.');
            }
        })
        .catch(() => alert('Error adding department.'));
    });
});
</script>
    <?php
} elseif (in_array('companymanager', $manag_role)) {
    ?>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const companySelect = document.getElementById('cid');
    const managerSelect = document.getElementById('mid');
    const deptManagerSelect = document.getElementById('deptmanager');
    const departmentSelect = document.getElementById('departments');
    const addDepartmentButton = document.getElementById('add-department');
    const newDepartmentInput = document.getElementById('new-department');
    deptManagerSelect?.addEventListener('change', function () {
        const deptManagerId = this.value;
        if (!deptManagerId) return;
        fetch(`ajax/get_departments.php?deptmanagerid=${deptManagerId}`)
            .then(res => res.json())
            .then(data => {
                departmentSelect.innerHTML = '';
                data.forEach(dep => {
                    const li = document.createElement('li');
                    li.className = 'list-group-item d-flex justify-content-between align-items-center flex-wrap';
                    li.innerHTML = `
                        <span class="dept-name" data-id="${dep.id}">${dep.name}</span>
                        <div>
                            <button type="button" class="btn btn-sm btn-primary me-1 manage-dept my-2" data-id="${dep.id}">Manage Individual Users</button>
                            <button type="button" class="btn btn-sm btn-warning me-1 edit-dept my-2" data-id="${dep.id}" data-name="${dep.name}">Edit</button>
                            <button type="button" class="btn btn-sm btn-danger delete-dept my-2" data-id="${dep.id}">Delete</button>
                        </div>
                    `;
                    departmentSelect.appendChild(li);
                });
                departmentSelect.disabled = true;
            });
    });
    addDepartmentButton?.addEventListener('click', function () {
        const deptName = newDepartmentInput.value.trim();
        const deptManagerId = deptManagerSelect?.value ?? $('input[name="bid"]').val();
        const companyid = $('input[name="cid"]').val();
        if (!deptName || !deptManagerId) {
            alert('Please enter a department name, select a department manager, and ensure business user ID is set.');
            return;
        }
        fetch('ajax/add_department.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                name: deptName,
                deptmanagerid: deptManagerId,
                companyid: companyid
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const li = document.createElement('li');
                li.className = 'list-group-item d-flex justify-content-between align-items-center flex-wrap';
                li.innerHTML = `
                    <span class="dept-name" data-id="${data.id}">${data.name}</span>
                    <div>
                        <button type="button" class="btn btn-sm btn-primary me-1 manage-dept my-2" data-id="${data.id}">Manage Individual Users</button>
                        <button type="button" class="btn btn-sm btn-warning me-1 edit-dept my-2" data-id="${data.id}" data-name="${data.name}">Edit</button>
                        <button type="button" class="btn btn-sm btn-danger delete-dept my-2" data-id="${data.id}">Delete</button>
                    </div>
                `;
                departmentSelect.appendChild(li);
                departmentSelect.disabled = true;
                newDepartmentInput.value = '';
            } else {
                alert(data.message || 'Failed to add department.');
            }
        })
        .catch(() => alert('Error adding department.'));
    });
});
</script>
    <?php
} elseif (in_array('companydepartmentmanager', $manag_role) || in_array('companyreporter', $manag_role)) {
    ?>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const companySelect = document.getElementById('cid');
    const deptManagerSelect = document.getElementById('bid');
    const departmentSelect = document.getElementById('departments');
    const addDepartmentButton = document.getElementById('add-department');
    const newDepartmentInput = document.getElementById('new-department');
    console.log('companySelect', companySelect);
    console.log('deptManagerSelect', deptManagerSelect);
    console.log('departmentSelect', departmentSelect);
    console.log('addDepartmentButton', addDepartmentButton);
    console.log('newDepartmentInput', newDepartmentInput);
    deptManagerSelect?.addEventListener('change', function () {
        const deptManagerId = this.value;
        if (!deptManagerId) return;
        fetch(`ajax/get_departments.php?deptmanagerid=${deptManagerId}`)
            .then(res => res.json())
            .then(data => {
                departmentSelect.innerHTML = '';
                data.forEach(dep => {
                    const li = document.createElement('li');
                    li.className = 'list-group-item d-flex justify-content-between align-items-center flex-wrap';
                    li.innerHTML = `
                        <span class="dept-name" data-id="${dep.id}">${dep.name}</span>
                        <div>
                            <button type="button" class="btn btn-sm btn-primary me-1 manage-dept my-2" data-id="${dep.id}">Manage Individual Users</button>
                            <button type="button" class="btn btn-sm btn-warning me-1 edit-dept my-2" data-id="${dep.id}" data-name="${dep.name}">Edit</button>
                            <button type="button" class="btn btn-sm btn-danger delete-dept my-2" data-id="${dep.id}">Delete</button>
                        </div>
                    `;
                    departmentSelect.appendChild(li);
                });
                departmentSelect.disabled = true;
            });
    });
    addDepartmentButton?.addEventListener('click', function () {
        const deptName = newDepartmentInput.value.trim();
        const deptManagerId = deptManagerSelect?.value ?? $('input[name="bid"]').val();
        const companyid = $('input[name="cid"]').val();
        if (!deptName || !deptManagerId) {
            alert('Please enter a department name, select a department manager, and ensure business user ID is set.');
            return;
        }
        fetch('ajax/add_department.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                name: deptName,
                deptmanagerid: deptManagerId,
                companyid: companyid
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const li = document.createElement('li');
                li.className = 'list-group-item d-flex justify-content-between align-items-center flex-wrap';
                li.innerHTML = `
                    <span class="dept-name" data-id="${data.id}">${data.name}</span>
                    <div>
                        <button type="button" class="btn btn-sm btn-primary me-1 manage-dept my-2" data-id="${data.id}">Manage Individual Users</button>
                        <button type="button" class="btn btn-sm btn-warning me-1 edit-dept my-2" data-id="${data.id}" data-name="${data.name}">Edit</button>
                        <button type="button" class="btn btn-sm btn-danger delete-dept my-2" data-id="${data.id}">Delete</button>
                    </div>
                `;
                departmentSelect.appendChild(li);
                departmentSelect.disabled = true;
                newDepartmentInput.value = '';
            } else {
                alert(data.message || 'Failed to add department.');
            }
        })
        .catch(() => alert('Error adding department.'));
    });
});
</script>
    <?php
}
?>
<script type="text/javascript">
document.addEventListener('click', function (e) {
    if (e.target.classList.contains('manage-dept')) {
        const deptId = e.target.dataset.id;
        const baseUrl = $("input[name='aid']").val();
        const finalUrl = baseUrl + "?id=" + deptId;
        window.location.href = finalUrl;
    }
    if (e.target.classList.contains('edit-dept')) {
        const deptId = e.target.dataset.id;
        const currentName = e.target.dataset.name;
        const newName = prompt('Edit Department Name:', currentName);
        if (newName && newName !== currentName) {
            fetch('ajax/edit_department.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ id: deptId, name: newName })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    e.target.closest('li').querySelector('.dept-name').textContent = newName;
                    e.target.dataset.name = newName;
                } else {
                    alert(data.message || 'Failed to update department.');
                }
            })
            .catch(() => window.location.href = window.location.href );
        }
    }
    if (e.target.classList.contains('delete-dept')) {
        const deptId = e.target.dataset.id;
        if (confirm('Are you sure you want to delete this department?')) {
            fetch('ajax/delete_department.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ id: deptId })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    e.target.closest('li').remove();
                } else {
                    alert(data.message || 'Failed to delete department.');
                }
            })
            .catch(() => alert('Error deleting department.'));
        }
    }
});
</script>



