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

require_once __DIR__ . '/../bootstrap.php';

$doGeoCoding = true;
$daysToScreen = 1;

// mail admin on last script run
mailLog();

logThis('*************************************************');
logthis( ( PROD ? 'Running production mode' : 'Running developer mode' ) );
logThis('Script started: ' . date('l jS \of F Y h:i:s A'));
logThis('Memory usage (Kb): ' . memory_get_peak_usage()/1000);

$docList = get_doclist($daysToScreen);


/* $stringData = serialize($docList);
file_put_contents('doclist-dev.txt',$stringData); 
exit;

// read back in: 
$string_data = file_get_contents("doclist.txt");
$docList = unserialize($string_data); */ 

//var_dump($docList);
add_to_db($docList,$doGeoCoding);

logThis('Script ended: ' . date('l jS \of F Y h:i:s A'));
logThis('END');
