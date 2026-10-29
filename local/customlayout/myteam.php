00<?php
require_once('../../config.php');
global $DB, $CFG, $USER, $PAGE;
require_login();

$userid = optional_param('userid', $USER->id, PARAM_INT);
if(!is_siteadmin()){
	$userid = $USER->id;  // Owner of the page
}

$strmymoodle = get_string('team_trainee_list', 'local_customlayout');
$PAGE->set_title($strmymoodle);
$PAGE->set_heading($strmymoodle);

$html = '
<article id="profile-content" class="lp-profile-content">
	<div id="profile-content-my-team">
		<a class="btn btn-primary" href="'.$CFG->wwwroot.'/local/customlayout/trainee.php">Create New Trainee</a>
	</div>
	<table class="table table-striped">
		<thead>
			<tr>
				<th></th>
				<th>Trainee Name</th>
				<th>Email</th>
				<th>Created Date</th>
				<th>Actions</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td>
					<div class="list-profimg">
						<a href="#">
						<img fetchpriority="high" decoding="async" alt="User Avatar" src="https://secure.gravatar.com/avatar/724b13208ffa605e13e4a3debfaa2f78?s=96&amp;d=mm&amp;r=g" height="50" width="50">					
						</a>
					</div>
				</td>
				<td>
					<span>
						<a href="#">sushil tribe one <span>(Me)</span></a>
					</span>
				</td>
				<td>
					<span>sushil.kumar.tribeadvertising1@gmail.com</span>
				</td>
				<td>
					<time>June 11, 2024</time>
				</td>
				<td>
					<span><a href="#" class="btn btn-primary">View Details</a></span>
				</td>
			</tr>
			<tr>
				<td>
					<div class="list-profimg">
						<a href="#">
						<img fetchpriority="high" decoding="async" alt="User Avatar" src="https://secure.gravatar.com/avatar/724b13208ffa605e13e4a3debfaa2f78?s=96&amp;d=mm&amp;r=g" height="50" width="50">							</a>
					</div>
				</td>
				<td>
					<span>
						<a href="#">sushil test2</a>
					</span>
				</td>
				<td>
					<span>sushil.kumar.tribeadvertising2@gmail.com</span>
				</td>
				<td>
					<time>June 11, 2024</time>
				</td>
				<td>
					<span><a href="#" class="btn btn-primary">View Details</a></span>
				</td>
			</tr>
		</tbody>
	</table>
</article>

';

echo $OUTPUT->header();
echo $html;
echo $OUTPUT->footer();



?>