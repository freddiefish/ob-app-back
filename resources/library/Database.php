<?php

use Google\Cloud\Firestore\FirestoreClient;
use Google\Cloud\Core\Timestamp;
use Google\Cloud\Core\GeoPoint;

class Database {


    public $firestore;
    public $app;

    public function __construct($app) {
        $this->app = $app;
    }

    public function storeDoc($doc) {

        $this->firestore = new FirestoreClient();

        if (!$this->docExists('docId', '=', $doc['docId'])) {

            $data = [
                'title' => $doc['title'],
                'offTitle' => $doc['offTitle'],
                'intID' => $doc['intId'], 
                'status' => "NS",
                'background' => $doc["background"],
                'date' => new Timestamp(new DateTime($doc['eventDate'])),
                'decision' => $doc['decision'],
                'docId' => $doc['docId'],
                'fullText' => $doc['title'] ,
                'groupId' => $doc['groupId'],
                'groupName' => $doc['groupName'],
                'published' => $doc['published'],
                'hasGeoData' => false,
                'hasNoRelevance' => true,
                'sortIndex1' => $doc['sortIndex1'],
            ];

            $ID = $this->addDocument('decisions',$data); // returns firestore ID
            
            if (!empty($doc['locations'])) {
                $decisionRef = $this->firestore->document('decisions/' . $ID);

                foreach ($doc['locations'] as $location) {
                    $data = [
                        'decisionRef' => $decisionRef,
                        'formattedAddress' => $location["formattedAddress"],
                        'point' => [
                            'geohash' => $location['geohash'],
                            'geopoint' => new GeoPoint($location['lat'],$location['lng'])
                            ]
                        ];

                        $this->addDocument('locations', $data);
                        //update decision: indicate has Geo Data
                        $this->updateDocument('decisions', $ID, array(['path' => 'hasGeoData', 'value' => true]));
                }
            }
            
            
        }
    }


    function addDocument($collection,$data) {
        
        $addDoc = $this->firestore->collection($collection)->newDocument();
        $ID = $addDoc->id();
        $this->app->log('Added ' . $collection . '>document with ID: ' . $ID);
        $addDoc->set($data);
    
        return $ID;
    }  
    
    function updateDocument($collection,$ID,$query){
        
        $updateRef = $this->firestore->collection($collection)->document($ID);
        $updateRef->update($query);
    
        $this->app->log('Updated hasGeoData field in ' . $collection . '>document with ID: ' . $ID);
    
    }


    public function docExists($field, $op, $val) {
        
        $decicionsRef = $this->firestore->collection('decisions');
        $query = $decicionsRef->where($field, $op , $val);
        $documents = $query->documents();
        foreach ($documents as $document) {
            if ($document->exists()) {
                $this->app->log('Document ' . $document->id()  . ' returned by query');
                return true;
            } else {
                $this->app->log('Document ' . $document->id() . ' does not exist' ); 
                return false;
            }
        }

    }


    /**
     * returns the total number of documents in a firestore collection
     * @param string    collection
     * @param mixed    query
     * @return  int numberDocs
     */
    
    public function getNumberDocs($collection, $query) {

        $numberDocs = 0;
        $docRef = $this->firestore->collection($collection);
        if(!$query == null) {
            $query = $docRef->where($query['field'], $query['operator'], $query['value']);
        } else {
            $query = $docRef;
        }

        $documents = $query->documents();
        foreach ($documents as $document) {
            if ($document->exists()) { 
                    $numberDocs++;
            } else {
                    printf('Document %s does not exist!' . PHP_EOL, $document->id());  
            }
        }

        return $numberDocs;
            
    }

    /**
     * for analysis, samples database and return documents
     * @param string    collection
     * @param int       limit
     * @return  array   documents
     */

     public function getSample( $collection, $limit) {

        $util = new Util();
        $random = $util -> getRandomString(12);
        $lowValue = "aaaaaaaaaaaa";
        $collectionRef = $this->firestore->collection($collection);
        $query = $collectionRef->where('random', '>', $random)->orderBy('random')->limit($limit);
        $documents = $query->documents();
        
        // reseed
        return $documents;
        
     }

}