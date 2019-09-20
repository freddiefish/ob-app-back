<?php
/**
 * build an terms index
 * becomes a cron job updated daily for new terms
 * TODO bigrams
 */

require_once __DIR__ . '/../bootstrap.php';

$docIdList  = array();
$index      = array();

$docTerms = new Ml($app);
$util = new Util();

$docListPath        = $app->procDir . '/doclist-dev.txt'; //40.000 docs 
$indexpath          = $app->procDir . '/index.txt';
$randDocListPath    = $app->procDir . '/randDocs.txt';

$docList = $util->readFile($docListPath);
$docIdList = $docTerms->get_docId_list($docList);

$index = $util->readFile($indexpath);
echo 'Read in: ' . count($index) . ' indexes ';
$randDocRefs = $docTerms->get_sample($docIdList, 2, 10);
$util->storeFile($randDocListPath ,  $randDocRefs);

foreach($randDocRefs as $key=>$val){

    $docTerms->download_doc($val);
    $text = $docTerms->extract_text($val);
    $terms = $docTerms->getTerms($text['fullText'], $val, false);
    $index = $docTerms->add_to_index($terms, $index);  
    echo ('This iteration, index has ' . count($index) . ' indexes ');
}

$util->storeFile($app->procDir . '/index.txt', $index);
echo ('At end of script, index has ' . count($index) . ' indexes ');
