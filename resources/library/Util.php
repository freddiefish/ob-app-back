<?php
    class Util {

        /**
         * do_curl, mail
         */

        /**
         * takes a path and an array to store to file and retruns true
         * @param   string  path
         * @param   array   list   
         * @return  bool    
         */

        public function store_file($path, $list){

            $serializedData = serialize($list);
        
            // save serialized data in a text file
            if ( is_int ( file_put_contents($path, $serializedData) ) ) return true;
        
        }

        /**
         * Takes a path and reads a file into array or returns false
         * @param   string  path
         * @param   array   list
         * @return  mixed list or empty array   
         */

        public function read_file($path){

            if (file_exists ( $path )) {
            
                $string_data = file_get_contents($path);
                $list = unserialize($string_data);  

                return $list;

            } else {
                return array();
            }
        
        }

        
        /**
         * returns the total number of documents in a firestore collection
         * @param string    db
         * @param string    collection
         * @param mixed    query
         * @return  int numberDocs
         */
        
        public function get_number_docs($db, $collection, $query) {

            $numberDocs = 0;
            $docRef = $db->collection($collection);
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

    }