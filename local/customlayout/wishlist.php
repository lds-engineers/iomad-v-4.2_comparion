<?php
require_once('../../config.php');
global $DB, $CFG, $USER, $PAGE;
require_login();

$userid = optional_param('userid', $USER->id, PARAM_INT);
if(!is_siteadmin()){
    $userid = $USER->id;  // Owner of the page
}

$strmymoodle = get_string('wishlist', 'local_customlayout');
$PAGE->set_title($strmymoodle);
$PAGE->set_heading($strmymoodle);



$html = '

<style>
.cst-heart i {
    font-size: 40px;
    color: #0f47ad;
}

 @media(max-width:635px){
    .list {
    flex-direction: column;
}

.list .cst-img-parent {
    width: 100% !important;
    height: 250px !important;
    border-radius: 0 !important;
    padding:0 !important
}

.list .cst-img-parent img {
    width: 100%;
}

.favrouit-listing {
    margin: 20px 0 !important;
    justify-content: flex-start !important;
}

.cst-heart {
    display: flex;
    flex-direction: column;
    font-size: 29px;
    align-items: flex-start;
}

}
</style>

<div class="cst-list-parent row col-lg-12">
  <div class="list col-lg-12 p-2 border d-flex align-items-center my-2" style="border-radius:30px; padding:0px !important;">
     <div class="col-lg-2 cst-img-parent" style="width:140px; height:80px">
       <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSsxVraP-b6X-K2-bA-i6oDoctCPBg5UY-vOg&s" style=" border-radius:20px; height:100%" />
     </div>

     <div class="peradescription col-lg-4">
         <span class="d-block" style="font-size:25px; font-wight:bolder;">Course name</span>
         <p>It is a long established fact that a reader will be distracted by the readable content of a page when </p>
     </div>
 
     <div class="favrouit-listing col-lg-4 ml-auto d-flex justify-content-end">
         <div class="cst-heart text-center">
             <span class="text-secondary">Wishlist</span>
             <i class="fa-solid fa-heart"></i>
         </div>
     </div>

  </div>
  <div class="list col-lg-12 p-2 border d-flex align-items-center my-2" style="border-radius:30px; padding:0px !important;">
     <div class="col-lg-2 cst-img-parent" style="width:140px; height:80px">
       <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSsxVraP-b6X-K2-bA-i6oDoctCPBg5UY-vOg&s" style=" border-radius:20px; height:100%" />
     </div>

     <div class="peradescription col-lg-4">
         <span class="d-block" style="font-size:25px; font-wight:bolder;">Course name</span>
         <p>It is a long established fact that a reader will be distracted by the readable content of a page when </p>
     </div>
 
     <div class="favrouit-listing col-lg-4 ml-auto d-flex justify-content-end">
         <div class="cst-heart text-center">
             <span class="text-secondary">Wishlist</span>
             <i class="fa-solid fa-heart"></i>
         </div>
     </div>

  </div>
  <div class="list col-lg-12 p-2 border d-flex align-items-center my-2" style="border-radius:30px; padding:0px !important;">
     <div class="col-lg-2 cst-img-parent" style="width:140px; height:80px">
       <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSsxVraP-b6X-K2-bA-i6oDoctCPBg5UY-vOg&s" style=" border-radius:20px; height:100%" />
     </div>

     <div class="peradescription col-lg-4">
         <span class="d-block" style="font-size:25px; font-wight:bolder;">Course name</span>
         <p>It is a long established fact that a reader will be distracted by the readable content of a page when </p>
     </div>
 
     <div class="favrouit-listing col-lg-4 ml-auto d-flex justify-content-end">
         <div class="cst-heart text-center">
             <span class="text-secondary">Wishlist</span>
             <i class="fa-solid fa-heart"></i>
         </div>
     </div>

  </div>
</div>
';
echo $OUTPUT->header();
echo $html;
echo $OUTPUT->footer();
