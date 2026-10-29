<?php
require_once('../../config.php');
global $DB, $CFG, $USER, $PAGE;
require_login();


echo $OUTPUT->header();
echo '
  <style>
  .box {
    border: 1px solid #ccc;
    padding: 10px;
    border-radius: 20px;
    box-shadow: rgba(0, 0, 0, 0.35) 0px 5px 15px;
    position:relative;
}

.box .left-col {
    width: 100px;
    padding: 20px;
    border: 1px solid;
    border-radius: 100%;
    height: 100px;
    font-size: 37px;
    text-align: center;
    background-color: #2174ff;
    color: #fff;
    box-shadow: rgba(0, 0, 0, 0.19) 0px 10px 20px, rgba(0, 0, 0, 0.23) 0px 6px 6px;
    transition: all .3s ease-in-out;
    transform: scale(0.8);
    position: absolute;
    top: -53px;
    left: 114px;
    width: 100px;
}

.box:hover .left-col {
    transform: scale(1);
    box-shadow: rgba(0, 0, 0, 0.3) 0px 19px 38px, rgba(0, 0, 0, 0.22) 0px 15px 12px;
}

.box .right-col span {
    font-size: 45px;
    padding: 0 20px;
}

.box .right-col p {
    font-size: 18px;
    color: #959597f5;
}



####### second section ##########

.notificatin-box {
    box-shadow: rgba(0, 0, 0, 0.35) 0px 5px 15px;
    border-radius: 10px;
}

.circle-bell {
    width: 80px;
    height: 80px;
    margin: 0 auto;
    position: relative;
    top: -10px;
    background-color: #2174ff;
    border-radius: 50px;
    text-align: center;
    display: flex;
    border: 5px solid #fff;
    align-items: center;
    outline: 1px solid #2174ff;
    justify-content: center;
    box-shadow: rgba(0, 0, 0, 0.35) 0px 5px 15px;
}

.circle-bell i {
    font-size: 50px;
}


.toast-body ul{
  padding:0;
  margin:0;
}

.toast-body ul li {
    padding: 10px;
    border-bottom: 1px solid #2174ff;
    color: #000;
    list-style: none;
    font-size: 16px;
    display: flex;
    align-items: baseline;
    font-weight: 900;
}

.toast-body ul li i {
    font-size: 16px;
    margin: 0 10px;
}


.toast-body ul li i.fa-solid.fa-envelope {
    color: #ef6d6d;
}

.toast-body ul li i.fa-solid.fa-check-double {
    color: #4CAF50;
}

.toast-body .parent-coast .arrow .arrow-inner {
    width: 1px;
    height: 100%;
    border: 1.6px dashed #2174ff;
}

.toast .parent-coast {
    color: #000;
}

.toast-body .parent-coast .arrow .arrow-inner {
    width: 1px;
    height: 100%;
    border: 2px dotted #2174ff;
}

.toast-body .parent-coast .arrow .arrow-inner i {
    position: absolute;
    left: -7px;
    top: 7px;
    font-size: 14px;
    width: 26px;
    height: 29px;
    text-align: center;
    border-radius: 26px;
    color: #fff;
    display: flex;
    vertical-align: middle;
    padding: 5px;
    background-color: #2174ff;
    justify-content: center;
    align-items: center;
}


.toast-body .product_name .product_list p {
    font-size: 18px;
}

.toast-body .product_name .product_list {
    margin: 10px 0;
    color:#000;
}

.toast-body .parent-coast .arrow .arrow-inner i.cst-second {
   top: 88%;
   animation: mymove 8s infinite;
   animation-fill-mode: forwards;
}

@keyframes mymove {
  0% {top: -7px;}

  25%{
    top:  88%;
  }

  50% {top:  48%;}

  100%{top:-7px}
}


.toast-body ul li i.fa-solid.fa-scroll {
    color: #2174ff;
}

.toast-body ul li.no_announce {
    font-weight: 400;
}

.toast-body ul li.no_announce i.fa-solid.fa-circle-exclamation {
    color: #e55959;
}


############# second section #######




@media(max-width:1680px){
.box {
    flex-direction: column;
}
}




@media(max-width:1479px){
.box .right-col {
    padding: 2px !important;
}
    .box .right-col p{
        font-size: 16px;
    }


    .box .right-col{
        margin: 20px 0;
}

footer#cst-footer .details_parent p {
    text-align: center;
}

}


@media(max-width:1280px){
 .toast-body .product_name .product_list {
    flex-direction: column;
    border-bottom: 1px solid #e5e0e0;
}

.toast-body .product_name .product_list p {
    font-size: 12px;
}

.toast-body .product_name .product_list img {
    width: auto !important;
    margin: 0 !important;
}

}


