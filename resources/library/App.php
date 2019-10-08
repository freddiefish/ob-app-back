<?php

    class App {

            public $name;
            public $baseUrl;
            public $pubDir;
            public $procDir;
            public $logDir;
            public $mode;

            public function __construct($config){
                $this -> name = $config['name'];
                $this -> baseUrl = $config['urls']['baseUrl'];
                $this -> pubDir = $config['paths']['publications'];
                $this -> procDir = $config['paths']['processed'];
                $this -> logDir = $config['paths']['log'];
                $this -> mode = $config['mode'];
            }

            public function log($text){

                $msg = date("d-m-Y H:i:s") . ": " . $text ;
                
                if (!$this -> mode) { // write to file only when developer mode
                    $save_path = $this -> logDir . '/log.txt';
                    if ($fp = @fopen($save_path, 'a')) {
                        // open or create the file for writing and append info
                        fputs($fp, "\n$msg"); // write the data in the opened file
                        fclose($fp); // close the file
                    }
                }
                
                // echo $msg . '<br>';

            }



    }