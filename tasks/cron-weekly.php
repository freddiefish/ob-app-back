<?php 
// TO DO script archives any entries older than 30 days
// mails the log every week

require $_SERVER['DOCUMENT_ROOT'] . '/functions.php';

$msg = file_get_contents('./log.txt');

$success = mail("frefeys@gmail.com","logs",$msg);

if (!$success) {
    $errorMessage = error_get_last()['message'];
    logThis($errorMessage);
} else {
    logThis("Mail with log send!"); 
}

?>