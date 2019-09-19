<?php 
// TO DO script archives any entries older than 30 days
// mails the log every week
require_once __DIR__ . '/../bootstrap.php';

$logFile = ROOT_DIR . '/log.txt';

if (file_exists($logFile)) {
    
    $msg = "Succes fully deleted!";
    unlink($logFile); // delete the log

} else {
    $msg = "Failed to delete!";
}

$msg .= "\n" . $logFile;

mail_this('Log file delete', $msg , '');
