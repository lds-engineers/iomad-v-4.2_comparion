<?php
require('../../config.php');
require_login();

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title('Help / Support');
$PAGE->set_heading('Help / Support');

echo $OUTPUT->header();
?>

<div class="container mt-4">
	
    <!-- <div class="card shadow-md mb-4">
        <div class="card-body">
            <h3 class="">SST Card Information</h3>
            <hr>

            <h5 class="mt-3">To Obtain an SST Refresher Card</h5>
            <p>
                Completion certificates must be emailed directly to 
                <strong>khertel@steamny.com</strong> 
                at least <strong>one week prior</strong> to your current SST card expiring.
                <br>
                <br>
                <span class="text-danger  font-weight-bold p-2 rounded-2" style="background: #efd5d5;">
                    There is NO GRACE PERIOD for expired cards.
                </span>
            </p>

            <h5 class="mt-4">To Obtain a First Time SST Card</h5>
            <p>
                Please reach out to the <strong>Training Center</strong> for further information.
            </p>
        </div>
    </div> -->


    <!-- <div class="card shadow-md mb-4">
        <div class="card-body">
            <h3 class="">For Reimbursement:</h3>
            <hr>

            <p>
                Fill out the Reimbursement Form (Service or Steamfitter depending on your trade) 
                and submit via email along with your receipt (that was emailed to you upon purchase of the courses.)
            </p> -->

            <!-- <p><strong>The forms can be found on the homepage</strong></p> -->

            <!-- <div class="mt-3">
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
    </div> -->


    <div class="card shadow-md">
        <div class="card-body">
            <h3 class="text-dark"><i class="fa fa-life-ring"></i> Need Help?</h3>
            <hr>
            <p class="mb-2">
                If you require any help or support please contact us via email or phone. For fastest response please contact us via email. Please use the email address you have registered with.
            </p>
            <p style="font-size:17px;">
                📧 <a href="mailto:support@pdhstar.com"><strong>support@pdhstar.com</strong></a><br>
                📞 <strong>(833) (734-7827)</strong>
            </p>
        </div>
    </div>

</div>

<div class="container mt-5 mb-5">
    

</div>


<?php
echo $OUTPUT->footer();
?>
