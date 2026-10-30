<?php
function block_custom_mycourse_after_require_login(){
    global $USER, $CFG, $PAGE, $DB;
    // Get the current page URL
    $currenturl = $CFG->wwwroot . $_SERVER['PHP_SELF'];

    // Define the course page URL
    $coursepage = $CFG->wwwroot . '/course/view.php';

 
    if (isloggedin()) {
    $context = context_user::instance($USER->id);
    $current_user_roles = get_user_roles($context, $USER->id);
    $authandicateuser = null;
        if($current_user_roles){
            $managerrole = array_column($current_user_roles, 'shortname');
            if($managerrole[0]=="companymanager"){
                $authandicateuser = true;
            }else{
                $authandicateuser = false;
            }
        }
    }

    if(!is_siteadmin()){
        if($currenturl==$coursepage){
            $redirecturlfiled = $DB->get_record("customfield_field",array("shortname"=>"course_redirect_url"));
            $redirecturldata = $DB->get_record_sql("SELECT * FROM {customfield_data} WHERE fieldid = ? AND instanceid =?",array($redirecturlfiled->id,$_GET['id']));
            $courseredirecturl = $redirecturldata->value;
            if($courseredirecturl){
                redirect($courseredirecturl);
                die();
            }
        }
    }

    if(is_siteadmin() || $authandicateuser){


    }else{
        // custom code for course view page 
        if($currenturl==$coursepage){
            $cousreaddresfid = $DB->get_record("customfield_field",array("shortname"=>"required_trainee_address"));
            $valcaddrequired = $DB->get_record_sql("SELECT * FROM {customfield_data} WHERE fieldid = ? AND instanceid =? AND value =1",array($cousreaddresfid->id,$_GET['id']));
            if($valcaddrequired){
                $caddress = $DB->get_record('user_info_field',array('shortname'=>'certificate_address'));
                $user_caddress  = $DB->get_record('user_info_data', array('userid'=>$USER->id,'fieldid'=>$caddress->id));
                if(empty(trim($user_caddress->data))){
                    redirect($CFG->wwwroot.'/local/customlayout/user_cretificate_info.php?id='.$_GET['id']);

                }
            }

            //add restriction for view course view page
            if($_GET['id']){
                $courseid = $_GET['id'];
                $coursestats =  $DB->get_record_sql('SELECT * FROM {course_completions} WHERE userid = ? AND course = ? AND timecompleted IS NOT NULL', array($USER->id,$courseid));
                $course = $DB->get_record('course', array('id' => $courseid), '*');
                if(empty($coursestats)){
                    $modinfo = get_fast_modinfo($course);
                    $sections = $modinfo->get_section_info_all();
                    $foundmodule = null;
                    $modinfosections = $modinfo->get_sections();
                    foreach ($sections as $key => $section) {
                        $modulefound = false;
                        foreach ($modinfosections[$section->section] as $cmid) {
                            $cm = $modinfo->cms[$cmid];
                            if (!$cm->uservisible) {
                                continue;
                            }
                            $url = $cm->url;
                            if ($url) { //labels don't have url
                                $foundmodule = $url->out(false);
                                $modulefound = true;
                                break;
                            }
                        }
                        if($modulefound){
                            break;
                        }
                    }
                    if($modulefound){
                        redirect($foundmodule);
                        exit;
                    }
                }else{
                    $coursecertificate = $DB->get_records_sql("SELECT cm.*,m.name FROM {course_modules} as cm INNER JOIN {modules} as m ON cm.module = m.id WHERE cm.course = ".$courseid." AND cm.deletioninprogress = 0 AND (m.name='customcert' OR m.name='iomadcertificate')");
                    if($coursecertificate){
                         foreach ($coursecertificate as $c_certificate) {
                            $modinfo = get_fast_modinfo($courseid);
                            $cminfo = $modinfo->get_cm($c_certificate->id);
                            if ($cminfo->uservisible) {
                                $certurl = $CFG->wwwroot.'/mod/customcert/view.php?id='.$c_certificate->id.'&downloadown=1';
                                redirect($certurl);
                            }
                        }
                    }else{
                        //updated code Aicc course

                        $modinfo = get_fast_modinfo($course);
                        $sections = $modinfo->get_section_info_all();
                        $foundmodule = null;
                        $modinfosections = $modinfo->get_sections();
                        foreach ($sections as $key => $section) {
                            $modulefound = false;
                            foreach ($modinfosections[$section->section] as $cmid) {
                                $cm = $modinfo->cms[$cmid];
                                if (!$cm->uservisible) {
                                    continue;
                                }
                                $url = $cm->url;
                                if ($url) { //labels don't have url
                                    $foundmodule = $url->out(false);
                                    $modulefound = true;
                                    break;
                                }
                            }
                            if($modulefound){
                                break;
                            }
                        }
                        if($modulefound){
                            redirect($foundmodule);
                            exit;
                        }


                        // get lat course activity
                        // $modinfo = get_fast_modinfo($course);
                        // $sections = $modinfo->get_section_info_all();
                        // $modinfosections = $modinfo->get_sections();

                        // $lastmoduleurl = null;

                        // // Loop through sections in reverse
                        // for ($i = count($sections) - 1; $i >= 0; $i--) {
                        //     $section = $sections[$i];

                        //     if (!isset($modinfosections[$section->section])) {
                        //         continue;
                        //     }

                        //     // Loop through modules in reverse order
                        //     $cmids = array_reverse($modinfosections[$section->section]);

                        //     foreach ($cmids as $cmid) {
                        //         $cm = $modinfo->cms[$cmid];

                        //         if (!$cm->uservisible) {
                        //             continue;
                        //         }

                        //         if ($cm->url) {
                        //             $lastmoduleurl = $cm->url->out(false); // get the URL
                        //             break 2; // exit both loops
                        //         }
                        //     }
                        // }

                        // // Redirect or return last module info
                        // if ($lastmoduleurl) {
                        //     redirect($lastmoduleurl); // or use it elsewhere
                        //     exit;
                        // } else {
                        //     echo "No visible activity found.";
                        // }


                        // end updated code Aicc

                       // redirect('/my/courses.php', 'You are already completed the course '.$course->fullname, null, \core\output\notification::NOTIFY_SUCCESS);
                    }
                }
            }
            

            
        }

    }   
} 

function block_custom_mycourse_before_standard_html_head()
{ 
} 
// function tool_myplugin_after_require_login() {
//     if (should_divert()) {
//         redirect($somewhere);
//     }


// add menu for course view page
function block_custom_mycourse_extend_navigation_course($navigation, $course, $context) {
    if (has_capability('moodle/course:update', $context)) {
        $url = new moodle_url('/group/index.php', array('id' => $course->id));
        $navigation->add('Manage Group', $url, navigation_node::TYPE_SETTING, null, null,
                new pix_icon('i/report', ''));
    }
}

// add field in user profile