@media(max-width:1250px){
.box .left-col{
      left: 46px;
}
      .toast-header>strong {
    font-size: 16px;
}
}


@media(max-width:1151px){
.col-lg-3 {
    flex: 0 0 49%;
    max-width: 81%;
    margin: 20px 0;
}
.box .left-col {
    position: absolute;
    top: -53px;
    left: -26px;
    width: 100px;
}

}


@media (max-width:991px){
.toast-body .product_name .product_list{
          flex-direction: row;
}
.toast .parent-coast .arrow {
    width: 1% !important;
}



.toast-body .product_name .product_list img {
    margin: 0px 10px 0px 0px !important;
}

}


@media(max-width:514px){
.cst-row {
    flex-direction: column;
}

.toast-body .product_name .product_list p{
 font-size:14px;
}

.toast-body ul li p {
    font-size: 14px;
}

.col-lg-3 {
    flex: auto;
    max-width: 100%;
    margin: 20px 0;
}
}



@media(max-width:375px){
  .toast-body .product_name .product_list{
    flex-direction:column !important;
  }
}


</style>
<section class="first-header-section">
  <div class="cst-row col-lg-12 row p-0">
    <div class="col-lg-3">
     <div class="box1 box d-flex align-items-center ">
       <div class="left-col"><i class="fa-solid fa-graduation-cap"></i></div>
       <div class="right-col p-2">
         <span>0</span>
         <p>Course IN-progress</p>
       </div>
     </div>
    </div>
    <div class="col-lg-3">
      <div class="box2 box d-flex align-items-center ">
       <div class="left-col"><i class="fa-solid fa-trophy"></i></div>
       <div class="right-col p-2">
         <span>1</span>
         <p>Course Completed</p>
       </div>
     </div>
    </div>
    <div class="col-lg-3">
      <div class="box3 box d-flex align-items-center ">
       <div class="left-col"><i class="fa-regular fa-newspaper"></i></div>
       <div class="right-col p-2">
         <span>1</span>
         <p>Course Certificate</p>
       </div>
     </div>
    </div>
    <div class="col-lg-3">
      <div class="box4 box d-flex align-items-center ">
       <div class="left-col"><i class="fa-solid fa-cart-shopping"></i></div>
       <div class="right-col p-2">
         <span>0</span>
         <p>Total Order</p>
       </div>
     </div>
    </div>
  </div>
