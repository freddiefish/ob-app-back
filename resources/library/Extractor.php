<?php
use Sk\Geohash\Geohash;

class Extractor {

    private $app;
    private $doc;
    private $filter;

    /**
     * DOM scraping, PDF, Financial, Location, Person, Organisation, Associated refs
     */

    public function __construct($app, $doc, $filter){
        $this -> app = $app;
        $this -> doc = $doc;
        $this -> filter = $filter;
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

    public function insertArray($array, $arrayNew, $offset) {
        $length = count($array);
        $head = array_slice($array, 0, $offset ) ;
        /** @todo take care of cases with three pieces */ 
        $insert = array( $offset-1 => $arrayNew[0], $offset => $arrayNew[1]);
        if (count($arrayNew) == 3) {
            array_push($insert, $arrayNew[2]);
        }
        $tail = array_slice($array, $offset+1, $length); 
        $res = array_merge($head, $insert, $tail);
        return $res;
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
                            $textChops = $this->insertArray($textChops, $textChopSplits, $i);
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
        $textChops = preg_split('/(?<=[a-z.:;0-9])(?=[A-Z<][a-z.])/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $textChops = $this->chopProcess($textChops);
        return $textChops;   
    }

    public function postProces($heading, $gluedText) {
        if ($heading == 'Besluit') { // makes sure we have Artikel headings
            $replText = preg_replace('/(?<=\.\s)(?=Artikel)/', '</p><p>', $gluedText);
            $gluedText = $replText;
        }
        return $gluedText;
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

        $tmplParts = $this->doc->tplParts;
        $extrParts = [];
        $glue = false;
        $heading = '';
        $gluedText = '';
        $textChop = '';
        $nrTextChops = count($textChops);
        $i=0;
        $docTitle = $textChops[0];
        $docCat = $this->setCategory($docTitle);

        foreach($textChops as $textChop) {
            
            if (in_array($textChop,  $tmplParts))  {
                $glue = true;
                // any postprocessing?
                if (!empty($heading) ) {
                    $gluedText = $this->postProces($heading, $gluedText);
                    array_push($extrParts, array('header' => $heading, 'text' => $gluedText));
                }
                $heading = $textChop;
                $gluedText = '';
                if( $i+1 < $nrTextChops && strpos($textChops[$i+1], '<li>') === 0 ) { // a new text part that starts with bullet list 
                        $gluedText .= '<ul>';  
                }   
            } 
            
            if (!in_array($textChop, $tmplParts ) && $glue) {
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
                        $gluedText = $this->postProces($heading, $gluedText);
                        array_push($extrParts, array('header' => $heading, 'text' => $gluedText));
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
          // $this->filter->table($text);
        return $text;
    }

    public function financials($textParts){

        $partsToCheck = array('Algemene financiële opmerkingen','Besluit');
        $textToScreen = $this->textToScreen($textParts, $partsToCheck);
        
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
    
    private function textToScreen($textParts, $toSreenTextParts) {

        $textToScreen = '';

        foreach ($textParts as $textPart) { // first glue all required textparts
            if(in_array($textPart['header'], $toSreenTextParts)) {
                $textToScreen .= $textPart['text'];
            }
        }

        return $textToScreen;
    }
    /**
     * extracts KBO number of organisation 
     */

    public function organisations($textParts) {
        $partsToCheck = array('Besluit');
        $textToScreen = $this->textToScreen($textParts, $partsToCheck);

        $organisations = $this->KBONumber($textToScreen);
        
        if (!empty($organisations)) $this->doc->organisations = $organisations;
    }

    function KBONumber ($text) {
        $data = [];
        $KBOs = [];
        preg_match_all('/([01][0-9]{3}\.*\s*[0-9]{3}\.\s*[0-9]{3})/', $text, $matches);
        if (!empty($matches[0])) {
            foreach ($matches[0] as $match) {
                //standardize formatting
                $match = $this->filter->punctuations($match);
                $match = $this->filter->whiteSpace($match);
                array_push($data, $match);
            }
            $KBOs = array_unique($data);
        }

        return $KBOs;
    }

    public function streets(){
        $file           = LIBRARY_PATH . '/straatnamen.txt';
        $contents       = file_get_contents($file);
        $streets        = explode("\n", $contents); // this is your array of words
        return $streets;
    }

      public function locations($textParts) {
    
        $partsToCheck = array('Aanleiding en context','Argumentatie','Besluit');
        $textToScreen = $this->textToScreen($textParts, $partsToCheck);
        $textToScreen .= $this->doc->title ;

        $locations = [];
        $streets = $this->streets();
        
        foreach($streets as $street) {
            $pieces   = explode(",", $street);
            $streetName = $pieces[0];
            $streetZIP  = $pieces[1];
            $match = false;
            
            if (strpos($textToScreen, $streetName) ) {
                // we have a match, see if house number is available
                $match = true;
                $piecestextToScreen  = explode($streetName, $textToScreen);
                $pieces2    = explode(' ', $piecestextToScreen[1]);
                $pieceNextToStreet = $pieces2[1];
                $returnNeedle = $streetName;
                
                if ( preg_match( '/\d/', $pieceNextToStreet) && is_numeric(substr($pieceNextToStreet, 0, 1)) ) { //validate if this piece contains al least one integer AND first char is numeric -> we have a streetNr (syntax can be 12a)!
                    $pieceNextToStreet = $this->filter->punctuations($pieceNextToStreet);
                    // cases "Oever 13-17" trim to "Oever 13"
                    if (strpos($pieceNextToStreet,'-')){
                        logThis('Clean double house numbers: ' . $pieceNextToStreet);
                        $piecesNextToStreet = explode('-',$pieceNextToStreet);
                        $pieceNextToStreet = $piecesNextToStreet[0];
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
                /**
                 * @todo maybe more than one location found, so make array to hash next
                 * 
                 * */
                // $this->geoCode($result);
            }        
        }
    }

    /**
     * returns array of lat, lng, geohash out of given array with address string
     */

    public function geoCode($address) {
        
        $searchString       = $address;
        $needleEncoded      = urlencode($searchString);
        $requestUrl         = 'http://loc.geopunt.be/geolocation/location?q=' . $needleEncoded; // docs: https://loc.geopunt.be/Help/Api/GET-v4-Location_q_latlon_xy_type_c
        
        $geoloc = json_decode( do_curl( $requestUrl ));
                
        if (!empty($geoloc->LocationResult)){ // sometimes ["LocationResult"]=> array(0) { }  is returned
            
            $formattedAddress   = $geoloc->LocationResult[0]->FormattedAddress;
            $lat                = $geoloc->LocationResult[0]->Location->Lat_WGS84;
            $lng                = $geoloc->LocationResult[0]->Location->Lon_WGS84;

            $geoHash = new Geohash();
            $g = $geoHash->encode($lat, $lng, 8);

            $location['formattedAddress']    = $formattedAddress;
            $location['lat']                 = $lat;
            $location['lng']                 = $lng;
            $location['geohash']             = $g;
            logThis('Geopoint API has result. Encoded: ' . $location['formattedAddress']);
            array_push( $geoLocations, $location);

        } else {
        
            logThis('Geopoint API has NO result');
        }
    

        $this->doc->locations = $location;
    }
   


}

