<?php
require_once __DIR__ . '/../bootstrap.php';
 
// Parse pdf file and build necessary objects.
$parser = new \Smalot\PdfParser\Parser();
$pdf    = $parser->parseFile(ROOT_DIR .'/document.pdf');
 
// Retrieve all pages from the pdf file.
$pages  = $pdf->getPages();
echo 'Memory usage (MB): ' . round( memory_get_peak_usage()/1000000 )  ;
// Loop over each page to extract text.
foreach ($pages as $page) {
    echo $page->getText();
    echo 'Memory usage (MB): ' . round( memory_get_peak_usage()/1000000 )  ;
}
 
