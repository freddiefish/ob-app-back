<?php
require_once __DIR__ . '/../bootstrap.php';

use Google\Cloud\Firestore\FirestoreClient;

$db = new FirestoreClient();
$ml = new Ml($app);
$util = new Util;

$decicionsRef = $db->collection('decisions');
$query = $decicionsRef->orderBy('date')->limit(1);
$documents = $query->documents();

foreach ($documents as $document) {

        $freqAnalysis = [];
        $results = [];
        $mostRelTermsResults = [];
        $sortedResults = [];

        if ($document->exists()) {
                $data = $document->data();
        } else {
                printf('Document %s does not exist!' . PHP_EOL, $document->id());  
        }
       
        $dataToAnalyze = [
                [
                        'text' => $data['title'],
                        'weight' => 2
                ],
                [
                        'text' => $data['fullText'],
                        'weight' => 1
                ]
        ];
                
        $query = null;
        $totNumberDocs = $util -> get_number_docs($db, 'decisions',$query);
        $freqAnalysis = $ml -> freqAnalysis($dataToAnalyze, $data['docId']);

        foreach($freqAnalysis as $key=>$val){
                $IDF = 1; // http://www.tfidf.com
                $query = ['field' => 'terms.' . $key, 'operator' => '<', 'value' => 1];
                $overalTermFreq = $util -> get_number_docs($db, 'decisions',$query);
                if ($overalTermFreq <> 0) {
                        $IDF = log($totNumberDocs / $overalTermFreq);
                }
                
                $relevance = $val + $IDF;
                $dataFreq = [
                        'count' => $val,
                        'relevance' => $relevance,
                        'term' => $key
                ];
                array_push($results, $dataFreq);
        }
        
        $sortedResults = $results;
        //sort on relavance
        usort($sortedResults, function($a, $b) {
                return -($a['relevance'] <=> $b['relevance']); // descending order
        });

        $mostRelTerms = [];
        $mostRelTerms = array_slice($sortedResults, 0, 10); // get most relevant ones
        //search position of most relevant terms
        $text = $data['fullText'];
        foreach($mostRelTerms as $key=>$val){
                $needle = $val['term'];
                $lastPos = 0;
                $positions = [];

                while (($lastPos = strpos($text, $needle, $lastPos)) !== false) {
                        $positions[] = $lastPos;
                        $lastPos = $lastPos + strlen($needle);
                }
                
                $finalData = [
                        'count' => $val['count'],
                        'relevance' => $val['relevance'],
                        'position_in_text' => $positions,
                        'term' => $val['term']
                ];
                array_push($mostRelTermsResults, $finalData);
        }
        
        unset($sortedResults);

        $decisionRef = $db->collection('decisions')->document($document->id());
        $decisionRef->update([
                ['path' => 'terms', 'value' => $mostRelTermsResults]
                ]);
        $app->log('Updated the terms field for doc ' . $document->id() );
        // set tagged true? in documents
}
