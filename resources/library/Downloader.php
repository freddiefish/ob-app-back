<?php
use Google\Cloud\Storage\StorageClient;

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
            
            $dir = sys_get_temp_dir();
            $tmp = tempnam($dir, "besluit");
            $file__download= $this -> doCurl(API_BASE_DIR . '/publication/' . $docId .'/download');
            file_put_contents($tmp, $file__download); 

            $this->upload_object($this->app->pubDir, $fileName, $tmp);
            
        }

        /**
         * takes a list of referenced docs, and if pubslished, then downloads pdfs to local storage
         * @param array list
         * @return void pdfs in local storage folder
         */

        public function downloadDocs($list) 
        {
            $cloudStorageDocList = $this->list_objects($this->app->pubDir);

            foreach($list as $item) {
                if ($item['published'] && !in_array('_besluit_' . $item['docId'] . '.pdf', $cloudStorageDocList )) $this->downloadDoc($item['docId']);
            }
        }


        /**
         * List all Cloud Storage buckets for the current project.
         *
         * @return void
         */
        function list_buckets()
        {
            $storage = new StorageClient();
            foreach ($storage->buckets() as $bucket) {
                printf('Bucket: %s' . PHP_EOL, $bucket->name());
            }
        }

        /**
         * List Cloud Storage bucket objects.
         *
         * @param string $bucketName the name of your Cloud Storage bucket.
         *
         * @return void
         */
        function list_objects($bucketName)
        {
            $cloudStorageDocList = [];
            $storage = new StorageClient();
            $bucket = $storage->bucket($bucketName);
            foreach ($bucket->objects() as $object) {
                // printf('Object: %s' . PHP_EOL, $object->name());
                $cloudStorageDocList[] = $object->name();
            }
            return $cloudStorageDocList;
        }


        /**
         * Upload a file.
         *
         * @param string $bucketName the name of your Google Cloud bucket.
         * @param string $objectName the name of the object.
         * @param string $source the path to the file to upload.
         *
         * @return Psr\Http\Message\StreamInterface
         */
        function upload_object($bucketName, $objectName, $source)
        {
            $storage = new StorageClient();
            $file = fopen($source, 'r');
            $bucket = $storage->bucket($bucketName);
            $object = $bucket->upload($file, [
                'name' => $objectName
            ]);
            printf('Uploaded %s to gs://%s/%s' . PHP_EOL, basename($source), $bucketName, $objectName);
        }

        function download_object($bucketName, $objectName, $destination)
        {
            $storage = new StorageClient();
            $bucket = $storage->bucket($bucketName);
            $object = $bucket->object($objectName);
            $object->downloadToFile($destination);
            printf('Downloaded gs://%s/%s to %s' . PHP_EOL,
                $bucketName, $objectName, basename($destination));
        }




        public function __destruct(){}

    }