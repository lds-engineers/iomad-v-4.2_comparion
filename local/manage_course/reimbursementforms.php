<?php
require('../../config.php');
require_login();

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title('Reimbursement Forms');
$PAGE->set_heading('Reimbursement Forms');

echo $OUTPUT->header();
?>

<div class="container mt-4">


    <div class="card shadow-md mb-4">
        <div class="card-body">
            <h3 class="">For Reimbursement:</h3>
            <hr>

            <p>
                Fill out the Reimbursement Form (Service or Steamfitter depending on your trade) 
                and submit via email along with your receipt (that was emailed to you upon purchase of the courses.)
            </p>

            <!-- <p><strong>The forms can be found on the homepage</strong></p> -->

            <div class="mt-3">
                <a class="btn bg_custom mr-2 text-white"
                   href="<?php echo $CFG->wwwroot; ?>/local/manage_course/2026.stm reimbursement form.pdf"
                   target="_blank">
                   📄 SST Reimbursement STM
                </a>

                <a class="btn bg_custom text-white"
                   href="<?php echo $CFG->wwwroot; ?>/local/manage_course/2026.mtb rimbursement.pdf"
                   target="_blank">
                   📄 SST Reimbursement MTB
                </a>
            </div>
        </div>
    </div>


    <div class="card shadow-md">
        <div class="card-body">
            <h3 class="text-dark"><i class="fa fa-life-ring"></i> Need Help?</h3>
            <hr>
            <p class="mb-2">
                If you experience any issues accessing your certificate or forms, please contact us:
            </p>
            <p style="font-size:17px;">
                📧 <a href="mailto:info@learnpeek.com"><strong>info@learnpeek.com</strong></a><br>
                📞 <strong>(718) 461-9370</strong>
            </p>
        </div>
    </div>

</div>

<div class="container mt-5 mb-5">
    

</div>


<?php
echo $OUTPUT->footer();
?>