function block_custom_mycourse_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
    global $DB,$USER;
    $companyid = iomad::get_my_companyid(context_system::instance());
    $businestrainer = $DB->get_record_sql("SELECT u.*, buc.parent_id, buc.created_date, buc.company_id FROM {business_user_child} as buc INNER JOIN {user} as u ON buc.child_id = u.id WHERE child_id= ? AND company_id = ? AND u.suspended = 0", array($user->id,$companyid));
    $companyname = "";
    $departmentname = "";

    if($businestrainer) {
        // $getcompany = $DB->get_record_sql("SELECT cu.*,c.name FROM {company_users} as cu INNER JOIN {company} as c ON cu.companyid = c.id WHERE cu.userid = ".$businestrainer->parent_id);

        $fielddata = $DB->get_record('user_info_field',array('shortname'=>'user_company_name'));
        $userfielddata = $DB->get_record('user_info_data', array('userid'=>$businestrainer->parent_id,'fieldid'=>$fielddata->id));
        // $getcompany = $DB->get_record_sql("SELECT cu.*,c.name FROM {company_users} as cu INNER JOIN {company} as c ON cu.companyid = c.id WHERE cu.userid = ".$businestrainer->parent_id);

        $userfielddata3 = $DB->get_record_sql("SELECT c.* FROM {customdepartment_assign} ca JOIN {customdepartment} c ON ca.department_id = c.id AND ca.parent_id = c.businessuserid WHERE ca.child_id = $user->id");
        if($userfielddata->data){
            $title = 'Company Details';
            $companyname = $userfielddata->data;
        }
        if($userfielddata3->name){
            $title2 = 'Department Details';
            $departmentname = $userfielddata3->name;
        }
    }else{
        $title = get_string('others', 'block_custom_mycourse');
        $companyname = "";
        $departmentname = "";
    }


    $category = new core_user\output\myprofile\category('company_name', $title,'', '');
    $tree->add_category($category);
    $node = new core_user\output\myprofile\node('company_name', 'viewassessments',$companyname, null, null);
    $tree->add_node($node);


    if($userfielddata3->name){
        $category2 = new core_user\output\myprofile\category('company_named', $title2,'', '');
        $tree->add_category($category2);
        $node2 = new core_user\output\myprofile\node('company_named', 'viewassessments3',$departmentname, null, null);
        $tree->add_node($node2);
    }

    $getcompany = $DB->get_record_sql("SELECT cu.*,c.name FROM {company_users} as cu INNER JOIN {company} as c ON cu.companyid = c.id WHERE cu.userid = ".$user->id);

    $externalurl2 = $CFG->wwwroot.'/user/preferences.php';
    $linkhtml2 = html_writer::link($externalurl2, get_string('overview', 'block_custom_mycourse'), ['rel' => 'noopener noreferrer']);
    if($companyname != '') {
        $category = new core_user\output\myprofile\category('others2', 'Others','', '');
        $tree->add_category($category);
        $node = new core_user\output\myprofile\node('others2', 'setting',$linkhtml2, null, null);
        $tree->add_node($node);
    } else {
        $node2 = new core_user\output\myprofile\node('company_name', 'user_setting', $linkhtml2, null, null);
        $tree->add_node($node2);
    }
    if($getcompany->managertype == 1){
        $externalurl = $CFG->wwwroot.'/local/customlayout/company-logo.php';
        $linkhtml = html_writer::link($externalurl, 'My Logo', ['rel' => 'noopener noreferrer']);
        $node3 = new core_user\output\myprofile\node(
            'company_name', 
            'company_logo', 
            $linkhtml,
            null, 
            null
        );
        $tree->add_node($node3);
        $externalurl3 = $CFG->wwwroot.'/blocks/iomad_company_admin/company_edit_form.php';
        $linkhtml3 = html_writer::link($externalurl3, 'Edit My Own Whitelabel Company', ['rel' => 'noopener noreferrer']);
        $node3 = new core_user\output\myprofile\node(
            'company_name', 
            'edit_company', 
            $linkhtml3,
            null, 
            null
        );
        $tree->add_node($node3);
    }
    if($getcompany->managertype == 2){
            $fielddata = $DB->get_record('user_info_field',array('shortname'=>'user_company_name'));
        $userfielddata = $DB->get_record('user_info_data', array('userid'=>$user->id,'fieldid'=>$fielddata->id));
        // $getcompany = $DB->get_record_sql("SELECT cu.*,c.name FROM {company_users} as cu INNER JOIN {company} as c ON cu.companyid = c.id WHERE cu.userid = ".$businestrainer->parent_id);
        // echo "<pre>";
        // print_r($userfielddata);
        // echo "</pre>";
        $title = get_string('company_details', 'block_custom_mycourse');
        if($userfielddata){
            $companyname = $userfielddata->data;
        }else{
            // $title = 'Others';
            $companyname = "";
        }
        $category = new core_user\output\myprofile\category('company_name2', $title,'', '');
        $tree->add_category($category);
        $node = new core_user\output\myprofile\node('company_name2', 'viewassessments2',$companyname, null, null);
        $tree->add_node($node);

    }
    // uploaded doument
    // if(!is_siteadmin()){
        $title = get_string('overview', 'block_custom_mycourse');
        $category = new core_user\output\myprofile\category('uploaded_docs', $title, '', '');
        $tree->add_category($category);

        $scanfacedata = $DB->get_record_sql("SELECT * FROM {lcl_scan_face_recognition} WHERE userid = ?",array($user->id));
        if($scanfacedata){
            // uploa image url
            if($scanfacedata->image){
                $upload_imgurl = $CFG->wwwroot."/local/scan_face_recognition/temp/".$scanfacedata->image;
            }else{
                $upload_imgurl ="";
            }

            //upload document url

            $documnetfile = $DB->get_record_sql("
                SELECT * FROM {files}
                WHERE contextid = 1 AND component = 'userdocument' AND itemid = ? AND filesize > 0 AND filearea = ? AND filename != '.'",
                array($scanfacedata->id, 'document'));

            if ($documnetfile) {
                $license_url = "$CFG->wwwroot/local/customlayout/file.php?id=$documnetfile->pathnamehash";
            } else {
                $license_url = '';
            }

            // Uploaded profile image with label
              // Flex container with both images
            $flexcontent = html_writer::start_tag('div', [
                'style' => 'display:flex; gap:20px; align-items:flex-start; flex-wrap:wrap;'
            ]);

            // First image (Image)
            if($upload_imgurl){
                $flexcontent .= html_writer::div(
                    html_writer::tag('strong', get_string('image', 'block_custom_mycourse')) . '<br>' .
                    html_writer::empty_tag('img', [
                        'src' => $upload_imgurl,
                        'style' => 'max-width: 150px;height:auto;border:1px solid #ccc;padding:4px;min-height: 150px;object-fit: cover;'
                    ])
                );
            }

            // Second image (Upload ID) - only if exists
            if ($license_url) {
                $flexcontent .= html_writer::div(
                    html_writer::tag('strong', get_string('upload_id', 'block_custom_mycourse')) . '<br>' .
                    html_writer::empty_tag('img', [
                        'src' => $license_url,
                        'style' => 'max-width: 150px;height:auto;border:1px solid #ccc;padding:4px;min-height: 150px;object-fit: cover;'
                    ])
                );
            }

            $flexcontent .= html_writer::end_tag('div');

            // Add as a single node to the profile tree
            $node4 = new core_user\output\myprofile\node(
                'uploaded_docs',
                'uploaded_images_flex',
                $flexcontent,
                null,
                null
            );
            $tree->add_node($node4);
        }
    // }

    // EPA Certificate Documents
    $title2 = get_string('epa_certificate', 'block_custom_mycourse');
    $category = new core_user\output\myprofile\category('epa_uploaded_docs', $title2, '', '');
    $tree->add_category($category);

    
    // $scanfacedata = $DB->get_record_sql("SELECT * FROM {lcl_scan_face_recognition} WHERE userid = ?",array($user->id));

    $epacertificatedata = $DB->get_record('user_info_field',array('shortname'=>'certificate_image'));
    $epa_user_caddress  = $DB->get_record('user_info_data', array('userid'=>$user->id,'fieldid'=>$epacertificatedata->id));
    if($epa_user_caddress){
        // epa user image
        $usercontext = context_user::instance($user->id);
        $epauserdata = $DB->get_record_sql("SELECT * FROM {files} WHERE contextid= ".$usercontext->id." AND component='profilefield_file' AND filesize > 0 AND filearea = ?", array('files_5'));
        if($epauserdata){
            $epauserimageurl = $CFG->wwwroot.'/blocks/custom_mycourse/file.php?id='.$epauserdata->pathnamehash;
        }else{
            $epauserimageurl = "";
        }
        

        //upload document text
        $caddress = $DB->get_record('user_info_field',array('shortname'=>'certificate_address'));
        $user_caddress  = $DB->get_record('user_info_data', array('userid'=>$user->id,'fieldid'=>$caddress->id));
        if ($user_caddress) {
            $uderaddress = $user_caddress->data;
        } else {
            $uderaddress = '';
        }
        // Flex container with both images
        $flexcontent = html_writer::start_tag('div', [
            'style' => 'display:flex; gap:20px; align-items:flex-start; flex-wrap:wrap;'
        ]);

        // First user cerificate image
        if($epauserimageurl){
            $flexcontent .= html_writer::div(
                html_writer::tag('strong', 'Image') . '<br>' .
                html_writer::empty_tag('img', [
                    'src' => $epauserimageurl,
                    'style' => 'max-width: 150px;height:auto;border:1px solid #ccc;padding:4px;min-height: 150px;object-fit: cover;'
                ])
            );
        }
        echo $uderaddress;
        // Second user address
        if ($uderaddress) {
            $flexcontent .= html_writer::div(
                html_writer::tag('strong', 'Address') . '<br>' .
                html_writer::tag('span', format_string($uderaddress))
            );
        }

        $flexcontent .= html_writer::end_tag('div');

        // Add as a single node to the profile tree
        $node5 = new core_user\output\myprofile\node(
            'epa_uploaded_docs',
            'epa_uploaded_docs_info',
            $flexcontent,
            null,
            null
        );
        $tree->add_node($node5);
    }  
    
    // uday block start
    $context = context_user::instance($user->id);
    $current_user_roles = get_user_roles($context, $user->id);

    $iscompanymanager = false;
    foreach ($current_user_roles as $role) {
        if ($role->shortname === 'companymanager') {
            $iscompanymanager = true;
            break;
        }
    }

    // If not company manager, do not show block content
    if ($iscompanymanager) {
        $title3 = get_string('enrolledcourses', 'block_custom_mycourse');
        $category = new core_user\output\myprofile\category('enrolled_courses', $title3, '', '');
        $tree->add_category($category);

        // Get licensed courses for this user
        $licensedcourses = $DB->get_records('companylicense_users', [
            'userid' => $user->id
        ]);

        $courselist = '';

        if ($licensedcourses) {

            foreach ($licensedcourses as $record) {

                $courseid = $record->licensecourseid;

                // Make sure course exists
                if (!$DB->record_exists('course', ['id' => $courseid])) {
                    continue;
                }

                $context = context_course::instance($courseid);

                // Check active enrolment
                if (is_enrolled($context, $user->id, '', true)) {

                    $course = get_course($courseid);

                    $courseurl = new moodle_url('/course/view.php', [
                        'id' => $course->id
                    ]);

                    $courselist .= html_writer::div(
                        html_writer::link(
                            $courseurl,
                            format_string($course->fullname),
                            ['target' => '_blank']
                        )
                    );
                }
            }
        }

        if (!empty($courselist)) {
            $node6 = new core_user\output\myprofile\node(
                'enrolled_courses',
                'enrolled_courses_list',
                $courselist,
                null,
                null
            );
            $tree->add_node($node6);
        } else {
            $node6 = new core_user\output\myprofile\node(
                'enrolled_courses',
                'no_courses',
                'No enrolled courses found.',
                null,
                null
            );
            $tree->add_node($node6);
        }
    }
    // uday block end
    
    // Last Billing Details
    $userinvoicedata = $DB->get_record_sql("SELECT * FROM {invoice} WHERE userid = ? ORDER BY id DESC LIMIT 1",array($user->id));
    if(empty($userinvoicedata->email)){
        $userinvoicedata = $DB->get_record_sql("SELECT * FROM {invoice} WHERE userid = ? AND status = 'p' ORDER BY id DESC LIMIT 1",array($user->id));
    }
    if($userinvoicedata){
        $titlebilling = get_string('billing_details', 'block_custom_mycourse');
        $category = new core_user\output\myprofile\category('billing_details', $titlebilling, '', '');
        $tree->add_category($category);

        $order_fullname = $userinvoicedata->firstname . ' ' . $userinvoicedata->lastname;
        $order_email = $userinvoicedata->email;
        $order_phone = $userinvoicedata->phone1;
        $order_address = $userinvoicedata->address . '<br>' .
                         $userinvoicedata->city . '<br>' .
                         $userinvoicedata->state . ' ' . $userinvoicedata->postcode;

        // Combine all billing info
        $billingdata = $order_fullname . '<br>' .
                       $order_email . '<br>' .
                       $order_phone . '<br>' .
                       $order_address;


        $node7 = new core_user\output\myprofile\node(
                'billing_details',
                'billing_info',
                $billingdata,
                null,
                null
            );
        $tree->add_node($node7);
    }
}

