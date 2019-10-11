<?php
require_once __DIR__ . '/../bootstrap.php';

$dl          = new Downloader($app,$logger);
$util        = new Util($logger);
$scraper     = new Scraper($logger, $dl, $util);

try 
{
    if(!$dl->APICheckOK())
    {
        throw new Exception('APIs health check failed');
    }

    $logger->info('*******************************************');
    $logger->info( (PROD? 'Production mode' : 'Developper mode'));
    $logger->info('Memory usage (MB): ' . round( memory_get_peak_usage()/1000000 )  );

    $scraper->getDocumentList();
    printf('Memory usage (MB) after creating docList: %s' . PHP_EOL , round( memory_get_peak_usage()/1000000 ) );
    $logger->info('Memory usage (MB) after creating docList: ' . round( memory_get_peak_usage()/1000000 ) );
    
    if ( $dl->storeFile($app->storage, 'core/docList.txt', $scraper->docList) )
    {
        echo 'succes!';
    };
    $logger->info('Memory usage (MB) at end of script: ' . round( memory_get_peak_usage()/1000000 ) );
    $logger->info('Script END');

} catch(Exception $e) 
{
    $logger->error('Job failed: ' . $e->getMessage());
}
