<?php
require_once __DIR__ . '/../bootstrap.php';

$util        = new Util($logger);
$filter      = new Filter($util);
$dl          = new Downloader($app,$logger);
$extractor   = new Extractor($app,$dl,$filter,$util,$logger);

try 
{
    if(!$dl->APICheckOK())
    {
        throw new Exception('APIs health check failed');
    }

    $logger->info('*******************************************');
    $logger->info( (PROD? 'Production mode' : 'Developper mode'));
    $logger->info('Memory usage (MB): ' . round( memory_get_peak_usage()/1000000 )  );

    $extractor->getDocumentList();
    printf('Memory usage (MB) after creating docList: %s' . PHP_EOL , round( memory_get_peak_usage()/1000000 ) );
    $logger->info('Memory usage (MB) after creating docList: ' . round( memory_get_peak_usage()/1000000 ) );
    $dl->downloadDocs($extractor->docList);
    printf('Memory usage (MB) after downloading docList: %s' . PHP_EOL , round( memory_get_peak_usage()/1000000 ) );
    $logger->info('Memory usage (MB) after downloading docList: ' . round( memory_get_peak_usage()/1000000 ) );

    foreach($extractor->docList as $item) 
    {
        $docExtractor = new Extractor($app,$dl,$filter,$util,$logger);
        $db = new Database($app,$logger);

        $docExtractor->doc['docId']         = $item['docId'];
        $docExtractor->doc['offTitle']      = $item['offTitle'];
        $docExtractor->doc['title']         = $item['title'];
        $docExtractor->doc['intId']         = $item['intId'];
        $docExtractor->doc['eventDate']     = $item['eventDate'];
        $docExtractor->doc['groupId']       = $item['groupId'];
        $docExtractor->doc['groupName']     = $item['groupName'];
        $docExtractor->doc['published']     = $item['published'];
        $docExtractor->doc['sortIndex1']    = $item['sortIndex1'];
        $docExtractor->doc['decision']      = '';

        if (!$db->docExists('docId', '==', $item['docId'])) 
        {
            if ($docExtractor->doc['published']) 
            {
                $docExtractor->document($item['docId']);
                $keyBGroundPart  = $util->multiDimArrayFindKey($docExtractor->doc['textParts'], 'id', 1);
                $docExtractor->doc['background'] = $docExtractor->doc["textParts"][$keyBGroundPart]["text"];
                $keyDecisionPart = $util->multiDimArrayFindKey($docExtractor->doc['textParts'], 'id', 11);
                foreach( $docExtractor->doc["textParts"][$keyDecisionPart]["headings"] as $key => $val ) 
                {
                    $docExtractor->doc['decision'] .=  $key . ' ' . $val ;
                }
            }
        $db->storeDoc($docExtractor->doc);
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
