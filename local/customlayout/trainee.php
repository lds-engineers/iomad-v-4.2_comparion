<?php
require_once('../../config.php');
global $DB, $CFG, $USER, $PAGE;
require_login();

$html = '
<div>
	<h6>Manager Name</h6><hr>
	<p>Create New Trainee</p>

	<form method="POST">
	  <label for="uname">Username*</label><br>
	  <input type="text" class="w-75" id="uname" name="uname"><br>
	  <label for="mail">Email address*</label><br>
	  <input type="text" class="w-75" id="mail" name="mail"><br>
	  <label for="fname">First name*</label><br>
	  <input type="text" class="w-75" id="fname" name="fname"><br>
	  <label for="lname">Last name*</label><br>
	  <input type="text" class="w-75" id="lname" name="lname"><br
	  <label for="pass">Password*</label><br>
	  <input type="password" class="w-75" id="pass" name="pass"><br>
	  <label for="cpass">Confirm password*</label><br>
	  <input type="password" class="w-75" id="cpass" name="cpass"><br><br>
	  <input type="submit" class="btn btn-warning w-75" value="Submit">
	</form> 
</div>
';


echo $OUTPUT->header();
echo $html;
echo $OUTPUT->footer();


?>