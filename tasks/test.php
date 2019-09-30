<?php
require_once __DIR__ . '/../bootstrap.php';


$doc = new Document();
$filter = new Filter($doc);
$util = new Util();
$extract = new Extractor($app,$doc,$filter,$util);


$doc->id = '16.1222.2498.9307';
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
