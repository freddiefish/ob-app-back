<?php
require_once __DIR__ . '/../bootstrap.php';

$util        = new Util;
$filter      = new Filter($util);
$dl          = new Downloader($app);
$extractor   = new Extractor($app,$dl,$filter,$util);

try 
{
    if(!$extractor->APICheckOK())
    {
        throw new Exception('APIs health check failed');
    }

    $app->log('*******************************************');
    $app->log( (PROD? 'Production mode' : 'Developper mode'));
    $app->log('Memory usage (Kb): ' . memory_get_peak_usage()/1000);

    $extractor->getDocumentList(4);
    $dl->downloadDocs($extractor->docList);

    foreach($extractor->docList as $item) 
    {
        $docExtractor = new Extractor($app,$dl,$filter,$util);
        $db = new Database($app);

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
        
    }
    $app->log('Script END');

} catch(Exception $e) 
{
    $this->app->log('Document scraping failed: ' . $e->getMessage());
}
