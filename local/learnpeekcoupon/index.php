<?php
require("../../config.php");
global $DB,$USER,$CFG;
require_login();
// Set up page context and layout
// $PAGE->set_url(new moodle_url("/local/learnpeekcoupon/index.php"));
// $PAGE->set_context(context_system::instance());
// $PAGE->set_title('Coupon');
// $PAGE->set_heading('Coupon');
// echo $OUTPUT->header();
$title = get_string('coupon', 'local_learnpeekcoupon');

$PAGE->set_url(new moodle_url("/local/learnpeekcoupon/index.php"));
$PAGE->set_context(context_system::instance());
$PAGE->set_title($title);
$PAGE->set_heading($title);
echo $OUTPUT->header();

// Fetch all coupon records

// $companyid = iomad::get_my_companyid(context_system::instance());

$com_id = iomad::get_my_companyid(context_system::instance());
// $companydata = $DB->get_record("company",array("id"=>$com_id));

$getcompnayadmin = $DB->get_record_sql("SELECT * FROM {company_users} WHERE companyid = ? AND userid =?",array($com_id,$USER->id));

if(is_siteadmin() || $getcompnayadmin->managertype ==1){

    if(is_siteadmin()){
        $records = $DB->get_records_sql("SELECT * FROM {local_coupon} c ORDER BY c.id ASC");

    }else{
        // echo $com_id;
        $records = $DB->get_records_sql("SELECT * FROM {local_coupon} WHERE  companyid = ?",array($com_id));

    }


//     echo '
//     <style>
//     .page-header-headings h1 {
// 			margin-top: 94px;
// 			padding: 0 33px;
// 			width: 100%;
// 			display: block;
// 		}
//     </style>
// ';

    // Link to create new coupon
    echo '<a class="btn btn-primary" href="'.$CFG->wwwroot.'/local/learnpeekcoupon/create.php" role="button">' . get_string('createnewcoupon', 'local_learnpeekcoupon') .' </a>';
    echo html_writer::empty_tag('br');
    echo html_writer::empty_tag('br');


    

    // Build table structure
    $table = new html_table();
    $table->head = [
    get_string('code', 'local_learnpeekcoupon'),
    get_string('company', 'local_learnpeekcoupon'),
    get_string('maxuses', 'local_learnpeekcoupon'),
    get_string('maxusesuser', 'local_learnpeekcoupon'),
    get_string('type', 'local_learnpeekcoupon'),
    get_string('discount_amount', 'local_learnpeekcoupon'),
    get_string('min_amount', 'local_learnpeekcoupon'),
    get_string('userscope', 'local_learnpeekcoupon'),
    get_string('productscope', 'local_learnpeekcoupon'),
    get_string('status', 'local_learnpeekcoupon'),
    get_string('startsat', 'local_learnpeekcoupon'),
    get_string('expiresat', 'local_learnpeekcoupon'),
    get_string('action', 'local_learnpeekcoupon')
    ];

   

    $table->data = [];
    //  echo "<pre>";
    // var_dump($records);
    // echo "</pre>";
    // exit();

     

    foreach ($records as $record) {
        $company = $DB->get_record_sql("SELECT c.name FROM {company} c WHERE id = ?", [$record->companyid]);
        $companyname = $company ? $company->name : '-';

        $editurl = new moodle_url('/local/learnpeekcoupon/edit.php', ['id' => $record->id]);
        $deleteurl = new moodle_url('/local/learnpeekcoupon/delete.php', ['id' => $record->id]);


        $user_scope = $record->allow_all_users ? 'All' : 'Selected';
        $product_scope = $record->allow_all_products ? 'All' : 'Selected';
        $status = $record->status ? 'Active' : 'Inactive';

        $table->data[] = [
            // $record->id, 
            $record->code,
            // $record->description,
            $companyname,
            $record->max_uses,
            $record->max_uses_user,
            $record->type,
            $record->discount_amount,
            $record->min_amount,
            $user_scope,
            $product_scope,
            $status,
            $record->starts_at == 0 ? "-" : userdate($record->starts_at) ,
            $record->expires_at == 0 ? "-" : userdate($record->expires_at),
            // html_writer::link($editurl, 'Edit'),
            // html_writer::link($deleteurl, 'Delete'),
            //  html_writer::link($editurl, 'Edit') . ' | ' . html_writer::link($deleteurl, 'Delete'),
            html_writer::link($editurl, get_string('edit', 'local_learnpeekcoupon')) . ' | ' . 
            html_writer::link($deleteurl, get_string('delete', 'local_learnpeekcoupon'))
        ];
    }

    // Render the table
    echo html_writer::table($table);
}else{
    redirect(new moodle_url($CFG->wwwroot.'/my'),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
}
echo $OUTPUT->footer();
