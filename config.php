<?php  // Moodle configuration file
unset($CFG);
global $CFG;
$CFG = new stdClass();
ini_set('session.cookie_domain', '.learnpeek.com');
$CFG->dbtype    = 'mysqli';
$CFG->dblibrary = 'native';
$CFG->dbhost    = 'localhost';
$CFG->dbname    = 'learnpeek';
$CFG->dbuser    = 'root';
$CFG->dbpass    = 'P!ssw0rd$131';
$CFG->prefix    = 'mdl_';
$CFG->dboptions = array (
  'dbpersist' => 0,
  'dbport' => '',
  'dbsocket' => '',
  'dbcollation' => 'utf8mb4_0900_ai_ci',
);
$CFG->dataroot  = '/var/www/learn-moodledata';
$CFG->cachedirect = '/var/www/cachedata';

//---------- set different URLs per tenant-----//

// $tenant_urls = [
//     'pdhstar.learnpeek.com' => 'https://pdhstar.learnpeek.com',
//     'sandbox.learnpeek.com' => 'https://sandbox.learnpeek.com',
//     // Add more tenants as needed
// ];

require_once(__DIR__ . '/blocks/custom_mycourse/tenant_host.php');
// Get the current domain/subdomain
$current_host = $_SERVER['HTTP_HOST'];
if($current_host != 'lms.learnpeek.com'){
	if (array_key_exists($current_host, $tenant_urls)) {
	    $CFG->wwwroot = $tenant_urls[$current_host];
	    $CFG->cachedir = "/var/www/cachedata/cache/{$current_host}";
	    $CFG->localcachedir = "/var/www/cachedata/localcache/{$current_host}";
	} else {
	    $CFG->cachedir = "/var/www/cachedata/cache/lms.learnpeek.com";
	    $CFG->localcachedir = "/var/www/cachedata/localcache/lms.learnpeek.com";
	    $CFG->wwwroot = 'https://lms.learnpeek.com'; // Set to a default or main URL
	// $CFG->sslproxy = true;
	}
} else {
    $CFG->cachedir = "/var/www/cachedata/cache/lms.learnpeek.com";
    $CFG->localcachedir = "/var/www/cachedata/localcache/lms.learnpeek.com";
    $CFG->wwwroot = 'https://lms.learnpeek.com'; // Set to a default or main URL
	// $CFG->sslproxy = true;
}
if($current_host == 'lms.learnpeek.com'){
	// $CFG->sslproxy = true;

}
$CFG->samlssoroot = 'https://sso.learnshare.com/485/Shibboleth.sso/Login';
// echo "https://{$current_host}";
//----------------- END -----------------------//


// $CFG->wwwroot   = 'https://lms.learnpeek.com';
$CFG->admin     = 'admin';
$CFG->noemailever = true;
$CFG->local_adminer_secret = 'le@rnpeek#&&Pdhst@r!354';

$CFG->directorypermissions = 0777;
// $CFG->usepaypalsandbox = 1;
$CFG->maxfileuploadprofile = 5*1024*1024;

// @error_reporting(E_ALL | E_STRICT); // NOT FOR PRODUCTION SERVERS!
// @ini_set('display_errors', '1');    // NOT FOR PRODUCTION SERVERS!
// $CFG->debug = (E_ALL | E_STRICT);   // === DEBUG_DEVELOPER - NOT FOR PRODUCTION SERVERS!
// $CFG->debugdisplay = 1;             // NOT FOR PRODUCTION SERVERS!

require_once(__DIR__ . '/lib/setup.php');