function block_custom_mycourse_before_footer() {
    global $USER,$CFG,$PAGE,$DB,$COURSE;

    // uday
    // Default: show button.
    $is_hide = false;

    // Site admins can always see the button.
    if (!is_siteadmin()) {

        // Get current host.
        $currenthost = $_SERVER['HTTP_HOST'];

        // Get company by hostname.
        $company = $DB->get_record_sql(
            "SELECT id, shortname
            FROM {company}
            WHERE hostname LIKE ?",
            [$currenthost]
        );

        if ($company) {

            // Get saved registration settings.
            $settings = $DB->get_record_sql("
                SELECT companies FROM {registration_settings} ORDER BY id ASC LIMIT 1"
            );

            if ($settings && !empty($settings->companies)) {

                $blockedcompanies = json_decode($settings->companies, true);

                if (
                    is_array($blockedcompanies) &&
                    in_array($company->shortname, $blockedcompanies, true)
                ) {
                    $is_hide = true;
                }
            }
        }
    }

    if ($is_hide) {
        echo '
        <style>
            .learnpic-user-registration {
                display: none !important;
            }
        </style>';
    }

    
    // Get the current page URL
    $currenturl = $CFG->wwwroot . $_SERVER['PHP_SELF'];

    // Define the group form page URL
    $addgroupurl = $CFG->wwwroot . '/group/group.php';
    $modediturl = $CFG->wwwroot . '/course/modedit.php';
    $coursepage1 = $CFG->wwwroot . '/course/view.php';

    if (isloggedin()) {
        $context = context_user::instance($USER->id);
        $current_user_roles = get_user_roles($context, $USER->id);
        if($current_user_roles){
            $managerrole = array_column($current_user_roles, 'shortname');
            if($managerrole[0]=="companymanager"){
                echo '<style>
                #page-my-index .block_custom_wdashboard{
                    display: block !important;
                }
                #page-my-index .block_custom_mycourse{
                    display: none !important;
                }
                </style>';
                 $gradereportpage = $CFG->wwwroot . '/grade/report/grader/index.php';
                //zaid code 
                if($currenturl==$gradereportpage){
                    if(!$USER->editing){
                        $USER->editing = true;
                        echo "<script>window.location.reload();</script>";
                    }
                    // echo "grade report page";
                }else{
                    if($USER->editing){
                        $USER->editing = false;
                        echo "<script>window.location.reload();</script>";
                    }
                }

                //zaid code end


                if($currenturl==$coursepage1){
                    if($_GET['id']){
                        $getcourse = $DB->get_record_sql("SELECT cm.id, cm.name,cm.shortname,cm.category FROM {company_users} as cu INNER JOIN {company} as cm on cm.id = cu.companyid INNER JOIN {course} as c ON c.category = cm.category WHERE cu.userid = ? AND cu.managertype = 1 AND c.id=?",array($USER->id,$_GET['id']));
                        if($getcourse){
                            
                            $company_category = $DB->get_record("company_category_mapping",array("company_id"=>$getcourse->id));
                            

                            if (strpos($company_category->course_category, $getcourse->category) == false) {
                                echo '<style>
                                form.editmode-switch-form {
                                  display: none !important;
                                }
                                </style>';
                            }
                                                        

                        }
                    }
                    


                }

                echo '
                 <style>
                     #page-course-view-topics #usernavigation>div{
                        display: none !important;
                    }
                     #page-course-view-topics .navbar-expand .navbar-nav#usernavigation {
                      display: flex !important;
                    }
                    #page-course-view-topics .secondary-navigation {
                      display: block !important;
                    }
                    #page-blocks-iomad_company_admin-index #fitem_id_company,
                    #page-blocks-iomad_company_admin-index form[action="'.$CFG->wwwroot.'/blocks/iomad_company_admin/index.php"],
                    
                    #page-blocks-iomad_company_admin-index a[href="#ECommerceAdmin"],
                    #page-blocks-iomad_company_admin-index a[href="#LicenseAdmin"]
                     {
                      display: none;
                    }
                    #page-blocks-iomad_company_admin-index .border-secondary{
                        border: 0 !important;
                    }
                    
                </style>';
                echo '<script>
                if (document.querySelector("#page-user-profile")?.classList.contains("context-'.$context->id.'")) {
                    console.log("dsf");
                    document.querySelectorAll(".node_category dt").forEach(function(dt){
                        console.log("node- ", dt.textContent.trim());
                        if (dt.textContent.trim() === "Course profiles") {
                            console.log("hide it");
                            dt.closest(".node_category")
                              .style.setProperty("display", "none", "important");
                        }
                    });
                }
                </script>';
            }
        } else {
        echo "<style>
            #page-user-edit #id_deletepicture, label[for='id_deletepicture'] {
                display: none !important;
            }
        </style>";
        }
        echo '<style>
            #page-user-profile:not(.context-'.$context->id.') .node_category:has(a[href="/user/preferences.php"]) {
                display:none !important;
            }
        </style>';
    }


    if(is_siteadmin() || $managerrole[0]=="companymanager"){
        // add additional fields seat availability in course group form 
        if($currenturl==$addgroupurl){
            $groupid = optional_param('id', 0, PARAM_INT);
            $groupdata =  $DB->get_record('groups', array('id' => $groupid), '*');
            $total_seat = $groupdata->member_allow;
            echo '
            <script>
                window.onload = function() {
                    var dynamicFieldsContainer = document.getElementById("fitem_id_imagefile");
                    
                    // Create the new "Total Seat" text field
                    var newFieldHTML = `
                        <div class="col-md-3 col-form-label d-flex pb-0 pr-md-0">
                            <label id="total_seat" class="d-inline word-break " for="total_seat">
                               Total Seat
                            </label>
                            <div class="form-label-addon d-flex align-items-center align-self-start">
                                <div class="text-danger" title="Required" id="yui_3_18_1_1_1740387011419_141">
                                <i class="icon fa fa-exclamation-circle text-danger fa-fw " title="Required" role="img" aria-label="Required" ></i>
                                </div>   
                            </div>
                        </div>
                        <div class="col-md-9 form-inline align-items-start felement" data-fieldtype="text">
                            <input type="number" id="total_seat" name="total_seat" value="'.$total_seat.'" class="form-control" required>
                        </div>
                    `;
                    
                    // Insert the new field before the form submit button
                    dynamicFieldsContainer.insertAdjacentHTML("beforeend", newFieldHTML);
                 };
            </script>';

        }

 $calendarpage = $CFG->wwwroot . '/calendar/view.php';
    if($currenturl == $calendarpage){
        echo '<style>
        .maincalendar [data-action="new-event-button"] {
            display:block !important;
        }

        </style>';
    }


    }
