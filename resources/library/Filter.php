<?php


class Filter
{
    private $util;

    public function __construct(Util $util) {
        $this->util = $util;
    }

    public function whiteSpaceFilter($text) {
        return preg_replace("/\s[[:space:]]+/", " ", str_replace(["\r", "\n"], ' ', $text));
    }

    public function removeTpl($text) {
        $text = $this->trimText($text); // cuts header
        $text = $this->removeFooterText($text);
        $text = $this->removePagings($text);
        return $text;
    }
    
    public function removeFooterText($text) {
        // $stringsToRemove = array("Grote Markt 1 - 2000 Antwerpen info@stad.antwerpen.be", "Grote Markt 1 - 2000 Antwerpen", "info@antwerpen.be","info@stad.antwerpen.be");
        // $text = str_replace($stringsToRemove,'',$text);
        $replText = preg_replace('/Grote Markt\s+1 - 2000 Antwerpen\s*\ninfo@stad\.antwerpen\.be\h/', '', $text);
        $replText2 = preg_replace('/Grote Markt 1 - 2000 Antwerpen\s\ninfo@antwerpen.be/', '', $replText);
        return $replText2;
    }
    
    public function trim($word){
        return trim($word);
    }

    public function removePagings($text) {
        $replText = preg_replace("/(\n\s[0-9]+\s\/\s[0-9]+\s\n)/", '', $text);
        return $replText;

    }

    function punctuations($text) {

        $gluedtext = '';
        $searchFor = array('.', ',');

        foreach($searchFor as $search) {
            $pieces = explode($search, $text);
            if (count ($pieces) >1) {
                foreach($pieces as $piece){
                    // glue
                    $gluedtext .= $piece;
                }
                $text = $gluedtext;
            }
            
            
        }
        return $text;
    }

    function whiteSpace($text) {

        $gluedText = '';
        $pieces = explode(' ', $text);
        if (count ($pieces) >1) {
            foreach($pieces as $piece){
                // glue
                $gluedText .= $piece;
            }
        } else {
            $gluedText = $text;
        }
        return $gluedText;
    }

    /**
     * cut header to obtain text starting from document title
     */

    public function trimText($text) {
        preg_match( "/([0-9]{4}\_[A-Z]+_[0-9]+)/", $text, $matches,PREG_OFFSET_CAPTURE);
        
        if (!empty($matches[0][0])) {
            $delimiter = $matches[0][0];
            $pieces = explode($delimiter , $text);
            $text = $pieces[1];
        }

        return $text;
    }

    function indicateListItems($text) {
        $delimiter = ' ';
        
        if(!strpos($text,'  ') === false) {
            $delimiter = '  ';
        }  

        $pieces = explode( $delimiter, $text );
        $nrElements = count($pieces);
        $i=0;
        foreach ($pieces as $piece ) {
            $piece = trim($piece);
            switch ($i) {
                case 0:
                    $text = $piece;
                    break;
                default:
                    $text .= '<li>' . $piece ;
                    break;
            } 

            $i++;
        }

        // ensure <li> next to non white space char, so chopping goes wel in next step.
        //$replText = preg_replace('/(?<=\s)(?=<li>)/', '=',$text);
        return $text;
    }

    public function textToScreen($textParts, $partsToCheck) {

        $textToScreen = '';

        foreach ($textParts as $textPart) { // first glue all required textparts
            if( in_array($textPart['id'], $partsToCheck)  ) {
                if ( $textPart['id'] == 11 && !empty( $textPart['headings']) ) { // besluit textpart has subheadings
                    foreach($textPart['headings'] as $heading) {
                        $textToScreen .= $heading['text'] . ' ';
                    } 
                } else {
                    $textToScreen .= $textPart['text'] . ' ';
                }                
            }
        }

        return $textToScreen;
    }

    /**
     * filter table out of Besluit textPart
     * @todo use $table for export to CSV , dor every texp part needed
     */

    function tables($textParts) {

        $tables =[];
        $patterns = array(
            array('heading' => 'Omschrijving Bedrag Boekingsadres Bestelbon', 
                'startPattern' => '(?<=volgt:<\/p>)(?=.+Omschrijving.+Bedrag.+Boekingsadres)'),
            array('heading' => 'Bestelbon Bedrag Omschrijving', 
                'startPattern' => '(?<=.)(?=\s*bestelbonbedragomschrijving)'),
            array('heading' => 'Datum Jaarnummer Titel', 
                'startPattern' => '((<p>)*Datum(<\/p><p>)*Jaarnummer(<\/p><p>)*Titel)'),
            array('heading' => 'Fase Bestuursorgaan Datum Jaarnummer', 
                'startPattern' =>'((<p>)*Fase(<\/p><p>)*Bestuursorgaan(<\/p><p>)*Datum(<\/p><p>)*Jaarnummer<\/p>*)',
                'endPattern' => '')
        );
        
        $keyTextPart = $this->util->multiDimArrayFindKey($textParts, 'id', 11);

        if(!empty($textParts[$keyTextPart]["headings"])) {

            $i = 0;
            foreach($textParts[$keyTextPart]["headings"] as $heading ) {
                
                foreach($patterns as $pattern) {
                    $pieces = preg_split('/'. $pattern['startPattern'] .'/', $heading['text']);
                    if(count($pieces) > 1) { // we have a table
                        $placeholder = '<p>< Tabel: zie PDF ></p>';
                        $text = $pieces[0] . $placeholder;
                        
                        if (array_key_exists('endPattern', $pattern)){ }// table is not located at begin or end of text
        
                        $table = $pattern['heading'] . ' ' . $pieces[1]; 
                        array_push($tables, $table);
                        
                        // update text
                        $textParts[$keyTextPart]['headings'][$i]['text'] = $text;
                        } 
            
                } 
                $i++;
            }
            
        }
        
        return array($textParts,$tables);
        
    }

    
}
