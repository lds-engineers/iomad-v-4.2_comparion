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

// We get these from the URL.
$start_date = optional_param('start_date', '', PARAM_TEXT);
$end_date   = optional_param('end_date', '', PARAM_TEXT);

$linktext = get_string('orders', 'block_learnpeek_shop');
$linkurl = new moodle_url('/blocks/learnpeek_shop/orderlist.php');

$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);
$PAGE->set_heading($linktext);

// IMPORTANT: We include the dates in the baseurl so sorting/paging doesn't lose them.
$baseurl = new moodle_url('/blocks/learnpeek_shop/orderlist.php',
                          ['sort' => $sort,
                           'dir' => $dir,
                           'perpage' => $perpage,
                           'start_date' => $start_date,
                           'end_date' => $end_date]);

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

// Logic for Admin/Manager 1
if(is_siteadmin() || $managertype == 1){
    $userfields = \core_user\fields::for_name()->with_identity($context)->excluding('id', 'deleted', 'firstname', 'lastname');
    $usersql = $userfields->get_sql('u');
    $selectsql ="i.id as invoiceid,
                 i.reference, i.status, i.paymentid, i.date, p.gateway, p.accountid, u.id, i.email,
                 (SELECT GROUP_CONCAT(DISTINCT css.name SEPARATOR '<br>') FROM {invoiceitem} ii LEFT JOIN {course_shopsettings} css ON ii.invoiceableitemid = css.id INNER JOIN {course_shopsettings_courses} csc ON csc.itemid = ii.invoiceableitemid WHERE ii.invoiceid = i.id) AS coursepurchased,
                CONCAT((SELECT FORMAT(SUM(ii.price * COALESCE(ii.license_allocation,1)), 2) FROM {invoiceitem} ii WHERE ii.invoiceid = i.id),' ',(SELECT DISTINCT(ii.currency) FROM {invoiceitem} ii WHERE ii.invoiceid = i.id LIMIT 1 )) AS whitelabelcourseprice,
                
                (SELECT SUM(price) FROM {invoiceitem} ii WHERE ii.invoiceid = i.id) AS value,
                CONCAT(FORMAT(p.amount, 2), ' ', (SELECT DISTINCT(currency) FROM {invoiceitem} ii WHERE ii.invoiceid = i.id)) AS amount,          
                (SELECT COUNT(*) FROM {invoiceitem} ii WHERE ii.invoiceid = i.id AND processed = 0) AS unprocesseditems,
                (SELECT DISTINCT(currency) FROM {invoiceitem} ii WHERE ii.invoiceid = i.id) AS currency,              
                u.firstname AS firstname, u.lastname AS lastname" . $usersql->selects;
    
    $fromsql = "{invoice} i JOIN {user} u ON (i.userid = u.id) LEFT JOIN {payments} p ON (i.paymentid = p.id)";
    $wheresql = " i.companyid = :companyid AND i.status IN ('p', 'pr', 'r') AND u.deleted = 0";
    $sqlparams = ['companyid' => $companyid];
    // echo "SELECT $selectsql FROM $fromsql WHERE $wheresql";

    $headers = [get_string('orderid', 'block_learnpeek_shop'), get_string('date'), get_string('fullname'), get_string('email'), get_string('paymentprovider', 'block_learnpeek_shop'), get_string('amount', 'block_learnpeek_shop'), get_string('status'), get_string('coursepurchased', 'block_learnpeek_shop'), get_string('whitelabelcourseprice', 'block_learnpeek_shop'), get_string('unprocesseditems', 'block_learnpeek_shop')];
    $columns = ['invoiceid', 'date', 'fullname', 'email', 'paymentprovider', 'amount', 'status', 'coursepurchased', 'whitelabelcourseprice', 'unprocesseditems'];

    $table = new \block_learnpeek_shop\tables\orders_table('block_learnpeek_shop_orders_table');
    $table->define_baseurl($baseurl);
    $table->no_sorting('paymentprovider');
    $table->sort_default_column = 'date DESC';
    $table->is_downloading($download, format_string($company->get('name')) . ' invoices ' . format_string(date($CFG->iomad_date_format, time())), 'companyinvoices');
    $table->sortable(true, 'date',SORT_DESC);
    if (!$table->is_downloading()) {
        $columns[]= 'actions';
        $headers[]= get_string('actions', 'block_learnpeek_shop');
        $help[] = NULL;
        echo $OUTPUT->header();
        // The form now specifically sends to the base URL to keep parameters clean
        echo '<form method="get" action="orderlist.php">
            <input type="date" name="start_date" value="'.s($start_date).'">
            <input type="date" name="end_date" value="'.s($end_date).'">
            <button type="submit">'.get_string('search', 'block_learnpeek_shop').'</button>
            <a href="orderlist.php" class="btn btn-link">'.get_string('clear', 'block_learnpeek_shop').'</a>
        </form>';
    }
    $table->define_columns($columns);
    $table->define_headers($headers);

   if (!empty($start_date) && !empty($end_date)) {
        $wheresql .= " AND i.date BETWEEN :start_date AND :end_date";
        $sqlparams['start_date'] = strtotime($start_date . ' 00:00:00 UTC');
        $sqlparams['end_date']   = strtotime($end_date . ' 23:59:59 UTC');
    } elseif (!empty($start_date)) {
        $wheresql .= " AND i.date >= :start_date";
        $sqlparams['start_date'] = strtotime($start_date . ' 00:00:00 UTC');
    } elseif (!empty($end_date)) {
        $wheresql .= " AND i.date <= :end_date";
        $sqlparams['end_date'] = strtotime($end_date . ' 23:59:59 UTC');
    }
    
    $table->set_sql($selectsql, $fromsql, $wheresql, $sqlparams);
    $table->out($CFG->iomad_max_list_users, true);

} else {
    // Logic for other users
    $userids_str = implode(",", $userids);
    $userfields = \core_user\fields::for_name()->with_identity($context)->excluding('id', 'deleted', 'firstname', 'lastname');
    $usersql = $userfields->get_sql('u');
    $selectsql = "i.id as invoiceid, i.reference, i.status, i.paymentid, i.date, p.gateway, p.accountid, u.id, i.email,
                (SELECT SUM(price) FROM {invoiceitem} ii WHERE ii.invoiceid = i.id) AS value, 
                CONCAT(FORMAT(p.amount, 2), ' ', (SELECT DISTINCT(currency) FROM {invoiceitem} ii WHERE ii.invoiceid = i.id)) AS amount,      
                (SELECT COUNT(*) FROM {invoiceitem} ii WHERE ii.invoiceid = i.id AND processed = 0) AS unprocesseditems,
                (SELECT DISTINCT(currency) FROM {invoiceitem} ii WHERE ii.invoiceid = i.id) AS currency,              
                u.firstname AS firstname, u.lastname AS lastname " . $usersql->selects;
    
    $fromsql = "{invoice} i JOIN {user} u ON (i.userid = u.id) LEFT JOIN {payments} p ON (i.paymentid = p.id)";
    $wheresql = " i.companyid = :companyid AND i.userid in($userids_str) AND i.status IN ('p', 'pr', 'r') AND u.deleted = 0";
    $sqlparams = ['companyid' => $companyid];
    $countsql = "SELECT COUNT(1) FROM $fromsql WHERE $wheresql ";
    $hasorders = $DB->count_records_sql($countsql, $sqlparams);

    $headers = [get_string('orderid', 'block_learnpeek_shop'), get_string('date'), get_string('fullname'), get_string('email'), get_string('paymentprovider', 'block_learnpeek_shop'), get_string('amount', 'block_learnpeek_shop'), get_string('status'), get_string('coursepurchased', 'block_learnpeek_shop'), get_string('unprocesseditems', 'block_learnpeek_shop')];
    $columns = ['invoiceid', 'date', 'fullname', 'email', 'paymentprovider', 'amount', 'status', 'coursepurchased', 'unprocesseditems'];

    $table = new \block_learnpeek_shop\tables\orders_table('block_learnpeek_shop_orders_table');
    $table->define_baseurl($baseurl);
    
    $table->no_sorting('email');
    $table->no_sorting('fullname');
    $table->no_sorting('paymentprovider');
    $table->no_sorting('view');
    $table->sort_default_column = 'date DESC';
    $table->is_downloading($download, format_string($company->get('name')) . ' invoices ' . format_string(date($CFG->iomad_date_format, time())), 'companyinvoices');
    
    if (!$table->is_downloading()) {

        $columns[]= 'view';
        $headers[]= get_string('action', 'block_learnpeek_shop');
        $help[] = NULL;
        echo $OUTPUT->header();
         if ($hasorders) {
            echo '<form method="get" action="orderlist.php">
                <input type="date" name="start_date" value="'.s($start_date).'">
                <input type="date" name="end_date" value="'.s($end_date).'">
                <button type="submit">Search</button>
                <a href="orderlist.php" class="btn btn-link">'.get_string('clear', 'block_learnpeek_shop').'</a>
            </form>';
        }
    }
    $table->define_columns($columns);
    $table->define_headers($headers);

   if (!empty($start_date) && !empty($end_date)) {
        $wheresql .= " AND i.date BETWEEN :start_date AND :end_date";
        $sqlparams['start_date'] = strtotime($start_date . ' 00:00:00 UTC');
        $sqlparams['end_date']   = strtotime($end_date . ' 23:59:59 UTC');
    } elseif (!empty($start_date)) {
        $wheresql .= " AND i.date >= :start_date";
        $sqlparams['start_date'] = strtotime($start_date . ' 00:00:00 UTC');
    } elseif (!empty($end_date)) {
        $wheresql .= " AND i.date <= :end_date";
        $sqlparams['end_date'] = strtotime($end_date . ' 23:59:59 UTC');
    }
    
    $table->set_sql($selectsql, $fromsql, $wheresql, $sqlparams);
    $table->out($CFG->iomad_max_list_users, false);
}

echo "<style>
form.dataformatselector .form-inline { gap: 10px; }
form.dataformatselector .form-inline select#downloadtype_download { margin: 0; }
.resettable, .initialbar{ display:none !important; }
.page-header-headings h1 { margin-top: 94px; padding: 0 33px; width: 100%; display: block; }
</style>";

if (!$table->is_downloading()) {
    echo $OUTPUT->footer();
}