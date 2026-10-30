<?php
use core_completion\progress;

class block_learnpeek_userdashboard extends block_base {
    public function init() {
        // Set to empty string if you want a completely frameless dashboard,
        // or add a title like 'Dashboard Overview'
        $this->title = ''; 
    }

    public function get_content() {
        global $PAGE, $USER, $DB, $CFG;

        require_once($CFG->dirroot . '/blocks/learnpeek_userdashboard/locallib.php');
        
        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';

        // Dynamic greeting based on current logged-in user
        $firstname = fullname($USER, true) ? $USER->firstname : 'User';

        // Static HTML structure using Moodle-compatible Bootstrap utility classes
        $html = '
        <link rel="stylesheet" href="'.$CFG->wwwroot .'/blocks/learnpeek_userdashboard/style.css">
        <div class="learnpeek-dashboard px-0">
            <div class="row mb-4">
                <div class="col-12 col-sm-6 col-lg-6">
                    <h2 class="font-weight-bold mb-1">Welcome back, ' . s($firstname) . '! 👋</h2>
                    <p class="text-muted">Continue your learning journey and achieve your goals.</p>
                </div>
                <div class="col-12 col-sm-6 col-lg-6">
                   
                <form action="'.$CFG->wwwroot .'/blocks/learnpeek_shop/shop.php" method="get">
                    <div>
                        <input class="w-100 border p-3" style="border-radius:20px;" type="search" name="q" placeholder="Search for Courses" />
                    </div>
                </form>

                </div>
            </div>

            ' . learnpeek_user_overview($USER->id) . '

            <div class="row">

                <div class="col-lg-8 col-md-12">
                    
                    <!------ Continue Learning ------>
                    
                    '. user_continue_learning($USER->id) .'

                    <!------ My Certificates ------>

                    '.learnpeek_user_certificates($USER->id).'
                    
                    <!------ Explore New Courses  ------>

                    '.explore_new_courses().'

                </div>

                 <div class="col-lg-4 col-md-12">
                   <!----- Learning Streak 1356---->
                    '. render_learning_streak_card($USER->id) .'

                    <!----- Recent Achievements ---->
                    '. learnpeek_user_recent_achievements($USER->id);

                    // ----- Upcoming Events  ----

                    $company = $DB->get_record("company_users", array("userid" => $USER->id));
                    if(!empty($company->companyid)){
                        $companyid = $company->companyid;
                       $html .= ''.learnpeek_user_upcoming_events($USER->id, 2, $companyid);
                    }
                    $html .=' 

                </div>

            </div>

        </div>';

        $this->content->text = $html;
        return $this->content;
    }
}