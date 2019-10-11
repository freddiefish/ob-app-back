<?php
/**
 * build an terms index
 * becomes a cron job updated daily for new terms
 * TODO bigrams
 */

require_once __DIR__ . '/../bootstrap.php';

$docIdList  = array();
$index      = array();

$docTerms =     new Ml($app);
$util =         new Util();
$extractor =    new Extractor($config);
$dl =           new Downloader($config);

$docListPath        = $app->procDir . '/doclist-dev.txt'; //40.000 docs 
$indexpath          = $app->procDir . '/index.txt';
$randDocListPath    = $app->procDir . '/randDocs.txt';

$docList = $util->readFile($docListPath);
$docIdList = $docTerms->getDocIdList($docList);

$index = $util->readFile($indexpath);
echo 'Read in: ' . count($index) . ' indexes ';
$randDocRefs = $docTerms->getSample($docIdList, 2, 10);
$dl->storeFile($randDocListPath ,  $randDocRefs);

foreach($randDocRefs as $key=>$val){

    $dl->downloadDoc($val);
    $text = $extractor->extractText($val);
    $terms = $docTerms->getTerms($text['fullText'], $val, false);
    $index = $docTerms->addToIndex($terms, $index);  
    echo ('This iteration, index has ' . count($index) . ' indexes ');
}

$dl->storeFile($app->procDir . '/index.txt', $index);
echo ('At end of script, index has ' . count($index) . ' indexes ');
