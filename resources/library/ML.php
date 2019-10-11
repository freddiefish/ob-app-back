<?php

    class Ml {

        const TRESHHOLD = 3;
        const MIN_DOCUMENT_FREQUENCY = 2;
        public $noNewTerms = 0;
        public $freqTerms = [];

        public function __construct(App $app, $logger){
            $this->app = $app;
            $this->logger = $logger;
        }
        
        /** 
         * Clean (does trim) a text into a array of terms, default on unique terms 
         * @param string    fullText
         * @param bool  unique
         * @return  array   Array of terms
         * @todo clean text on text level first
         * split cases like "GoedkeuringMotiveringAanleiding"
         *      also get bi-grams
        */

        public function getTerms($fullText, $unique = true){
            
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
        
            $this->logger->info('getTerms: found ' .  count($cleanTerms) . ' terms');
        
            return $cleanTerms;
        
        }

        
        /**
         * takes ratio sample of given list with given limit (if limit = 0, no limit applied)
         * @param array array
         * @param int   ratio
         * @param   int limit
         * @return array sample
         */

        public function getSample ($array, $limit){
            $randarray = [];
            $cases = count($array);
            $randDocKeys = array_rand($array,$limit);
            foreach($randDocKeys as $key) {
                array_push($randarray, $array[$key]);
            }

            $this->logger->info('Sampled ' . $limit . " from " . $cases . " cases");
            
            return $randarray;
        
        }

        
        /**
         * given a file path, and input array, read a file and returns a list of published doc ids
         * @param   string   filename
         * @param   array    docIdList
         * @return  mixed    docIdList, bool
         */
        
        function getDocIdList($docList) 
        {        
            $docIdList = array();
            foreach ($docList as $doc) 
            {
                if ($doc['published']) 
                {
                    $docId = $doc['id'] ;
                    array_push($docIdList , $docId);
                }
            }
            $this->logger->info('getDocIdList: put ' . count ($docIdList) . " docIds in docIDList"); 
            return $docIdList;
        }

        
        /**
         * takes terms, if a term is new, adds it to index of terms
         * @param   array   terms
         * @param   array   index
         * @return  array   index
         */
        
        public function addToIndex($terms, $index, $unique = true){

            $i= 0;
        
            foreach($terms as $key=>$val) {
                if ( !in_array($val, $index) && $unique) {
                    array_push ($index , $val); 
                } else if (!$unique) {
                    array_push ($index , $val);
                }
                $i = $i + 1;
            }
            
            if ($i == 0){
                $this->noNewTerms++;
                $this->logger->info('addToIndex: ' . $this->noNewTerms . ' iterations with no new terms added');
            }
            $this->logger->info('addToIndex: added ' . $i . " new terms");
        
            return $index;
        
        }

        /**
         * takes a array with text and weight, calculates the normalised frequency of terms in text, its relevance against all array in db, and the position of the terms in the text
         * @param array data
         * @param string id 
         * @return array freqAnalysis
         */

        public function freqAnalysis($data){
            
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
                    $termsChild = $this -> getTerms($text, false);
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

        /** show frequecy of each term in a string
         * @param string $text
         * @return string $freqText
         */

        function textWithFreqTerms($text)
        {
            $freqText = '';
            $terms = $this->getTerms($text, false);
            foreach($terms as $term)
            {
                $freqText  .= $term . '(' . round( ($this->freqTerms[$term] / count ($this->freqTerms)) *100)  . '%) ';
            }
            return $freqText;
        }

        /** get the most frequent terms, above treshhold %
         * @param int treshold percentage
         * @return array topFreqTerms
         */

        function mostFreqTerms($treshhold) 
        {
            $total = count($this->freqTerms);
            $normalizeFactor = 1 / ( max( $this->freqTerms ) / $total ); 
            foreach( $this->freqTerms as $key=>$term ) {
                $percTerm = round( ( ($term / $total) * 100 ) * $normalizeFactor);
                if ($percTerm >= $treshhold) 
                {
                    echo $key . ': ' . $percTerm . '%<br>';
                }
            }
        }

        /**
         * checks if any terms are in a given array
         * @param array terms
         * @param array refArray
         * @return boolean 
         */

        function inRefArray($terms, $refArray)
        {
            foreach ($refArray as $refItem)
            {
                foreach($terms as $term)
                {
                    if (strpos($term, $refItem) !== false) return true;
                }

            }
            
            echo 'No matching for: ';
            foreach($terms as $term)
            {
                echo $term . ' ';
            }
            echo '<br>';
        }

    }