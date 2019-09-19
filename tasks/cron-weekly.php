<?php 
// TO DO script archives any entries older than 30 days
// mails the log every week

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../resources/config.php';
require_once LIBRARY_PATH . '/functions.php';

$logFile = sys_get_temp_dir() . '/log.txt';

if (file_exists($logFile)) {
    
    $msg = "Succes!";
    unlink($logFile); // delete the log

} else {
    $msg = "Failed!";
}

$msg .= "\n" . $logFile;

mail_this('Log file deleted?', $msg , '');
