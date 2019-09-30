<?php
require_once __DIR__ . '/../bootstrap.php';
$ml = new Ml($app);
$util =  new Util();

$randDocs = [];

$docsArray = $util->readFile('/Users/Main/Apps/ob-app-back/resources/storage/processed/doclist.txt');

$randDocs = $ml->getSample($docsArray, 100);

// $docs = $ml->getSample($docsArray,2,100);
$docs = $util->array_sort($randDocs, 'title', SORT_DESC);

foreach($docs as $doc) {
    echo $doc['title'] . "\n";
}