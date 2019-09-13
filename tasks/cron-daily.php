<?php
/* This script scrapes https://ebesluit.antwerpen.be/calendar/show for data. 
// The data are optionally enriched with geolocation data.
// 
// TODO 
    Clean db: get out any locations without corresponding doc
    Extract 
        -"Gekoppelde besluiten" and index them
        -References of "Bijlagen"
        -financial data
    Geolocation: some boundary located streets occur in other postal codes too (eg Smetsstraat, 2100 , Smetsstraat, 2140), find relevant one
*/

$doGeoCoding = true;
$daysToScreen = 3;

// Use the composer autoloader to load dependencies. On GC App Engine paths are different
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../functions.php';

set_time_limit(0); // script runs infinitely
ini_set('memory_limit', '-1'); // script gets meomory ad infinitely

// mail admin on last script run
mailScriptResult();

logThis('*************************************************');
logthis( ( PROD ? 'Running production mode' : 'Running developer mode' ) );
logThis('Script started: ' . date('l jS \of F Y h:i:s A'));
logThis('Memory usage (Kb): ' . memory_get_peak_usage()/1000);


$docList = get_doclist($daysToScreen);

/* 
$stringData = serialize($docList);
file_put_contents('doclist.txt',$stringData); 

// read back in:
$string_data = file_get_contents("doclist.txt");
$docList = unserialize($string_data);  */

//var_dump($docList);
add_to_db($docList,$doGeoCoding);

logThis('Script ended: ' . date('l jS \of F Y h:i:s A'));
logThis('END');

// clean database (delete any location doc without a corresponding decision doc)
?>