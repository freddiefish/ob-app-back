<?php
require_once __DIR__ . '/../bootstrap.php';


$doc = new Document();
$filter = new Filter($doc);
$util = new Util();
$extract = new Extractor($app,$doc,$filter,$util);


$doc->id = '17.0804.7360.9337';
$doc->title = 'Titel';
$text = $extract->document($doc->id);
// echo $text;

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
