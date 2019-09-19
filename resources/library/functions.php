<?php

require_once __DIR__ . '/../config.php';

use Sunra\PhpSimple\HtmlDomParser;
use Google\Cloud\Firestore\FirestoreClient;
use Google\Cloud\Firestore\DocumentReference;
use Sk\Geohash\Geohash;
use Google\Cloud\Core\GeoPoint;
use Google\Cloud\Core\Timestamp;
use PHPMailer\PHPMailer\PHPMailer;



function get_doclist($daysToScreen) {
    $startDate = new DateTime();
    $stopDate = new DateTime();

    // go 30 days back 
    if (!isset($daysToScreen) ) {
        $daysToScreen = 30;
    }
    $thirtyDaysAgo = new DateInterval('P' . $daysToScreen . 'D');
    $thirtyDaysAgo->invert =1; // make it negative
    $startDate->add($thirtyDaysAgo);

    //$data = do_curl('https://ebesluit.antwerpen.be/calendar/filter?year=' . $year . '&month=' . $month );
    //$data = do_curl('https://ebesluit.antwerpen.be/agenda/18.1122.4613.7270/view?' );
    // https://ebesluit.antwerpen.be/publication/19.0911.5329.2155/download?

    //store our scraped data
    $docList        = array();
    $updateDate     = $startDate;
    $timestamp      = $updateDate->getTimestamp();
    $year           = date("Y", $timestamp);
    $month          = date("n", $timestamp);
    $monthTrail0    = date("m", $timestamp);
    $updateDateDay  = date("j", $timestamp);

    // get json list of current month's meetings
    $dom_raw = do_curl(BASE_DIR . '/calendar/filter?year=' . $year . '&month=' . $monthTrail0);
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
                $dom_raw = do_curl(BASE_DIR . '/calendar/filter?year=' . $year . '&month=' . $monthTrail0);
                $dom_json = json_decode($dom_raw,true);
                logThis("Updated the DOM to month ' . $month . ' of year '. $year . ' in calendar view: "); 
            }
    
            // do stuf for all days of the month
            $iter = "$year$month$updateDateDay";
            // logThis('Current iter: ' . $iter);

            foreach($dom_json as  $obj){
                
                if (array_key_exists( $iter , $obj )) { // some dates are not present

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

                
            } 

            $updateDate->add(new DateInterval('P1D')); //update date
        }
    
    // var_dump($docList);
    logThis('Found ' . count($docList) . ' documents');

    return $docList;
}

function add_to_db($docList,$doGeoCoding) {
    // iterate trough doclist and if the doc is not in the db, extract document data, 
    // optionally enrich with geolocation data

    foreach($docList as $val) {

        // first check if document is in firestore (https://console.cloud.google.com/firestore/data?folder=&organizationId=&project=ob-app-backend)
        $isInDb =get_document('docId', '=',$val['id']); // returns bool
        
        if (!$isInDb) {
            // scrape if document published, enrich and insert into db
            if ( $val['published']) {
                list($fullTxt, $background, $finalDecision,$assDecisions, $amountsAtStake, $addenda) = extractFromPDF($val['id']); // returns fulltext, background, decision
                // insert and get doc ID (we need it for the location collection)
            } else { // not published
                $fullTxt        = 'Niet gepubliceerd';
                $background     = 'Niet gepubliceerd. <a href="mailto:' . EMAIL_BESLUITVORMING . '?subject="lezen%20besluiten&body=Goede%20dag,%0Aik%20wil%20een%20besluit%20lezen%20op%20pagina:%20https://ebesluit.antwerpen.be/agenda/' . $val['id'] . '/view%20De%20link%20werkt%20helaas%20niet.%20Hoe%20kan%20ik%20het%20lezen?">vraag via email volledige tekst</a>';
                $finalDecision  = 'Niet gepubliceerd';
                $assDecisions = array();
                $amountsAtStake = array();
                $addenda = array();
            }
                $data = [
                    'title' => $val['title'],
                    'offTitle' => $val['offTitle'],
                    'intID' => $val['intId'], 
                    'status' => $val['status'],
                    'background' => $background,
                    'date' => new Timestamp(new DateTime($val['eventDate'])),
                    'decision' => $finalDecision,
                    'docId' => $val['id'],
                    'fullText' => $val['title'] . ' ' . $fullTxt,
                    'groupId' => $val['groupId'],
                    'groupName' => $val['groupName'],
                    'published' => $val['published'],
                    'hasGeoData' => false,
                    'sortIndex1' => $val['sortIndex1'],
                    'assocDecision' => $assDecisions,
                    'amountsAtStake' => $amountsAtStake,
                    'addenda' => $addenda
                ];

                $ID = add_document('decisions',$data); // returns ID

                if ($doGeoCoding) {
                    //ENRICH WITH GEODATA
                    $stringLocations = array();
                    $txtExtractAddress = $val['title'] . ' ' . $fullTxt ;
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
                                
                                //update decision: indicate has Geo Data
                                update_document('decisions',$ID);
                            
                        }
                        
                    }
                }
                
        } // end !$isInDb
        
        
    } 
}

