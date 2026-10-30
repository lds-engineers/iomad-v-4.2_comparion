<?php
use core_completion\progress;
// include_once($CFG->dirroot . '/course/lib.php');
class block_leanpress_enrolledcourses extends block_base {
    public function init() {
        // $this->title = get_string('pluginname', 'block_custom_mycourse');
        $this->title = '';
    }
    

    public function get_content() {
        global $PAGE, $USER, $DB, $CFG;
        $this->content = new stdClass();
        $companyid = iomad::get_my_companyid(context_system::instance());
        $wp_siteurl = get_config(null, "main_site_url{$companyid}");
        $enrolledcourseurl = rtrim($wp_siteurl, '/') . '/wp-json/custom/v1/lpcenrolledcourses';
        $verified = $DB->get_record('sso_validation', array('user_id' => $USER->id, 'required_validation' => 1));
        if($verified){
            $is_verified = 1;
        }else{
            $is_verified = 0;
        }

        $request_data = [
            'lms_userid'    => $USER->id,
            'user_email'    => $USER->email,
            'is_verified'   => $is_verified,
            'moodlewsrestformat'=> 'json'
        ];

        $curl = new curl();
        $response = $curl->post($enrolledcourseurl, $request_data);
        $result = json_decode($response);
        $enrolcourses = $result->coursesdata;
        // echo "<pre>";
        // print_r($enrolcourses);
        // echo "</pre>";
        $html = '
        <div class="row">
            <div class="col-md-12" align="left">
            <h3>PDHStar Enrolled Courses</h3>
            <hr/>
            </div>';
                if($result->status == 1){
                    if ($enrolcourses && is_array($enrolcourses)) {
                        foreach ($enrolcourses as $course) {
                            $percentage = $course->progress;
                            $html .= '
                            <a href="'.$CFG->wwwroot.'/local/user_registration/sso_validation.php?lpcourseid='.$course->course_id.'">
                                <div class="col-12 col-lg-4 col-md-4 my-3">
                                    <div class="card">  
                                        <div class="img-frame img-frame-1by1">
                                        <img src="'.$course->course_image_url.'" alt="'.$course->course_name.'" class="courseimage">
                                        </div>
                                        <div class="card-body custom-border p-3">
                                            <a href="'.$CFG->wwwroot.'/local/user_registration/sso_validation.php?lpcourseid='.$course->course_id.'"><h6 class="card-title head-lime-clamp">'.$course->course_name.'</h6></a>
                                            <div class="h-progress">
                                                <div id="myProgress">
                                                    <div id="myBar" style="width: '.$percentage.'%;"></div>
                                                </div>
                                                <p>'.$percentage.'% complete</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>';
                        }
                    }else{
                        $html .= '<div class="alert alert-warning text-center mt-4 width_100pr">Not Enrolled.</div>';
                    }
                }else{
                    $html .= '<div class="alert alert-warning text-center mt-4 width_100pr">SSO Are pending.</div>';
                }
            $html .= '    
        </div>';
        $html .= '
         <style>
            .block_leanpress_enrolledcourses{
                box-shadow: none !important;
            }
            .width_100pr{
                width:100%;
            }
            .img-frame {
                position: relative;
                display: block;
                width: 100%;
                padding: 0;
                overflow: hidden;
                height: 10rem !important;
            }
            .img-frame::before {
                display: block;
                content: "";
            }
            .img-frame-21by9::before {
                padding-top: 42.8571428571%;
            }
            .img-frame-16by9::before {
                padding-top: 56.25%;
            }
            .img-frame-4by3::before {
                padding-top: 75%;
            }
            .img-frame-1by1::before {
                padding-top: 100%;
            }
            .img-frame img {
                position: absolute;
                top: 0;
                bottom: 0;
                left: 0;
                width: 100%;
                border: 0;
                -o-object-fit: cover;
                object-fit: cover;
                border-top-left-radius: 8px;
                border-top-right-radius: 8px;
                height:100%;
            }

            .head-lime-clamp{
                display: -webkit-box;
                -webkit-box-orient: vertical;
                -webkit-line-clamp: 2;
                overflow: hidden;
            }

            .txt-lime-clamp{
                display: -webkit-box;
                -webkit-box-orient: vertical;
                -webkit-line-clamp: 3;
                overflow: hidden;
            }

            #myProgress { 
                background : #f2f2f2;
                width : 100%;
                height : 3px;
            }

            #myBar{
                background : #3986b6;
                height : 100%;
            }

            .theme-btn,
            .theme-btn:hover,
            .theme-btn:focus{
                background: #3986b6;
                border: 2px solid #3986b6;
                color: #fff;
                padding: 3px 10px;
                border-radius: 3px;
            }
            .h-progress{
                min-height: 50px;
                display: flex;
                flex-direction: column;
                justify-content: center;
            }
            .custom-border{
                border: 1px solid #e3e3e3;
                border-bottom-left-radius: 8px;
                border-bottom-right-radius: 8px;
            }
        </style>';



        // $html = 'Leanpress Enrolled Course';
        if($companyid ==36){
            $this->content->text = $html;
        }else{
            $this->content->text = "";
        }

        return $this->content;
    }
}
