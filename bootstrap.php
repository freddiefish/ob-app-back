<?php
require_once __DIR__ . '/resources/config.php';
require_once ROOT_DIR . '/vendor/autoload.php';
include ROOT_DIR . '/autoload.php';
include ROOT_DIR . '/logger.php';

$app = new App($config);

echo 'Welcome ' . $app->name . "\n";