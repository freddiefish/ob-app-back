<?php
require_once __DIR__ . '/../bootstrap.php';
$ml = new Ml($app);
$util =  new Util();

$randDocs = [];
$offCategories = [];
$titles = [];
$index = [];
$gluedText = '';
$docsArray = $util->readFile('/Users/Main/Apps/ob-app-back/resources/storage/processed/doclist.txt');
$randDocs = $ml->getSample($docsArray, 5000);

foreach($randDocs as $doc) {
    $title = trim ( strip_tags( $doc['title'] ) );
    $pieces = explode(' - ', $title);
    $offCategory = trim ($pieces[0]);
    $terms = $ml->getTerms($title, false);
    $index = $ml->addToIndex($terms, $index, false);
    array_push($titles, $title);
    array_push($offCategories, $offCategory);

    $gluedText .= $title . ' ';
}

$data = [['text' => $gluedText, 'weight' => 1]];
$normTermFreqList = $ml->freqAnalysis($data);
$freqTerms = array_count_values($index);
// ksort($freqCats);
asort($titles);

foreach($freqCats as $key=>$val) {
    echo "'" . addslashes($key) . "', {$val}<br>";
}




function addCorpus($offCategory) {

}

$cats = array('Kinderopvang');