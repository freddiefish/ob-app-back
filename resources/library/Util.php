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

        public function storeFile($path, $list){

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

        public function readFile($path) {

            if (file_exists ( $path )) {
            
                $string_data = file_get_contents($path);
                $list = unserialize($string_data);  

                return $list;

            } else {
                return array();
            }
        
        }

        function getRandomString($num) { 
            
            $final_string = ""; 
            //Range of values used for generating string
            $range = "ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890"; 
            // Find the length of created string 
            $length = strlen($range); 
            
            // Loop to create random string 
            for ($i = 0; $i < $num; $i++) { 
                // Generate a random index to pick chars
                $index = rand(0, $length - 1); 
                // Concatenating the character in resultant string 
                $final_string.=$range[$index]; 
            } 
            
            return $final_string; 
        }


    }