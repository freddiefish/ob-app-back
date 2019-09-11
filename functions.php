<?php
// setting environment variables
define("PROD" , true);

if (PROD == true) {
    putenv('GOOGLE_APPLICATION_CREDENTIALS='. __DIR__  . '/tasks/ob-app-5e6adab126e2.json');
    define("LOG_PATH" , __DIR__ . "/tasks/log.txt");
    error_reporting(E_ERROR | E_PARSE);
} else { // developers mode
    putenv('GOOGLE_APPLICATION_CREDENTIALS='. __DIR__  . '/tasks/ob-app-dev-40dcf7752b62.json');
    define("LOG_PATH" , __DIR__ . '/tasks/log-dev.txt');
    // show all errors
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}
putenv('SUPPRESS_GCLOUD_CREDS_WARNING=true');

use Sunra\PhpSimple\HtmlDomParser;
use Google\Cloud\Firestore\FirestoreClient;
use Google\Cloud\Firestore\DocumentReference;
use Sk\Geohash\Geohash;
use Google\Cloud\Core\GeoPoint;
use Google\Cloud\Core\Timestamp;
use PHPMailer\PHPMailer\PHPMailer;

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
        $row['id'] = $docId;
        $row['published'] = true;
        $row['title'] = cleanTitle( $docTitle) ;
        
        $row['sortIndex1'] = new Timestamp(new DateTime($row['eventDate'])) . $docId; // to order the items in the infinite scroll view in the app
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

        // non published docs have no id, so create a random id
        $docId = RandomString(12) ;
        $row['sortIndex1'] = new Timestamp(new DateTime($row['eventDate'])) . $docId; // to order the items in the infinite scroll view in the app
        
        array_push($docList,  $row);
    }

    // prevent memory leaks
    $dom->clear();
    unset($dom);

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


function extractFromPDF($id) {
    // Parse pdf file, trim, return entities: fulltext, background, decision
    $text ='';

    $dir = sys_get_temp_dir();
    var_dump($dir);
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
    $artikelPieces = explode('Artikel 1', $arrayBGText[1]);
    unset($artikelPieces[0]);
    foreach ($artikelPieces as $piece) {
        $position = preg_match("/^[A-Z]/", $piece);
        if ($position = 1) $finalDecision = $piece;
    }
    // var_dump($finalDecision);

    return array ($fullTxt, $background, $finalDecision);
} 

function extractAddress($txt, $stringLocations) {
    
    $file           = __DIR__ . "/tasks/straatnamen.txt";
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
    echo $msg . '<br>';
}


function mailWhenScriptResult() {
    
    $file = escapeshellarg(LOG_PATH); // for the security concious (should be everyone!)
    $line = `tail -n 1 $file`;
    
    ( PROD? $msg= "https://ob-app-db2b6.appspot.com/log" : $msg='file:///Users/Main/Apps/ob-app-back/tasks/log-dev.txt\nhttps://ob-app-db2b6.appspot.com/log-dev' );

    if ( strpos( $line, 'END' ) === false ) {
        $subj = "Last cron job did not finish completely";
    } else {
        $subj = "Last cron job did finish!";
    }
    
    mail_this($subj, $msg);
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

function mail_this($subj, $msg) {

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
    $mail->setFrom('frefeys@gmail.com', 'Frederik Feys');
    $mail->addAddress('frefeys@gmail.com', 'Admin Fred'); 
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

?>