if(is_siteadmin()){
    echo '<style>
        body.path-admin .custom-cart {
            display: none;
        }
        #page-blocks-iomad_company_admin-index a[href="'.$CFG->wwwroot.'/blocks/iomad_company_admin/company_course_create_form.php"]{
            display:none;
        }
        #page-blocks-iomad_company_admin-index a[href="#LicenseAdmin"]{
            display:none;
        }
        
    </style>';
}
    if(!is_siteadmin()){
        // add restrcition for hide restricted certificate
        $usercurrenturl = qualified_me(); // or use: $CFG->wwwroot . $_SERVER['PHP_SELF']
        if (preg_match('#/mod/([^/]+)/view\.php#', $usercurrenturl, $matches)) {
            $course = $COURSE;
            $modulename = $matches[1]; // this will give you the module name, e.g., 'quiz', 'customcert', etc.
            $customcert = $DB->get_record('modules', array("name" => "customcert"));   
            $cm = $DB->get_records('course_modules', array("course" => $course->id, "module" => $customcert->id, "deletioninprogress" => 0));
            if (!empty($cm)) {
                $compnayid =  $USER->company->id;
                foreach ($cm as $ccm) {
                    $modinfo = get_fast_modinfo($course->id);
                    $cminfo = $modinfo->get_cm($ccm->id);
                    
                    if ($cminfo->uservisible) {
                        if (!empty($ccm->availability)) {
                            $availability = json_decode($ccm->availability);

                            if (!empty($availability->c)) {
                                foreach ($availability->c as $condition) {
                                    if (isset($condition->type) && $condition->type == 'company' && isset($condition->id) && $condition->id != $compnayid) {
                                        echo "<style>
                                                li#module-" . $ccm->id . " {
                                                    display: none !important;
                                                }
                                                li#course-index-cm-" . $ccm->id . "{
                                                    display: none !important;
                                                }
                                            </style>";

                                        // echo "moduleid " . $ccm->id;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        // custom code for scorm view page
        $scormviewpage = $CFG->wwwroot . '/mod/scorm/view.php';
        if($currenturl==$scormviewpage){

            $coursemodid = optional_param('id', false, PARAM_INT);

            $currentmodule = $DB->get_record(
                'course_modules',
                ['id' => $coursemodid],
                '*',
                MUST_EXIST
            );

            $scormid = $currentmodule->instance;


            // Check whether the current SCORM is completed.
            $getscormstatus = $DB->get_record_sql(
                'SELECT * 
                FROM {course_modules_completion} 
                WHERE coursemoduleid = ? 
                    AND userid = ? 
                    AND completionstate IN (1, 2)', 
                [$coursemodid, $USER->id]);

            if($getscormstatus){

                $aicc_scorm = $DB->get_record("scorm", array('id' => $scormid));

                 if($aicc_scorm->version == "AICC"){
                    $courseratingmd = $DB->get_record("modules",array("name"=>"courserating"));
                    if($courseratingmd){
                        $checkrating_cmd = $DB->get_record("course_modules",array("course"=>$currentmodule->course,"module"=>$courseratingmd->id));
                        if($checkrating_cmd){
                            $ratingcompletionstatus = $DB->get_record('course_modules_completion',array('coursemoduleid'=>$checkrating_cmd->id,'userid'=>$USER->id,'completionstate'=>1));
                            if($ratingcompletionstatus){
                                // echo $checkrating_cmd->id;
                                echo "<style>
                                        #scormviewform button[name='mode'] { visibility: hidden; }
                                        #courseindex-content li#course-index-cm-".$checkrating_cmd->id." { display: none !important;}
                                        .activity-navigation {display: none;}
                                    </style>
                                    <script>
                                        document.addEventListener('DOMContentLoaded', function () {
                                            const certButton = document.querySelector('#scormviewform button[name=\"mode\"]');
                                            if (certButton) {
                                                certButton.textContent = 'Get Certificate';
                                                certButton.style.visibility = 'visible';
                                            }
                                        });
                                    </script>";

                            }else{
                                echo "<span class='alert-success' style='font-size: 19px;padding: 10px;'> Educational activity has been completed. Please go to the next activity to leave mandatory feedback and receive your Certificate of Completion</span>";
                                echo "<style>
                                form#scormviewform button[name='mode'] {
                                      display: none;
                                    }

                                </style>";
                            }
                        }
                    }

                 }else{
                    /*
                    * Get all sections for this course.
                    * This allows us to find the next activity even when
                    * it is located in another section.
                    */

                    $sections = $DB->get_records(
                        'course_sections',
                        ['course' => $currentmodule->course],
                        'section ASC',
                        'id,section,sequence'
                    );

                    $moduleids = [];

                    foreach ($sections as $section) {

                        if (!empty($section->sequence)) {

                            $sectionmoduleids = array_filter(
                                array_map('intval', explode(',', $section->sequence))
                            );

                            foreach ($sectionmoduleids as $moduleid) {
                                $moduleids[] = $moduleid;
                            }
                        }
                    }

                    // Find current activity position in the complete course sequence.
                    $currentposition = array_search(
                        (int)$coursemodid,
                        $moduleids
                    );

                    $nextcoursemoduleid = false;

                    if ($currentposition !== false) {

                        //Find the next activity after the current SCORM.
                        
                        if (isset($moduleids[$currentposition + 1])) {
                            $nextcoursemoduleid = $moduleids[$currentposition + 1];
                        }
                    }

                    $showmessage = false;

                    if ($nextcoursemoduleid) {

                        // Get next course module.
                        $nextmodule = $DB->get_record(
                            'course_modules',
                            ['id' => $nextcoursemoduleid],
                            'id,module,instance'
                        );

                        if ($nextmodule) {

                            // Get module name.
                            $nextmodulename = $DB->get_field(
                                'modules',
                                'name',
                                ['id' => $nextmodule->module]
                            );

                            /*
                             * Show message only when the NEXT activity
                             * is courserating.
                             */
                            if ($nextmodulename === 'courserating') {
                                $showmessage = true;
                            }
                        }
                    }

                    if ($showmessage) {
                         echo "<span class='alert-success' style='font-size: 19px;padding: 10px;'> Educational activity has been completed. Please go to the next activity to leave mandatory feedback and receive your Certificate of Completion</span>";
                        echo "<style>
                        form#scormviewform button[name='mode'] {
                              display: none;
                            }

                        </style>";
                    }else{
                        echo "<span class='alert-success' style='font-size: 19px;padding: 10px;'> Activity completed. Please proceed to the next activity to continue your course</span>";

                        echo "<style>
                            form#scormviewform button[name='mode'] {
                                display: none !important;
                            }
                        </style>";
                    }
                 }
               
            }else{
                $scormstatus = $DB->get_record_sql('SELECT * FROM {scorm_scoes_track} WHERE userid = ? AND scormid = ? AND element = "cmi.core.lesson_status"', array($USER->id,$scormid));
                if($scormstatus->value == "passed"){
                    echo "<script>
                        document.addEventListener('DOMContentLoaded', function () {
                            const button = document.querySelector('#scormviewform button[name=\"mode\"]');
                            // if (button && button.textContent.trim() === 'RESUME COURSE') {
                                button.textContent = 'REVIEW COURSE';
                            // }
                        });
                        </script>";

                }else{
                    $scormdata = $DB->get_record('scorm_scoes_track',array('userid'=>$USER->id,'scormid'=>$scormid));
                    if($scormdata){
                        echo "<style>
                                #scormviewform button[name='mode'] { visibility: hidden; }
                            </style>";

                        echo "<script>
                            document.addEventListener('DOMContentLoaded', function () {
                                var button = document.querySelector('#scormviewform button[name=\"mode\"]');

                                if (button) {
                                    button.innerText = 'RESUME COURSE';
                                    button.style.visibility = 'visible'; // show after update
                                }
                            });
                        </script>";
                        // echo "<script>
                        //         window.onload = function() {

                        //             var button = document.querySelector('#scormviewform button[name=\"mode\"]');
                                    
                        //             // Check if the button exists
                        //             if (button) {

                        //                 var isCourseStarted = true; 

                        //                 if (isCourseStarted) {
                        //                     button.innerText = 'RESUME COURSE';
                        //                 } else {
                        //                     button.innerText = 'START COURSE';
                        //                 }
                        //             }
                        //         };
                        //     </script>";
                    }
                }
            }
        }
            // custom code for scorm view page

        echo "
            <script>
                document.querySelectorAll('.card-body h3').forEach(function(h3) {
                    if (['Miscellaneous','Privacy and policies','Mobile app','Reports'].includes(h3.textContent.trim()) !== false) {
                        h3.closest('.node_category').style.setProperty('display', 'none', 'important');
                    }
                });

                document.querySelectorAll('.card-body h4').forEach(function(h4) {
                    if (['Badges','Blogs'].includes(h4.textContent.trim()) !== false) {
                        h4.closest('.card-body').style.setProperty('display', 'none', 'important');
                    }
                });
            </script>";

        echo '
        <style>
            .navbar-expand .navbar-nav#usernavigation {
              display: none;
            }
            /*.primary-navigation {
              display: none !important;
            }*/

            .primary-navigation [data-key="myhome"],
            .primary-navigation [data-key="home"],
            .primary-navigation [data-key="ioaddashboardnode"],
            .primary-navigation [data-key="mycourses"],
            .primary-navigation .dropdown,
            .primary-navigation [data-key=""]{
                display:none;
            }
            
            form.editmode-switch-form{
                display:none !important;
            }

            .drawer-toggler.drawer-right-toggle [data-toggler="drawers"] {
              display: none;
            } 
            
            
            #page-user-edit #fitem_id_maildisplay,#page-user-edit #fitem_id_moodlenetprofile,#page-user-edit #fitem_id_description_editor, #page-user-edit #id_moodle_additional_names, #page-user-edit #id_moodle_interests, #page-user-edit #id_moodle_optional{
              display: none;
            }
            button.navbar-toggler[data-target="theme_moove-drawers-primary"]{
              display: none !important;
            }
            #page-course-view-topics .secondary-navigation {
              display: none;
            }
            .card.activity-navigation.mdl-bottom #jump-to-activity {
                display: none;
            }

        }
        </style>';
            if($currenturl==$modediturl){
                echo '<style>
                .bg-white{
                    background-color: #fff !important;
                }
                </style>';
            }else{
                echo '<style>
                .format-topics.path-mod .navbar{
                    background-color: #0f47ad !important;
                }
                .format-topics.path-mod .navbar a{
                    color: #fff !important;
                }
                .format-topics.path-mod .navbar a:hover{
                    color: black !important;
                }
                .format-topics.path-mod .navbar-light .navbar-brand {
                    color: #fff !important;
                }
                </style>';
            }

    }

    echo ' 
    <style>
    /* hide page user/preferences.php */

        #page-user-preferences a[href^="'.$CFG->wwwroot.'/user/language.php"],#page-user-preferences a[href^="'.$CFG->wwwroot.'/message/notificationpreferences.php"],#page-user-preferences a[href^="'.$CFG->wwwroot.'/user/forum.php"],#page-user-preferences a[href^="'.$CFG->wwwroot.'/user/editor.php"], #page-user-preferences a[href^="'.$CFG->wwwroot.'/user/contentbank.php"], #page-user-preferences a[href^="'.$CFG->wwwroot.'/user/calendar.php"], #page-user-preferences a[href^="'.$CFG->wwwroot.'/user/contentbank.php"], #page-user-preferences a[href^="'.$CFG->wwwroot.'/message/edit.php"]{
        display:none;
        }
   
        /** hide zoom activity fields ***/

        #page-mod-zoom-mod #id_breakoutrooms,
        #page-mod-zoom-mod #id_security,
        #page-mod-zoom-mod #id_media,
        #page-mod-zoom-mod #id_host,
        #page-mod-zoom-mod #id_modstandardelshdr,
        #page-mod-zoom-mod #id_tagshdr,
        #page-mod-zoom-mod #id_competenciessection{
            display: none;
        }
        /** hide Reservation activity fields ***/   

        
        /** hide scorm activity fields ***/

        #page-mod-scorm-mod #id_tagshdr,
        #page-mod-scorm-mod #id_competenciessection,
        #page-mod-scorm-mod #id_modstandardelshdr,
        #page-mod-scorm-mod #id_compatibilitysettingshdr{
            display:none;
        }

        /** hide scorm activity fields ***/

        #page-mod-customcert-mod #id_modstandardelshdr,
        #page-mod-customcert-mod #id_tagshdr,
        #page-mod-customcert-mod #id_competenciessection{
            display:none;
        }

        /** hide course rating ***/

        #page-mod-courserating-mod #id_timinghdr,
        #page-mod-courserating-mod #id_courseratinghdr,
        #page-mod-courserating-mod #id_aftersubmithdr,
        #page-mod-courserating-mod #id_modstandardelshdr,
        #page-mod-courserating-mod #id_tagshdr,
        #page-mod-courserating-mod #id_competenciessection{
            display:none;
        }

    

        /** hide Iomad dashboard admin view***/

        #page-blocks-iomad_company_admin-company_edit_form #id_manageremails,
        #page-blocks-iomad_company_admin-company_edit_form #id_userdefaults,
        #page-blocks-iomad_company_admin-company_edit_form #id_appearance,
        #page-blocks-iomad_company_admin-company_edit_form #id_certificatedesign,
        #page-blocks-iomad_company_admin-company_edit_form #fitem_id_companydomains,
        #page-blocks-iomad_company_admin-company_edit_form #fitem_id_roletemplate,
        #page-blocks-iomad_company_admin-company_edit_form #fitem_id_templates,
        #page-blocks-iomad_company_admin-company_edit_form #fitem_id_parentid,
        #page-blocks-iomad_company_admin-company_edit_form #fitem_id_parentid,
        #page-blocks-iomad_company_admin-company_edit_form #fitem_id_suspendafter,
        #page-blocks-iomad_company_admin-company_edit_form #fitem_id_custom1,
        #page-blocks-iomad_company_admin-company_edit_form #fitem_id_custom2,
        #page-blocks-iomad_company_admin-company_edit_form #fitem_id_custom3,
        #page-user-profile .profile_tree .editprofile

        {
            display:none;
        }

        #page-blocks-iomad_company_admin-index a[href="#MicrolearningAdmin"],
        #page-blocks-iomad_company_admin-index a[href="#CompetencyAdmin"],
        #page-blocks-iomad_company_admin-index a[href="#Reports"],
        #page-blocks-iomad_company_admin-index a[href="'.$CFG->wwwroot.'/local/email/template_list.php"],
        #page-blocks-iomad_company_admin-index a[href="'.$CFG->wwwroot.'/blocks/iomad_company_admin/company_departments.php"],
        #page-blocks-iomad_company_admin-index a[href="'.$CFG->wwwroot.'/blocks/iomad_company_admin/company_user_profiles.php"],
        #page-blocks-iomad_company_admin-index a[href="'.$CFG->wwwroot.'/blocks/iomad_company_admin/company_users_form.php"],
        #page-blocks-iomad_company_admin-index a[href="'.$CFG->wwwroot.'/blocks/iomad_company_admin/uploaduser.php"],
        #page-blocks-iomad_company_admin-index a[href="'.$CFG->wwwroot.'/blocks/iomad_company_admin/user_bulk_download.php"],
        #page-blocks-iomad_company_admin-index a[href="'.$CFG->wwwroot.'/blocks/iomad_company_admin/user_bulk_download.php"],
        #page-blocks-iomad_company_admin-index a[href="'.$CFG->wwwroot.'/blocks/iomad_approve_access/approve.php"],
        #page-blocks-iomad_company_admin-index a[href="'.$CFG->wwwroot.'/blocks/iomad_company_admin/company_courses_form.php"],
        #page-blocks-iomad_company_admin-index a[href="'.$CFG->wwwroot.'/blocks/iomad_company_admin/company_groups_users_form.php"],
        
        #page-blocks-iomad_company_admin-index a[href="'.$CFG->wwwroot.'/blocks/iomad_company_admin/company_groups_create_form.php"],
        #page-blocks-iomad_company_admin-index a[href="'.$CFG->wwwroot.'/local/iomad_learningpath/manage.php"],
        #page-blocks-iomad_company_admin-index a[href="'.$CFG->wwwroot.'/blocks/iomad_company_admin/company_license_users_form.php"]
        {
            display:none;
        }


        #page-blocks-iomad_company_admin-company_user_create_form #id_licenses,
        #page-blocks-iomad_company_admin-company_user_create_form #id_courses{
            display:none;
        } 
        #page-blocks-iomad_company_admin-company_course_create_form #id_category_1{
            display:none;
        }

        #page-blocks-iomad_company_admin-editcompanies .generaltable a[href^="'.$CFG->wwwroot.'/blocks/iomad_company_admin/company_edit_form.php"]{
            display:none;
        }

        /** hide field course create form**/
        #page-course-edit #id_courseformathdr,
        #page-course-edit #id_appearancehdr,
        #page-course-edit #id_completionhdr,
        #page-course-edit #id_filehdr,
        #page-course-edit #id_rolerenaming,
        #page-course-edit #id_groups,
        #page-course-edit #id_tagshdr,
        #page-course-edit #fitem_id_customfield_tool_courserating_mode,
        #page-course-edit #fitem_id_customfield_tool_courserating_mode_static
        {
            display:none;
        }


        #page-blocks-iomad_company_admin-company_license_list .singlebutton form[action="company_license_edit_form.php"],
        #page-blocks-iomad_company_admin-company_license_list table a[href^="company_license_edit_form.php"]{
            display:none;
        }

        

        #page-user-profile .profile_tree a[href^="'.$CFG->wwwroot.'/payment/gateway/stripe/subscriptions.php"]{
            display:none;
        }

        /** END code hide Iomad dashboard admin view***/


        #page-login-index .new-logo-class{
           display: flex !important; 
        }




        #page-mod-courserating-view a[data-action="tool_courserating-viewratings"]{
          display: none;
        }

        #page-user-profile .custom-cart{
            display:none !important;
        }

        #page-mod-scorm-view form#scormviewform .pt-1 {
          display: none;
        }


        #courseindexsection0 {
          display: none !important;
        }

        /** moodle course quiz page css **/

        #page-mod-scorm-player .flex-row-reverse {
            display: none !important;
        }
 



        .format-topics.path-mod-quiz .que .answer input[type="radio"],
        .format-topics.path-mod-quiz .que .answer input[type="checkbox"] {
                width: 30px !important;
                height: 30px !important;
                margin-botom: 0px !important;
                box-shadow: none !important;
            }
                
        .que.multichoice .answer div.r0, 
        .que.multichoice .answer div.r1, 
        .que.truefalse .answer div.r0, 
        .que.truefalse .answer div.r1 {
            align-items: center !important;
            display: flex !important;
            margin: 10px 0 !important;
            border-radius: 5px;
            background-color: #fff;
            padding: 10px !important;
            border: 1px solid #e2e0db !important;
            color:#666 !important;
            font-size: 16px !important;
            font-weight:normal !important; 
            cursor: pointer !important;
        }

       .que.multichoice .answer div.r0:hover, 
        .que.multichoice .answer div.r1:hover, 
        .que.truefalse .answer div.r0:hover, 
        .que.truefalse .answer div.r1:hover{
          background-color:#f3f1f1 ;
        }

        .que.multichoice .answer div.r1 p, 
        .que.multichoice .answer div.r0 p , 
        .que.multichoice .answer div.r1 label, 
        .que.multichoice .answer div.r0 label {
            margin: 0;
        }
        #page-mod-quiz-attempt .que .info, #page-mod-quiz-review .que .info{
            display:none;
        }
        #page-mod-quiz-attempt .que .formulation, #page-mod-quiz-review .que .formulation {
            background-color: #fff !important;
            border-color: #fff !important;
        }
        .format-topics.path-mod #page-navbar {
          display: none !important;
        }
        .format-topics .activityiconcontainer{
            display:none;
        }
        #page-mod-quiz-attempt .tertiary-navigation .navitem{
            display:none;
        }
        #page-mod-quiz-summary .tertiary-navigation .navitem{
            display:none;
        }
        .format-topics.path-mod .activity-header{
            display:none;
        }

        table.quizreviewsummary {
          width: 50% !important;
          margin: auto;
        }
        table.quizreviewsummary th.cell {
            padding: 20px !important;
            vertical-align: middle;
            background-color: #0f47ad !important;
            color: #fff;
        }

        table.quizreviewsummary td.cell {
            color: #666;
            font-size: 15px;
            vertical-align: middle;
        }

        .que .qtext > p {
            font-size: 18px;
            color: #777;
        }
        body {
          background-color: #fff !important;
        }
        #page-my-index .page-header-headings{
            display:none;
        }
        #page-message-index #page-header{
            display:block !important;
        }
        #page-message-index .userpicture {
          display: none;
        }
        
        // .login-form-forgotpassword.form-group.text-center {
        //     display: none;
        // }
    </style>';

    $customcerturl= $CFG->wwwroot . '/mod/customcert/view.php';

    if($currenturl==$customcerturl){
        echo "<script>
            var form = document.querySelector('form[action=\"{$customcerturl}\"]');
            if (form) {
                form.target = '_blank';
            }
        </script>";
    }
    $userprofileurl =$CFG->wwwroot .'/user/profile.php';
    if($currenturl==$userprofileurl){

        echo "     <script>
        var h3 = document.createElement('h3');
        h3.textContent = '".get_string('my_profile', 'block_custom_mycourse')."'; 
        var topofscroll = document.getElementById('topofscroll');
        topofscroll.prepend(h3);


        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.node_category').forEach(section => {
              const heading = section.querySelector('h3');
              if (heading && heading.textContent.trim().toLowerCase().includes('stripe payment subscriptions')) {
                section.setAttribute('style', 'display: none !important;');
              }
            });
        });
    </script>"; 

    }  
    $loginurl = $CFG->wwwroot .'/login/index.php';
    if($currenturl == $loginurl){
        $current_host = $_SERVER['HTTP_HOST'];
        $getcompany = $DB->get_record_sql("SELECT * FROM {company} WHERE hostname LIKE ?", array($current_host));
        $companyid = $getcompany->id;
        // $companyid = iomad::get_my_companyid(context_system::instance());
        if($companyid > 0){
            $comlogodetails = $DB->get_record_sql('SELECT * FROM {company_logo} WHERE company_id = ?',array($companyid));

            if($comlogodetails){
                $companylogo = false;

                // Try mobile topbar logo first.
                if($comlogodetails->logo_image){
                    $companylogo = $DB->get_record_sql(
                        "SELECT * FROM {files} 
                            WHERE contextid= 1 
                            AND component='landingpage' 
                            AND itemid=? AND filesize > 0 
                            AND filearea = ?", 
                            [$comlogodetails->id,'logo_image']
                    );
                }
                if (!$companylogo) {
                    $companylogo = $DB->get_record_sql(
                        "SELECT * FROM {files} 
                            WHERE contextid= 1 
                            AND component='landingpage' 
                            AND itemid=? AND filesize > 0 
                            AND filearea = ?", 
                            [$comlogodetails->id,'logo_mobile_topbar']
                    );

                }

                if ($companylogo) {
                    $logourl = $CFG->wwwroot.'/blocks/custom_mycourse/file.php?id='.$companylogo->pathnamehash;
                    echo '<script>
                            const newImage = "'.$logourl.'";
                            const newClass = "new-logo-class"; // Change this to whatever class you want to add
                            console.log(newImage);
                            document.addEventListener("DOMContentLoaded", function () {
                                const logoImg = document.getElementById("logoimage");
                                if (logoImg) {
                                    logoImg.src = newImage;
                                    loginlogo.classList.add(newClass);
                                }
                            });
                        </script>';
                    
                }

            }
        }else{
            echo "<style>
            #page-login-index #loginlogo{
                display: flex !important;
            }

            </style>";
        }
    }








    // login page validation
    $loginpageurl =$CFG->wwwroot .'/login/index.php';
    if($currenturl==$loginpageurl){
        $current_host = $_SERVER['HTTP_HOST'];
        $getcompany = $DB->get_record_sql("SELECT * FROM {company} WHERE hostname LIKE ?", array($current_host));
        if(empty($getcompany)){
            echo "<style>
            .learnpic-user-registration {
              display: none;
            }
            </style>";
        }
    }

    if (isloggedin()) {
        echo '<div class="custom-cart">';
            \block_iomad_commerce\helper::show_basket_info();
            // if (array_key_exists('tag', $_GET)) {
            //      $shoptags = \block_iomad_commerce\helper::get_shop_tags();
            // }
            echo '</div>
            <style>
            .custom-cart {
              position: absolute;
              top: 20px;
              right: 20px;
            }
            </style>';


    //update compnay logo 
    // $companyid = iomad::get_my_companyid(context_system::instance());
    $current_host = $_SERVER['HTTP_HOST'];
    $getcompany = $DB->get_record_sql("SELECT * FROM {company} WHERE hostname LIKE ?", array($current_host));

    if($getcompany){
        $companyid = $getcompany->id;
        $comlogodetails = $DB->get_record_sql(
            'SELECT * FROM {company_logo} 
            WHERE company_id = ?',
            [$companyid]
        );

        if($comlogodetails){

            $companylogo = false;

            // Try mobile topbar logo first.
            if (!empty($comlogodetails->logo_image)) {

                $companylogo = $DB->get_record_sql(
                    "SELECT * FROM {files} 
                        WHERE contextid= 1 
                        AND component='landingpage' 
                        AND itemid=? 
                        AND filesize > 0 
                        AND filearea = ?", 
                    [$comlogodetails->id,'logo_image']
                );
            }

            if (!$companylogo) {
                $companylogo = $DB->get_record_sql(
                    "SELECT * FROM {files} 
                        WHERE contextid= 1 
                        AND component='landingpage' 
                        AND itemid=? 
                        AND filesize > 0 
                        AND filearea = ?", 
                    [$comlogodetails->id,'logo_mobile_topbar']
                );

            }

            if ($companylogo) {
                 $logourl = $CFG->wwwroot.'/blocks/custom_mycourse/file.php?id='.$companylogo->pathnamehash;
                echo '<script>
                            const newImage = "'. $logourl.'";
                            // Wait until the DOM is fully loaded
                            document.addEventListener("DOMContentLoaded", function () {
                                const logoImg = document.querySelector(".navbar-brand img.logo");
                                if (logoImg) {
                                    logoImg.src = newImage;
                                }
                            });
                        </script>
                    <style>
                    .navbar-brand .logo{
                      display: block !important;
                    }

                    </style>';

            }
        }

    }else{
        echo "<style>
                .navbar-brand .logo {
                  display: block !important;
                }
        </style>";
    }




        $currenturl;
        // Define the course page URL
        $calendarpage = $CFG->wwwroot . '/calendar/view.php';
        if($currenturl == $calendarpage){
            echo '<style>
            .maincalendar [data-action="new-event-button"] {
                display:none;
            }

            </style>';
        }
    }




}




// delete chache
function deleteFolderAndFiles($folderPath) {
    if (!is_dir($folderPath)) {
        return false;
    }

    $items = scandir($folderPath);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $fullPath = $folderPath . DIRECTORY_SEPARATOR . $item;

        if (is_dir($fullPath)) {
            deleteFolderAndFiles($fullPath);
        } else {
            unlink($fullPath);
        }
    }

    return rmdir($folderPath);
}



     