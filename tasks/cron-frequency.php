<?php
// Use the composer autoloader to load dependencies. On GC App Engine paths are different
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../functions.php';

set_time_limit(300);

use Google\Cloud\Firestore\FirestoreClient;

// Create the Cloud Firestore client
$db = new FirestoreClient();

$totNumbDocs = 0;
$tagRef = $db->collection('tags');
$query = $tagRef;
$documents = $query->documents();
foreach ($documents as $document) {
    if ($document->exists()) { 
            $data = $document->data();
            $totNumbDocs++;
    } else {
            printf('Document %s does not exist!' . PHP_EOL, $document->id());  
    }
}

var_dump($totNumbDocs);
    
foreach ($documents as $document) {
        if ($document->exists()) { 
                $data = $document->data();
        } else {
                printf('Document %s does not exist!' . PHP_EOL, $document->id());  
        }
        
        foreach($data['terms'] as $key=>$value){
                $dbTermOccur = 0;
                echo $key;
                $containsQuery = $tagRef->where('terms.' . $key, '<', 1);
                foreach($containsQuery->documents() as $tagDocument) {
                        $dbTermOccur++;
                }
                // echo ':' . $dbTermOccur . '<br>';
        
                var_dump($dbTermOccur);
                $IDF = log($totNumbDocs / $dbTermOccur);
                var_dump($IDF);
                $Tfidf = $value * $IDF;
                var_dump($Tfidf); // http://www.tfidf.com
        }

}   





