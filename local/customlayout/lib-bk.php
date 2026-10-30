<?php

function local_customlayout_after_require_login()
{
    global $PAGE;
    // print_r($PAGE->pagetype);
    // die;
    if ((substr($PAGE->pagetype, 0, strlen('admin-')) != 'admin-') && (substr($PAGE->pagetype, 0, strlen('course-')) != 'course-') && (substr($PAGE->pagetype, 0, strlen('mod-')) != 'mod-')) {
        $PAGE->requires->jquery();
        $PAGE->requires->js('/local/customlayout/js/custom_layout.js');
    }
}

function local_customlayout_before_standard_top_of_body_html()
{
    $html = '<style>';
    if (is_siteadmin()) {
        $html .= '
            .activity-navigation.mdl-bottom .urlselect {
                display:none;
            }
        ';
    }
    $html .= '</style>';
    return $html;
}


function local_customlayout_before_footer()
{
    global $PAGE, $DB, $CFG, $USER;
    if (empty($USER->id) || $USER->id <= 1) {
        return;
    }
    if ((substr($PAGE->pagetype, 0, strlen('admin-')) != 'admin-') && (substr($PAGE->pagetype, 0, strlen('course-')) != 'course-') && (substr($PAGE->pagetype, 0, strlen('mod-')) != 'mod-')) {
    } else {
        return;
    }

    require_once($CFG->dirroot . "/course/externallib.php");
    $userpicture = new user_picture($USER);
    $userpicture->size = 1; // Size f1.
    $img = $userpicture->get_url($PAGE)->out(false);


    $context = context_user::instance($USER->id); // User context
    $current_user_roles = get_user_roles($context, $USER->id);
    $mdepartments = '';
    // Check if the user has the 'companymanager' role
    foreach ($current_user_roles as $role) {
        if ($role->shortname == 'companymanager') {
            // Role found, show the "Manage departments" link
            $mdepartments = '
            <li>
                <a href="' . $CFG->wwwroot . '/blocks/iomad_company_admin/index.php" class="nav-link text-dark">
                    <i class="fa fa-user-o mx-2" aria-hidden="true"></i>
                    Manage departments
                 </a>
            </li>';
            break; // Exit loop as we found the role
        }
    }
    $orderlist = '';
    if (is_siteadmin()) {
        $orderlist = '
        <li>
            <a href="' . $CFG->wwwroot . '/blocks/iomad_commerce/orderlist.php" class="nav-link text-dark">
                <i class="fas fa-shopping-cart mx-2"></i>
                 Order
             </a>
        </li>';
    } else {
        $orderlist =
            '<li>
            <a href="' . $CFG->wwwroot . '/blocks/learnpeek_shop/orderlist.php" class="nav-link text-dark">
                <i class="fas fa-shopping-cart mx-2"></i>
                 Order
             </a>
        </li>';
    }

    $html = '
 
    <style>
        .activity-navigation.mdl-bottom .urlselect {
            display:none;
        }

        section.block_myoverview{
            display: block !important;

        }

        body{
             overflow-x: hidden;
        }

        body.adjust .cst-sidebar{
          transform: translateX(0%);
        }


        #page-wrapper #page{
          height:100vh !important;
          margin-bottom:100px;
        }


        .trigger {
            width: 100px;
            background: #0f47ad;
            text-align: center;
            font-size: 30px;
            padding: 10px;
            border-radius: 20px;
            position: absolute;
            top: 71px;
            display:none;
            left: -11px;
            color: #fff;
            z-index: 999;
        }

        .cst-close {
            font-size: 20px;
            font-weight: bolder;
            color: #fff;
            position: absolute;
            right: 0px;
            display:none;
            background-color: black;
            top: -1px;
            width: 50px;
            text-align: center;
            cursor: pointer;
        }

        .drawer.show + div#page {
            width: calc(100% - 575px) !important;
            margin-left: 250px !important;
        }

        div#page {
            width: calc(100% - 152px);
            margin-left: 146px;
        }

        .secondary-navigation {
            width: calc(100% - 250px) !important;
            margin-left: 250px;
        }

        ::-webkit-scrollbar {
          width: 5px;
        }

        footer#cst-footer {
            padding: 0px 10px; 
            position: fixed;
            background-color: #343a40;
            left: 0;
            right: 0;
            bottom:0;
            width: 100%;
            color: #fff;
        }

        footer#cst-footer .menu_parent ul {
            padding: 0;
            margin: 0;
            list-style: none;
            display: flex;
            justify-content: center;
        }

        footer#cst-footer .menu_parent ul li {
            padding: 20px;
            position:relative;
        }

        footer#cst-footer .menu_parent ul li:after {
            content: "";
            position: absolute;
            width: 1px;
            background-color: #d9d9d9;
            height: 43%;
            margin-left: 13px;
        }

        footer#cst-footer .menu_parent ul li a {
            color: #d9d9d9;
            font-size: 16px;
            text-decoration: none;
        }

        footer#cst-footer .details_parent {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            margin: 0;
            color: #d9d9d9;
        }

        footer#cst-footer .details_parent p span {
            color: #767676;
        }

        @media(max-width:767px){
            .cst-close{
             display:block
            }


            #page-wrapper #page {
                margin: 100px 0 !important;
            }

            .cst-sidebar {
                transform: translateX(-100%);
                transition: all .5s ease-in-out;
            }

            .trigger{
            display:block;
            }


            div#page {
                width: 100%;
                margin-left: 0;
            }
                
            // body.adjust div#page{
            //     width: calc(100% - 152px);
            //     margin-left: 146px;
            // }

        }


        @media(max-width:991px){
            div#page{
                 margin-left: 185px;
            }
        }


    </style>

    <!-- ####### START FIXED SIDEBAR ############ -->

    <div class="trigger">
    <i class="fa-solid fa-bars"></i> 
    </div>


    <div class="cst-sidebar d-flex flex-column flex-shrink-0 p-3 text-dark bg-white position-fixed  fixed-left fixed-bottom" style="width:250px; top:70px; height: calc(100% -70px) !important; overflow-x:hidden; overflow-y:auto">
     <div class="cst-close trigger">X</div>    
    <a href="/" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-dark text-decoration-none flex-column justify-content-center">
            <div class="img-parent rounded-circle border-0 d-flex">
                <img class="rounded-circle"  fetchpriority="high" decoding="async" alt="User Avatar"
                     src="' . $img . '" height="100"
                     width="100">
            </div>
            <span class="fs-4">'.fullname($USER).'</span>
        </a>
        <hr>
        <ul class="cst-nav nav  flex-column mb-auto">


            <li>
                <a href="'.$CFG->wwwroot.'/my" class="nav-link text-dark">
                    <i class="fas fa-shopping-cart mx-2"></i>
                     My Overview
                 </a>
            </li>

            <li>
                <a href="' . $CFG->wwwroot . '/user/profile.php" class="nav-link text-dark">
                    <i class="fa fa-user-o mx-2" aria-hidden="true"></i>
                     My profile
                 </a>
            </li>
            ' . $mdepartments . '
            <li class="nav-item">
                <a href="' . $CFG->wwwroot . '/blocks/iomad_commerce/shop.php" class="nav-link text-dark" aria-current="page">
                    <i class="fa-solid fa-home mx-2"></i>
                   Shop courses 
                </a>
            </li>
            <li class="nav-item">
                <a href="' . $CFG->wwwroot . '/my/courses.php" class="nav-link text-dark" aria-current="page">
                    <i class="fa-solid fa-bars mx-2"></i>
                   My Course 
                </a>
            </li>
            <li>
                <a href="' . $CFG->wwwroot . '/local/customlayout/certificates.php" class="nav-link text-dark">
                    <i class="fas fa-certificate mx-2"></i>
                    Certificates 
                </a>
            </li>
            <!--<li>
                <a href="' . $CFG->wwwroot . '/" class="nav-link text-dark">
                    <i class="fas fa-certificate mx-2"></i>
                    Quizzes 
                </a>
            </li>
            <li>
                <a href="' . $CFG->wwwroot . '/" class="nav-link text-dark">
                    <i class="fas fa-certificate mx-2"></i>
                    Wishlist 
                </a>
            </li>-->
            
            <li>
                <a href="' . $CFG->wwwroot . '/calendar/view.php?view=month" class="nav-link text-dark">
                    <i class="fas fa-certificate mx-2"></i>
                    Events 
                </a>
            </li>

            ' . $orderlist . '
            <li>
                <a href="' . $CFG->wwwroot . '/user/preferences.php" class="nav-link text-dark">
                    <i class="fas fa-shopping-cart mx-2"></i>
                     Settings
                 </a>
            </li>
            <li>
                <a href="' . $CFG->wwwroot . '/message/index.php" class="nav-link text-dark">
                    <i class="fas fa-shopping-cart mx-2"></i>
                     Messages
                 </a>
            </li>
            <!--<li>
                <a href="' . $CFG->wwwroot . '/user/files.php" class="nav-link text-dark">
                    <i class="fas fa-shopping-cart mx-2"></i>
                     Uploads
                 </a>
            </li>-->

            <li>
                <a href="' . $CFG->wwwroot . '/login/logout.php?sesskey=' . $USER->sesskey . '" class="nav-link text-dark">
                    <i class="fa-solid fa-right-from-bracket mx-2"></i>
                     Logout
                 </a>
            </li>

        </ul>
        <hr>
       <!-- <ul class="nav  flex-column">
            <li class="list-style-none">
                <a href="' . $CFG->wwwroot . '/login/logout.php?sesskey=' . $USER->sesskey . '" class="nav-link text-dark">
                    <i class="fa-solid fa-right-from-bracket mx-2"></i>
                     Logout 
                </a>
            </li>
        </ul>
        <div class="dropdown">
            <a href="' . $CFG->wwwroot . '/user/edit.php?id=' . $USER->id . '&returnto=profile" class="d-flex align-items-center text-dark text-decoration-none dropdown-toggle" id="dropdownUser1"
               data-bs-toggle="dropdown" aria-expanded="false" style="padding: 8px 16px;">
                <i class="fas fa-cog  mx-2"></i>
               Profile
            </a>
            <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1" style="width:250px">
                <li><a class="dropdown-item" href="#">General</a></li>
                <li><a class="dropdown-item" href="#">Avatar</a></li>
                <li><a class="dropdown-item" href="#">Password</a></li>
                <li><a class="dropdown-item" href="#">uploaded ID</a></li>
                <li>
                    <hr class="dropdown-divider">
                </li>
                <li><a class="dropdown-item" href="' . $CFG->wwwroot . '/login/logout.php?sesskey=' . $USER->sesskey . '">Sign out</a></li>
            </ul> -->
        </div>
        
    </div>

    <script>
     $( document ).ready(function() {
        $(".trigger").click(function(){
            $("body").toggleClass("adjust")
        });

        $(".cst-nav .nav-link").click(function(){
            $(this).addClass("active")
        });
     });

    </script>



    <!-- ######## END FIXED SIDEBAR ############ -->




    <!-- ########## START FOOTER CUSTOM  ############### -->

    <!--
    <footer class="footer" id="cst-footer">
      <div class="container">
        <div class="menu_parent">
           <ul>
              <li><a href="#">Home</a></li>
              <li><a href="#">FAQs</a></li>
              <li><a href="#">Contact Us</a></li>
           </ul>
        </div>
        <div class="details_parent">
           <p><span class="mx-2">PDH STAR</span>by MZI Consulting LLC.</p>
           <p>Copyright by MZI Consulting LLC. All right reserved.</p>
        </div>
      </div>
    </footer>
    -->
    <!-- ########## END FOOTER CUSTOM  ############### -->
    ';

    return $html;
}
