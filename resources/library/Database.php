<?php

class Database {

    /**
     * returns the total number of documents in a firestore collection
     * @param string    firestore
     * @param string    collection
     * @param mixed    query
     * @return  int numberDocs
     */
    
    public function getNumberDocs($firestore, $collection, $query) {

        $numberDocs = 0;
        $docRef = $firestore->collection($collection);
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
     * @param object    firestore
     * @param string    collection
     * @param int       limit
     * @return  array   documents
     */

     public function getSample($firestore, $collection, $limit) {

        $util = new Util();
        $random = $util -> getRandomString(12);
        $lowValue = "aaaaaaaaaaaa";
        $collectionRef = $firestore->collection($collection);
        $query = $collectionRef->where('random', '>', $random)->orderBy('random')->limit($limit);
        $documents = $query->documents();
 /*        foreach($documents as $document) {
            if (!$document->exists()) { // query against lowest value
                $query = $collectionRef->where('random', '>', $lowValue)->orderBy('random')->limit($limit);
                $documents = $query->documents();
            }
        } */
        
        // reseed
        return $documents;
        
     }

}