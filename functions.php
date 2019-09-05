<?php
// setting environment variables
putenv('GOOGLE_APPLICATION_CREDENTIALS='. $_SERVER['DOCUMENT_ROOT'] . '/tasks/ob-app-5e6adab126e2.json');
putenv('SUPPRESS_GCLOUD_CREDS_WARNING=true');

define("LOG_PATH" , $_SERVER['DOCUMENT_ROOT'] . "/tasks/log.txt");

// show all errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

// TODO function extractfromPDF, if word "Besluit" occurs more than once, get only the relevant section
// Use the composer autoloader to load dependencies.
require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

use Sunra\PhpSimple\HtmlDomParser;
use Google\Cloud\Firestore\FirestoreClient;
use Google\Cloud\Firestore\DocumentReference;
use Sk\Geohash\Geohash;
use Google\Cloud\Core\GeoPoint;

function get_data($url) {

	$ch = curl_init();
    $timeout = 5;
    
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
    $dom = HtmlDomParser::file_get_html( BASE_DIR . $pathToScrape );

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
        $row['id'] = $docId;
        $row['published'] = true;
        $row['title'] = cleanTitle( $docTitle) ;
        array_push($docList, $row);
    }  

    // find not published docs 
    foreach($agenda_html->find('span.title-no-rights') as $e) {
        $docTitle = $e->innertext;
        $row['eventDate'] = $eventDate; // format as timestamp
        $row['groupId'] = $groupId;
        $row['groupName'] = $groupName;
        $row['title'] = cleanTitle($docTitle);
        $row['id'] = null;
        $row['published'] = false;
        array_push($docList,  $row);
    }

    return $docList;
}

function cleanTitle($txt) {
    //cleanup the official title, split off first part (e.g. 2016_MV_00157 - Mondelinge vraag van raa...)
    $pieces = explode(" - ", $txt);  
    //escape speciale letters in titel 
    $cleanTitle = str_replace( $pieces[0] . " - " , "" , $txt ); 
                        
    // get decision result
    // $result = isolateResult($txt);
    // remove result from title to clean title
    // $cleanTitle = str_ireplace( " - " . $result , "" , $cleanTitle ); //case insensitive replace
    
    return $cleanTitle;
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

function get_document($projectId,$field,$op, $val)
{
    global $credentials;
    
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

function add_document($projectId,$collection,$data) {
    // Create the Cloud Firestore client
    /* $db = new FirestoreClient([
        'projectId' => $projectId,
    ]); */

    $db = new FirestoreClient();
    
    $addDoc = $db->collection($collection)->newDocument();
    $ID = $addDoc->id();
    logThis('Added ' . $collection . '>document with ID: ' . $ID);
    $addDoc->set($data);

    return $ID;
}  


function extractFromPDF($id) {
    // Parse pdf file, trim, return entities: fulltext, background, decision
    $text ='';

    $parser = new \Smalot\PdfParser\Parser();
    $pdf    = $parser->parseFile(BASE_DIR . '/publication/' . $id .'/download');

    $text = $pdf->getText();

    // trim footer delete anything below bijlagen section
    $arrayText = explode('Bijlagen', $text);
    $trimedFooterText = $arrayText[0];

    // trim header
    $arrayText = explode('Aanleiding en context', $trimedFooterText);
    $trimmedText = $arrayText[1];

    //get out some templated elements
    $stripTxt       = "Grote Markt 1 - 2000 Antwerpen";
    $stippedText1   = str_replace($stripTxt,'',$trimmedText);
    $stripTxt       = "info@antwerpen.be";
    $clean1Txt      = str_replace($stripTxt,'',$stippedText1);

    //get fulltext
    $fullTxt = $clean1Txt;

    //get background
    $arrayBGText = explode('Juridische grond', $clean1Txt);
    $background = $arrayBGText[0];

    // get decision
    $arrayBGText = explode('Artikel 1', $clean1Txt);
    $finalDecision = 'Artikel 1' . $arrayBGText[1];

    return array ($fullTxt, $background, $finalDecision);
} 

function extractAddress($txt, $stringLocations) {

    $file           = "straatnamen.txt";
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
        $ch                 = curl_init();
        curl_setopt($ch, CURLOPT_URL, $requestUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        //Disable CURLOPT_SSL_VERIFYHOST and CURLOPT_SSL_VERIFYPEER by
        //setting them to false.
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $output             = curl_exec($ch);
        curl_close($ch);
       
        $geoloc = json_decode($output);
       
        $checkEmpty = $geoloc->LocationResult[0]; // sometimes ["LocationResult"]=> array(0) { }  is returned
               
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
    $save_path = LOG_PATH;
    if ($fp = @fopen($save_path, 'a')) {
        // open or create the file for writing and append info
        fputs($fp, "\n$msg"); // write the data in the opened file
        fclose($fp); // close the file
    }
}


function mailWhenScriptHalted() {
    $file = escapeshellarg('log.txt'); // for the security concious (should be everyone!)
    $line = `tail -n 1 $file`;
        
    if ( strpos( $line, 'END' ) === false ) {
        echo "Script did not run till the end. Investigation of logs required!!!! " . LOG_PATH;
        // script halted so email admin
        $msg = LOG_PATH;
        
        $success = mail("frefeys@gmail.com","Investigation required", $msg);

        if (!$success) {
            $errorMessage = error_get_last()['message'];
            logThis($errorMessage);
        } else {
            // logThis("Mail with log send!"); 
        }
        return true;
    } 
}


?>