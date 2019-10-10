<?php

use Google\Cloud\Firestore\FirestoreClient;
use Google\Cloud\Core\Timestamp;
use Google\Cloud\Core\GeoPoint;

class Database 
{
    public $firestore;
    public $app;
    public $logger;

    public function __construct($app, $logger) 
    {
        $this->app = $app;
        $this->logger = $logger;
    }

    public function storeDoc($doc, $locations) 
    {
        $this->firestore = new FirestoreClient();

            $data = $doc;
            $ID = $this->addDocument('decisions',$data); // returns firestore ID
            
            if (!empty($locations)) 
            {
                $decisionRef = $this->firestore->document('decisions/' . $ID);

                foreach ($locations as $location) 
                {
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


    function addDocument($collection,$data) 
    {
        $addDoc = $this->firestore->collection($collection)->newDocument();
        $ID = $addDoc->id();
        $this->logger->info('Added ' . $collection . '>document with ID: ' . $ID);
        $addDoc->set($data);
    
        return $ID;
    }  
    
    function updateDocument($collection,$ID,$query)
    {
        $updateRef = $this->firestore->collection($collection)->document($ID);
        $updateRef->update($query);
    
        $this->logger->info('Updated hasGeoData field in ' . $collection . '>document with ID: ' . $ID);
    
    }


    public function docExists($field, $op, $val) 
    {
        $this->firestore = new FirestoreClient();
        $decicionsRef = $this->firestore->collection('decisions');
        $query = $decicionsRef->where($field, $op , $val);
        $documents = $query->documents();
        foreach ($documents as $document) {
            if ($document->exists()) {
                $this->logger->info('Document ' . $document->id()  . ' returned by query');
                return true;
            } else {
                $this->logger->info('Document ' . $document->id() . ' does not exist' ); 
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
    
    public function getNumberDocs($collection, $query) 
    {
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

     public function getSample( $collection, $limit) 
     {
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
