<?php
require_once(dirname(__FILE__) . '/../config.php');
global $CFG, $DB;
require_once($CFG->dirroot. '/blocks/iomad_company_admin/lib.php');
require_once($CFG->dirroot. '/blocks/learnpeek_shop/locallib.php');
require_once($CFG->dirroot . '/courses/header.php');


// $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
// $current_host = $protocol . $_SERVER['HTTP_HOST'];

\block_iomad_commerce\helper::require_commerce_enabled();

$itemid         = required_param('itemid', PARAM_INT);
$licenseformempty = optional_param('licenseformempty', 0, PARAM_INT);

// Correct the navbar.
// Set the name for the page.
$linktext = get_string('course_shop_title', 'block_learnpeek_shop');
// Set the url.
$linkurl = new moodle_url('/courses');

// Page stuff.
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);
$PAGE->navbar->add($linktext, $linkurl);

// $companyid = iomad::get_my_companyid(context_system::instance());
$current_host = $_SERVER['HTTP_HOST'];
$getcompany = $DB->get_record_sql("SELECT * FROM {company} WHERE hostname LIKE ?", array($current_host));
$companyid = $getcompany->id;

// if ($item = $DB->get_record('course_shopsettings', ['id' => $itemid, 'enabled' => 1, 'companyid' => $companyid])) {
//     $PAGE->navbar->add($item->name);
// }
//"SELECT css.*, c.fullname, c.summary FROM mdl_course_shopsettings as css INNER JOIN mdl_course_shopsettings_courses as csc ON css.id = csc.itemid INNER JOIN mdl_course as c ON c.id = csc.courseid WHERE css.id = 21 AND css.companyid = 3 AND css.enabled = 1"

