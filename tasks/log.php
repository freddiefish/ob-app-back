<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../functions.php';

$file = LOG_PATH; 

header("Content-Disposition: attachment; filename=\"" . basename($file) . "\"");
header("Content-Type: application/octet-stream");
header("Content-Length: " . filesize($file));
header("Connection: close");
