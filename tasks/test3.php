<?php
require_once __DIR__ . '/../bootstrap.php';
$util        = new Util;
$filter      = new Filter($util);
$dl          = new Downloader($app);
$extr   = new Extractor($app,$dl,$filter,$util);

$docId = '17.0720.1614.5153';
$dl->list_buckets();
$dl->downloadDoc($docId);
$extr->text($docId);