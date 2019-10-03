<?php
require_once __DIR__ . '/../bootstrap.php';


$doc = new Document();
$util = new Util();
$filter = new Filter($doc,$util);
$dl = new Downloader($app);
$extract = new Extractor($app,$dl,$doc,$filter,$util);


$doc->id = '17.0502.9875.6824';
$doc->title = 'Titel';
$extract->document($doc->id);

foreach($doc->textParts as $textPart) {
    echo "<h2>{$textPart['name']}</h2>";
    echo $textPart['text'];
    if (isset($textPart['headings'])) {
        foreach($textPart['headings'] as $key=>$val) {
            echo "<h3>{$key}</h3>{$val}";
        }
    }
}

try {
    if (empty($doc->addenda)) {
        throw new Exception('No addenda found');
    }

    foreach($doc->addenda as $addendum) {
        echo "{$addendum} <br>";
    }
}

catch (Exception $e) {
    $app->log( $e->getMessage() );
}
