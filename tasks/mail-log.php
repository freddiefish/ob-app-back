<?php
require_once __DIR__ . '/../bootstrap.php';
$util = new Util();

$subj = "Fred, your log";
$attFilePath = ROOT_DIR . '/log.txt';

// get a joke  “categories”:[“Programming”,“Miscellaneous”,“Dark”,“Any”]
$jokeRes = json_decode ( do_curl('https://sv443.net/jokeapi/category/Programming') , true);

if ($jokeRes['type'] == "single") {
    $msg = $jokeRes['joke'];
} else {
    $msg= $jokeRes['setup'] . "\n\n" . $jokeRes['delivery'];
} 

if (file_exists($attFilePath)) {
    $util -> mailThis($subj, $msg, $attFilePath);
} else {
    $app -> log('Log file could not be found');
}