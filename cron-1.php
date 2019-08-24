<?php
// TODO fulltext must include title

// Use the composer autoloader to load dependencies.
require __DIR__ . '/vendor/autoload.php';

phpinfo();
exit;

// Parse pdf file and, cleanup, build necessary objects: fulltext, background, decision, every location
$text ='';

$parser = new \Smalot\PdfParser\Parser();
$pdf    = $parser->parseFile('https://ebesluit.antwerpen.be/publication/19.0802.4796.5619/download');

$text = $pdf->getText();
$arrayText = explode('Bijlagen', $text);
$trimedFooterText = $arrayText[0];


$arrayText = explode('Aanleiding en context', $trimedFooterText);
$trimedText = $arrayText[1];

//get out some elements
$stripTxt = "Grote Markt 1 - 2000 Antwerpen	
info@antwerpen.be";

$clean1Txt = str_replace($stripTxt,' ',$trimedText);

//get fulltext
$fulltext = $clean1Txt;

//get background
$arrayBGText = explode('Juridische grond', $clean1Txt);
$background = $arrayBGText[0];

// get decision
$arrayBGText = explode('Besluit', $clean1Txt);
$decision = $arrayBGText[1];

// get all the  locations 
echo $fulltext;
// $trimedHeadText = substr($text,strpos($text,'Aanleiding en context') + 1 );
//echo $trimedHeadText; 
?>