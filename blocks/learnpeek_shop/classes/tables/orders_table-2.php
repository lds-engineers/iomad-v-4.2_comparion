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
            $orderview = 'View';
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
            $actions[] = html_writer::link($viewurl, 'View');
        }

        // Delete link
        if ($row->status == 'p' || $row->status == 'pr' || $row->status == 'r') {
            $deleteurl = new moodle_url('/blocks/learnpeek_shop/order_delete.php', ['id' => $row->invoiceid]);
            $deletejs = "return confirm('Are you sure you want to delete this order? This action cannot be undone.');";
            $actions[] = html_writer::link($deleteurl, 'Delete', ['onclick' => $deletejs]);
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
