<?php 
// $PAGE->requires->jquery();
// $id = optional_param('id', '', PARAM_RAW);
// $reg_id = base64_decode($id);

// createInvoicefile(10431);
function createInvoicefile($reg_id){

    require_once('../../config.php');
    global $DB, $CFG, $PAGE; 
    require($CFG->dirroot.'/local/user_registration/pdfgenerator/pdf/vendor/autoload.php');
    
    if($DB->record_exists('lcl_individual_enrollment', array('registration_id'=>$reg_id))) {
        $user_details = $DB->get_record('lcl_individual_enrollment',array('registration_id'=> $reg_id));

        $email_template = $DB->get_record('lcl_email_template', array('type'=>1, 'template_type'=>4));
        $msg = $email_template->template;
        $subject = $email_template->subject;
        $email_template_logo_address = $DB->get_record('lcl_logo_address', array('type'=>3));
        $logo = $email_template_logo_address->logo;
        $address = text_to_html($email_template_logo_address->address);
        $lcl_user_created = $DB->get_records('lcl_user_created', array('registration_id'=>$reg_id));

        $child = $DB->get_records('lcl_application_form',array('registration_id'=> $reg_id,'relation'=>'parent'));
        $childusr = $DB->count_records('lcl_application_form',array('registration_id'=> $reg_id,'relation'=>'parent'));
        if($childusr != 0){
            // $tolprice1 = ($chk_data->course_price * $childusr); 
            $tolprice1 = number_format(($user_details->course_price * $childusr),2); 
        }else{
            $tolprice1 = number_format(($user_details->course_price),2); 
            // $tolprice1 = $chk_data->course_price; 
        }
        
    }

    if($DB->record_exists('lcl_corporate_enrollment', array('registration_id'=>$reg_id))) {
        $user_details = $DB->get_record('lcl_corporate_enrollment',array('registration_id'=> $reg_id));

        $email_template = $DB->get_record('lcl_email_template', array('type'=>2, 'template_type'=>4));
        $msg = $email_template->template;
        $subject = $email_template->subject;
        $email_template_logo_address = $DB->get_record('lcl_logo_address', array('type'=>3));
        $logo = $email_template_logo_address->logo;
        $address = text_to_html($email_template_logo_address->address);
        $lcl_user_created = $DB->get_records('lcl_user_created', array('registration_id'=>$reg_id));

        // $child = $DB->get_records('lcl_application_form',array('registration_id'=> $reg_id,'relation'=>'child'));
        // $childusr = $DB->count_records('lcl_application_form',array('registration_id'=> $reg_id,'relation'=>'child'));
        $child = $DB->get_records_sql('SELECT * FROM {lcl_application_form} where registration_id = '.$reg_id.' AND relation = "child" AND (assessor_child_status IS NULL OR assessor_child_status = 1 OR assessor_child_status = 2)');
        $childusr = $DB->count_records_sql('SELECT COUNT(id) FROM {lcl_application_form} where registration_id = '.$reg_id.' AND relation = "child" AND (assessor_child_status IS NULL OR assessor_child_status = 1 OR assessor_child_status = 2)');
        // $tolprice1 = number_format(($user_details->course_price * $childusr),2); 
        // echo '<pre>';
        // print_r($user_details);
        // echo '------------';
        // print_r($childusr);
        // die;
        if($childusr != 0 && !empty($user_details->course_price)){
            $tolprice1 = number_format(($user_details->course_price * $childusr),2); 
        }else if(!empty($user_details->course_price)){
            $tolprice1 = number_format(($user_details->course_price),2); 
        }else{
            $tolprice1 = number_format(0,2); 
        }
    }

    $coursedata = $DB->get_record('course',array("id"=>$user_details->course_id)); 
    $description = $DB->get_record_sql("SELECT cd.id,cd.value FROM {customfield_field} cf INNER JOIN {customfield_data} cd ON cf.id = cd.fieldid WHERE cf.shortname = 'invoice_description' AND cd.instanceid = $user_details->course_id");
    // $customFieldData = $DB->get_record_sql("SELECT cdd.* FROM {customfield_data} cdd INNER JOIN {customfield_field} cf ON cdd.fieldid = cf.id WHERE cf.shortname = 'price' AND cdd.contextid =". context_course::instance($user_details->course_id)->id);

    // echo $OUTPUT->header();
    $abhi = $CFG->wwwroot."/local/user_registration/temp/".$logo;
    $invoiceContent = " 
        <style>
            .containersss {
                display: flex;
                justify-content: space-between; /* Adjust as needed */
            }
            .box {
                width: 48%; /* Adjust as needed */
            }
            .table {
                border-collapse: collapse;
                width: 100%; 
            }
            .table thead td {
                padding: 5px;
            }
            .tablediv{
                width: 100%; 
                margin: 10px;
            }
            .txt-color{
                color: #ff8e05;
            }
            .tablessss{
                float: right;
                width: 30%;
            }
            .summarytbl{
                margin-top: 100px;
            }
        </style>
        <div id='invoice_summery' class='main p-4'>

            <div class='containersss'>
            <div class='box' align='left'>
                ".$address."
            </div>
            <div class='box' align='right'>
                <img src='data:image/png;base64,".base64_encode(file_get_contents($abhi))."' alt='image' width='100'>
            </div>
            </div>

            <div style='width: 100%; padding-top:20px'>
                <div class='txt-color' style='width: 100% !important;' align='left'><h3>Tax Invoice</h3></div>
            </div>

            <div class='containersss' style='width: 100% !important;'>
                <div style='width: 100% !important;' align='left'><strong>BILL TO<br>".(($user_details->name)?strtoupper($user_details->name):strtoupper($user_details->client_name))."</strong></div>
                <div style='width: 100% !important;' align='right'>
                    <div ><strong>INVOICE: ".$reg_id."</strong></div>
                    <div ><strong>DATE :".date("d-m-Y",$user_details->created_date)."</strong></div>
                    <div ><strong>TERMS :Due on receipt</strong></div>
                </div>
            </div>

            <table class='table m-2'>
                <thead style='background-color: antiquewhite;'>
                    <tr>
                        <th class='txt-color' >DATE</th>
                        <th class='txt-color' >ACTIVITY</th>
                        <th class='txt-color' >DESCRIPTION</th>
                        <th class='txt-color' >QTY</th>
                        <th class='txt-color' >RATE</th>
                        <th class='txt-color' >AMOUNT</th>
                    </tr>
                </thead>
                <tbody style='border-bottom-style: dashed;'>
                    <tr>
                        <td>".date('d/m/y',$user_details->created_date)."</td>
                        <td>".$coursedata->shortname."</td>
                        <td>
                            <ul style='border-bottom-style: dashed;color: #808080;' >";
            $invoiceContent .= "<li  style='list-style: none;' ><h5>Candidates</h5></li>";
                            foreach($child as $user){
            $invoiceContent .= "<li>".$user->fullname."</li>";
                            }
        $invoiceContent .= "</ul>
                        ".$description->value."</td>
                        <td>".$childusr."</td>
                        <td>".(!empty($user_details->course_price) ? number_format($user_details->course_price,2) : number_format(0,2))."</td>
                        <td>".$tolprice1."</td>
                    </tr>
                </tbody>
            </table>";
            $amt = (float) str_replace(',', '', $tolprice1);
        $invoiceContent .= "
            <table class='tablessss'>
                <thead>
                    <tr>
                        <th style='text-align: left; width: 80%;'>SUBTOTAL</th>
                        <th style='text-align: left; width: 40%;'>".$tolprice1."</th>
                    </tr>
                    <tr>
                        <th style='text-align: left; width: 80%;'>TAX</th>
                        <th>".number_format(($amt * (0.10)),2)."</th>
                    </tr>
                    <tr style='border-bottom-style: dashed;' >
                        <th style='text-align: left; width: 80%;'>TOTAL</th>
                        <th>".number_format(($amt + ($amt * 0.10)),2)."</th>
                    </tr>
                    <tr>
                        <th style='text-align: left; width: 80%;'>BALANCE DUE</th>
                        <th>".number_format(($amt + ($amt * 0.10)),2)."</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>


            <table class='table m-2 summarytbl'>
                <thead >
                    <tr>
                        <th class='txt-color' >TAX SUMMARY</th>
                        <th></th>
                        <th></th>
                        <th></th>
                    </tr>
                    <tr style='background-color: antiquewhite;'>
                        <th></th>
                        <th class='txt-color' >RATE</th>
                        <th class='txt-color' >TAX</th>
                        <th class='txt-color' >NET</th>
                    </tr>
                    <tr>
                        <th></th>
                        <th>VAT @ 10%</th>
                        <th>".number_format(($amt * 0.10),2)."</th>
                        <th>".$tolprice1."</th>
                    </tr>

                </thead>
            </table>

            <div class='row' style='margin: 3px;'>
                <div style='width: 100%;' align='left' style='background-color: antiquewhite; padding: 5px;' >
                Bank 2:<br>
                Beneficiary Name: Al Mashreq Training<br>
                IBAN Number: BH69AUBB00016204372001<br>
                Bank Name: Ahli United Bank SEEF AREA<br>
                Bank Identifier Code/SWIFT: AUBBBHBM<br>
                </div>
            </div>

        </div>
                                    
    ";

    
    // HTML content
    $html = $invoiceContent;
    
    // Create Dompdf instance
    $options = new Dompdf\Options();
    $options->set('isHtml5ParserEnabled', true); // Enable HTML5 parser
    $dompdf = new Dompdf\Dompdf($options);
    
    // Load HTML content
    $dompdf->loadHtml($html);
    
    // (Optional) Set paper size and orientation
    $dompdf->setPaper('A4', 'portrait');
    
    // Render PDF (optional: you can save PDF to a file instead of outputting it directly)
    $dompdf->render();
    
    // Output PDF to browser
    // $dompdf->stream('output.pdf', ['Attachment' => true]);

    $outputFilename = $reg_id.'_output.pdf'; // Set the filename
    $outputDir = $CFG->dirroot."/local/user_registration/temp/"; // Set the directory where you want to save the PDF
    // Save PDF to file
    file_put_contents($outputDir . $outputFilename, $dompdf->output());
    
}

?>