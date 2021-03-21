<?php
define("PROD" , false);
define('ROOT_DIR', realpath(__DIR__.'/..'));
define("API_BASE_DIR",      "https://ebesluit.antwerpen.be");
define("EMAIL_BESLUITVORMING", "besluitvorming.an@antwerpen.be");
define("EMAIL_ADMIN", "admin@gmail.com");

putenv('SUPPRESS_GCLOUD_CREDS_WARNING=true');

/*
    Creating constants for heavily used paths makes things a lot easier.
    ex. require_once(LIBRARY_PATH . "Paginator.php")
*/

defined("LIBRARY_PATH")
    or define("LIBRARY_PATH", realpath(dirname(__FILE__) . '/library'));
     
defined("TEMPLATES_PATH")
    or define("TEMPLATES_PATH", realpath(dirname(__FILE__) . '/templates'));

if (PROD) {
    require_once(__DIR__  . '/environments/environment.prod.php');
} else {
    require_once(__DIR__  . '/environments/environment.php');
}