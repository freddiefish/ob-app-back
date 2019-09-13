<?php
// Use the composer autoloader to load dependencies. On GC App Engine paths are different
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../functions.php';

use Google\Cloud\Firestore\FirestoreClient;

// Create the Cloud Firestore client
$db = new FirestoreClient();

$totNumbDocs = 0;
$decicionsRef = $db->collection('decisions');
$query = $decicionsRef->orderBy('title');
$documents = $query->documents();
foreach ($documents as $document) {
        if ($document->exists()) {
                $data = $document->data();
                $totNumbDocs++;
        } else {
                printf('Document %s does not exist!' . PHP_EOL, $document->id());  
        }
        // $title = $data['title'];
        $dataToAnalyze = $data['title'] . ' ' . $data['fullText'];
        $normTermFrequencies = normTermFrequencies($dataToAnalyze);
        var_dump($normTermFrequencies);

        $decisionRef = $db->document('decisions/' . $document->id());
        $data = [
                'decisionRef' => $decisionRef,
                'terms' => $normTermFrequencies
        ];
        add_document('tags',$data);
        // set tagged true? in documents
}
