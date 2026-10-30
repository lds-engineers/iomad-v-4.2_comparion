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
// Filters
$couponcode       = optional_param('couponcode', '', PARAM_TEXT);
$selectedbusiness = optional_param('businessid', 0, PARAM_INT);
$selecteddept     = optional_param('departmentid', 0, PARAM_INT);
$searchtext       = optional_param('searchtext', '', PARAM_TEXT);
$productname      = optional_param('productname', '', PARAM_TEXT);
$start_date       = optional_param('start_date', '', PARAM_TEXT);
$end_date         = optional_param('end_date', '', PARAM_TEXT);

$linktext = get_string('orders', 'block_learnpeek_shop');
$linkurl = new moodle_url('/blocks/learnpeek_shop/orderlist.php');

$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);
$PAGE->set_heading($linktext);

$qs  = $_SERVER['QUERY_STRING'];
// IMPORTANT: We include the dates in the baseurl so sorting/paging doesn't lose them.
$baseurl = new moodle_url('/blocks/learnpeek_shop/orderlist.php?'.$qs);

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
    // get all business users trainee list
    $businestrainer = $DB->get_fieldset_sql("SELECT u.id FROM {business_user_child} as buc INNER JOIN {user} as u ON buc.child_id = u.id WHERE parent_id= ? AND company_id = ? AND u.suspended = 0", array($USER->id,$companyid));
    $userids = array_merge($userids, $businestrainer);
}

// echo $sql = "SELECT ca.parent_id, ca.department_id FROM {customdepartment_assign} ca 
//                     INNER JOIN {customdepartment} AS c ON ca.department_id = c.id 
//                     WHERE ca.child_id =:child_id AND ca.is_departmentmanager = 1 ORDER BY ca.id DESC";
// if($department = $DB->get_record_sql($sql,['child_id'=>$USER->id])){
//     $userids = array($USER->id);
//     $department_id = $department->department_id;
//     $sql = "SELECT child_id FROM {customdepartment_assign} WHERE department_id =:department_id AND child_id != $USER->id ORDER BY id ASC";
//     $businestrainer = $DB->get_fieldset_sql($sql, ['department_id'=>$department_id]);
//     $userids = array_merge($userids, $businestrainer);
// }

// Step 1: Get all departments where user is manager
$sql = "SELECT ca.id, ca.parent_id, ca.department_id
        FROM {customdepartment_assign} ca
        INNER JOIN {customdepartment} c ON ca.department_id = c.id
        WHERE ca.child_id = :child_id 
        AND ca.is_departmentmanager = 1
        ORDER BY ca.id DESC";

$departments = $DB->get_records_sql($sql, ['child_id' => $USER->id]);

// Step Extract department IDs
$department_ids = [];
foreach ($departments as $dept) {
    $department_ids[] = $dept->department_id;
}

$businestrainer = [];

if (!empty($department_ids)) {
   // code for get all department users trainee list
    // SAFE way to build IN clause
    list($insql, $params) = $DB->get_in_or_equal($department_ids, SQL_PARAMS_NAMED);

    // Add user exclusion param
    $params['userid'] = $USER->id;

    $sql = "SELECT 
                cda.child_id,
                GROUP_CONCAT(c.name SEPARATOR '<br>') AS departments,
                MAX(cda.is_departmentmanager) AS is_departmentmanager
            FROM {customdepartment_assign} cda
            JOIN {customdepartment} c ON c.id = cda.department_id
            JOIN {user} u ON u.id = cda.child_id
            WHERE cda.department_id $insql
              AND cda.child_id != :userid
              AND u.deleted = 0
              AND u.suspended = 0
            GROUP BY cda.child_id
            ORDER BY u.firstname ASC";

    $businestrainer = $DB->get_records_sql($sql, $params);
    $userids = [$USER->id];
    $userids = array_merge($userids, array_keys($businestrainer));
}

