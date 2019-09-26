<?php

    class Ml {

        const TRESHHOLD = 3;
        const MIN_DOCUMENT_FREQUENCY = 2;
        var $noNewTerms = 0;

        public function __construct(App $app){
            $this->app = $app;
        }
        
        /** 
         * Clean a text into a array of terms, unique terms or not
         * @param string    fullText
         * @param string    docId
         * @param bool  unique
         * @return  array   Array of terms
         * @todo clean text on text level first
         * split cases like "GoedkeuringMotiveringAanleiding"
         *      also get bi-grams
        */

        public function getTerms($fullText, $docId, $unique){
            
            $punctures = array (';','\'','',':','-', ',' , '.' , '/',')','('); // case "Stad." -> clean to "Stad"
            $cleanTerms =array();
        
            $terms = explode(' ', $fullText);
            
            $i = 0;
            foreach($terms as $term) {
    
                $strip = $term;
                $strip1 = preg_replace( "/\r|\n/", "", $strip ); // remove empty lines
                $strip2 = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $strip1);
                $term = preg_replace('/[[:digit:]]/', "", $strip2);
                $term = trim($term); // removes any whitespace
                
                // trim punctures . / , -
                $term = str_replace($punctures , '', $term); 
                // var_dump($term);
                /*$matches = array();
                split cases: "GoedkeuringMotiveringAanleiding"
                 preg_match_all('/([A-Z]{1})[a-z]+/', $term, $matches); 
                if (!empty($matches)) {
                    var_dump($matches);
                    // echo val($matches[0]);
                    unset($matches);    
                } */
                
                // make lowercase
                $term = strtolower($term);
                // remove dirty element
                unset($terms[$i]); 
                // add clean element
                if (strlen($term) <> 1) {// remove string with 1 character 'a', 'b', ....
                    array_push($terms, $term) ;        
                }    
                $i++;
                    
            }
    
            $termsClean1 = array_filter($terms, function($var) {
                $ignTermsStandard = file(LIBRARY_PATH . '/stopwords_nl.txt', FILE_IGNORE_NEW_LINES); 
                return !in_array($var,$ignTermsStandard);
            });
    
            $termsClean2 = array_filter($termsClean1, function($var){
                if ( 0 === preg_match('~[0-9]~', $var) ) return true;
            });
    
            foreach($termsClean2 as $termClean){
                array_push($cleanTerms,$termClean);
            }
        
            // remove empty or duplicates
            if ($unique) {
                $cleanTerms = array_unique($cleanTerms);
            }
            $cleanTerms = array_filter($cleanTerms);
        
            $this->app->log('Found ' .  count($cleanTerms) . ' terms in doc ' . $docId);
        
            return $cleanTerms;
        
        }
        
        /**
         * takes ratio sample of given list with given limit (if limit = 0, no limit applied)
         * @param array array
         * @param int   ratio
         * @param   int limit
         * @return array sample
         */

        public function getSample ($array, $ratio , $limit){

            $numCases = count($array);
            $sample = array_intersect_key( $array, array_flip( array_rand( $array, ($numCases / $ratio) ) ) ) ;
            if($limit <> 0) { // we have a limit
                $sample = array_slice($sample, 0, $limit);
            }
            $this->app->log('Sampled ' . count ($sample) . ' cases');
            
            return $sample;
        
        }

        
        /**
         * given a file path, and input array, read a file and returns a list of published doc ids
         * @param   string   filename
         * @param   array    docIdList
         * @return  mixed    docIdList, bool
         */
        
        function getDocIdList($docList) {
             
            $docIdList = array();

            foreach ($docList as $doc) {
                if ($doc['published']) {
                    $docId = $doc['id'] ;
                    array_push($docIdList , $docId);
                }
            }

            $this->app->log('Stored ' . count ($docIdList) . ' docs in docIDList'); 
    
            return $docIdList;
            
        }

        
        /**
         * takes terms, if a term is new, adds it to index of terms
         * @param   array   terms
         * @param   array   index
         * @return  array   index
         */
        
        public function addToIndex($terms, $index){

            $i= 0;
        
            foreach($terms as $key=>$val) {
                if ( !in_array($val, $index)) {
                    $i = $i + 1;
                    array_push ($index , $val); 
                } else {
                    // $this->app->log($val . ' already in index');
                }
            }
            
            if ($i == 0){
                $this->noNewTerms++;
                $this->app->log($this->noNewTerms . ' iterations with no new terms added to index');
            }
            $this->app->log('Added ' . $i . ' new terms to index');
        
            return $index;
        
        }

        /**
         * takes a text, calculates the normalised frequency of terms in text, its relevance against all docs in db, and the position of the terms in the text
         * @param array data
         * @param string id 
         * @return array freqAnalysis
         */

        public function freqAnalysis($data, $id){
            
            $terms = [];
            $termsChild = [];
            $normTermFreqList = [];
            $text = '';
            $weight = 0;
            
            
            foreach($data as $row){
                
                $text = $row['text'];
                $weight = $row['weight'];

                $i = 0;
                while($i < $weight) {
                    $termsChild = $this -> getTerms($text, $id, false);
                    foreach($termsChild as $termChild) {
                        array_push($terms, $termChild);
                    }
                
                    $i++;
                }
                
            }
            

            $termFreqList = array_count_values($terms);
            // apply  MIN_DOCUMENT_FREQUENCY = 2
            // normalize against total number of terms in doc
            $totalNrTermsInDoc = array_sum ($termFreqList);   
            
            foreach($termFreqList as $key=>$value) {
                    $normTermFrequency = $value/$totalNrTermsInDoc;
                    // var_dump($normTermFreqListuency);
                    $key = utf8_encode($key);
                    $normTermFreqList[$key] = $normTermFrequency;
            
            }  
            
            arsort($normTermFreqList);
            // var_dump($normTermFreqList);
            return $normTermFreqList;
        
            unset($normTermFreqList);
            unset($termFreqList);
        
        }

        /**
         * stores a .csv file that has matched terms for every document
         * @param string documents
         * @return void 
         */

        public function getMatchedTerms($documents) {

            foreach($documents as $document) {
                $text = $documents['fullText'];
                $this->getTerms($text, 'analysis', $unique);
            };
        }

        

    }