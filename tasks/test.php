<?php
require_once __DIR__ . '/../bootstrap.php';


$doc = new Document();
$filter = new Filter($doc);
$extract = new Extractor($app,$doc,$filter);


$doc->id = '17.0512.4655.8288';
$text = $extract->document($doc->id);
echo $text;

// (?<=[a-z:])(?=[A-Z]|['][A-Z][a-z])