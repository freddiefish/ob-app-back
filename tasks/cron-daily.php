<?php
/* This script scrapes https://ebesluit.antwerpen.be/calendar/show for data. 
// The data are enriched with geolocation data.
// For every doc checks if it is already in the db. If not, it is inserted. 
// TODO 
    Clean db: get out any locations without corresponding doc
    Extract "Gekoppelde besluiten" and index them
    References of "Bijlagen"
    Sometimes a streetname occurs in different postal codes (eg Smetsstraat, 2100 , Smetsstraat, 2140), find relevant one
*/

define("BASE_DIR",      "https://ebesluit.antwerpen.be");
define("EMAIL_BESLUITVORMING", "besluitvorming.an@antwerpen.be");

// Use the composer autoloader to load dependencies.
require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
require $_SERVER['DOCUMENT_ROOT'] . '/functions.php';

set_time_limit(0); // script runs infinitely
ini_set('memory_limit', '-1'); // script gets meomory ad infinitely

// mail admin if script halted on the last run
if (mailWhenScriptHalted()) exit();

logThis('*************************************************');
logThis('Script started: ' . date('l jS \of F Y h:i:s A'));
logThis('Memory usage (Kb): ' . memory_get_peak_usage()/1000);

// scraping the DOM, so init 
use Sunra\PhpSimple\HtmlDomParser;
use Google\Cloud\Core\Timestamp;
use Google\Cloud\Core\GeoPoint;
use Google\Cloud\Firestore\FirestoreClient;
 
$startDate = new DateTime();
$stopDate = new DateTime();
// go 30 days back 
$thirtyDaysAgo = new DateInterval('P' . $daysToScreen . 'D');
$thirtyDaysAgo->invert =1; // make it negative
$startDate->add($thirtyDaysAgo);

//$data = get_data('https://ebesluit.antwerpen.be/calendar/filter?year=' . $year . '&month=' . $month );
//$data = get_data('https://ebesluit.antwerpen.be/agenda/18.1122.4613.7270/view?' );
//$data = get_data( 'https://ebesluit.antwerpen.be/publication/19.0802.4796.5619/detail?');

//store our scraped data
$docList        = array();
$updateDate     = $startDate;
$timestamp      = $updateDate->getTimestamp();
$year           = date("Y", $timestamp);
$month          = date("n", $timestamp);
$monthTrail0    = date("m", $timestamp);
$updateDateDay  = date("j", $timestamp);

// get json list of current month's meetings
$dom_raw = get_data(BASE_DIR . '/calendar/filter?year=' . $year . '&month=' . $monthTrail0);
$dom_json = json_decode($dom_raw,true);


    while( $updateDate < $stopDate) {

        $updateDateTimestamp    = $updateDate->getTimestamp();
        $updateDateFormatted    = date("d-m-Y",$updateDateTimestamp);
        logThis("Update for date: " . $updateDateFormatted ); 

        $updateDateYear         = date("Y", $updateDateTimestamp);
        $updateDateMonth        = date("n", $updateDateTimestamp);
        $updateDateMonthTrail0  = date("m", $updateDateTimestamp);
        $updateDateDay          = date("j", $updateDateTimestamp);

        if ($year <> $updateDateYear OR $monthTrail0 <> $updateDateMonthTrail0) {
            // update values
            $year           = $updateDateYear;
            $month          = $updateDateMonth;
            $monthTrail0    = $updateDateMonthTrail0;
            
            // update calender view
            $dom_raw = get_data(BASE_DIR . '/calendar/filter?year=' . $year . '&month=' . $monthTrail0);
            $dom_json = json_decode($dom_raw,true);
            logThis("Updated the DOM to month ' . $month . ' of year '. $year . ' in calendar view: "); 
        }
   
        // do stuf for all days of the month
        $iter = "$year$month$updateDateDay";
        // logThis('Current iter: ' . $iter);

        foreach($dom_json as  $obj){
            foreach($obj[$iter] as $val) { 
                // logThis("objectId ". $val['objectId']);
                // get the agenda items
                $pathToScrape   = $val['url'];
                $eventDate      = $val['startDateString'];
                $groupId        = $val['groupId'];
                $groupName      = $val['className'];
                $docList = addDocsToList($pathToScrape,$eventDate,$groupId, $groupName,$docList);
            }
        } 

        $updateDate->add(new DateInterval('P1D')); //update date
    }
 
// var_dump($docList);
logThis('Found ' . count($docList) . ' documents');

// iterate trough doclist and if the doc is not in the db, extract document data, 
// enrich with geolocation data
foreach($docList as $val) {

    // logThis('Memory usage (Kb): ' . memory_get_peak_usage()/1000);

    // first check if document is in firestore (https://console.cloud.google.com/firestore/data?folder=&organizationId=&project=ob-app-backend)
    $isInDb =get_document('docId', '=',$val['id']); // returns bool
    
    if (!$isInDb) {
        // scrape if document published, enrich and insert into db
        if ( $val['published']) {

            list($fullTxt, $background, $finalDecision) = extractFromPDF($val['id']); // returns fulltext, background, decision
            // insert and get doc ID (we need it for the location collection)
        } else { // not published
            $fullTxt        = 'Niet gepubliceerd';
            $background     = 'Niet gepubliceerd. <a href="mailto:' . EMAIL_BESLUITVORMING . '?subject="lezen%20besluiten&body=Goede%20dag,%0Aik%20wil%20een%20besluit%20lezen%20op%20pagina:%20https://ebesluit.antwerpen.be/agenda/' . $val['id'] . '/view%20De%20link%20werkt%20helaas%20niet.%20Hoe%20kan%20ik%20het%20lezen?">vraag via email volledige tekst</a>';
            $finalDecision  = 'Niet gepubliceerd';
        }
            $data = [
                'title' => $val['title'],
                'background' => $background,
                'date' => new Timestamp(new DateTime($val['eventDate'])),
                'decision' => $finalDecision,
                'docId' => $val['id'],
                'fullText' => $fullTxt,
                'groupId' => $val['groupId'],
                'groupName' => $val['groupName'],
                'published' => $val['published']
            ];

            $ID = add_document('decisions',$data); // returns ID

            //ENRICH WITH GEODATA
            $stringLocations = array();
            $txtExtractAddress = $val['title'] . ' ' . $val['fullText'];
            $stringLocations = extractAddress($txtExtractAddress, $stringLocations);
            
            // var_dump($stringLocations);

            if (count($stringLocations) > 0) { // lets do some geocoding and add in db
                
                $geoLocations = geoCode($stringLocations) ;

                // remove possible duplicates 
                $geoLocations = unique_multidim_array($geoLocations, 'formattedAddress') ;

                $db_temp = new FirestoreClient();
                $decisionRef = $db_temp->document('decisions/' . $ID);
                
                // var_dump($geoLocations);

                foreach ($geoLocations as $location) {

                    $dataLocation = [
                        'decisionRef' => $decisionRef,
                        'formattedAddress' => $location['formattedAddress'],
                        'point' => [
                            'geohash' => $location['geohash'],
                            'geopoint' => new GeoPoint($location['lat'],$location['lng'])
                            ]
                        ];

                        add_document('locations',$dataLocation);
                       
                }
                
            }
            
    } // end !$isInDb
    
    
} 

logThis('Script ended: ' . date('l jS \of F Y h:i:s A'));
logThis('END');

// clean database (delete any location doc without a corresponding decision doc)
?>