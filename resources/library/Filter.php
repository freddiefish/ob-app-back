<?php

/**
 * Remove extract whitespaces
 * @author dcardin
 */
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
        $replText = preg_replace('/Grote Markt 1 - 2000 Antwerpen\s*\ninfo@stad.antwerpen.be\h/', '', $text);
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

    function tableFilter($text) {
        // https://kbopub.economie.fgov.be/kbopub/zoeknummerform.html?nummer=0403+503+568
        
        $startTable =[];
        $textTable = '';
        $startTable = preg_split('/(?<=volgt:<\/p>)(?=.+Omschrijving.+Bedrag.+Boekingsadres)/', $text);

        if(count($startTable) > 1) {
            $placeholder = '<p>Gegevens onderneming</p>
            <p>< Tabel: zie PDF ></p>';
            $filteredText = $startTable[0] . $placeholder;
            $textTable = $startTable[1];
            $delimter = 'Bijlagen';
            if (strpos($textTable, $delimter) ) {
                $pieces = explode($delimter, $textTable);
                $textTable = $pieces[0];
                $filteredText .= $pieces[1];
            }
            // store it
        } else {
            $filteredText = $startTable[0];
        }
        
        return array($filteredText,$textTable);
    }

}
