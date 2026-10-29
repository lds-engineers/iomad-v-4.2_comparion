<?php
require_once(dirname(__FILE__) . '/../iomad_company_admin/lib.php');

\block_iomad_commerce\helper::require_commerce_enabled();

$sort     = optional_param('sort', 'name', PARAM_ALPHA);
$dir      = optional_param('dir', 'ASC', PARAM_ALPHA);
$page     = optional_param('page', 0, PARAM_INT);
$perpage  = optional_param('perpage', 30, PARAM_INT);        // How many per page.
$download = optional_param('download', 0, PARAM_CLEAN);

$context = context_system::instance();
require_login();


// Correct the navbar.
// Set the name for the page.
$linktext = get_string('orders', 'block_learnpeek_shop');
// Set the url.
$linkurl = new moodle_url('/blocks/learnpeek_shop/orderlist.php');

// Print the page header.
// $PAGE->set_context($context);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);

// Set the page heading.
$PAGE->set_heading($linktext);

$baseurl = new moodle_url('/blocks/learnpeek_shop/orderlist.php',
                          ['sort' => $sort,
                           'dir' => $dir,
                           'perpage' => $perpage]);
$returnurl = $baseurl;
$companyid = iomad::get_my_companyid($context);
if(empty($companyid)){
  redirect('/my', 'You are not purchased any course', null, \core\output\notification::NOTIFY_SUCCESS);
  die;
}
$company = new company($companyid);
$managerrole = $DB->get_record_sql('SELECT * FROM {company_users} WHERE userid = ? AND companyid = ?', array($USER->id,$company->id));
 $managertype = $managerrole->managertype;
$userids = array($USER->id);
if($managertype == 2){
	$businestrainer = $DB->get_fieldset_sql("SELECT u.id FROM {business_user_child} as buc INNER JOIN {user} as u ON buc.child_id = u.id WHERE parent_id= ? AND company_id = ? AND u.suspended = 0", array($USER->id,$companyid));
	$userids = array_merge($userids, $businestrainer);
}

$sql = "SELECT ca.parent_id, ca.department_id FROM {customdepartment_assign} ca 
                    INNER JOIN {customdepartment} AS c ON ca.department_id = c.id 
                    WHERE ca.child_id =:child_id AND ca.is_departmentmanager = 1 ORDER BY ca.id DESC";
if($department = $DB->get_record_sql($sql,['child_id'=>$USER->id])){
	$userids = array($USER->id);
	$department_id = $department->department_id;
	$sql = "SELECT child_id FROM {customdepartment_assign} WHERE department_id =:department_id AND child_id != $USER->id ORDER BY id ASC";
	$businestrainer = $DB->get_fieldset_sql($sql, ['department_id'=>$department_id]);
	$userids = array_merge($userids, $businestrainer);

}



