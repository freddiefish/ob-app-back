<?php
use Sk\Geohash\Geohash;
use Sunra\PhpSimple\HtmlDomParser;
use Google\Cloud\Core\Timestamp;

class Extractor {
    
    private $app;
    private $dl;
    private $filter;
    private $util;

    public  $docList = [];
    public  $doc = [];
    private $pathToScrape;
    private $eventDate;
    private $groupId;
    private $groupName;

    // entities
    public $locations;
    public $financialStakeholders;
    public $people;

    // general fields
    public $docId;
    public $intId;
    public $category;
    public $title;
    public $textParts;
    public $addenda;
    public $tables;
    public $tplParts = array(0 => 'Gekoppelde besluiten', 1 => 'Aanleiding en context', 2 => 'Omschrijving stedenbouwkundige handelingen', 3 => 'Juridische grond', 4 => 'Regelgeving: bevoegdheid', 5 => 'Openbaar onderzoek', 6 => 'Argumentatie', 7 => 'Financiële gevolgen', 8 => 'Algemene financiële opmerkingen', 9 => 'Strategisch kader', 10 => 'Adviezen', 11 => 'Besluit', 12 => 'Bijlagen');


    /**
     * DOM scraping, PDF, Financial, Location, Person, Organisation, Associated refs
     */

    public function __construct(App $app, Downloader $dl, Filter $filter, Util $util){
        $this -> app = $app;
        $this -> dl = $dl;
        $this -> filter = $filter;
        $this -> util = $util;
    }

    public function APICheckOK() {
        $APIList = array(
            'https://ebesluit.antwerpen.be',
            'http://ec.europa.eu/taxation_customs/vies/viesquer.do?ms=NL&iso=NL&vat=152239108B01&name=&companyType=&street1=&postcode=&city=&BtnSubmitVat=Verify',
            'https://www.btw-opzoeken.be/VATSearch/Search?KeyWord=0643479093',
            'http://loc.geopunt.be/geolocation/location?q=kerkstraat');
        
        foreach ($APIList as $url) {
            $response = $this->dl->doCurl($url);
            if (empty($response)){
                $this->app->log('APICheck failed with ' . $url);
                return false;
            }
        }
        
        return true;
    }

    /**
     * 
     *
     */
    public function dateStringify($date){
            $dateTimestamp   = $date->getTimestamp();

            $year            = date("Y", $dateTimestamp);
            $month           = date("n", $dateTimestamp);
            $monthTrailZero  = date("m", $dateTimestamp);
            $day             = date("j", $dateTimestamp);

        return array($day, $month, $monthTrailZero, $year);
    }


    /**
     * gets json response for calendar month 
     * @param int year
     * @param int month (with trailing zero!)
     * @return string   json 
     */

    public function scrapeCalendar($month, $year) {

        $DOM = $this->dl->doCurl(API_BASE_DIR . '/calendar/filter?year=' . $year . '&month=' . $month);
        $json = json_decode($DOM,true);
        $this->app->log('Returned json for ' . $month . ' '. $year . ' in calendar'); 

        return $json;
    }

    /** 
     * for a given time into history, scrapes all documents form calendar webpage: https://ebesluit.antwerpen.be/calendar/show
     * @param int daysToScreen
     * @return array docList
    */

    public function getDocumentList($daysToScreen = 30) {

        $startDate  = new DateTime();
        $timeSpan   = new DateInterval('P' . $daysToScreen . 'D');
        $timeSpan->invert = 1;
        $startDate->add($timeSpan);
        $stopDate   = new DateTime();
        $updateDate = $startDate;
        list($day, $month, $monthTrailZero, $year) = $this->dateStringify($updateDate);

        $json = $this->scrapeCalendar($monthTrailZero, $year);

        while( $updateDate < $stopDate) {

            list($updateDay, $updateMonth, $updateMonthTrailZero, $updateYear) = $this->dateStringify($updateDate);
            $this->app->log("Update for: " . $updateDay . '-' .  $updateMonth . '-' . $updateYear); 

            if ($year <> $updateYear OR $monthTrailZero <> $updateMonthTrailZero) {
                $year               = $updateYear;
                $month              = $updateMonth;
                $monthTrailZero     = $updateMonthTrailZero;
                // update calender view
                $json = $this->scrapeCalendar($monthTrailZero, $year);
            }

            $iterator = "$year$month$updateDay";

            foreach ($json as $obj) {

                if (array_key_exists($iterator, $obj)) { // not all dates are available
                    foreach($obj[$iterator] as $val) { 
                        // get the day's event
                        $this->pathToScrape   = $val['url'];
                        $this->eventDate      = $val['startDateString'];
                        $this->groupId        = $val['groupId'];
                        $this->groupName      = $val['className'];
                        $this->scrapeEventDocs();
                    }
                }

            }
            $updateDate->add(new DateInterval('P1D')); //increment one day
        }

        $this->app->log('Found docs: ' . count($this->docList));
       
    }

