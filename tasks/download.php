<?php
require_once __DIR__ . '/../bootstrap.php';

$dl          = new Downloader($app,$logger);
$util        = new Util($logger);
$filter      = new Filter($util);


$logger->info('*******************************************');
$logger->info( (PROD? 'Production mode' : 'Developper mode'));
$logger->info('Memory usage (MB): ' . round( memory_get_peak_usage()/1000000 )  );

$docList = $dl->readFile($app->storage, 'core/docList.txt'); 
$dl->downloadDocs($docList);
printf('Memory usage (MB) after downloading docList: %s' . PHP_EOL , round( memory_get_peak_usage()/1000000 ) );
$logger->info('Memory usage (MB) after downloading docList: ' . round( memory_get_peak_usage()/1000000 ) );


$logger->info('Memory usage (MB) at end of script: ' . round( memory_get_peak_usage()/1000000 ) );
$logger->info('Script END');
