<?php 
// TO DO script archives any entries older than 30 days
// mails the log every week
// Use the composer autoloader to load dependencies.
// Use the composer autoloader to load dependencies. On GC App Engine paths are different
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../functions.php';

$logFile = sys_get_temp_dir() . '/log.txt';

unlink($logFile); // delete the log
