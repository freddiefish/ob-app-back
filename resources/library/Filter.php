<?php


class Filter
{
    private $doc;

    public function __construct($doc) {
        $this->doc = $doc;
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
            $intRef =  $this->doc->intId = $matches[0][0];
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

    /**
     * filter table and entity
     * @todo use $table for export to CSV , dor every texp part needed
     */

    function tables($textParts) {

        $tables =[];
        $partsToCheck = array(11); // Aanl&Context , Besluit
        $patterns = array(
            array('heading' => 'Omschrijving Bedrag Boekingsadres', 
                'startPattern' => '(?<=volgt:<\/p>)(?=.+Omschrijving.+Bedrag.+Boekingsadres)'),
            array('heading' => 'Datum Jaarnummer Titel', 
                'startPattern' => '((<p>)*Datum(<\/p><p>)*Jaarnummer(<\/p><p>)*Titel)'),
            array('heading' => 'Fase Bestuursorgaan Datum Jaarnummer', 
                'startPattern' =>'((<p>)*Fase(<\/p><p>)*Bestuursorgaan(<\/p><p>)*Datum(<\/p><p>)*Jaarnummer<\/p>*)',
                'endPattern' => '')
        );

        foreach($partsToCheck as $partToCheck) {

            $textPartKey = array_search($partToCheck, array_column($textParts, 'id'));
            
            if ($partToCheck == 11) { // Besluit, special case
                
                $output = array_values($textParts[$textPartKey]['headings']); 

            } else { // general 
                $output = array(0 => $textParts[$textPartKey]['text']);
            }

            foreach($output as $index=>$val) {
                foreach($patterns as $pattern) {
                    $pieces = preg_split('/'. $pattern['startPattern'] .'/', $val);
                    if(count($pieces) > 1) { // we have a table
                        $placeholder = '<p>< Tabel: zie PDF ></p>';
                        $text = $pieces[0] . $placeholder;
                        
                        if (array_key_exists('endPattern', $pattern)){ // table is not located at begin or end of text

                        }
                        $table = $pattern['heading'] . ' ' . $pieces[1]; 
                        array_push($tables, $table);

                        // update text
                        $fieldIndex = $index+1;
                        $fieldName = 'Artikel ' . $fieldIndex;
                        $textParts[$textPartKey]['headings'][$fieldName] = $text;
                    } 
                }
                
            }

        }
        
        if (!empty($tables)) {
            $this->doc->tables = $tables;
            $this->doc->textParts = $textParts;
        }
        
    }

    
}