function do_curl($url) {

	$ch = curl_init();
    $timeout = 300; // https://curl.haxx.se/libcurl/c/CURLOPT_CONNECTTIMEOUT.html
    
	curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    //Disable CURLOPT_SSL_VERIFYHOST and CURLOPT_SSL_VERIFYPEER by
    //setting them to false.
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

	$data = curl_exec($ch);
    curl_close($ch);
    
	return $data;
}

function addDocsToList ($pathToScrape,$eventDate,$groupId, $groupName,$docList){ 
    
    // Create DOM from URL or file
    $dom = HtmlDomParser::str_get_html( do_curl( BASE_DIR . $pathToScrape ) );

    // get only the agenda html 
    $agenda_html = $dom->getElementById("agenda");

    // find published docs first
    foreach($agenda_html->find('a') as $e) {
        $docTitle = $e->innertext;
        $docUrl = $e->href;
        $display = explode('/', $docUrl);
        $docId = $display[2];

        $row= array();
        $row['eventDate'] = $eventDate;
        $row['groupId'] = $groupId;
        $row['groupName'] = $groupName;
        $row['offTitle'] = $docTitle;
        list($row['intId'], $row['title'],$row['status']) = getTitleElements($docTitle) ;
        $row['id'] = $docId;
        $row['published'] = true;
        
        
        $row['sortIndex1'] = new Timestamp(new DateTime($row['eventDate'])) . $docId; // to order the items in the infinite scroll view in the app
        array_push($docList, $row);
    }  

    // find not published docs 
    foreach($agenda_html->find('span.title-no-rights') as $e) {
        $docTitle = $e->innertext;
        $row['eventDate'] = $eventDate; // format as timestamp
        $row['groupId'] = $groupId;
        $row['groupName'] = $groupName;
        $row['offTitle'] = $docTitle;
        list($row['intId'], $row['title'],$row['status']) = getTitleElements($docTitle) ;
        $row['id'] = null;
        $row['published'] = false;

        // non published docs have no id, so create a random id
        $docId = RandomString(4) ;
        $row['sortIndex1'] = new Timestamp(new DateTime($row['eventDate'])) . $docId; // to order the items in the infinite scroll view in the app
        
        array_push($docList,  $row);
    }

    // prevent memory leaks
    $dom->clear();
    unset($dom);

    return $docList;
}

function getTitleElements($txt) {
    //process the official title, split off first part (e.g. 2016_MV_00157 - Mondelinge vraag van raa...)
    $pieces = explode(" - ", $txt);  
    
    $intId = trim($pieces[0]);

    //escape speciale letters in titel 
    $cleanTitle = str_replace( $pieces[0] . " - " , "" , $txt ); 
                        
    // get decision result
    $status = isolateResult($txt);
    // remove result from title to clean title
    $cleanTitle = str_ireplace( " - " . $status , "" , $cleanTitle ); //case insensitive replace
    
    return array($intId, $cleanTitle , $status);
}

function isolateResult($title) {
    $result = "onbekend"; //default doc status
    
    $pieces = explode(" - ", $title);
    $lastPieceTitle = trim ( strtolower( array_pop($pieces) ) ); //lowercase
    $result_array = array('goedkeuring','kennisneming','bekrachtiging','weigering','afwijzing','verdaagd','vaststelling','wijziging' ); //lowercase
    
    if ( in_array($lastPieceTitle,$result_array) ) {
        //put value into field result
        $result = $lastPieceTitle;
    }
    
    return $result;
}

function get_document($field,$op, $val)
{
    // Create the Cloud Firestore client
    $db = new FirestoreClient();

    $decicionsRef = $db->collection('decisions');
    $query = $decicionsRef->where($field, $op , $val);
    $documents = $query->documents();
    foreach ($documents as $document) {
        if ($document->exists()) {
            logThis('Document ' . $document->id()  . ' returned by query');
            return true;
        } else {
            logThis('Document ' . $document->id() . ' does not exist!' ); 
        return false;
        }
    }
}

