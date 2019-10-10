<?php
require_once __DIR__ . '/../bootstrap.php';

$dl          = new Downloader($app,$logger);
$util        = new Util($logger);
$filter      = new Filter($util);

try 
{
    if(!$dl->APICheckOK())
    {
        throw new Exception('APIs health check failed');
    }

    $logger->info('*******************************************');
    $logger->info( (PROD? 'Production mode' : 'Developper mode'));
    $logger->info('Memory usage (MB): ' . round( memory_get_peak_usage()/1000000 )  );

    $docList = $dl->readFile($app->storage, 'core/docList.txt');
    foreach($docList as $item) 
    {
        $docExtractor   = new Extractor($app,$dl,$filter,$util,$logger);
        $db             = new Database($app,$logger);

        $docExtractor->doc['docId']         = $item['docId'];
        $docExtractor->doc['offTitle']      = $item['offTitle'];
        $docExtractor->doc['title']         = $item['title'];
        $docExtractor->doc['intId']         = $item['intId'];
        $docExtractor->doc['date']          = $item['eventDate'];
        $docExtractor->doc['groupId']       = $item['groupId'];
        $docExtractor->doc['groupName']     = $item['groupName'];
        $docExtractor->doc['published']     = $item['published'];
        $docExtractor->doc['sortIndex1']    = $item['sortIndex1'];
        $docExtractor->doc['hasGeoData']    = false;

        if (!$db->docExists('docId', '==', $item['docId'])) 
        {
            if ($docExtractor->doc['published']) $docExtractor->document($item['docId']);
            else $docExtractor->locations( $inTitleOnly = true ); // unpublished doc
        
            $db->storeDoc($docExtractor->doc, $docExtractor->locations);
            unset($docExtractor);
            gc_collect_cycles();
        }
        printf('Memory usage (MB) after extracting & storing docId %s: %s' . PHP_EOL , $item['docId'], round( memory_get_peak_usage()/1000000 ) );
        $logger->info('Memory usage (MB) after extracting & storing docId ' . $item['docId'] . ': ' . round( memory_get_peak_usage()/1000000 ));
        
    }
    $logger->info('Memory usage (MB) at end of script: ' . round( memory_get_peak_usage()/1000000 ) );
    $logger->info('Script END');

} catch(Exception $e) 
{
    $logger->error('Job failed: ' . $e->getMessage());
}
