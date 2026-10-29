<?php
namespace local_learnpeek_email_template\task;

// global $CFG;
// require_once($CFG->dirroot . '/cohort/lib.php');

defined('MOODLE_INTERNAL') || die();

class cumulative_order_email extends \core\task\scheduled_task {
    //const HOURS_AFTER_COMPLETION_BEFORE_TRANSFER = 48;

    public function get_name() {
      return "Cumulative Order Email";
    }

     /**
     * Execute the task.
     */
    public function execute() {
        global $DB,$CFG;
        // $timestart = strtotime("today midnight");
        $timestart = strtotime("-4 days midnight");
        $timeend = strtotime("tomorrow midnight") - 1;
        $companyInvoices = [];
        $cumulative_companies = $DB->get_records_sql("SELECT * FROM {company_mail_configuration} WHERE cumulative_email = ?", [1]);
        if ($cumulative_companies) {
            foreach ($cumulative_companies as $cumulative_company) {
                $companyid = $cumulative_company->company_id;
                // Get today's invoices for this company

                $invoices = $DB->get_records_sql("
                    SELECT id FROM {invoice}
                    WHERE status = 'p'
                      AND companyid = ?
                      AND date BETWEEN ? AND ?
                ", [$companyid, $timestart, $timeend]);

                $user_invoice = array();
                foreach ($invoices as $invoice) {
                  $user_invoice[] = $invoice->id;
                }

                if($user_invoice){
                  $invoice_ids_string = implode(',', $user_invoice);
                  // echo "SELECT i.id, i.userid, p.amount, p.currency, GROUP_CONCAT(css.name) FROM {invoice} i INNER JOIN {invoiceitem} ii on ii.invoiceid = i.id INNER JOIN {payments} p on p.id = i.paymentid LEFT JOIN {course_shopsettings} as css ON ii.invoiceableitemid = css.id WHERE i.id IN($invoice_ids_string) GROUP BY i.id";
                  $getcompanyoders = $DB->get_records_sql("SELECT i.id, i.userid, p.amount, p.currency, GROUP_CONCAT(css.name) FROM {invoice} i INNER JOIN {invoiceitem} ii on ii.invoiceid = i.id INNER JOIN {payments} p on p.id = i.paymentid LEFT JOIN {course_shopsettings} as css ON ii.invoiceableitemid = css.id WHERE i.id IN($invoice_ids_string) GROUP BY i.id");
                  if($getcompanyoders){
                      echo '<table border="1" cellpadding="6" cellspacing="0">';
                      echo '<thead>
                              <tr>
                                  <th>Invoice ID</th>
                                  <th>Name</th>
                                  <th>Product Name(s)</th>
                                  <th>Total Price ('.$getcompanyoders->currency.')</th>
                              </tr>
                            </thead>';
                      echo '<tbody>';

                      foreach ($getcompanyoders as $order) {
                          $invoiceid = $order->id;
                          $userdata = $DB->get_record("user", array("id" => $order->userid));
                          $username =  fullname($userdata);;
                          $products = htmlspecialchars($order->{'group_concat(css.name)'});
                          $amount = number_format($order->amount, 2); // Format to 2 decimal places

                          echo "<tr>
                                  <td>$invoiceid</td>
                                  <td>$username</td>
                                  <td>$products</td>
                                  <td>$amount</td>
                                </tr>";
                      }
                      echo '</tbody>';
                      echo '</table>';
                  }
                }
            }

        }



        //SELECT i.id, i.userid, p.amount, p.currency, GROUP_CONCAT(css.name) FROM mdl_invoice i INNER JOIN mdl_invoiceitem ii on ii.invoiceid = i.id INNER JOIN mdl_payments p on p.id = i.paymentid LEFT JOIN mdl_course_shopsettings as css ON ii.invoiceableitemid = css.id WHERE i.id IN(580,582,584) GROUP BY i.id;






    }
}