function add_document($collection,$data) {
    // Create the Cloud Firestore client
    $db = new FirestoreClient();
    
    $addDoc = $db->collection($collection)->newDocument();
    $ID = $addDoc->id();
    logThis('Added ' . $collection . '>document with ID: ' . $ID);
    $addDoc->set($data);

    return $ID;
}  

function update_document($collection,$ID){

    $db = new FirestoreClient();
    
    $updateRef = $db->collection($collection)->document($ID);
    $updateRef->update([
        ['path' => 'hasGeoData', 'value' => true]
    ]);

    logThis('Updated hasGeoData field in ' . $collection . '>document with ID: ' . $ID);

}

/**
 * Parse pdf file, trim, return entities: fulltext, background, decision
 * example PDF: https://ebesluit.antwerpen.be/publication/19.0911.6621.028112-09-2019/download
 * @param string id
 * @return array 
 */

function extractFromPDF($id) {

    $text ='';
    $fullTxt='';
    $background='';
    $finalDecision ='';

    $dir = sys_get_temp_dir();
    var_dump($id);
    $URL = BASE_DIR . '/publication/' . $id .'/download';
    $fileName= '_besluit';
    $path = $dir . '/' . $fileName.'.pdf';
    $file__download= curl_init();

    curl_setopt($file__download, CURLOPT_URL, $URL);
    curl_setopt($file__download, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($file__download, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($file__download, CURLOPT_AUTOREFERER, true);
    $result= curl_exec($file__download);
    
    file_put_contents($path, $result); // write to /tmp

    $parser = new \Smalot\PdfParser\Parser();
    $pdf    = $parser->parseFile($path);

    $text = $pdf->getText();

    $isVerdaagd = false;
    if ( strpos($text,'Verdaagd') OR strpos($text,'VERDAAGD')) {
        $isVerdaagd = true;
        $finalDecision='Verdaagd';
    }

    // var_dump($text);

    if (!$isVerdaagd) {
        // we have someting to process
        //get out some templated elements 
        $stripTxt             = array("Grote Markt 1 - 2000 Antwerpen", "info@antwerpen.be");
        $cleanTxt            = str_replace($stripTxt,'',$text);

        $trimedFooterText   = $cleanTxt;
        $textStartAtBijlagen = $cleanTxt;
        if (strpos($cleanTxt ,'Bijlagen') ) {
            $arrayText = explode('Bijlagen', $cleanTxt );
            $trimedFooterText = $arrayText[0];
            $textStartAtBijlagen = $arrayText[1];
        }
        // var_dump($textStartAtBijlagen);
        // trim header
        $arrayText = explode('Aanleiding en context', $trimedFooterText);
        $trimmedText = $arrayText[1];
        $textEndAtAanlCont = $arrayText[0];

        // coupled decisions
        $assDecisions = array();
        $assDecisions = get_assoc_decisions($textEndAtAanlCont,$assDecisions);

        //get fulltext
        $fullTxt = $cleanTxt;

        //get background
        $breakstring = '';
        if (strpos($cleanTxt,'Juridische grond')) $breakstring = 'Juridische grond';
        if (strpos($cleanTxt,'Regelgeving: bevoegdheid')) $breakstring = 'Regelgeving: bevoegdheid';
        $arrayBGText = explode($breakstring, $cleanTxt);
        $background = $arrayBGText[0];

        // var_dump($arrayBGText[1]);
        // get decision
        $artikelPieces = explode('Artikel 1', $arrayBGText[1]);
        unset($artikelPieces[0]);
        foreach ($artikelPieces as $piece) {
            $position = preg_match("/^[A-Z]/", $piece);
            if ($position = 1) $finalDecision = 'Artikel 1 ' . $piece;
        }

        // financial stakes
        $amountsAtStake = array();
        $amountsAtStake = get_financials($fullTxt,$amountsAtStake);

        $addenda = array();
        $addenda = get_addenda($textStartAtBijlagen,$addenda);
    }
        
    // var_dump($textEndAtAanlCont);
    // var_dump($amountsAtStake);

    return array ($fullTxt, $background, $finalDecision, $assDecisions, $amountsAtStake, $addenda);
} 

function get_financials($text,$amounts){
    $amountsClean = array();
    $pieces = explode (' EUR', $text);
    $nrElements = count($pieces);
    //var_dump($pieces);

    $i =0;
    foreach($pieces as $piece){

        $words = explode(' ', $piece);
        $amount = array_pop($words);

        if (strpos($amount, ',') ) { // case "10890,67 EUR" round to 10890
            $parts = explode(',', $amount);
            $wholeNumber = $parts[0];
            $integer = intval(str_replace('.','',$wholeNumber));
        }
        
        if ($i < $nrElements-1 && $integer <> 0) array_push($amounts ,$integer);
        
        $i++;
    }

    // var_dump( $amounts );
    //remove duplicates
    $amountsClean = array_unique($amounts);
    
    var_dump( $amountsClean ); 
    return $amountsClean;
}

function get_assoc_decisions($text,$assocDecisions){
    
    $focus = 'Gekoppelde besluiten';

    if (strpos($text, $focus)) {
        $pieces = explode( $focus, $text);
        preg_match_all( "/([0-9]+\_[A-Z]+_[0-9]+)/", $pieces[1], $matches);
        $assocDecisions =  $matches[0];
    }
    // var_dump($assocDecisions);
    return $assocDecisions ;
}

function get_addenda($text,$addenda){
    
    /* var_dump($text);
    preg_match_all("/[0-9]+.\s([a-zA-Z+]+.[a-z]{2,5})\s*\n/", $text, $matches);
    // "^/[0-9]+.\s(\w+)$/
    $addenda = $matches[0];

    var_dump($addenda);
    return $addenda; */
    
}


function extractAddress($txt, $stringLocations) {
    
    $file           = LIBRARY_PATH . '/straatnamen.txt';
    $contents       = file_get_contents($file);
    $lines          = explode("\n", $contents); // this is your array of words
    
    foreach($lines as $line) {
        $elements   = explode(",", $line);
        $streetName = $elements[0];
        $streetZIP  = $elements[1];
        $match = false;
        
        if (strpos($txt, $streetName) ) {
            // we have a match, see if house number is available
            $match = true;
            $pieces     = explode($streetName, $txt);
            $pieces2    = explode(' ', $pieces[1]);
            $pieceNextToStreet = $pieces2[1];
            // var_dump($pieceNextToStreet);

            $returnNeedle = $streetName;
            $pattern = "/\d/";
            if ( preg_match( $pattern, $pieceNextToStreet) && is_numeric(substr($pieceNextToStreet, 0, 1)) ) { //validate if this piece contains al least one integer AND first char is numeric -> we have a streetNr (syntax can be 12a)!
                // cases "Oever 13-17" trim to "Oever 13"
                if (strpos($pieceNextToStreet,'-')){
                    logThis('Clean double house numbers: ' . $pieceNextToStreet);
                    $piecesNextToStreet = explode('-',$pieceNextToStreet);
                    $pieceNextToStreet = $piecesNextToStreet[0];
                }
                //cases "Oever 15." trim to "Oever 15"
                if (strpos($pieceNextToStreet, '.')) {
                    logThis('Clean . out of house number: ' . $pieceNextToStreet);
                    $pieceNextToStreet = trim($pieceNextToStreet,'.');
                }
                //cases "Oever 15." trim to "Oever 15"
                if (strpos($pieceNextToStreet, ',')) {
                    logThis('Clean , out of house number: ' . $pieceNextToStreet);
                    $pieceNextToStreet = trim($pieceNextToStreet,',');
                }
                //cases "Schutstraat 39/1" trim to "Schutstraat 39"
                if (strpos($pieceNextToStreet, '/')) {
                    logThis('Clean / out of house number: ' . $pieceNextToStreet);
                    $piecesNextToStreet = explode('/', $pieceNextToStreet);
                    $pieceNextToStreet = $piecesNextToStreet[0];
                }
                $returnNeedle .= ' ' . $pieceNextToStreet; //eg: Wolstraat 15a
            }
            $result = $returnNeedle . ', ' . $streetZIP ;
            logThis('Address extracted: ' . $result);
            array_push($stringLocations, $result);
        }    

        
    }
    return $stringLocations; 
}


function geoCode($stringLocations) {
    // returns array of lat, lng, geohash out of given array with address strings 
    $geoLocations = array();
    
    foreach($stringLocations as $stringLocation) {
        $searchString       = $stringLocation;
        $needleEncoded      = urlencode($searchString);
        $requestUrl         = 'http://loc.geopunt.be/geolocation/location?q=' . $needleEncoded; // docs: https://loc.geopunt.be/Help/Api/GET-v4-Location_q_latlon_xy_type_c
        
        $geoloc = json_decode( do_curl( $requestUrl ));
        var_dump($geoloc);
        $checkEmpty = $geoloc->LocationResult; // sometimes ["LocationResult"]=> array(0) { }  is returned
               
        if (!empty($checkEmpty)){
            
            $formattedAddress   = $geoloc->LocationResult[0]->FormattedAddress;
            $lat                = $geoloc->LocationResult[0]->Location->Lat_WGS84;
            $lng                = $geoloc->LocationResult[0]->Location->Lon_WGS84;

            $geoHash = new Geohash();
            $g = $geoHash->encode($lat, $lng, 8);

            $row['formattedAddress']    = $formattedAddress;
            $row['lat']                 = $lat;
            $row['lng']                 = $lng;
            $row['geohash']             = $g;
            logThis('Geopoint API has result. Encoded: ' . $row['formattedAddress']);
            array_push( $geoLocations, $row);

        } else {
        
            logThis('Geopoint API has NO result');
        }

        
    }

    return $geoLocations;
}

function unique_multidim_array($array, $key) {
    $temp_array = array();
    $i = 0;
    $key_array = array();
   
    foreach($array as $val) {
        if (!in_array($val[$key], $key_array)) {
            $key_array[$i] = $val[$key];
            $temp_array[$i] = $val;
        }
        $i++;
    }
    return $temp_array;
}


function logThis($data) {
    $msg = date("d-m-Y H:i:s") . ": " . $data ;
    
    $save_path =  ROOT_DIR . '/log.txt';

    if ($fp = @fopen($save_path, 'a')) {
        // open or create the file for writing and append info
        fputs($fp, "\n$msg"); // write the data in the opened file
        fclose($fp); // close the file
    }
    echo $msg . '<br>';
}


function mailScriptResult() {
    $subj = "Fred, your latest Log";

    // get a joke  “categories”:[“Programming”,“Miscellaneous”,“Dark”,“Any”]
    $jokeRes = json_decode ( do_curl('https://sv443.net/jokeapi/category/Programming') , true);

    if ($jokeRes['type'] == "single") {
        $msg = $jokeRes['joke'];
    } else {
        $msg= $jokeRes['setup'] . "\n\n" . $jokeRes['delivery'];
    } 

    $attFilePath = sys_get_temp_dir() . '/log.txt';
    if (file_exists($attFilePath)) {
        mail_this($subj, $msg, $attFilePath); // must be local system ref
    } else {
        logThis('Log file could not be found');
    }
    

    // unset();
}

function RandomString($num) 
{ 
  // Variable that store final string 
  $final_string = ""; 
  
  //Range of values used for generating string
  $range = "ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890"; 
  
  // Find the length of created string 
  $length = strlen($range); 
  
  // Loop to create random string 
  for ($i = 0; $i < $num; $i++) 
  { 
    // Generate a random index to pick 
    // characters 
    $index = rand(0, $length - 1); 
    
    // Concatenating the character 
    // in resultant string 
    $final_string.=$range[$index]; 
  } 
  
  // Return the random generated string 
  return $final_string; 
}

function mail_this($subj, $msg, $attFilePath) {

    $mail = new PHPMailer();

    ( PROD ? $debugMode = 1 : $debugMode = 2 );
    $mail->SMTPDebug = $debugMode;

    /* //setup mailtrap
    $mail->isSMTP();
    $mail->Host = 'smtp.mailtrap.io';
    $mail->SMTPAuth = true;
    $mail->Username = 'e62458779718b2'; //paste one generated by Mailtrap
    $mail->Password = 'aa625b7617c3db' ; //paste one generated by Mailtrap
    $mail->SMTPSecure = 'tls';
    $mail->Port = 2525; */

    //setup mailjet
    $mail->isSMTP();
    $mail->Host = 'in-v3.mailjet.com';
    $mail->SMTPAuth = true;
    $mail->Username = '08f0ffd5a702d5b663f39b69f213f40b'; 
    $mail->Password = '20b9614eed9a3fb48ce428ae25eba13c' ; 
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    //headers
    $mail->setFrom(EMAIL_ADMIN, 'Frederik Feys');
    $mail->addAddress(EMAIL_ADMIN, 'Admin Fred'); 
    if (!empty($attFilePath)) $mail->addAttachment($attFilePath);
    // $mail->addCC('cc1@example.com', 'Elena');
    // $mail->addBCC('bcc1@example.com', 'Alex');

    // mail
    $mail->isHTML(true);
    $mail->Subject = $subj;
    $mailContent = $msg;
    $mail->Body = $mailContent;

    // $mail->msgHTML(do_curl('contents.html'), __DIR__);

    if($mail->send()){
        return true;
    }else{
        echo 'Message could not be sent.';
        echo 'Mailer Error: ' . $mail->ErrorInfo;
        logThis('Mail send error: ' . $mail->ErrorInfo);
        // files must be in the same dir! $mail->addAttachment('path/to/invoice1.pdf', 'invoice1.pdf');
    }
}
