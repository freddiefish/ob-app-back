<?php
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
    $URL = API_BASE_DIR . '/publication/' . $id .'/download';
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
