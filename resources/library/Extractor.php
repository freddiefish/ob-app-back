<?php

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

    public function extractText($docId){

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
        /** @todo take care of cases with three elements */ 
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

   

    public function extractTextParts($textChops) {

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
                    array_push($extrParts, array($heading => $gluedText));
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
                        array_push($extrParts, array($heading => $gluedText));
                    } else {
                        $gluedText .= '<p>'. $textChop . '</p>';
                    }

                }   
            }

        $i++; 
        }

        return $extrParts;     
    }


    public function processAddenda($text) {
        $pieces = preg_split('/(?<=Bijlagen)(?=1)/', $text);        
        $text = $pieces[0];
        if(!empty($pieces[1])) { // we have addenda
            $this->setAddenda($pieces[1]);
        }
        
        return $text;
    }


    public function setAddenda($text) {
        $links = [];
        $links = preg_split('/(?<=.pdf|.doc|.docx|.ppt|.pptx)(?=[0-9][.])/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $this->doc->addenda =  $links;   
    }


    /**
     * take a docId, downloads to storage, parses the text, cleans text, extracts (text paragraphs, addenda, associated docs, financial stakes
     * @param string    docId
     * @return  array   fullTxt, background, finalDecision, assDecisions, amountsAtStake, addenda
     * 
     */

    public function extractDoc($docId) {
        $text ="Artikel 4Dit besluit heeft in principe geen financiële gevolgen.Bijlagen1. algemene voorwaarden + formaliteiten vergunning.pdf2. beroepsmogelijkheden en verval.pdf3. plannenoverzicht_2019083393.pdf
        
        Algemene voorwaarden / c	ontrolelijst vóór u start met de vergunde handelingen	 	
             
        U hebt een omgevingsvergunning gekregen	. 	
        Wat moet u eerst doen of aan denken voor u start met de uitvoering van de vergunde handelingen?	 	
        We raden u aan om even stil te staan 	bij een aantal noodzakelijke acties of informatieve punten.	 Deze verplichtingen gelden als voorwaarden bij uw 	
        vergunning. Het niet naleven ervan is een bouwovertreding!	";
        $text = $this->extractText($docId);
        $text = $this->filter->removeTpl($text);
        $text = $this->filter->whiteSpaceFilter($text);
        // list($text, $table) = $this->filter->tableFilter($text); // works only after whiteSpacefileter
        $text = $this->filter->indicateListItems($text);
        $text = $this->processAddenda($text);
        $textChops = $this->chopText($text);
        $extrParts = $this->extractTextParts($textChops);  
        // $text = $this->filter->makeHTMLList($text); */
        //return $textChops; */
        return $text;
    }


}