$item = $DB->get_record_sql('SELECT css.*, c.id as course_id , c.fullname, c.summary 
	FROM {course_shopsettings} as css 
	INNER JOIN {course_shopsettings_courses} as csc ON css.id = csc.itemid 
	INNER JOIN {course} as c ON c.id = csc.courseid 
	WHERE css.id = ? AND css.companyid = ? AND css.enabled = ?',array($itemid,$companyid,1));

if ($item) {
    $PAGE->navbar->add($item->name);
}
// require_once($CFG->dirroot . '/courses/sidebarnav.php');
echo $OUTPUT->header();

flush();

// \block_iomad_commerce\helper::show_basket_info();

if ($item) {
    $getcoursebundle = $DB->get_records_sql("SELECT c.*, csc.itemid FROM {course_shopsettings_courses} as csc INNER JOIN {course} as c ON c.id = csc.courseid WHERE csc.itemid =".$itemid);
	// echo "<pre>";
	// print_r($getcoursebundle);
 // //    print_r($item);
	// echo "</pre>"; 

    if(count($getcoursebundle) > 1){
        // product image
        $productimage = $DB->get_record_sql("SELECT * FROM {files} WHERE contextid= 1 AND component='productpage' AND itemid=? AND filesize > 0 AND filearea = ?", array($item->id,'product_image'));
        if ($productimage) {
            $productimageurl = $CFG->wwwroot.'/blocks/custom_mycourse/file.php?id='.$productimage->pathnamehash;
            $product_image =  '<img src="'.$productimageurl.'" alt="'.$item->name.'" class="courseimage">';
        }else{
            $product_image = $CFG->wwwroot.'/blocks/learnpeek_shop/pix/learning.jpeg';
            echo '<img src="'.$dumyimage.'" alt="'.$item->name.'" class="courseimage">';
        }

        // product description

        if ($item->long_description) {
            $productdesc =  $item->long_description;
        } else {
            $productdesc = $item->short_description;
        }
    }else{
       $product_image = course_image($item->course_id);
        $context1 = context_course::instance($item->course_id, MUST_EXIST);

        $productdesc = file_rewrite_pluginfile_urls($item->summary, "pluginfile.php", $context1->id, "course", "summary",null);
       // $productdesc = $item->summary;
    }


	echo '
	<link rel="stylesheet" href="css/coursesstyle.css">
	<div class="cst_column">
		<h3>' . format_string($item->name) . '</h3>
		<div class="cst_img_parent"> '.$product_image.'</div>';

	    $strbuynow = get_string('buynow', 'block_learnpeek_shop');
	    $strmoreinfo = get_string('moreinfo', 'block_learnpeek_shop');

      

	    if($productdesc){
		    echo '
		    <div class="cst_course_description">
		    '.$productdesc.'
	    	</div>';
	    }
        echo '
        <div class="p-2 cst_addition_course">
                <ul>';
            $course_ids = [];
            if(count($getcoursebundle) > 1){
                foreach ($getcoursebundle as $coursebundle) {
                    array_push($course_ids, $coursebundle->id);
                    echo '
                    <li>
                    <span><img src="'.$CFG->wwwroot.'/blocks/learnpeek_shop/pix/mycourse.png"/></span>
                        <h4><a href="#">'.$coursebundle->fullname.'</a></h4>
                    </li>';
                }

            }
    echo '
        </ul>
    </div>
    </div>';

echo '
<div class="table-responsive">
	<table class="generaltable" width="600px">
		<thead>
			<tr>
				<th class="header c0" style="text-align:left;" scope="col">Price options</th>
				<td class="header c1" style="text-align:center;"></td>
				<td class="header c2 lastcol" style="text-align:center;"></td>
			</tr>
		</thead>
		<tbody>
			<tr class="lastrow">
				<td class="cell c0" style="text-align:left;">Single purchase</td>
				<td class="cell c1" style="text-align:center;">'.$item->single_purchase_currency. number_format($item->single_purchase_price, 2).'</td>
				<td class="cell c2 lastcol" style="text-align:center;"><a href="' . $CFG->wwwroot . '/blocks/learnpeek_shop/buynow.php?itemid=' . $item->id . '" class="btn btn-primary">Buy now</a></td>

			</tr>
		</tbody>
	</table>
</div>';

// require_once($CFG->dirroot. '/blocks/learnpeek_shop/course-rating.php');


    // echo "<pre>";
    // print_r($CFG);
    // echo "</pre>";
    // var_dump(($item->allow_single_purchase || $item->allow_license_blocks));
    // var_dump((iomad::has_capability('block/iomad_commerce:buyitnow', $context) || iomad::has_capability('block/iomad_commerce:buyinbulk', $context)));
    // if (($item->allow_single_purchase || $item->allow_license_blocks) &&
    //     (iomad::has_capability('block/iomad_commerce:buyitnow', $context) || iomad::has_capability('block/iomad_commerce:buyinbulk', $context))) {
    //     $table = new html_table();
    //     $table->head = array (get_string('priceoptions', 'block_iomad_commerce'), "", "");
    //     $table->align = array ("left", "center", "center");
    //     $table->width = "600px";


    //     if ($item->allow_single_purchase && iomad::has_capability('block/iomad_commerce:buyitnow', $context)) {
    //         $buynowurl = new moodle_url($CFG->wwwroot . '/blocks/learnpeek_shop/buynow.php', ['itemid' => $item->id]);
    //         $table->data[] = [get_string('single_purchase', 'block_iomad_commerce'),
    //                           $item->single_purchase_currency . number_format($item->single_purchase_price, 2),
    //                           "<a href='" . $buynowurl->out() . "' class='btn btn-primary'>" .
    //                                get_string('buynow', 'block_iomad_commerce') .
    //                                "<a>"];
    //     }

    //     $form = '';

    //     if ($item->allow_license_blocks) {
    //         $priceblocks = $DB->get_records('course_shopblockprice', ['itemid' => $item->id], 'price_bracket_start');
    //         if (count($priceblocks)) {
    //             // var_dump(iomad::has_capability('block/iomad_commerce:buyinbulk', $context));
    //             if (iomad::has_capability('block/iomad_commerce:buyinbulk', $context)) {
    //                 foreach ($priceblocks as $priceblock) {
    //                     $table->data[] = array(get_string('licenseblock_n', 'block_iomad_commerce',
    //                                                        $priceblock->price_bracket_start),
    //                                             $priceblock->currency . ' ' . number_format($priceblock->price, 2),
    //                                             '');
    //                 }

    //             	//if (isloggedin() && iomad::is_company_admin()) {
    //                 $msg = '';
    //                 if ($licenseformempty) {
    //                     $msg = "<p class='error'>" . get_string('licenseformempty', 'block_learnpeek_shop') . "</p>";
    //                 }

    //                 $licenseformurl = new moodle_url($CFG->wwwroot . '/blocks/learnpeek_shop/buynow.php', ['itemid' => $itemid]);
    //                 $form = '<form action="' . $licenseformurl->out() .'" method="get">
    //                     <input type="hidden" name="itemid" value="' . $itemid . '" />
    //                     <input type="hidden" name="licenses" value="1" />
    //                     ' . $msg . '
    //                     <div style="float:left;display:inline-flex;">
    //                     <label for="id_nlicenses"> How many licenses? </label>
    //                     <input type="text" class="form-control" style="width: 195px;margin-left:20px;" name="nlicenses" id="id_nlicenses" value="" />
    //                     <input type="submit" class="btn btn-primary" style="margin-left:20px;" value="' . get_string('buynow', 'block_learnpeek_shop') . '" />
    //                     </div>
    //                 </form>';
    //             }
    //         }
    //     }

    //     if (!empty($table)) {
    //         echo "<a name='buynow'></a>";
    //         echo html_writer::table($table);
    //         echo $form;
    //         echo "<br>";
    //         echo html_writer::tag('a', get_string('back'), ['class' => 'btn btn-secondary', 'href' => $linkurl]);
    //     }
    // }

} else {
    echo "<p>" . get_string('courseunavailable', 'block_learnpeek_shop') . "</p>";
}


echo $OUTPUT->footer();