    /**
     * scrape an event for all its documents
     */

     public function scrapeEventDocs() {

        $DOM = HtmlDomParser::str_get_html( $this->dl->doCurl( API_BASE_DIR . $this->pathToScrape ) );

        // get the agenda html 
        $agendaHtml = $DOM->getElementById("agenda");

        // published 
        foreach($agendaHtml->find('a') as $e) {
            $docHref = $e->href;
            $pieces = explode('/', $docHref);
            $docId = $pieces[2];

            $this->createDocEntry($e, $docId);
        }

        // not published 
        foreach($agendaHtml->find('span.title-no-rights') as $e) {
            // non published docs have no id, so create random id
            $docId = $this->util->getRandomString(8); 

            $this->createDocEntry($e, $docId);
        }

        // prevent memory leaks
        $DOM->clear();
        unset($DOM);

     }

    /**
     * creates a doc entry in docList
     * @param object e
     * @param string    docId
     */

    public function createDocEntry($e, $docId) {

        $docTitle           = $e->innertext;
        $row['docId']       = $docId;
        $row['offTitle']    = $docTitle;
        list($row['intId'], $row['title']) 
                            = $this->getTitleElements($docTitle) ;
        $row['eventDate']   = $this->eventDate;
        $row['groupId']     = $this->groupId;
        $row['groupName']   = $this->groupName;
        $row['published']   = true;
        $row['sortIndex1']  = new Timestamp(new DateTime($row['eventDate'])) . $docId; // to order the items in the infinite scroll view in the app
        array_push($this->docList, $row);

    }


    /**
     * process official title (eg. "2019_DCME_00239 - Districtscollege - Notulen 19 september 2019 - Goedkeuring")
     * @param string text
     * @return array intId, cleanTitle
     */

    public function getTitleElements($txt) {
        //process the official title, split off first part (e.g. 2016_MV_00157 - Mondelinge vraag van raa...)
        $pieces = explode(" - ", $txt);  
        $intId = trim($pieces[0]);
    
        //remove intId from title 
        $cleanTitle = str_replace( $pieces[0] . " - " , "" , $txt ); 

        $endToRemove = end($pieces);        
        $cleanTitle = str_replace( " - " . $endToRemove , '' , $cleanTitle ); //case insensitive replace
        
        return array($intId, $cleanTitle );
    }

    /**
     * take a docId, downloads to storage, parses the text, cleans text, extracts (text paragraphs, addenda, associated docs, financial stakes
     * @param string    docId
     * @return  array   fullTxt, background, finalDecision, assDecisions, dataAtStake, addenda
     * 
     */

    public function document($docId) 
    {
        $text = $this->text($docId);
        $text = $this->filter->removeTpl($text);
        $text = $this->filter->whiteSpaceFilter($text);
        $text = $this->filter->indicateListItems($text);
        $textChops = $this->chopText($text);
        $this->textParts($textChops);
        $this->whoGetsWhat();
        $this->locations() ; 
        // 'Niet gepubliceerd. <a href="mailto:' . EMAIL_BESLUITVORMING . '?subject="lezen%20besluiten&body=Goede%20dag,%0Aik%20wil%20een%20besluit%20lezen%20op%20pagina:%20https://ebesluit.antwerpen.be/agenda/' . $val['id'] . '/view%20De%20link%20werkt%20helaas%20niet.%20Hoe%20kan%20ik%20het%20lezen?">vraag via email volledige tekst</a>';
    }

    /**
     * Given a docID, Checks if the PDF file exists locally, extracts text, returns text
     * @param   string  docId
     * @return  string   docFullText
     */

