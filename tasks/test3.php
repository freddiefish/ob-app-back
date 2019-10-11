<?php
require_once __DIR__ . '/../bootstrap.php';

$dl          = new Downloader($app,$logger);
$util        = new Util($logger);
$filter      = new Filter($util);

$db             = new Database($app,$logger);
$docExtractor   = new Extractor($app,$dl,$filter,$util,$logger);
 
$id = '19.1004.0935.0779';
$docExtractor->doc['title'] = '';
$docExtractor->document($id);