// Logic for Admin/Manager 1
if(is_siteadmin() || $managertype == 1){
    // we need to select business user and department user filter for this user else condition are working currectly
    $userfields = \core_user\fields::for_name()->with_identity($context)->excluding('id', 'deleted', 'firstname', 'lastname');
    $usersql = $userfields->get_sql('u');
    $selectsql ="i.id as invoiceid,
                 i.reference, i.status, i.paymentid, i.date, p.gateway, p.accountid, u.id, i.email,
                 (SELECT GROUP_CONCAT(DISTINCT css.name SEPARATOR '<br>') FROM {invoiceitem} ii LEFT JOIN {course_shopsettings} css ON ii.invoiceableitemid = css.id INNER JOIN {course_shopsettings_courses} csc ON csc.itemid = ii.invoiceableitemid WHERE ii.invoiceid = i.id) AS coursepurchased,

                /*CONCAT((SELECT FORMAT(SUM(ii.price * COALESCE(ii.license_allocation,1)), 2) FROM {invoiceitem} ii WHERE ii.invoiceid = i.id),' ',(SELECT DISTINCT(ii.currency) FROM {invoiceitem} ii WHERE ii.invoiceid = i.id LIMIT 1 )) AS whitelabelcourseprice,*/

                CONCAT(
                    CASE 
                        WHEN (SELECT DISTINCT(ii.currency) FROM {invoiceitem} ii WHERE ii.invoiceid = i.id LIMIT 1) = 'USD'
                        THEN '$'
                        ELSE (SELECT DISTINCT(ii.currency) FROM {invoiceitem} ii WHERE ii.invoiceid = i.id LIMIT 1)
                    END,
                    (SELECT FORMAT(SUM(ii.price * COALESCE(ii.license_allocation,1)), 2)
                     FROM {invoiceitem} ii WHERE ii.invoiceid = i.id)
                ) AS whitelabelcourseprice,

                (SELECT SUM(price) FROM {invoiceitem} ii WHERE ii.invoiceid = i.id) AS value,
                /* CONCAT(FORMAT(p.amount, 2), ' ', (SELECT DISTINCT(currency) FROM {invoiceitem} ii WHERE ii.invoiceid = i.id)) AS amount, */

                CONCAT(
                    CASE 
                        WHEN (SELECT DISTINCT(currency) FROM {invoiceitem} ii WHERE ii.invoiceid = i.id) = 'USD' 
                        THEN '$' 
                        ELSE (SELECT DISTINCT(currency) FROM {invoiceitem} ii WHERE ii.invoiceid = i.id)
                    END,
                    FORMAT(p.amount, 2)
                ) AS amount,

                lcp.code as couponcode,
                (SELECT COUNT(*) FROM {invoiceitem} ii WHERE ii.invoiceid = i.id AND processed = 0) AS unprocesseditems,
                (SELECT DISTINCT(currency) FROM {invoiceitem} ii WHERE ii.invoiceid = i.id) AS currency,              
                u.firstname AS firstname, u.lastname AS lastname" . $usersql->selects;
    
    $fromsql = "{invoice} i JOIN {user} u ON (i.userid = u.id) LEFT JOIN {payments} p ON (i.paymentid = p.id) LEFT JOIN {coupon_invoice} cp ON cp.invoiceid = i.id
            LEFT JOIN {local_coupon} lcp ON lcp.id = cp.couponid";
    $wheresql = " i.companyid = :companyid AND i.status IN ('p', 'pr', 'r') AND u.deleted = 0";
    $sqlparams = ['companyid' => $companyid];
    // print_r($sqlparams); 
    //  echo "SELECT $selectsql FROM $fromsql WHERE $wheresql";

    $headers = [get_string('orderid', 'block_learnpeek_shop'), get_string('date'), get_string('fullname'), get_string('email'), get_string('paymentprovider', 'block_learnpeek_shop'), get_string('amount', 'block_learnpeek_shop'),  get_string('coupon', 'block_learnpeek_shop'), get_string('status'), get_string('coursepurchased', 'block_learnpeek_shop'), get_string('whitelabelcourseprice', 'block_learnpeek_shop'), get_string('unprocesseditems', 'block_learnpeek_shop')];
    $columns = ['invoiceid', 'date', 'fullname', 'email', 'paymentprovider', 'amount', 'couponcode', 'status', 'coursepurchased', 'whitelabelcourseprice', 'unprocesseditems'];

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


        // Business user dropbox 
        $businessusers = $DB->get_records_sql(
            "SELECT cu.userid
             FROM {company_users} cu
             INNER JOIN {user} u ON u.id = cu.userid
             WHERE cu.managertype = 2 AND cu.companyid = ? AND u.deleted = 0",
            [$company->id]
        );

        // Department manager 
        $departments = [];

        if (!empty($selectedbusiness)) {

            $sql = "SELECT 
                        cda.child_id AS id,
                        CONCAT(u.firstname, ' ', u.lastname, ' (', 
                               GROUP_CONCAT(cd.name ORDER BY cd.name SEPARATOR ', '), 
                        ')') AS name
                    FROM {customdepartment_assign} cda
                    INNER JOIN {user} u ON u.id = cda.child_id
                    LEFT JOIN {customdepartment} cd ON cd.id = cda.department_id
                    WHERE cda.parent_id = :parentid
                      AND cda.is_departmentmanager = 1
                      AND u.deleted = 0
                      AND u.suspended = 0
                    GROUP BY cda.child_id, u.firstname, u.lastname
                    ORDER BY u.firstname ASC";

            $departments = $DB->get_records_sql($sql, ['parentid' => $selectedbusiness]);
        }

        echo '<form method="get" action="orderlist.php" 
              style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:12px; align-items:end;">';

        // Coupon
        echo '<div>
                <label style="font-size:12px; color:#666;">Coupon</label>
                <input type="text" name="couponcode" placeholder="Enter coupon"
                value="'.s($couponcode).'"
                style="width:100%; padding:8px; border:1px solid #767676; border-radius:6px;">
              </div>';

        // Name/email
        echo '<div>
                <label style="font-size:12px; color:#666;">User</label>
                <input type="text" name="searchtext" placeholder="Name or email"
                value="'.s($searchtext).'"
                style="width:100%; padding:8px; border:1px solid #767676; border-radius:6px;">
              </div>';

        // Product
        echo '<div>
                <label style="font-size:12px; color:#666;">Product</label>
                <input type="text" name="productname" placeholder="Product name"
                value="'.s($productname).'"
                style="width:100%; padding:8px; border:1px solid #767676; border-radius:6px;">
              </div>';

        // Business dropdown
        // echo '<div>
        //         <label style="font-size:12px; color:#666;">Business User</label>
        //         <select name="businessid" style="width:100%; padding:8px; border-radius:6px;" onchange="this.form.submit()">
        //         <option value="">All</option>';

        // foreach ($businessusers as $bu) {
        //     $user = core_user::get_user($bu->userid);
        //     $selected = ($selectedbusiness == $bu->userid) ? 'selected' : '';
        //     echo "<option value='{$bu->userid}' $selected>" . fullname($user) . ' (' .$user->email. ')' . "</option>";
        // }

        // echo '</select></div>';

        echo '<div>
                    <label style="font-size:12px; color:#666;">Business User</label>
                    <select id="businessuserselect" name="businessid" 
                        style="width:100%;" onchange="this.form.submit()">
                        <option value="">All</option>';

                        foreach ($businessusers as $bu) {
                            $user = core_user::get_user($bu->userid);
                            $selected = ($selectedbusiness == $bu->userid) ? 'selected' : '';
                            $label = fullname($user) . ' (' . $user->email . ')';
                            echo "<option value='{$bu->userid}' $selected>{$label}</option>";
                        }

                echo '</select>
            </div>';

        // Department dropdown
        echo '<div>
                <label style="font-size:12px; color:#666;">Department</label>
                <select name="departmentid" style="width:100%; padding:8px; border-radius:6px;">
                <option value="">All</option>';

        foreach ($departments as $dept) {
            $selected = ($selecteddept == $dept->id) ? 'selected' : '';
            echo "<option value='{$dept->id}' $selected>{$dept->name}</option>";
        }

        echo '</select></div>';

        // Dates
        echo '<div>
                <label style="font-size:12px; color:#666;">Start Date</label>
                <input type="date" name="start_date" value="'.s($start_date).'"
                style="width:100%; padding:8px; border-radius:6px;">
              </div>';

        echo '<div>
                <label style="font-size:12px; color:#666;">End Date</label>
                <input type="date" name="end_date" value="'.s($end_date).'"
                style="width:100%; padding:8px; border-radius:6px;">
              </div>';

        // Buttons
        echo '<div style="display:flex; gap:10px;">
                <button type="submit" style="" class="btn btn-primary">
                    Search
                </button>
                <a href="orderlist.php" style="" class="btn btn-secondary">
                    Clear
                </a>
              </div>';

        echo '</form>';

        // echo '<form method="get" action="orderlist.php" style="display:flex; flex-wrap:wrap; gap:10px; align-items:center; padding:10px 0;">';
        // echo '<input type="text" name="couponcode" placeholder="Search Coupon" value="'.s($couponcode).'" style="min-width:200px; padding:6px;">';
        // echo '<input type="text" name="searchtext" placeholder="Search name or email" value="'.s($searchtext).'"style="min-width:200px; padding:6px;">';
        // echo '<input type="text" name="productname" placeholder="Search product" value="'.s($productname).'" style="min-width:200px; padding:6px;">';
        //     // Business dropdown
        //     echo '<select name="businessid" style="min-width:180px; padding:6px;" onchange="this.form.submit()">';
        //     echo '<option value="">Select Business User</option>';

        //     foreach ($businessusers as $bu) {
        //         $user = core_user::get_user($bu->userid);
        //         $selected = ($selectedbusiness == $bu->userid) ? 'selected' : '';
        //         echo "<option value='{$bu->userid}' $selected>" . fullname($user) . "</option>";
        //     }
        //     echo '</select>';

        //     // Department dropdown
        //     echo '<select name="departmentid" style="min-width:180px; padding:6px;">';
        //     echo '<option value="">Select Department Manager</option>';

        //     foreach ($departments as $dept) {
        //         $selected = ($selecteddept == $dept->id) ? 'selected' : '';
        //         echo "<option value='{$dept->id}' $selected>{$dept->name}</option>";
        //     }

        //     echo '</select>';
            

        //     // date filter
        //     echo '
        //     <input type="date" name="start_date" value="'.s($start_date).'">

        //     <input type="date" name="end_date" value="'.s($end_date).'">

        //     <button type="submit">'.get_string('search', 'block_learnpeek_shop').'</button>
        //     <a href="orderlist.php" class="btn btn-link">'.get_string('clear', 'block_learnpeek_shop').'</a>
        // </form>';

    }
    $table->define_columns($columns);
    $table->define_headers($headers);

 
    // Apply Filter

    // filter couponcode
    if (!empty($couponcode)) {

        $couponparam = '%' . $DB->sql_like_escape($couponcode) . '%';

        $wheresql .= " AND " . $DB->sql_like("lcp.code", ":couponcode", false);
        $sqlparams['couponcode'] = $couponparam;
    }

    // filter specific user
    if (!empty($searchtext)) {

        $searchparam = '%' . $DB->sql_like_escape($searchtext) . '%';

        $wheresql .= " AND (
            " . $DB->sql_like("u.firstname", ":search1", false) . " OR
            " . $DB->sql_like("u.lastname", ":search2", false) . " OR
            " . $DB->sql_like("u.email", ":search3", false) . " OR
            " . $DB->sql_like("CONCAT(u.firstname, ' ', u.lastname)", ":search4", false) . "
        )";

        $sqlparams['search1'] = $searchparam;
        $sqlparams['search2'] = $searchparam;
        $sqlparams['search3'] = $searchparam;
        $sqlparams['search4'] = $searchparam;
    }



    // filter productname
    if (!empty($productname)) {

        $prodparam = '%' . $DB->sql_like_escape($productname) . '%';

        $wheresql .= " AND EXISTS (
            SELECT 1
            FROM {invoiceitem} ii
            JOIN {course_shopsettings} css ON css.id = ii.invoiceableitemid
            WHERE ii.invoiceid = i.id
              AND " . $DB->sql_like("css.name", ":prod1", false) . "
        )";

        $sqlparams['prod1'] = $prodparam;
    }

    // Business users  trainees
    if (!empty($selectedbusiness)) {

        $trainees = $DB->get_fieldset_sql(
            "SELECT child_id FROM {business_user_child}
             WHERE parent_id = :parentid AND company_id = :companyid",
            ['parentid' => $selectedbusiness, 'companyid' => $companyid]
        );

        $allusers = array_merge([$selectedbusiness], $trainees);

        list($insql, $inparams) = $DB->get_in_or_equal($allusers, SQL_PARAMS_NAMED);

        $wheresql .= " AND i.userid $insql";
        $sqlparams = array_merge($sqlparams, $inparams);
    }

    // Department filter
    if (!empty($selecteddept)) {

        $deptids = $DB->get_fieldset_sql(
            "SELECT department_id
             FROM {customdepartment_assign}
             WHERE child_id = :userid
               AND is_departmentmanager = 1",
            ['userid' => $selecteddept]
        );

        if (!empty($deptids)) {
            list($insql, $inparams) = $DB->get_in_or_equal($deptids, SQL_PARAMS_NAMED);

            $deptusers = $DB->get_fieldset_sql(
                "SELECT child_id
                 FROM {customdepartment_assign}
                 WHERE department_id $insql",
                $inparams
            );

            if (!empty($deptusers)) {
                list($usersql, $userparams) = $DB->get_in_or_equal($deptusers, SQL_PARAMS_NAMED);
                $wheresql .= " AND i.userid $usersql";
                $sqlparams = array_merge($sqlparams, $userparams);
            } else {
                $wheresql .= " AND 1=0";
            }

        } else {
            $wheresql .= " AND 1=0";
        }
    }

    // Date filter

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
    // print_r($sqlparams);
    // print_r($sqlparams);
    //  echo "SELECT $selectsql FROM $fromsql WHERE $wheresql";
    $table->set_sql($selectsql, $fromsql, $wheresql, $sqlparams);
    $table->out($CFG->iomad_max_list_users, true);

} else {
    // Logic for other users
    $userids_str = implode(",", $userids);
    $userfields = \core_user\fields::for_name()->with_identity($context)->excluding('id', 'deleted', 'firstname', 'lastname');
    $usersql = $userfields->get_sql('u');
    $selectsql = "i.id as invoiceid, i.reference, i.status, i.paymentid, i.date, p.gateway, p.accountid, u.id, i.email,
                (SELECT SUM(price) FROM {invoiceitem} ii WHERE ii.invoiceid = i.id) AS value, 
                /*CONCAT(FORMAT(p.amount, 2), ' ', (SELECT DISTINCT(currency) FROM {invoiceitem} ii WHERE ii.invoiceid = i.id)) AS amount,*/

                CONCAT(
                    CASE 
                        WHEN (SELECT DISTINCT(currency) FROM {invoiceitem} ii WHERE ii.invoiceid = i.id) = 'USD' 
                        THEN '$' 
                        ELSE (SELECT DISTINCT(currency) FROM {invoiceitem} ii WHERE ii.invoiceid = i.id)
                    END,
                    FORMAT(p.amount, 2)
                ) AS amount,



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

            echo '<form method="get" action="orderlist.php" 
                  style="display:flex; flex-wrap:wrap; gap:10px; align-items:end;">';

            // Start date
            echo '<div>
                    <label style="font-size:12px; color:#666;">Start Date</label>
                    <input type="date" name="start_date" value="'.s($start_date).'"
                    style="width:100%; padding:8px; border:1px solid #ccc; border-radius:6px;">
                  </div>';

            // End date
            echo '<div>
                    <label style="font-size:12px; color:#666;">End Date</label>
                    <input type="date" name="end_date" value="'.s($end_date).'"
                    style="width:100%; padding:8px; border:1px solid #ccc; border-radius:6px;">
                  </div>';

            // Buttons
            echo '<div style="display:flex; gap:8px;">
                    <button type="submit" class="btn btn-primary">
                        Search
                    </button>
                    <a href="orderlist.php" class="btn btn-secondary">
                        Clear
                    </a>
                  </div>';

            echo '</form> <br>';
            // echo '<form method="get" action="orderlist.php">
            //     <input type="date" name="start_date" value="'.s($start_date).'">
            //     <input type="date" name="end_date" value="'.s($end_date).'">
            //     <button type="submit">Search</button>
            //     <a href="orderlist.php" class="btn btn-link">'.get_string('clear', 'block_learnpeek_shop').'</a>
            // </form>';
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

$PAGE->requires->js_call_amd('core/form-autocomplete', 'enhance', [
    '#businessuserselect',
    [
        'placeholder' => 'Search user...',
        'allowClear' => true,
    ]
]);
if (!$table->is_downloading()) {
    echo $OUTPUT->footer();
}

