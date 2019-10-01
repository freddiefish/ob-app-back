<?php
use Sk\Geohash\Geohash;
use Sunra\PhpSimple\HtmlDomParser;

class Extractor {

    private $app;
    private $doc;
    private $filter;
    private $util;

    /**
     * DOM scraping, PDF, Financial, Location, Person, Organisation, Associated refs
     */

    public function __construct($app, $doc, $filter, $util){
        $this -> app = $app;
        $this -> doc = $doc;
        $this -> filter = $filter;
        $this -> util = $util;
    }

    /**
     * take a docId, downloads to storage, parses the text, cleans text, extracts (text paragraphs, addenda, associated docs, financial stakes
     * @param string    docId
     * @return  array   fullTxt, background, finalDecision, assDecisions, amountsAtStake, addenda
     * 
     */

    public function document($docId) {
        $text = $this->text($docId);
        $text = $this->filter->removeTpl($text);
        $text = $this->filter->whiteSpaceFilter($text);
        $text = $this->filter->indicateListItems($text);
        $text = $this->addenda($text);
        $textChops = $this->chopText($text);
        $this->textParts($textChops);
        $this->organisations($this->doc->textParts); // run before table filter
        $this->financials($this->doc->textParts); 
        $this->locations($this->doc->textParts) ; 
        /* $this->filter->tables($this->doc->textParts);   */
        return $text;
    }

    /**
     * Given a docID, Checks if the PDF file exists locally, extracts text, returns text
     * @param   string  docId
     * @return  string   docFullText
     */

    public function text($docId){

        $fileName = $this->app->pubDir . '/_besluit_' . $docId . '.pdf';
        $parser = new \Smalot\PdfParser\Parser();
    
        if (file_exists($fileName)) {
            $pdf = $parser->parseFile($fileName);
            $fullText = $pdf->getText(); 
        }
    
        return $fullText ;

    }



