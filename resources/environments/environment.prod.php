<?php

/*
    The important thing to realize is that the config file should be included in every
    page of your project, or at least any page you want access to these settings.
    This allows you to confidently use these settings throughout a project because
    if something changes such as your database credentials, or a path to a specific resource,
    you'll only need to update it here.
*/

putenv('GOOGLE_APPLICATION_CREDENTIALS='. __DIR__  . '/ob-app-5e6adab126e2.json');
 
$config = array(
    "name" => "Open Bestuur",
    "mode" => PROD,
    "urls" => array(
        "baseUrl" => "http:google.com"
    ),
    "paths" => array(
        "publications" => 'ob-app-db2b6.appspot.com', 
        "processed" => ROOT_DIR . "/resources/storage/processed/",
        "log" => ROOT_DIR  // gcloud has its own logger: https://console.cloud.google.com/logs/viewer?project=ob-app-dev-252415&minLogLevel=0&expandAll=false&timestamp=2019-09-18T11:48:04.181000000Z&customFacets=&limitCustomFacetWidth=true&dateRangeStart=2019-09-18T10:48:04.434Z&dateRangeEnd=2019-09-18T11:48:04.434Z&interval=PT1H&resource=gae_app&logName=projects%2Fob-app-dev-252415%2Flogs%2Fstderr&logName=projects%2Fob-app-dev-252415%2Flogs%2Fappengine.googleapis.com%252Frequest_log
        )
    );
 

/*
    Error reporting.
*/

error_reporting(E_ERROR | E_PARSE);

set_time_limit(0); // scripts run infinitely
ini_set('memory_limit', '-1'); // scripts gets meomory ad infinitely