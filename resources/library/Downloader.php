<?php

    class Downloader {

        private $app;

        public function __construct($app){
            $this -> app = $app;
        }

        /**
         * curl a url and return data
         * @param   string  url
         * @return  string  data
         */

        public function doCurl($url) {

            $ch = curl_init();
            $timeout = 300; // https://curl.haxx.se/libcurl/c/CURLOPT_CONNECTTIMEOUT.html
            
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            //Disable CURLOPT_SSL_VERIFYHOST and CURLOPT_SSL_VERIFYPEER by
            //setting them to false.
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
            $data = curl_exec($ch);
            if (curl_errno($ch)) {
                $errorMsg = curl_error($ch);
                $this -> app -> log('doCurl: ' . $errorMsg);
            }
            curl_close($ch);

            if (isset($errorMsg)) {

            }
            
            return $data;
        }

        /**
         * checks if a doc exists in staorage directory, else will download it
         * @param string docId
         * @return void
         * @todo google buckets for remote
         */
        
        public function downloadDoc($docId){
    
            $fileName= '_besluit_' . $docId . '.pdf';
            $path = $this -> app -> pubDir . '/' . $fileName;
        
            // dwonload if PDF is in local directory
            if (!file_exists($path)) {
        
                $URL = API_BASE_DIR . '/publication/' . $docId .'/download';
                $file__download= $this -> doCurl($URL);
                file_put_contents($path, $file__download); 
            }
            
        }

        /**
         * takes a list of referenced docs, and downloads them to local storage
         * @param array list
         * @return void pdfs in local storage folder
         */

        public function downloadDocs($list) {
            foreach($list as $item) {
                $this->downloadDoc($item['docId']);
            }
        }

        public function __destruct(){}

    }