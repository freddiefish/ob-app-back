<?php
require_once __DIR__ . '/../bootstrap.php';


$doc = new Document();
$filter = new Filter($doc);
$extr = new Extractor($app,$doc,$filter);

$doc->id = '17.0505.7158.5708';
$text = $extr->extractDoc($doc->id);
echo $text;

// (?<=[a-z:])(?=[A-Z]|['][A-Z][a-z])