</section>

 <section class="my-5 second-section">
        <div class="col-lg-12 row p-0">
            <div class="col-lg-8 my-2">
                <div class="notificatin-box">
                    <div class="toast " role="alert" aria-live="assertive" aria-atomic="true"
                         style="opacity:1; background-color:transparent; max-width:100%; border-radius:10px">
                        <div class="circle-bell"><i class="fa-solid fa-bell mx-2"></i></div>
                        <div class="toast-header p-2" style="color:#2174ff; font-size:20px">
                            <strong class="fs-3 me-auto">Notification</strong>
                        </div>
                        <div class="toast-body">
                            <ul>
                                <li><i class="fa-solid fa-envelope"></i><p class="px-2">Your profile picture has been updated</p></li>
                                <li><i class="fa-solid fa-check-double"></i><p class="px-2">Download the Certificates</p></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 my-2">
                <div class="toast " role="alert" aria-live="assertive" aria-atomic="true"
                     style="opacity:1; background-color:transparent; max-width:100%; border-radius:10px">
                    <div class="circle-bell recent-purchase"><i class="fa-solid fa-tags mx-2"></i></div>
                    <div class="toast-header p-2" style="color:#2174ff; font-size:20px">
                        <strong class="fs-3 me-auto">Recent Purchase</strong>
                    </div>
                    <div class="toast-body">
                       <div class="parent-coast d-flex">
                         <div class="arrow position-relative" style="width:10%; margin: 0 20px 0 0px;">
                            <div class="arrow-inner"><i class="fa-solid fa-cart-shopping"></i><i class="fa-solid fa-cart-shopping cst-second"></i></div>
                         </div>
                        <div class="product_name">
                                    <div class="product_list d-flex">
                                        <img class="mx-2"
                                            src="http://34.229.51.34/pluginfile.php/22/course/overviewfiles/Handling-Heavy-Materials.png"
                                            style="width:50px; height:50px; border-radius:5px; object-fit:cover;" />
                                        <div class="d-flex flex-column">
                                            <p class="m-0">1-Hour Handling Heavy Materials and Proper Lifting Techniques SST-B105</p>
                                            <small class="text-secondary">2022-06-23</small>
                                        </div>
                                    </div>
                                    <div class="product_list d-flex">
                                        <img class="mx-2"
                                            src="http://34.229.51.34/pluginfile.php/22/course/overviewfiles/Handling-Heavy-Materials.png"
                                            style="width:50px; height:50px; border-radius:5px; object-fit:cover;" />
                                        <div class="d-flex flex-column">
                                            <p class="m-0">Test 2</p>
                                            <small class="text-secondary">2022-06-23</small>
                                        </div>
                                    </div>
                                    <div class="product_list d-flex">
                                        <img class="mx-2"
                                            src="http://34.229.51.34/pluginfile.php/22/course/overviewfiles/Handling-Heavy-Materials.png"
                                            style="width:50px; height:50px; border-radius:5px; object-fit:cover;" />
                                        <div class="d-flex flex-column">
                                            <p class="m-0">Test 3</p>
                                            <small class="text-secondary">2022-06-23</small>
                                        </div>
                                    </div>
                                    <div class="product_list d-flex">
                                        <img class="mx-2"
                                            src="http://34.229.51.34/pluginfile.php/22/course/overviewfiles/Handling-Heavy-Materials.png"
                                            style="width:50px; height:50px; border-radius:5px; object-fit:cover;" />
                                        <div class="d-flex flex-column">
                                            <p class="m-0">Test 4</p>
                                            <small class="text-secondary">2022-06-23</small>
                                        </div>
                                    </div>
                                    <div class="product_list d-flex">
                                        <img class="mx-2"
                                            src="http://34.229.51.34/pluginfile.php/22/course/overviewfiles/Handling-Heavy-Materials.png"
                                            style="width:50px; height:50px; border-radius:5px; object-fit:cover;" />
                                        <div class="d-flex flex-column">
                                            <p class="m-0">Test 5</p>
                                            <small class="text-secondary">2022-06-23</small>
                                        </div>
                                    </div>
                         </div>
                       </div> 
                    </div>
                </div>
            </div>
        </div>
  </section>


  <section class="my-5 third-section">
        <div class="col-lg-12 row p-0">
            <div class="col-lg-8 my-2">
                <div class="announcements-box">
                    <div class="toast " role="alert" aria-live="assertive" aria-atomic="true"
                         style="opacity:1; background-color:transparent; max-width:100%; border-radius:10px">
                         <div class="circle-bell"><i class="fa-solid fa-bullhorn mx-2"></i></div>
                        <div class="toast-header p-2" style="color:#2174ff; font-size:20px">
                            <strong class="fs-3 me-auto">Announcements</strong>
                        </div>
                        <div class="toast-body">
                            <ul>
                                <li><i class="fa-solid fa-scroll"></i><p class="px-2">Your profile picture has been updated</p></li>
                                <li><i class="fa-solid fa-scroll"></i><p class="px-2">Download the Certificates</p></li>
                                <li class="no_announce"><i class="fa-solid fa-circle-exclamation"></i><p class="px-2">No New Announcements</p></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 my-2">
                <div class="toast " role="alert" aria-live="assertive" aria-atomic="true"
                     style="opacity:1; background-color:transparent; max-width:100%; border-radius:10px">
                    <div class="circle-bell"><i class="fa-solid fa-calendar mx-2"></i></div>
                    <div class="toast-header p-2" style="color:#2174ff; font-size:20px">
                        <strong class="fs-3 me-auto">Upcoming Events</strong>
                    </div>
                    <div class="toast-body">
                        <div class="product_name">
                            <div class="product_list">
                                <p class="m-0">Test course: 2</p>
                                <div class="d-flex justify-content-between py-2">
                                    <small class="text-primary px-2">Start Date:2024-06-23</small>
                                    <small class="text-primary px-2">End Date:2024-12-23</small>
                                </div>
                            </div>
                            <div class="product_list">
                                <p class="m-0">Test course: 3</p>
                                <div class="d-flex justify-content-between py-2">
                                    <small class="text-primary px-2">Start Date:2024-06-23</small>
                                    <small class="text-primary px-2">End Date:2024-12-23</small>
                                </div>
                            </div>
                            <div class="product_list">
                                <p class="m-0">1-Hour Handling Heavy Materials and Proper Lifting Techniques SST-B105</p>
                                <div class="d-flex justify-content-between py-2">
                                    <small class="text-primary px-2">Start Date:2024-06-23</small>
                                    <small class="text-primary px-2">End Date:2024-12-23</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

';
echo $OUTPUT->footer();
