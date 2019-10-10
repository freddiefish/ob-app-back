<?php
require_once __DIR__ . '/../bootstrap.php';

$dl          = new Downloader($app,$logger);
$util        = new Util($logger);
$filter      = new Filter($util);
$docExtractor   = new Extractor($app,$dl,$filter,$util,$logger);

$id='19.1009.8496.1351';
$docExtractor->document($id);
echo 'Ho';