    public function text($docId){

        $fileName = '_besluit_' . $docId . '.pdf';
        $dir = sys_get_temp_dir();
        $filePath = $dir . '/' . $fileName;

        $this->dl->download_object($this->app->pubDir, $fileName, $filePath);

        if (file_exists($filePath)) {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($filePath);
            $fullText = $pdf->getText(); 
        }
    
        return $fullText ;

    }



    public function chopProcess($textChops) {

        $offset = 0;
        foreach ($this->tplParts as $tplPart) {
            $i =0;
            
            foreach($textChops as $key => $textChop) {
                if($key >= $offset ) {
                    
                    if(is_int( strpos($textChop, $tplPart) )) {
                        $offset = $i+1;
                        if(strlen($textChop) <> strlen($tplPart)) { // further chopping needed
                            $del = $tplPart;
                            $pieces = explode($del, $textChop);
                            $offset = $i+2;
                            if (empty($pieces[0])){ // tplPart at beginning of textChop
                                $textChopSplits = array($tplPart, $pieces[1]);
                            } else if (empty($pieces[1])) {
                                $textChopSplits = array($pieces[0], $tplPart);
                            } else {
                                $textChopSplits = array($pieces[0], $tplPart, $pieces[1]);
                                $offset = $i+3;
                            }
                            $textChops = $this->util->insertArray($textChops, $textChopSplits, $i);
                        }
                    // leave the foreach loop
                    break;
                    } 
                }
                
                $i++;
            }
        }
        return $textChops;
    }

