<?php
echo "
<link rel='stylesheet' href='css/coursesstyle.css'>
<style>
.cst_sidebefore_login {
    width: 250px;
    top: 91px;
    height: calc(100vh - 73px) !important;
    overflow-x: hidden;
    overflow-y: auto;
    position: fixed;
    background-color: #fff !important;
    z-index: 99;
    box-shadow: 0 .375rem 1.5rem 0 rgba(140, 152, 164, .13) !important;
}
.cst_sidebefore_login ul {
    padding: 0;
    margin-top: 60px;
}
.cst_sidebefore_login ul li {
    list-style: none;
    margin: 0;
    line-height: 1.2;
    padding: 20px 5px;
}
.cst_sidebefore_login ul li a{
    text-decoration: none;
    color: #343a40 !important;
    padding: .5rem;
    text-transform: uppercase;
    letter-spacing: 0.9;
    font-size: 14px;
}
.cst_sidebefore_login ul li.active a{
    background-color: #0f47ad;
    color: #fff !important;
    margin-bottom: .25rem;
    padding:10px;
    border-radius: .5rem;
    width: 100%;
    display:block;
    margin-top: 4px;
    white-space: nowrap;
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
    display: none;
    left: -11px;
    color: #fff;
    z-index: 99;
}
.cst-close {
    font-size: 20px;
    font-weight: bolder;
    color: #fff;
    position: absolute;
    right: 0px;
    display: none;
    background-color: black;
    top: -1px;
    width: 50px;
    text-align: center;
    cursor: pointer;
}
 body.adjust .cst_sidebefore_login {
    transform: translateX(0%) !important;
}
@media (max-width: 767px) {

    .cst_sidebefore_login {
        transform: translateX(-100%);
        transition: all .5s ease-in-out;
    }
    .trigger{
      display:block;
    }
}
</style>";
echo '
<div class="trigger"><i class="fa-solid fa-bars"></i></div>
 <div class="cst_sidebefore_login">
  <div class="cst-close trigger">X</div>
   <div class="conatainer-fluid">
     <ul class="my-nav-list">';
         $shoptags = company_mapped_category($companyid);
         // echo "<pre>";
         // print_r($shoptags);
         // echo "<pre>";
        if (count($shoptags)) {
            // echo get_string('filter_by_tag', 'block_iomad_commerce');
            foreach ($shoptags as $shoptag) {
                // $SESSION->shoptag = $_GET["tag"];
                if(!empty($SESSION->shoptag) && $SESSION->shoptag == $shoptag['id']){
                    $classactive ="active";
                }else{
                    $classactive ="";
                }


                echo "<li class='".$classactive."'><a href='?tag={$shoptag['id']}'>{$shoptag['name']}</a></li>";
            }
        }
        // <li><a href="#">backupcoursecategoru</a></li>
        //  <li><a href="#">SST Courses</a></li>
        //  <li><a href="#">LDS category4025</a></li>
        //  <li><a href="#">FLAGGER</a></li>
        //  <li><a href="#">SUMO</a></li>
     echo '
     </ul>
   </div>
 </div>
';
echo '
  <script>
     document.querySelector(".trigger").addEventListener("click", function() {
        document.body.classList.toggle("adjust");
    });

    document.addEventListener("DOMContentLoaded", function() {
    const navItems = document.querySelectorAll(".my-nav-list li"); // Select all <li> elements

    navItems.forEach(item => {
        item.addEventListener("click", function() {
            // Remove "active" from all items
            navItems.forEach(li => li.classList.remove("active"));

            // Add "active" to the clicked item
            this.classList.add("active");
        });
    });
});
  </script>
';