    public function chopProcess($textChops) {

        $offset = 0;
        foreach ($this->doc->tplParts as $tplPart) {
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
        $textChops = preg_split('/(?<=[a-z.:;])(?<![A-Z0-9]\.)(?=[A-Z<][a-z.])|(?<=[:;])(?=[0-9])|(?<=[a-z])(?=[0-9]\.)/', $text, -1, PREG_SPLIT_NO_EMPTY);
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

        $tmplParts   = $this->doc->tplParts;
        $extrParts   = [];
        $heading     = '';
        $gluedText   = '';
        $textChop    = '';
        $nrTextChops = count($textChops);
        $i           = 0;
        
        /**
         * @todo process title
         */
        $docTitle    = $textChops[0];
        $docCat = $this->setCategory($docTitle);

        foreach($textChops as $textChop) {
            
            if (in_array($textChop,  $tmplParts))  {
                if (!empty($heading) ) {
                    if ($heading == 'Besluit') { // postprocessing needed
                        list($introText, $subHeadings) = $this->postProces($heading, $gluedText);
                        array_push($extrParts, array(
                            'id' => array_search($heading, $tmplParts) ,
                            'name' => $heading,
                            'text' => $introText,
                            'headings' => $subHeadings ) );
                    } else {
                        array_push($extrParts, array(
                            'id' => array_search($heading, $tmplParts) ,
                            'name' => $heading,
                            'text' => $gluedText) );
                    }
                }
                
                
                $heading = $textChop;
                $gluedText = '';
                if( $i+1 < $nrTextChops && strpos($textChops[$i+1], '<li>') === 0 ) { // a new text part that starts with bullet list 
                        $gluedText .= '<ul>';  
                }   
            } 
            
            if (!in_array($textChop, $tmplParts )) {
                $pos = strpos($textChop, '<li>');  
                if ($pos === 0) { // we have a lis item
                    $gluedText .= $textChop . '</li>';
                    if( $i+1 < $nrTextChops && strpos($textChops[$i+1], '<li>') === false ) { // if next textChop is not a list item, close list 
                            $gluedText .= '</ul>';
                        }
                } else { // we have a paragraph text
                    if($i+1 == $nrTextChops){ // last run, so finalize the textParts
                        if ( $heading == 'Bijlagen') {
                            $textChop = $this->getBijlagen($textChop);
                        }
                        $gluedText .= '<p>' . $textChop . '</p>';
                        list($introText, $subHeadings) = $this->postProces($heading, $gluedText);
                        array_push($extrParts, array(
                            'id' => array_search($heading, $tmplParts) ,
                            'name' => $heading,
                            'text' => $introText,
                            'headings' => $subHeadings ) );
                    } else {
                        $gluedText .= '<p>'. $textChop . '</p>';
                    }

                }   
            }

        $i++; 
        }

        $this->doc->textParts = $extrParts;     
    }


    public function addenda($text) {
        $pieces = preg_split('/(?<=Bijlagen)(?=1)/', $text);        
        $text = $pieces[0];
        if(!empty($pieces[1])) { // we have addenda
            $this->filesList($pieces[1]);
        }
        
        return $text;
    }


    public function filesList($text) {
        $files = [];
        preg_match_all('/([a-zA-Z0-9-_]+\.(pdf|doc|docx|ppt|pptx))/', $text, $matches);
        foreach ($matches[0] as $match) {
            array_push($files, $match);
        }
        $this->doc->addenda =  $files;  
    }


    public function financials($textParts){

        $partsToCheck = array(11);
        $textToScreen = $this->textToScreen($textParts, $partsToCheck);
        echo $textToScreen;
        $financials = [];
        $amounts = [];
        $pieces = explode ('EUR', $textToScreen);
        $nrpieces = count($pieces);

        $i =0;
        foreach($pieces as $piece){
            $piece = trim($piece);
            $words = explode(' ', $piece);
            $amount = array_pop($words);
            
            if (strpos($amount, ',') ) { // case "10890,67 EUR" round to 10890
                $parts = explode(',', $amount);
                $amount = $parts[0];
            } 
            $amount = intval(str_replace('.','',$amount));
            
            if ($i < $nrpieces-1 && $amount <> 0) {
                array_push($amounts ,$amount);
                $amount = '';
            }
            $i++;
        }
        //remove duplicates
        $financials = array_unique($amounts);
        if (!empty($financials)) $this->doc->financials = $financials;
    }
    
    private function textToScreen($textParts, $partsToCheck) {

        $textToScreen = '';

        foreach ($textParts as $textPart) { // first glue all required textparts
            if( in_array($textPart['id'], $partsToCheck)  ) {
                if ( $textPart['id'] == 11 ) { // besluit textpart has subheadings
                    foreach($textPart['headings'] as $key=>$val) {
                        $textToScreen .= $key . ' ' . $val . ' ';
                    } 
                } else {
                    $textToScreen .= $textPart['text'] . ' ';
                }                
            }
        }

        return $textToScreen;
    }
    /**
     * extracts KBO number of organisation 
     */

    public function organisations($textParts) {
        $partsToCheck = array(11); // Besluit part only
        $textToScreen = $this->textToScreen($textParts, $partsToCheck);
        
        $KBOs = $this->KBONumbers($textToScreen);

        $organisations = $this->getKBODetails($KBOs);
        
        if (!empty($organisations)) $this->doc->organisations = $organisations;
    }

    function KBONumbers ($text) {
        $data = [];
        $KBOs = [];
        $patterns = array('((BE)*\s*[01][0-9]{3}\.*\s*[0-9]{3}\.\s*[0-9]{3})', '(NL*\s*[0-9]{9}B[0-9]{2})');
        
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
                $KBOs = array_unique($data);
            }
        }
        
        return $KBOs;
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

    public function locations($textParts) {
    
        $textToScreen = $this->doc->title ;
        $partsToCheck = array(1,6,11);
        $textToScreen .= $this->textToScreen($textParts, $partsToCheck);
        
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
                array_push($locations, $result);

            }        
        }
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
            
            $geoloc = json_decode( do_curl( $requestUrl ));
                    
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
            
                logThis('Geopoint API has NO result');
            }
        }

        if (!empty($geoLocations)) $this->doc->locations = $geoLocations;
    }
   
    


}

