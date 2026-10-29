<?php
global $CFG;
$dbhost = $CFG->dbhost;
$dbname = $CFG->dbname;
$dbuser = $CFG->dbuser;
$dbpass = $CFG->dbpass;
$con = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);
if ($con) {
	$getcompany  = "SELECT name,shortname,hostname FROM mdl_company WHERE hostname != '' AND hostname IS NOT NULL;";
	$companyhost = mysqli_query($con, $getcompany);
	if (mysqli_num_rows($companyhost) > 0) {
	    $companyhostlist = mysqli_fetch_all($companyhost, MYSQLI_ASSOC);
	    foreach ($companyhostlist as $hostlist) {
	    	 $tenant_urls[$hostlist['hostname']] = "https://{$hostlist['hostname']}";
	    }
	}
}