    public function chopText($text) {
        $textChops = [];
        // echo $text;
        $textChops = preg_split('/(?<=[a-z.:;])(?<![A-Z0-9]\.)(?=[A-Z<][a-z.])|(?<=[:;])(?=[0-9])|(?<=[a-z])(?=[0-9]\.)|(?<=[A-Z]\.)(?=Artikel)/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $textChops = $this->chopProcess($textChops);
        return $textChops;   
    }

    public function postProces($heading, $gluedText) {

        $subHeadings = [];
        $introText = '';

        if ($heading == 'Besluit') { // makes sure we have array with "Artikel" headings

            $continue = true;

            for($i=1; $continue; $i++) {
                $pattern = "/(<p>)*Artikel " . $i . "(<\/p>)*/";
                $splits= preg_split($pattern, $gluedText);
                if ($i==1) {
                    $introText = $splits[0]; 
                }
                
                if (count($splits)<2){
                    $continue = false;
                } else {
                    $next = $i+1;
                    $patternNext = "/(<p>)*Artikel " . $next . "(<\/p>)*/";
                    $splitsNext= preg_split($patternNext, $splits[1]);
                    if (count($splitsNext)>1) { // we have a next header
                        $subHeadings['Artikel ' . $i] = $splitsNext[0];
                    } else {
                        $subHeadings['Artikel ' . $i] = $splits[1];
                    }
                }
            }
            
        }
        return array($introText, $subHeadings);
    }

    public function setCategory($docTitle) {
        $cats = array(0 =>'Onbekend',1 =>'Omgevingsvergunning');
        $docCat = 0;
        $cleanTitle = trim($docTitle);
        $pieces = explode('-', $cleanTitle);

        foreach($cats as $key=>$val){
            foreach($pieces as $piece) {
                $piece = trim($piece);
                if ($val == $piece){
                    $docCat = $key;
                }
                break;
            }
        }
        return $docCat;
    }

   

    public function textParts($textChops) {

        $tplParts   = $this->tplParts;
        $tplDocument = false; // some docs have no template and will be handled different.
        $extrParts   = [];
        $heading     = '';
        $gluedText   = '';
        $notTplDocGluedText = '';
        $textChop    = '';
        $nrTextChops = count($textChops);
        $i           = 0;
        
        /**
         * @todo process title
         * @todo format some "aanleiding en context" as timeline
         */
        // $docTitle    = $textChops[0];
        // $docCat = $this->setCategory($docTitle);

        foreach($textChops as $textChop) {
            
            if (in_array($textChop,  $tplParts))  {
                $tplDocument = true;
                if (!empty($heading) ) {
                    if ($heading == 'Besluit') { // postprocessing needed
                        list($introText, $subHeadings) = $this->postProces($heading, $gluedText);
                        array_push($extrParts, array(
                            'id' => array_search($heading, $tplParts) ,
                            'name' => $heading,
                            'text' => $introText,
                            'headings' => $subHeadings ) );
                    } else {
                        array_push($extrParts, array(
                            'id' => array_search($heading, $tplParts) ,
                            'name' => $heading,
                            'text' => $gluedText) );
                    }
                }
                
                $heading = $textChop;
                if ($heading == 'Gekoppelde besluiten') {
                    //process like bijlagen
                }
                $gluedText = '';
                if( $i+1 < $nrTextChops && strpos($textChops[$i+1], '<li>') === 0 ) { // a new text part that starts with bullet list 
                        $gluedText .= '<ul>';  
                }   
            } 
            
            if (!in_array($textChop, $tplParts ) && $tplDocument) {
                $pos = strpos($textChop, '<li>');  
                if ($pos === 0) { // we have a list item
                    $gluedText .= $textChop . '</li>';
                    if( $i+1 < $nrTextChops && strpos($textChops[$i+1], '<li>') === false ) { // if next textChop is not a list item, close list 
                            $gluedText .= '</ul>';
                        }
                } else { // we have a paragraph text
                    if($heading == 'Bijlagen'){ // send any remaining textChops to the method
                        $remaingText = '';
                        foreach($textChops as $key=>$val){
                            if ($key >= $i){
                                $remaingText .= $val . ' ';
                            }
                        }
                        $this->addenda($remaingText);
                        break;
                    }
                    if($i+1 == $nrTextChops){ // works best when no  bijlagen ! last run, so finalize the textParts
                        
                        $gluedText .= '<p>' . $textChop . '</p>';
                        list($introText, $subHeadings) = $this->postProces($heading, $gluedText);
                        array_push($extrParts, array(
                            'id' => array_search($heading, $tplParts) ,
                            'name' => $heading,
                            'text' => $introText,
                            'headings' => $subHeadings ) );
                    } else {
                        $gluedText .= '<p>'. $textChop . '</p>';
                    }

                }   
            }

            if(!$tplDocument && $i > 0) { // no need for title on $i = 0 
                $notTplDocGluedText .= '<p>'. $textChop . '</p>';
            }

        $i++; 
        }
        if(!$tplDocument) {
            array_push($extrParts, array(
                'id' => 11 ,
                'name' => 'Besluit',
                'text' => $notTplDocGluedText ));
        }
        $this->doc['textParts'] = $extrParts;     
    }


    public function addenda($text) {
         
        $text = strip_tags($text);
        if(!empty($text)) { // we have addenda
            $this->filesList($text);
        }
        
    }


    public function filesList($text) {
        $files = [];
        preg_match_all('/([a-zA-Z0-9-_]+\.(pdf|doc|docx|ppt|pptx))/', $text, $matches);
        foreach ($matches[0] as $match) {
            array_push($files, $match);
        }
        $this->doc['addenda'] = $files;  
    }

    public function financials($text){

        $financials = [];
        $data = [];

        // get out numbers that might interfere with extraction
        // IBAN rekeningnummer
        // Years
        $text = preg_replace('/(BE\s*[0-9]{2}\s*[0-9]{4}\s*[0-9]{4}\s*[0-9]{4})/', 'IBAN', $text);
        $text = preg_replace('/([12][0-9]{3})/', 'YEAR', $text);
        preg_match_all('/([0-9.,\s]+)(E\s*U\s*R)/', $text, $matches);
    
        foreach($matches[1] as $match){
            $piece = trim($match);
            $amount = $this->filter->whiteSpace($piece);
            
            if (strpos($amount, ',') ) { // case "10890,67 EUR" round to 10890
                $parts = explode(',', $amount);
                $wholeAmount = $parts[0];
            } 

            $amount = intval(str_replace('.','',$amount));
            array_push($data ,$amount);
            
        }
        //remove duplicates?
        $financials = $data;
        
        return $financials;
    }
    

    public function whoGetsWhat() {
        $whoGetsWhat= [];

        // see if we have a table to extract
        list($this->doc['textParts'],$tables) = $this->filter->tables($this->doc['textParts']);

        if(!empty($tables)) {

            foreach($tables as $table) {

                list($KBOs, $text) = $this->KBONumbers($table);
                $financials = $this->financials($text);
                try {
                    if (!count($KBOs) === count($financials)) {
                        throw new Exception('Number of KBOs do not match number of financials.');
                    } 
                    if (count($KBOs) === 0 ){
                        throw new Exception('Found no KBOs in this table.');
                    }
                    
                    $KBODetails = $this->getKBODetails($KBOs);
                    $i = 0;
                    foreach($KBOs as $KBO) {
                        $whoGetsWhat[$i] = array(
                            'VAT' => $KBODetails[$i]["VAT"], 
                            'Name' => $KBODetails[$i]["Name"], 
                            'JuridicalForm' => $KBODetails[$i]["JuridicalForm"], 
                            'Address' => $KBODetails[$i]["Address"], 
                            'Amount' => $financials[$i] );
                        $i++;
                    }
                    if (!empty($whoGetsWhat)) $this->doc['financialStakeholders'] = $whoGetsWhat;
                }
                catch (Exception $e) { 
                    $this->app->log('Caught exception: ' . $e->getMessage());
                }

            }

        }
    }

    function KBONumbers ($text) {
        $data = [];
        $KBOs = [];
        
        $patterns = array('((BE)*\s*[01][0-9]{3}\.*\s[0-9]{3}\.*\s[0-9]{3})|((BE)*\s*[01][0-9]{3}\.[0-9]{3}\.[0-9]{3})', '(NL*\s*[0-9]{9}B[0-9]{2})');
        
        foreach ($patterns as $pattern) {
            preg_match_all('/'. $pattern .'/', $text, $matches);
            if (!empty($matches[0])) {
                foreach ($matches[0] as $match) {
                    //standardize formatting
                    $match = $this->filter->punctuations($match);
                    $match = $this->filter->whiteSpace($match);
                    if(strpos($match, 'BE') === false) { 
                        $match = 'BE' . $match;
                    }
                    array_push($data, $match);
                }
                $KBOs = $data;
                // remove all KBOs out of text to make financial extraction easier
                $text = str_replace($matches[0], '', $text);
            }
        }
        
        return array($KBOs, $text);
    }

    private function requestVIES($KBO) {

        $cleanData = [];

        $url = 'http://ec.europa.eu/taxation_customs/vies/viesquer.do?ms='.$country.'&iso='.$country.'&vat='.$vatnum.'&name=&companyType=&street1=&postcode=&city=&BtnSubmitVat=Verify';
        // Create DOM from URL or file
        $html = HtmlDomParser::str_get_html( do_curl($url) );
        $table = $html->find('table', 0);

        $data = [];

        foreach($table->find('tr') as $row) {
            $rowData = [];
            foreach($row->find('td') as $cell) {
                $rowData[] = trim($cell->plaintext);
            }
            $data[] = $rowData;
        }

        //get VAT Number, Name, Address from response
        $neededParts = array('VAT Number', 'Name', 'Address');

        if ($data[0][0] == "Yes, valid VAT number") {
            foreach($data as $row) {
                if (!empty($row[1]) && in_array($row[0], $neededParts) ) $cleanData[$row[0]] = $row[1] ;
            }
        } else {
            $this->app->log($country.$vatnum . ' is not a valid VAT number');
        }

        return $cleanData;
    }

    private function requestBillit($KBO) {
        try {
            $url = 'https://www.btw-opzoeken.be/VATSearch/Search?KeyWord=' . $KBO ;
            $response = do_curl($url);
            $dom_json = json_decode($response,true);
            if(empty($dom_json)) {
                throw new Exception('Could not get response from ' .  $url);
            }

            
            $data = [
                'VAT' => $dom_json[0]['VAT'],
                'Name' => $dom_json[0]["CompanyName"],
                'JuridicalForm' => $dom_json[0]["JuridicalForm"],
                'Address' => $dom_json[0]["Street"] . ' '. $dom_json[0]["StreetNumber"] . ', ' . $dom_json[0]["Zipcode"] . ' ' . $dom_json[0]["City"]
            ];
            return $data;

        } catch (Exception $e){
            $this->app->log( $e->getMessage());
        }
            
    }

    private function getKBODetails($KBOs) {
        $KBOData = [];
        foreach($KBOs as $KBO) {

            $country = substr($KBO, 0, 2);
            $vatnum = substr($KBO, 2);

            if($country == 'BE') {
                $data = $this->requestBillit($KBO);
            } else {
                $data = $this->requestVIES($KBO);
            }

            $KBOData[] = $data;
        }
        return $KBOData;
    }

    public function streets(){
        $file           = LIBRARY_PATH . '/straatnamen.txt';
        $contents       = file_get_contents($file);
        $streets        = explode("\n", $contents); // this is your array of words
        return $streets;
    }

    public function locations() {
    
        $textToScreen = $this->title ;
        $partsToCheck = array(1,6,11);
        $textToScreen .= $this->filter->textToScreen($this->doc['textParts'], $partsToCheck);
        
        $data = [];
        $locations = [];
        $streets = $this->streets();
        
        foreach($streets as $street) {

            $pieces   = explode(",", $street);
            $streetName = $pieces[0];
            $streetZIP  = $pieces[1];

            if (strpos($textToScreen, $streetName) ) {
                // we have a match, see if house number is available
                $piecestextToScreen  = explode($streetName, $textToScreen);
                $pieces2    = explode(' ', $piecestextToScreen[1]);
                $pieceNextToStreet = $pieces2[1];
                $ZIP = trim($pieces2[2]);
                if(strlen($ZIP)==4 && !intval($ZIP)==0){ // seems to be a valid zip
                    $streetZIP = $ZIP;
                }
                $returnNeedle = $streetName;
                
                if ( preg_match( '/^\d+/', $pieceNextToStreet)  ) { //validate string starts with at least one integer (syntax can be 12a)!
                    $pieceNextToStreet = $this->filter->punctuations($pieceNextToStreet);
                    // cases "Oever 13-17" trim to "Oever 13"
                    if (strpos($pieceNextToStreet,'-')){
                        // logThis('Clean double house numbers: ' . $pieceNextToStreet);
                        $piecesNextToStreet = explode('-',$pieceNextToStreet);
                        $pieceNextToStreet = $piecesNextToStreet[0];
                    }
                    //cases "Schutstraat 39/1" trim to "Schutstraat 39"
                    if (strpos($pieceNextToStreet, '/')) {
                        // logThis('Clean / out of house number: ' . $pieceNextToStreet);
                        $piecesNextToStreet = explode('/', $pieceNextToStreet);
                        $pieceNextToStreet = $piecesNextToStreet[0];
                    }
                    $returnNeedle .= ' ' . $pieceNextToStreet; //eg: Wolstraat 15a
                }
                $result = $returnNeedle . ', ' . $streetZIP ;
                array_push($data, $result);

            }        
        }
        $locations = array_unique($data);
        $this->geoCode($locations);
    }

    /**
     * returns array of lat, lng, geohash out of given array with address string
     */

    public function geoCode($locations) {

        $geoLocations = [];

        foreach($locations as $location) {

            $searchString       = $location;
            $needleEncoded      = urlencode($searchString);
            $requestUrl         = 'http://loc.geopunt.be/geolocation/location?q=' . $needleEncoded; // docs: https://loc.geopunt.be/Help/Api/GET-v4-Location_q_latlon_xy_type_c
            
            $geoloc = json_decode( $this->dl->doCurl( $requestUrl ));
                    
            if (!empty($geoloc->LocationResult)){ // sometimes ["LocationResult"]=> array(0) { }  is returned
                
                $formattedAddress   = $geoloc->LocationResult[0]->FormattedAddress;
                $lat                = $geoloc->LocationResult[0]->Location->Lat_WGS84;
                $lng                = $geoloc->LocationResult[0]->Location->Lon_WGS84;

                $geoHash = new Geohash();
                $g = $geoHash->encode($lat, $lng, 8);

                $geoLocation['formattedAddress']    = $formattedAddress;
                $geoLocation['lat']                 = $lat;
                $geoLocation['lng']                 = $lng;
                $geoLocation['geohash']             = $g;
                // logThis('Geopoint API has result. Encoded: ' . $geoLocation['formattedAddress']);
                array_push( $geoLocations, $geoLocation);

            } else {
            
                $this->app->log('Geopoint API returned no result');
            }
        }

        if (!empty($geoLocations)) $this->doc['locations'] = $geoLocations;
    }
   
    public function __destruct() {
        // closing persistent connections
    }


}

