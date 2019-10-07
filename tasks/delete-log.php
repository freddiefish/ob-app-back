<?php 
// TO DO script archives any entries older than 30 days
// mails the log every week
require_once __DIR__ . '/../bootstrap.php';
$util = new Util();

$logFile = ROOT_DIR . '/log.txt';

if (file_exists($logFile)) {
    
    $msg = "Succes fully deleted!";
    unlink($logFile); // delete the log

} else {
    $msg = "File not found. Failed to delete!";
}

$msg .= "\n" . $logFile;

$util->mailThis('Log file delete', $msg , '');
