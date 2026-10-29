<?php
require_once('../../config.php');
require_once($CFG->libdir . '/tablelib.php');
require $CFG->dirroot."/local/customlayout/test_table.php";
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/local/customlayout/assigneelisting.php');
$companyid = iomad::get_my_companyid(context_system::instance());
$context = context_user::instance($USER->id);
$current_user_roles = get_user_roles($context, $USER->id);
$manag_role = array_column($current_user_roles, 'shortname');

$getcompnayadmin = $DB->get_record_sql("SELECT * FROM {company_users} WHERE companyid = ? AND userid =?",array($companyid,$USER->id));
if ($manag_role[0] != 'companydepartmentmanager' && $manag_role[0] != 'companyreporter') {
    redirect(new moodle_url($CFG->wwwroot.'/my'),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
    die;
}
$download = optional_param('download', '', PARAM_ALPHA);
$search = optional_param('search', '', PARAM_TEXT);

$table = new test_table('uniqueid', $companyid);
$table->is_downloading($download, 'test', 'testing123');

if (!$table->is_downloading()) {
    $PAGE->set_title(get_string('enrolledusers', 'local_customlayout'));
    $PAGE->set_heading(get_string('enrolledusers', 'local_customlayout'));
    $PAGE->navbar->add('Enrolled users', new moodle_url('/local/customlayout/assigneelisting.php'));
    echo $OUTPUT->header();

    echo '<form method="get" action="" class="mb-3 d-flex align-items-center gap-2">';
    echo '<input type="text" name="search" placeholder="Search..." value="' . s($search) . '" class="form-control w-25" />';
    echo '<button type="submit" class="btn btn-primary btn-sm ml-2">'. get_string('search','local_customlayout').'</button>';
    echo '<a href="' . new moodle_url('/local/customlayout/assigneelisting.php') . '" class="btn btn-secondary btn-sm ml-2">'. get_string('clear','local_customlayout').'</a>';
    echo '</form>';


}
$params = [];
$clause = "";
$where  = "u.deleted = 0 AND u.suspended = 0";


// ------companydepartmentmanager ---------//
if ($manag_role[0] == 'companydepartmentmanager') {

    $clause = "LEFT JOIN {company_users} cu 
                   ON cu.userid = u.id AND cu.companyid = :companyid
               LEFT JOIN {business_user_child} buc 
                   ON buc.child_id = u.id";

    $where .= " AND buc.parent_id = :parentid";

    $params['companyid'] = $companyid;
    $params['parentid']  = $USER->id;
}



// ------ companyreporter (Department Manager) -----//
elseif ($manag_role[0] == 'companyreporter') {

    // Step 1: Get departments assigned to logged-in user
    $sql = "SELECT ca.department_id
            FROM {customdepartment_assign} ca
            WHERE ca.child_id = :userid
              AND ca.is_departmentmanager = 1";

    $departments = $DB->get_records_sql($sql, ['userid' => $USER->id]);

    $department_ids = [];
    foreach ($departments as $dept) {
        $department_ids[] = $dept->department_id;
    }

    $clause = "LEFT JOIN {company_users} cu 
                   ON cu.userid = u.id AND cu.companyid = :companyid
               JOIN {customdepartment_assign} cda 
                   ON cda.child_id = u.id";

    $params['companyid'] = $companyid;

    if (!empty($department_ids)) {

        list($insql, $inparams) = $DB->get_in_or_equal($department_ids, SQL_PARAMS_NAMED);

        $where .= " AND cda.department_id $insql";
        $params = array_merge($params, $inparams);

    } else {

        $where .= " AND 1=0";
    }
}


// ------------------------ Only enrolled users  ------------------------

$where .= " AND EXISTS (
    SELECT 1
    FROM {user_enrolments} ue
    JOIN {enrol} e ON e.id = ue.enrolid
    JOIN {course} c ON c.id = e.courseid
    WHERE ue.userid = u.id
)";


// ------------------------ SEARCH FILTER ------------------------

if (!empty($search)) {
    $where .= " AND " . $DB->sql_like(
        "CONCAT(u.firstname, u.lastname, u.email)",
        ":search",
        false
    );
    $params['search'] = "%$search%";
}



