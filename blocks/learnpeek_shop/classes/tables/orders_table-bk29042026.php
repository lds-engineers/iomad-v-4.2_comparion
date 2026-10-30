<?php
namespace block_learnpeek_shop\tables;

use \table_sql;
use \moodle_url;
use \iomad;
use \html_writer;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/tablelib.php');

class orders_table extends table_sql {

    /**
     * Generate the display of the user's| fullname
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_fullname($row) {
        $name = fullname($row, has_capability('moodle/site:viewfullnames', $this->get_context()));

        $profileurl = new moodle_url('/user/profile.php', ['id' => $row->id]);
        return html_writer::tag('a', $name, ['href' => $profileurl]);
    }

    /**
     * Generate the display of the order reference.
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_reference($row) {
        return format_string($row->reference);
    }
    public function col_view($row) {
        if ($row->status == 'p' || $row->status == 'pr' || $row->status == 'r') {
            $orderview =  get_string("view","block_learnpeek_shop");
            $orderviewurl = new moodle_url('/blocks/learnpeek_shop/order-view.php', ['id' => $row->invoiceid]);
            return html_writer::tag('a', $orderview, ['href' => $orderviewurl]);
        }else{
            return '-';
        }
    }
    
    // public function col_delete($row) {
    //     if ($row->status == 'p' || $row->status == 'pr' || $row->status == 'r') {
    //         $orderdelete = 'delete';
    //         $orderdeleteurl = new moodle_url('/blocks/learnpeek_shop/order_delete.php', ['id' => $row->invoiceid]);
    //         return html_writer::tag('a', $orderdelete, ['href' => $orderdeleteurl]);
    //     }else{
    //         return '-';
    //     }
    // }


    /**
     * Generate the display of the order reference.
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_value($row) {
        return $row->value . "&nbsp" . $row->currency;
    }

    /**
     * Generate the display of theorder payment provider
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_paymentprovider($row) {
        global $DB;

        if (!empty($row->gateway)) {
            return get_string('pluginname', 'paygw_' . $row->gateway);
        }

        if ($row->status == 'p' || $row->status == 'pr' || $row->status == 'r') {
            return get_string('pp_historic', 'block_learnpeek_shop');
        }
        return '';
    }

    /**
     * Generate the display of the order status.
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_status($row) {

        return get_string('status_' . $row->status, 'block_learnpeek_shop');
    }

    /**
     * Generate the display of the order status.
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_unprocesseditems($row) {

        return  ($row->unprocesseditems > 0 ? $row->unprocesseditems : "");
    }

    /**
    /**
     * Generate the display of the user's departments  
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_company($row) {

        return format_string($row->company);
    }

    /**
     * Generate the display of the user's license allocated timestamp
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_date($row) {
        global $CFG;

        return format_string(date($CFG->iomad_date_format, $row->date));
    }

    public function col_coursepurchased($row) {
    global $DB;

    // DEBUG 1: Check if the ID is even reaching the function
    if (empty($row->invoiceid)) {
        return "DEBUG: No invoiceid found in row. Check your SQL SELECT.";
    }

    $sql = "SELECT css.name AS shopname, c.fullname AS coursename, c.id AS courseid
            FROM {invoiceitem} ii
            LEFT JOIN {course_shopsettings} css ON ii.invoiceableitemid = css.id
            LEFT JOIN {course_shopsettings_courses} csc ON ii.invoiceableitemid = csc.itemid
            LEFT JOIN {course} c ON csc.courseid = c.id
            WHERE ii.invoiceid = ?";

    $items = $DB->get_records_sql($sql, array($row->invoiceid));

    // DEBUG 2: Check if the query found anything
    if (empty($items)) {
        return "DEBUG: Query returned 0 items for Invoice ID: " . $row->invoiceid;
    }

    $output = [];
    foreach ($items as $item) {
        if (!empty($item->shopname)) {
            $productname = s($item->shopname);
        } else if (!empty($item->coursename)) {
            $productname = s($item->coursename);
        } else {
            $productname = '<span style="color: #b70909; background-color:#fdbfbf; padding: 2px 4px; border-radius: 3px;">Product deleted</span>';
        }

        if (!empty($item->courseid)) {
            $url = new moodle_url('/course/view.php', array('id' => $item->courseid));
            $output[] = html_writer::link($url, $productname);
        } else {
            $output[] = $productname;
        }
    }

    return implode('<br>', $output);
}
    public function col_whitelabelcourseprice($row) {
        return $row->whitelabelcourseprice ?? '';
    }
    /**
     * Generate the display of the ucourses has grade column.
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    // public function col_actions($row) {
    //     global $context;

    //     $stredit   = get_string('edit');
    //     if (iomad::has_capability('block/iomad_commerce:admin_view', $context)) {
    //         $editbutton = "<a href='" . new moodle_url('edit_order_form.php', array("id" => $row->id)) . "'>
    //         <i class='icon fa fa-cog fa-fw ' title='$stredit' role='img' aria-label='$stredit'></i></a>";
    //     } else {
    //         $editbutton = "";
    //     }
    //     return $editbutton;
    // }

    // uday block start
    public function col_actions($row) {
        global $context;

        $actions = [];

        // View link
        if ($row->status == 'p' || $row->status == 'pr' || $row->status == 'r') {
            $viewurl = new moodle_url('/blocks/learnpeek_shop/order-view.php', ['id' => $row->invoiceid]);
            $actions[] = html_writer::link($viewurl, get_string("view","block_learnpeek_shop"));
        }

        // Delete link
        if ($row->status == 'p' || $row->status == 'pr' || $row->status == 'r') {
            $deleteurl = new moodle_url('/blocks/learnpeek_shop/order_delete.php', ['id' => $row->invoiceid]);
            $deletejs = "return confirm('" . get_string('delete_confirm', 'block_learnpeek_shop') . "');";
            $actions[] = html_writer::link($deleteurl, get_string("delete","block_learnpeek_shop"), ['onclick' => $deletejs]);
        }

        // Optional Edit icon
        // $stredit = get_string('edit');
        // if (iomad::has_capability('block/iomad_commerce:admin_view', $context)) {
        //     $editurl = new moodle_url('edit_order_form.php', ['id' => $row->id]);
        //     $editicon = "<i class='icon fa fa-cog fa-fw' title='$stredit' role='img' aria-label='$stredit'></i>";
        //     $actions[] = html_writer::link($editurl, $editicon);
        // }

        return implode(' | ', $actions); // Separate actions by pipe symbol
    }
    // uday block end
    /**
     * @return string sql to add to where statement.
     */
    function get_sql_where() {
        global $DB, $SESSION;

        $uniqueid = 'block_iomad_commerce_orders_table';

        if (isset($SESSION->flextable[$uniqueid])) {
            $prefs = $SESSION->flextable[$uniqueid];
        } else if (!$prefs = json_decode(get_user_preferences("flextable_{$uniqueid}", ''), true)) {
            return '';
        }

        $conditions = array();
        $params = array();

        static $i = 0;
        $i++;

        if (!empty($prefs['i_first'])) {
            $conditions[] = $DB->sql_like('u.firstname', ':ifirstc'.$i, false, false);
            $params['ifirstc'.$i] = $prefs['i_first'].'%';
        }
        if (!empty($prefs['i_last'])) {
            $conditions[] = $DB->sql_like('u.lastname', ':ilastc'.$i, false, false);
            $params['ilastc'.$i] = $prefs['i_last'].'%';
        }

        return array(implode(" AND ", $conditions), $params);
    }
}
