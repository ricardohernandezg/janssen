<?php 

namespace Janssen\Helpers;
/**
* Encrypt Helper
*
* This class handles all data encryption and decryption for this 
* application
* 
*/

use Janssen\Helpers\Exception;
use Janssen\Engine\Config;

class Encrypt
{
        /**
         * Encodes base64 and makes modifications to destroy clues
         * that can help revert the cipher
         *
         * @param String $text
         * @return void
         */
        private static function safe_b64encode($text) {
            $data = base64_encode($text);
            $data = str_replace(['+','/','='],['-','_',''],$data);
            return $data;
        }

        /**
         * Decodes $payload returning the clues to its position and
         * reverting base64 encoding to original
         *
         * @param String $payload
         * @return String
         */
        private static function safe_b64decode($payload) {
            $data = str_replace(['-','_'],['+','/'],$payload);
            $mod4 = strlen($data) % 4;
            if ($mod4) {
                $data .= substr('====', $mod4);
            }
            return base64_decode($data);
        }

        /**
         * Gets the key for encryption
         * 
         * @todo Read config instead of env file
         * @return void
         */
        private static function getKey()
        {
            $k = Config::get('enc_key');
            if(!empty($k))
                return $k;
            else
                throw new Exception('You must set a key for Encrypter to work', 500);
        }

        /**
         * Ciphers text
         *
         * @param String $value
         * @return String
         */
        public static function encrypt($text){ 
            if(empty($text))
                return false;

            $method = Config::get('enc_method');
            if(empty($method))
                throw new Exception('You must configure a cipher method in you config file!');

            $iv_length = openssl_cipher_iv_length($method);
            $iv = openssl_random_pseudo_bytes($iv_length);
            $crypted = openssl_encrypt($text, $method, self::getKey(), null, $iv);
            $iv_b64 = self::safe_b64encode($iv);
            $payload = $iv_b64 . '$' . $crypted;
            return trim(self::safe_b64encode($payload)); 
        }

        /**
         * Deciphers text
         *
         * @param String $value
         * @return String
         */
        public static function decrypt($crypted){
            if(empty($crypted))
                return false;

            $method = Config::get('enc_method');
            if(empty($method))
                throw new Exception('You must configure a cipher method in you config file!');                

            $decoded = self::safe_b64decode($crypted);                 
            $a = explode('$', $decoded);
            if(is_array($a) && count($a)>1)
            {   
                $iv_b64 = $a[0];
                $payload = $a[1];
                $text = openssl_decrypt($payload, $method, self::getKey(), null, self::safe_b64decode($iv_b64));
                return trim($text);
            }else
                return false;

        }
    }