// ------------------------ FINAL QUERY ------------------------//
$table->set_sql(
    "DISTINCT u.id,
     u.firstname,
     u.lastname,
     u.email,
     u.address,
     u.phone1,
     f.image,
     f.id AS uploadid,
     (
        SELECT GROUP_CONCAT(DISTINCT c.id)
        FROM {user_enrolments} ue
        JOIN {enrol} e ON e.id = ue.enrolid
        JOIN {course} c ON c.id = e.courseid
        WHERE ue.userid = u.id
     ) AS courseids",

    "{user} u
     LEFT JOIN {lcl_scan_face_recognition} f ON f.userid = u.id
     $clause",

    $where,
    $params
);

// $fields = "u.id, 
//      u.firstname, 
//      u.lastname, 
//      u.email, 
//      u.address, 
//      u.phone1, 
//      f.image, 
//      f.id AS uploadid,
//      ( 
//        SELECT GROUP_CONCAT(DISTINCT c.id)
//        FROM {user_enrolments} ue
//        JOIN {enrol} e ON e.id = ue.enrolid
//        JOIN {course} c ON c.id = e.courseid
//        WHERE ue.userid = u.id
//      ) AS courseids";

// $from = "{user} u
//      LEFT JOIN {lcl_scan_face_recognition} f ON f.userid = u.id
//      $clause";

// $sql = "SELECT $fields FROM $from WHERE $where";

// echo "<pre>";
// print_r($sql);
// print_r($params);
// die;
$baseurl = new moodle_url('/local/customlayout/assigneelisting.php');
if (!empty($search)) {
    $baseurl->param('search', $search);
}
$table->define_baseurl($baseurl);
$table->out(10, true);

if (!$table->is_downloading()) {
    $PAGE->requires->js_init_code("
        require(['jquery'], function($) {

            $(document).on('change', '.company-toggle', function() {
                let row = $(this).closest('td'); 
                var userid = $(this).data('userid');
                var status = $(this).is(':checked') ? 1 : 0;

                $.ajax({
                    url: '{$CFG->wwwroot}/local/customlayout/sst_cart_allow.php',
                    type: 'POST',
                    data: {
                        userid: userid,
                        status: status,
                        companyid: {$companyid}
                    },
                    success: function(response) {
                        // console.log('Saved successfully');
                        var res = JSON.parse(response);
                        if (res.success) {
                            if (status == 1) {
                                row.find('.check-date').text(res.date);
                            } else {
                                row.find('.check-date').text('');
                            }
                        }
                    },
                    error: function() {
                        alert('Error saving data');
                    }
                });

            });

        });
    ");
    echo '<style type="text/css">
            .dataformatselector {
                display: none !important;
            }
            .no-overflow {
               overflow-x:auto;
            }

            body#page-local-customlayout-studentslisting table.flexible {
                    border: 1px solid #ccc !important;
                }
             


            body#page-local-customlayout-studentslisting div#topofscroll {
                 max-width: 94% !important;
                 margin-left: auto;
                 margin-right: 0;
            }


            @media(max-width:767px){
              body#page-local-customlayout-studentslisting .page-header-headings h1 {
                margin-top: 20px;
                padding: 0;
            }
            body#page-local-customlayout-studentslisting .page-context-header{
                margin-top: 0px; 
            }
            
           body#page-local-customlayout-studentslisting div#topofscroll {
                width: auto;
                padding: 0;
            }
              body#page-local-customlayout-studentslisting th {
                padding: 5px;
                font-size: 14px;
                white-space: nowrap;
            }
              body#page-local-customlayout-studentslisting th a {
                padding: 5px !important;
                display: block;
            }

            body#page-local-customlayout-studentslisting .generaltable th,  
            body#page-local-customlayout-studentslisting .generaltable td{
              padding:5px;
            }
            
           body#page-local-customlayout-studentslisting table.flexible table.table {
            font-size: 12px !important;
            white-space: nowrap;
        }

            }

            </style>';
    echo $OUTPUT->footer();
}

?>