if(is_siteadmin() || $managertype == 1){
	$userfields = \core_user\fields::for_name()->with_identity($context)->excluding('id', 'deleted', 'firstname', 'lastname');
	$usersql = $userfields->get_sql('u');
	$selectsql ="i.id as invoiceid,
	            i.reference,
	            i.status,
	            i.paymentid,
	            i.date,
	            p.gateway,
	            p.accountid,
	            u.id,
	            i.email,
	            (SELECT SUM(price) FROM {invoiceitem} ii WHERE ii.invoiceid = i.id)
	            AS value,
	            CONCAT(FORMAT(p.amount, 2), ' ', (SELECT DISTINCT(currency) FROM {invoiceitem} ii WHERE ii.invoiceid = i.id)) AS amount,          
	            (SELECT COUNT(*) FROM {invoiceitem} ii WHERE ii.invoiceid = i.id AND processed = 0)
	            AS unprocesseditems,
	            (SELECT DISTINCT(currency) FROM {invoiceitem} ii WHERE ii.invoiceid = i.id)
	            AS currency,             
	            u.firstname AS firstname,
	            u.lastname AS lastname " . $usersql->selects;
	$fromsql = "{invoice} i JOIN {user} u ON (i.userid = u.id) LEFT JOIN {payments} p ON (i.paymentid = p.id)";
	$wheresql = " i.companyid = :companyid AND i.status='p' AND u.deleted = 0";
	$sqlparams = ['companyid' => $companyid];

	//Set up the table headers.
	$headers = [
			  get_string('reference', 'block_learnpeek_shop'),
	          get_string('date'),
	          get_string('fullname'),
	          get_string('email'),
	          get_string('paymentprovider', 'block_learnpeek_shop'),
	          // get_string('value', 'block_learnpeek_shop'),
	          'Amount',
	          get_string('status'),
	          get_string('unprocesseditems', 'block_learnpeek_shop'),
	          'Actions'
	      ];
	$columns = ['reference',
	          'date',
	          'fullname',
	          'email',
	          'paymentprovider',
	          // 'value',
	          'amount',
	          'status',
	          'unprocesseditems',
	          'view'
	      ];

	// Actually create and display the table.
	$table = new \block_learnpeek_shop\tables\orders_table('block_learnpeek_shop_orders_table');
	$table->set_sql($selectsql, $fromsql, $wheresql, $sqlparams);
	$table->define_baseurl($baseurl);
	$table->define_columns($columns);
	$table->define_headers($headers);
	$table->no_sorting('paymentprovider');
	$table->no_sorting('view');
	$table->sort_default_column = 'date DESC';
	$table->is_downloading($download, format_string($company->get('name')) . ' invoices ' . format_string(date($CFG->iomad_date_format, time())), 'companyinvoices');


	if (!$table->is_downloading()) {
	    echo $OUTPUT->header();
	}

	$table->out($CFG->iomad_max_list_users,true);

}else{
	$userids = implode(",", $userids);
	$userfields = \core_user\fields::for_name()->with_identity($context)->excluding('id', 'deleted', 'firstname', 'lastname');
	$usersql = $userfields->get_sql('u');
	$selectsql = "i.id as invoiceid,
	            i.reference,
	            i.status,
	            i.paymentid,
	            i.date,
	            p.gateway,
	            p.accountid,
	            u.id,
	            i.email,
	            (SELECT SUM(price) FROM {invoiceitem} ii WHERE ii.invoiceid = i.id)
	            AS value, 
	            CONCAT(FORMAT(p.amount, 2), ' ', (SELECT DISTINCT(currency) FROM {invoiceitem} ii WHERE ii.invoiceid = i.id)) AS amount,      
	            (SELECT COUNT(*) FROM {invoiceitem} ii WHERE ii.invoiceid = i.id AND processed = 0)
	            AS unprocesseditems,
	            (SELECT DISTINCT(currency) FROM {invoiceitem} ii WHERE ii.invoiceid = i.id)
	            AS currency,             
	            u.firstname AS firstname,
	            u.lastname AS lastname " . $usersql->selects;
	$fromsql = "{invoice} i JOIN {user} u ON (i.userid = u.id) LEFT JOIN {payments} p ON (i.paymentid = p.id)";
	$wheresql = " i.companyid = :companyid AND i.userid in($userids) AND i.status='p' AND u.deleted = 0";
	// $sqlparams = ['companyid' => $companyid];
	$sqlparams = [
	'companyid' => $companyid
	];
	// die;
	//Set up the table headers.
	$headers = [get_string('reference', 'block_learnpeek_shop'),
	          get_string('date'),
	          get_string('fullname'),
	          get_string('email'),
	          get_string('paymentprovider', 'block_learnpeek_shop'),
	          // get_string('value', 'block_learnpeek_shop'),
	         'Amount',
	          get_string('status'),
	          get_string('unprocesseditems', 'block_learnpeek_shop'),
	          'Actions'
	      ];
	$columns = ['reference',
	          'date',
	          'fullname',
	          'email',
	          'paymentprovider',
	          // 'value',
	          'amount',
	          'status',
	          'unprocesseditems',
	          'view'
	      ];

	// Actually create and display the table.
	$table = new \block_learnpeek_shop\tables\orders_table('block_learnpeek_shop_orders_table');
	$table->set_sql($selectsql, $fromsql, $wheresql, $sqlparams);
	$table->define_baseurl($baseurl);
	$table->define_columns($columns);
	$table->define_headers($headers);
	$table->no_sorting('email');
	$table->no_sorting('fullname');
	$table->no_sorting('paymentprovider');
	$table->no_sorting('view');
	$table->sort_default_column = 'date DESC';
	$table->is_downloading($download, format_string($company->get('name')) . ' invoices ' . format_string(date($CFG->iomad_date_format, time())), 'companyinvoices');
	if (!$table->is_downloading()) {
	    echo $OUTPUT->header();
	}

	$table->out($CFG->iomad_max_list_users,false);
}

if (!$table->is_downloading()) {
    echo $OUTPUT->footer();
}