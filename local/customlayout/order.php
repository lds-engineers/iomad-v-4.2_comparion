<?php
require_once('../../config.php');
global $DB, $CFG, $USER, $PAGE;
require_login();
$strmymoodle = get_string('orders', 'local_customlayout');
$PAGE->set_title($strmymoodle);
$PAGE->set_heading($strmymoodle);

$userid = optional_param('userid', $USER->id, PARAM_INT);
if(!is_siteadmin()){
	$userid = $USER->id;  // Owner of the page
}

$html = '

<style>
 .cst-table{
   overflow-x:auto;
 }
</style>


<div class="cst-table">
<table class="table table-striped">
	<thead>
		<tr>
			<th>Order</th>
			<th>Name</th>
			<th>Total</th>
			<th>Status</th>
			<th>Date</th>
			<th>Actions</th>
		</tr>
	</thead>

	<tbody>
		<tr>
			<td>
				<a href="">#37332</a>
			</td>
			<td>
				<span>Course 1</span>
			</td>
			<td>
				<span><span>$</span>49.00</span> for 1 item
			</td>
			<td>
				<span>Completed</span>
			</td>
			<td>
				<time>June 11, 2024</time>
			</td>
			<td>
				<a href="#">View</a>
			</td>
		</tr>
		<tr>
			<td>
				<a href="">#37332</a>
			</td>
			<td>
				<span>Course 1</span>
			</td>
			<td>
				<span><span>$</span>49.00</span> for 1 item
			</td>
			<td>
				<span>Completed</span>
			</td>
			<td>
				<time>June 11, 2024</time>
			</td>
			<td>
				<a href="#">View</a>
			</td>
		</tr>
		<tr>
			<td>
				<a href="">#37332</a>
			</td>
			<td>
				<span>Course 1</span>
			</td>
			<td>
				<span><span>$</span>49.00</span> for 1 item
			</td>
			<td>
				<span>Completed</span>
			</td>
			<td>
				<time>June 11, 2024</time>
			</td>
			<td>
				<a href="#">View</a>
			</td>
		</tr>
	</tbody>
</table>

</div>

';


echo $OUTPUT->header();
echo $html;
echo $OUTPUT->footer();

?>