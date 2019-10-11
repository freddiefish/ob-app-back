<?php

    class App {

            public $name;
            public $baseUrl;
            public $storage;
            public $mode;

            public function __construct($config){
                $this -> name = $config['name'];
                $this -> baseUrl = $config['urls']['baseUrl'];
                $this -> storage = $config['paths']['storage'];
                $this -> mode = $config['mode'];

                unset($config);
            }
    
    }