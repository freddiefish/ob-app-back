<?php

use PHPMailer\PHPMailer\PHPMailer;

    class Util {

        public $logger;
        
        public function __construct($logger)
        {
            $this->logger = $logger;
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

        /**
         * sends an email
         * @param string subj
         * @param string msg
         * @param string attFilePath (must be local file ref)
         * @return mixed true or logs error
         */

        function mailThis($subj, $msg, $attFilePath) {

            $mail = new PHPMailer();
        
            ( PROD ? $debugMode = 1 : $debugMode = 2 );
            $mail->SMTPDebug = $debugMode;
        
            //setup mailjet
            $mail->isSMTP();
            $mail->Host = 'in-v3.mailjet.com';
            $mail->SMTPAuth = true;
            $mail->Username = '08f0ffd5a702d5b663f39b69f213f40b'; 
            $mail->Password = '20b9614eed9a3fb48ce428ae25eba13c' ; 
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
        
            //headers
            $mail->setFrom(EMAIL_ADMIN, 'Open Bestuur');
            $mail->addAddress(EMAIL_ADMIN, 'Admin'); 
            if (!empty($attFilePath)) $mail->addAttachment($attFilePath);
            // $mail->addCC('cc1@example.com', 'Elena');
            // $mail->addBCC('bcc1@example.com', 'Alex');
        
            // mail
            $mail->isHTML(true);
            $mail->Subject = $subj;
            $mailContent = $msg;
            $mail->Body = $mailContent;
        
            // $mail->msgHTML(do_curl('contents.html'), __DIR__);
        
            if($mail->send()){
                return true;
            }else{
                echo 'Message could not be sent.';
                echo 'Mailer Error: ' . $mail->ErrorInfo;
                $this->logger->error('mailThis: send error ' . $mail->ErrorInfo);
                // files must be in the same dir! $mail->addAttachment('path/to/invoice1.pdf', 'invoice1.pdf');
            }
        }

        function array_sort($array, $on, $order=SORT_ASC)
        {
            $new_array = array();
            $sortable_array = array();

            if (count($array) > 0) {
                foreach ($array as $k => $v) {
                    if (is_array($v)) {
                        foreach ($v as $k2 => $v2) {
                            if ($k2 == $on) {
                                $sortable_array[$k] = $v2;
                            }
                        }
                    } else {
                        $sortable_array[$k] = $v;
                    }
                }

                switch ($order) {
                    case SORT_ASC:
                        asort($sortable_array);
                    break;
                    case SORT_DESC:
                        arsort($sortable_array);
                    break;
                }

                foreach ($sortable_array as $k => $v) {
                    $new_array[$k] = $array[$k];
                }
            }

            return $new_array;
        }

        /**
         * inserts array into existing array at $offset point
         */
        
        public function insertArray($array, $arrayNew, $offset) {
            $length = count($array);
            $head = array_slice($array, 0, $offset ) ;
            /** @todo take care of cases with three pieces */ 
            $insert = array( $offset-1 => $arrayNew[0], $offset => $arrayNew[1]);
            if (count($arrayNew) == 3) {
                array_push($insert, $arrayNew[2]);
            }
            $tail = array_slice($array, $offset+1, $length); 
            $res = array_merge($head, $insert, $tail);
            return $res;
        }

        /**
         * searches the key for a specific value in an multidimensional array
         */

        function multiDimArrayFindKey($multiDimArray, $field, $value){
            foreach($multiDimArray as $key => $item) {
                if ( $item[$field] === $value )
                    return $key;
            }
            return false;
        }
        
        function startsWithNumber($str) {
            return preg_match('/^\d/', $str) === 1;
        }

    }