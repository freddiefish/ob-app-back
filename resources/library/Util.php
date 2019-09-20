<?php

use PHPMailer\PHPMailer\PHPMailer;

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
            $mail->setFrom(EMAIL_ADMIN, 'Frederik Feys');
            $mail->addAddress(EMAIL_ADMIN, 'Admin Fred'); 
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
                logThis('Mail send error: ' . $mail->ErrorInfo);
                // files must be in the same dir! $mail->addAttachment('path/to/invoice1.pdf', 'invoice1.pdf');
            }
        }


    }