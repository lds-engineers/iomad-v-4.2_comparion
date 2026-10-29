<?php
require_once('../../config.php');
global $DB, $CFG, $USER;
 require_once($CFG->dirroot . "/course/externallib.php");
$userpicture = new user_picture($USER);
$userpicture->size = 1; // Size f1.
$img = $userpicture->get_url($PAGE)->out(false);

$html = '

<style>
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

</style>

<!-- ####### START FIXED SIDEBAR ############ -->
<div class="d-flex flex-column flex-shrink-0 p-3 text-white bg-dark position-fixed  fixed-left fixed-bottom" style="width:250px; top:70px; height: calc(100% -70px) !important; overflow-x:hidden; overflow-y:auto">
    <a href="/" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none flex-column justify-content-center">
        <div class="img-parent rounded-circle border-0">
            <img class="rounded-circle"  fetchpriority="high" decoding="async" alt="User Avatar"
                 src="'.$img.'" height="100"
                 width="100">
        </div>
        <span class="fs-4">Name</span>
    </a>
    <hr>
    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item">
            <a href="'.$CFG->wwwroot.'/my/" class="nav-link active" aria-current="page">
                <i class="fa-solid fa-bars mx-2"></i>
               My Course 
            </a>
        </li>
        <li>
            <a href="'.$CFG->wwwroot.'/local/customlayout/certificates.php" class="nav-link text-white">
                <i class="fas fa-certificate mx-2"></i>
                Certificates 
            </a>
        </li>
        <li>
            <a href="'.$CFG->wwwroot.'/local/customlayout/quizzes.php" class="nav-link text-white">
                <i class="fas fa-puzzle-piece mx-2"></i>
                Quizzes 
            </a>
        </li>
        <li>
            <a href="#" class="nav-link text-white">
                <i class="fas fa-heart mx-2"></i>
                 Wishlist 
            </a>
        </li>
        <li>
            <a href="#" class="nav-link text-white">
                <i class="fas fa-shopping-cart mx-2"></i>
                 Order
             </a>
        </li>
        <li>
            <a href="#" class="nav-link text-white">
                <i class="fas fa-calendar mx-2"></i>
                Events 
            </a>
        </li>
    </ul>
    <hr>
    <ul class="nav nav-pills flex-column">
        <li class="list-style-none">
            <a href="'.$CFG->wwwroot.'/login/logout.php?sesskey='.$USER->sesskey.'" class="nav-link text-white">
                <i class="fa-solid fa-right-from-bracket mx-2"></i>
                 Logoutss
            </a>
        </li>
    </ul>
    <div class="dropdown">
        <a href="'.$CFG->wwwroot.'/user/edit.php?id='.$USER->id.'&returnto=profile" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false" style="padding: 8px 16px;">
            <i class="fas fa-cog  mx-2"></i>
           Profile
        </a>
        <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1" style="width:250px">
            <li><a class="dropdown-item" href="'.$CFG->wwwroot.'/user/edit.php?id='.$USER->id.'&returnto=profile">General</a></li>
            <li><a class="dropdown-item" href="'.$CFG->wwwroot.'/user/edit.php?id='.$USER->id.'&returnto=profile">Avatar</a></li>
            <li><a class="dropdown-item" href="'.$CFG->wwwroot.'/user/edit.php?id='.$USER->id.'&returnto=profile">Password</a></li>
            <li><a class="dropdown-item" href="'.$CFG->wwwroot.'/user/edit.php?id='.$USER->id.'&returnto=profile">uploaded ID</a></li>
            <li>
                <hr class="dropdown-divider">
            </li>
            <li><a class="dropdown-item" href="'.$CFG->wwwroot.'/login/logout.php?sesskey='.$USER->sesskey.'">Sign out</a></li>
        </ul>
    </div>
    
</div>
<!-- ######## END FIXED SIDEBAR ############ -->
';

$html='';

$response = array(
    "status" => 1,
    "msg" => $html,
);
echo json_encode($response);
