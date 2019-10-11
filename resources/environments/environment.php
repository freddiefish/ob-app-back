<?php

/*
    The important thing to realize is that the config file should be included in every
    page of your project, or at least any page you want access to these settings.
    This allows you to confidently use these settings throughout a project because
    if something changes such as your database credentials, or a path to a specific resource,
    you'll only need to update it here.
*/

putenv('GOOGLE_APPLICATION_CREDENTIALS='. __DIR__  . '/ob-app-dev-40dcf7752b62.json');
 
$config = array(
    "name" => "Open Bestuur",
    "mode" => PROD,
    "urls" => array(
        "baseUrl" => "http://127.0.0.1:8080"
    ),
    "paths" => array(
        "storage" => "ob-app-dev-252415.appspot.com"
        )
    );

/*
    Error reporting.
*/

error_reporting(E_ALL);

set_time_limit(0); // scripts run infinitely
ini_set('memory_limit', '-1'); // scripts gets meomory ad infinitely
