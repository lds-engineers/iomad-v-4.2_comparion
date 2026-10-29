<?php
require_once("../../config.php");
require_once($CFG->libdir . '/formslib.php');

require_login();
$companyid = iomad::get_my_companyid(context_system::instance());
$companydata = $DB->get_record("company",array("id"=>$companyid));
$getcompnayadmin = $DB->get_record_sql("SELECT * FROM {company_users} WHERE companyid = ? AND userid =?",array($companyid,$USER->id));
if (!is_siteadmin() && (!$getcompnayadmin || $getcompnayadmin->managertype != 1)) {
    redirect(new moodle_url($CFG->wwwroot.'/my'),'Access denied', null, \core\output\notification::NOTIFY_ERROR);
     die;
}

$title = get_string('gradereport', 'local_learnpeek_report');

$PAGE->set_url(new moodle_url("/local/learnpeek_report/gradereport.php"));
$PAGE->set_context(context_system::instance());
$PAGE->set_title($title);
$PAGE->set_heading($title);

 $wwwroot = $CFG->wwwroot;
class grade_form extends moodleform {
    public function definition() {
        global $DB;
        $mform = $this->_form; 

        $companyid = iomad::get_my_companyid(context_system::instance()); 
       $companies = $DB->get_records_sql("SELECT cc.id, c.id as compandid,cc.name,c.name as companyname FROM {course_categories} as cc LEFT JOIN {company} as c ON cc.id = c.category AND cc.visible=1 AND c.suspended = 0");

        $companies_for_select = []; 
        if(is_siteadmin()){
            foreach ($companies as $record) {
            $companyname = $record->companyname ? "({$record->companyname})" : ""; 
            $companies_for_select[0] = "Select Company";
            $companies_for_select[$record->id] = "{$record->name} $companyname";
            }
        }else{
            foreach ($companies as $record) {
            $companyname = $record->companyname ? "({$record->companyname})" : "";  
            if($companyid == $record->compandid ){
            $companies_for_select[$record->id] = "{$record->name} $companyname";
            }
        }
        }
  
        $this->_form->disable_form_change_checker();
        // $mform->addElement('select', 'categoryid', 'Select Company', $companies_for_select);
        $mform->addElement('select','categoryid',get_string('selectcompany', 'local_learnpeek_report'),$companies_for_select);
        $mform->addRule('categoryid', null, 'required', null, 'client'); 
    }
}

$form = new grade_form(); 
echo $OUTPUT->header();
$form->display();
?>

<!-- Include Select2 CSS & JS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- JavaScript to Fetch and Display Data -->  
<script>
document.addEventListener('DOMContentLoaded', function () {
     const WWWROOT = "<?php echo $wwwroot; ?>";
     const companyid = "<?php echo(iomad::get_my_companyid(context_system::instance()));  ?>"; 

    const companySelect = document.querySelector('[name=categoryid]'); 
    const tabledata = document.querySelector('.table-data');

    function fetchAndPopulate(categoryid) { 
        fetch(`/local/learnpeek_report/getgrades.php?action=getdata&sesskey=${M.cfg.sesskey}&categoryid=${categoryid}&companyid=${companyid}`)
        .then(response => response.json())
        .then(data => {
            tabledata.innerHTML = ''; // Clear existing content
            
            if (data.course && data.course.length > 0) {
                let tableHTML = `<table border="1" class="table">
                    <thead style="background: #0f47ad; color: white;">
                        <tr> 
                             <th><?php echo get_string('coursename', 'local_learnpeek_report'); ?></th>
                             <th><?php echo get_string('gradereport', 'local_learnpeek_report'); ?></th>
                        </tr>
                    </thead>
                    <tbody>`;
                
                data.course.forEach(course => {
                    tableHTML += `<tr> 
                        <td class="col-lg-10" value="${course.id}">${course.fullname}</td>
                        <td class="col-lg-2 text-center"><button class="grade-btn"><a href="${WWWROOT}/grade/report/grader/index.php?id=${course.id}" ><?php echo get_string('gradeupdate', 'local_learnpeek_report'); ?></a></button></td>
                    </tr>`;
                });

                tableHTML += `</tbody></table>`;
                tabledata.innerHTML = tableHTML;  
            } else {
                tabledata.innerHTML = '<p>No courses found for this category.</p>';
            }
        })
        .catch(error => {
            console.error("Fetch Error:", error);
            tabledata.innerHTML = '<p>Error fetching data.</p>';
        });
    }

    companySelect.addEventListener('change', function () { 
        if (this.value) {
            fetchAndPopulate(this.value);
        } 
    });  
    companySelect.dispatchEvent(new Event('change'));
});
</script>

<!-- Placeholder for Table Data -->
<div class="table-data"></div>
<style>

    .grade-btn{
    background: #0f47ad;
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 6px;
    white-space: nowrap;
    }

    .grade-btn a{
        color: white;
    }

</style>

<?php
echo $OUTPUT->footer();
?>
