<?php
echo "
<link rel='stylesheet' href='css/coursesstyle.css'>
<style>
.my-nav-list-child {
    padding-left: 20px;
}
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
    margin-top: 20px;
}
.cst_sidebefore_login ul li {
    list-style: none;
    margin: 0;
    line-height: 1.2;
    padding: 20px 5px;;
}
.cst_sidebefore_login ul li a {
    text-decoration: none;
    color: #343a40 !important;
    padding: .5rem;
    text-transform: uppercase;
    letter-spacing: 0.9;
    font-size: 12px;
    text-overflow: ellipsis;
    text-wrap-mode: nowrap;
}
.cst_sidebefore_login ul li.active>a{
    background-color: #0f47ad;
    color: #fff !important;
    margin-bottom: .25rem;
    padding:10px;
    border-radius: .5rem;
    width: 100%;
    display:block;
    margin-top: 4px;
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

.cst_sidebefore_login ul li.active {
    border-bottom: 0px solid #ddd;
}

.cst_sidebefore_login ul li ul.my-nav-list-child {
    border-left: 2px solid #ddd;
    margin: ;
    margin-left: 10px;
}

ul.my-nav-list .child-active > a {
    font-weight: bold;
    color: #0f47ad !important;
    font-size: 14px;
    transition: all .3s ease-in-out;
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

function has_active_child($tree, $categoryid, $activetag) {
    if (empty($tree[$categoryid])) {
        return false;
    }

    foreach ($tree[$categoryid] as $child) {
        if ($child->id == $activetag) {
            return true;
        }

        if (has_active_child($tree, $child->id, $activetag)) {
            return true;
        }
    }

    return false;
}

function render_category_menu($tree, $parent = 0) {
    global $SESSION;

    if (empty($tree[$parent])) {
        return;
    }

    // UL class
    $ulclass = ($parent == 0) ? 'my-nav-list' : 'my-nav-list-child';
    echo '<ul class="' . $ulclass . '">';

    foreach ($tree[$parent] as $category) {

        $class = '';

        if (!empty($SESSION->shoptag)) {

            // Exact selected category
            if ($category->id == $SESSION->shoptag) {
                $class = 'active';

            // Parent of selected category
            } elseif (has_active_child($tree, $category->id, $SESSION->shoptag)) {
                $class = 'child-active';
            }
        }

        echo '<li class="' . $class . '">';
        echo '<a href="?tag=' . $category->id . '">' . format_string($category->name) . '</a>';

        // Render children
        if (!empty($tree[$category->id])) {
            render_category_menu($tree, $category->id);
        }

        echo '</li>';
    }

    echo '</ul>';
}


// function render_category_menu($tree, $parent = 0) {
//     global $SESSION;

//     if (empty($tree[$parent])) {
//         return;
//     }

//     // Top level vs child UL
//     $ulclass = ($parent == 0) ? 'my-nav-list' : 'my-nav-list-child';

//     echo '<ul class="' . $ulclass . '">';

//     foreach ($tree[$parent] as $category) {

//         $isactive = (!empty($SESSION->shoptag))
//             ? is_category_active($tree, $category->id, $SESSION->shoptag)
//             : false;

//         $classactive = $isactive ? 'active' : '';

//         echo '<li class="' . $classactive . '">';
//         echo '<a href="?tag=' . $category->id . '">' . format_string($category->name) . '</a>';

//         // Render children
//         if (!empty($tree[$category->id])) {
//             render_category_menu($tree, $category->id);
//         }

//         echo '</li>';
//     }

//     echo '</ul>';
// }
echo '
<div class="trigger"><i class="fa-solid fa-bars"></i></div>
<div class="cst_sidebefore_login">
    <div class="cst-close trigger">X</div>
    <div class="container-fluid p-0 m-0">';

        $categorytree = company_mapped_category_list($companyid);
        render_category_menu($categorytree);

echo '
    </div>
</div>';
// echo '
// <div class="trigger"><i class="fa-solid fa-bars"></i></div>
//  <div class="cst_sidebefore_login">
//   <div class="cst-close trigger">X</div>
//    <div class="conatainer-fluid">
//      <ul class="my-nav-list">';
//          $shoptags = company_mapped_category($companyid);
//          // echo "<pre>";
//          // print_r($shoptags);
//          // echo "<pre>";
//         if (count($shoptags)) {
//             // echo get_string('filter_by_tag', 'block_iomad_commerce');
//             foreach ($shoptags as $shoptag) {
//                 // $SESSION->shoptag = $_GET["tag"];
//                 if(!empty($SESSION->shoptag) && $SESSION->shoptag == $shoptag['id']){
//                     $classactive ="active";
//                 }else{
//                     $classactive ="";
//                 }


//                 echo "<li class='".$classactive."'><a href='?tag={$shoptag['id']}'>{$shoptag['name']}</a></li>";
//             }
//         }
//         // <li><a href="#">backupcoursecategoru</a></li>
//         //  <li><a href="#">SST Courses</a></li>
//         //  <li><a href="#">LDS category4025</a></li>
//         //  <li><a href="#">FLAGGER</a></li>
//         //  <li><a href="#">SUMO</a></li>
//      echo '
//      </ul>
//    </div>
//  </div>
// ';
echo '
 <script>
        document.addEventListener("DOMContentLoaded", function () {
            document.querySelectorAll(".trigger").forEach(function (triggerEl) {
            triggerEl.addEventListener("click", function () {
                document.body.classList.toggle("adjust");
            });
            });
            document.querySelectorAll(".cst-nav .nav-link").forEach(function (navLink) {
            navLink.addEventListener("click", function () {
                navLink.classList.add("active");
            });
            });
        });
    </script>
';