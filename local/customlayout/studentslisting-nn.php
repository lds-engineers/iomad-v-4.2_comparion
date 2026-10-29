<?php
require_once('../../config.php');
require_once($CFG->libdir . '/tablelib.php');
require $CFG->dirroot."/local/customlayout/test_table.php";
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/local/customlayout/studentslisting.php');
$companyid = iomad::get_my_companyid(context_system::instance());
$getcompnayadmin = $DB->get_record_sql("SELECT * FROM {company_users} WHERE companyid = ? AND userid =?",array($companyid,$USER->id));
if (!is_siteadmin() && (!$getcompnayadmin || $getcompnayadmin->managertype != 1)) {
    redirect(new moodle_url($CFG->wwwroot.'/my'),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
     die;
}
$download = optional_param('download', '', PARAM_ALPHA);
$search = optional_param('search', '', PARAM_TEXT);

$table = new test_table('uniqueid', $companyid);
$table->is_downloading($download, 'test', 'testing123');

if (!$table->is_downloading()) {
    $PAGE->set_title('Enrolled users');
    $PAGE->set_heading('Enrolled users');
    $PAGE->navbar->add('Enrolled users', new moodle_url('/local/customlayout/studentslisting.php'));
    echo $OUTPUT->header();

    echo '<form method="get" action="" class="mb-3 d-flex align-items-center gap-2">';
    echo '<input type="text" name="search" placeholder="Search..." value="' . s($search) . '" class="form-control w-25" />';
    echo '<button type="submit" class="btn btn-primary btn-sm ml-2">Search</button>';
    echo '<a href="' . new moodle_url('/local/customlayout/studentslisting.php') . '" class="btn btn-secondary btn-sm ml-2">Clear</a>';
    echo '</form>';


}

$clause = "";
if(!is_siteadmin()) {
    $clause = "LEFT JOIN {company_users} cu ON cu.userid = u.id AND cu.companyid = $companyid";
} else {
    $clause = "LEFT JOIN {company_users} cu ON cu.userid = u.id";
}

$where = '1=1 AND u.deleted = 0 AND u.suspended = 0 AND cu.managertype = 0
          AND EXISTS (
            SELECT 1
            FROM {user_enrolments} ue
            JOIN {enrol} e ON e.id = ue.enrolid
            JOIN {course} c ON c.id = e.courseid
            WHERE ue.userid = u.id
          )';
          
          
if (!empty($search)) {
    $like = $DB->sql_like('CONCAT(u.firstname, u.lastname, u.email)', ':search', false);
    $where .= " AND $like";
}


$params = [];
if (!empty($search)) {
    $params['search'] = "%$search%";
}

$table->set_sql(
    "u.id, 
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
// echo $sql = "SELECT u.id, u.firstname, u.lastname, u.email, u.address, u.phone1,f.image,f.id AS uploadid,
//     (
//         SELECT GROUP_CONCAT(DISTINCT c.id)
//         FROM {user_enrolments} ue
//         JOIN {enrol} e ON e.id = ue.enrolid
//         JOIN {course} c ON c.id = e.courseid
//         WHERE ue.userid = u.id
//     ) AS courseids
// FROM {user} u
// LEFT JOIN {lcl_scan_face_recognition} f ON f.userid = u.id
// $clause
// WHERE $where";

$baseurl = new moodle_url('/local/customlayout/studentslisting.php');
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