<?php

/**
 * This is script handles utility functions
 * @author Phelix Juma <jumaphelix@kuzalab.co.ke>
 * @copyright (c) 2018, Kuza Lab
 * @package Kuzalab
 */

namespace Kuza\Krypton\Classes;

use Kuza\Krypton\Config\Config;

/**
 * Miscellaneous utility methods.
 */
final class Utils {

    private function __construct() {

    }

    /**
     * Generate a slug from a text.
     * @param $string
     * @param string $delimiter
     * @return mixed|null|string|string[]
     * @throws \Exception
     */
    public static function slugify($string, $delimiter = "-") {
        //replace ampersand with and
        str_ireplace("&", "and", $string);

        if (!extension_loaded('iconv')) {
            throw new \Exception('iconv module not loaded');
        }
        // Save the old locale and set the new locale to UTF-8
        $oldLocale = setlocale(LC_ALL, '0');
        setlocale(LC_ALL, 'en_US.UTF-8');
        $clean = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
        if (!empty($replace)) {
            $clean = str_replace((array) $replace, ' ', $clean);
        }
        $clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
        $clean = strtolower($clean);
        $clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);
        $clean = trim($clean, $delimiter);

        // Revert back to the old locale
        setlocale(LC_ALL, $oldLocale);

        return $clean;
    }

    /**
     * Escape the given string
     * @param $input string to be escaped
     * @return string escaped string
     */
    public static function escape($input) {
        if(is_array($input)){
            array_walk_recursive($input,function (&$val,$key){
                $val = htmlspecialchars(trim($val), ENT_QUOTES);
            });
            return $input;
        }
        return htmlspecialchars(trim($input), ENT_QUOTES);
    }

    /**
     * Unescape characters
     * @param string $string
     * @return string
     */
    public static function unescape($string) {
        return html_entity_decode(htmlspecialchars_decode($string, ENT_NOQUOTES));
    }



    /**
     * Check if a request is an ajax request
     * @return boolean
     */
    public static function isAjax() {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH'] && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
            return true;
        }
        return false;
    }


    /**
     * hash the password of a user
     * @param string $password
     * @return string
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * Verify hashed if a password matches the hashed version
     * @param $password
     * @param $hash
     * @return bool
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    /**
     * Format  a phone number
     * @param string $phoneNumber
     * @return bool
     */
    public static function isValidPhoneNumber($phoneNumber) {

        //check that the phone number starts with a '+'
        if (substr($phoneNumber, 0, 1) != "+") {
            return false;
        }

        //phone number starts with a '+'. Check that all the other characters are numeric
        if (!is_numeric(str_replace("+", "", $phoneNumber))) {
            return false;
        }

        //check that the digits are btw 10 and 13 : +254729941254
        if (strlen($phoneNumber) < 10 || strlen($phoneNumber) > 13) {
            return false;
        }

        //phone number is OK;
        return true;
    }

    /**
     * Prepare page limits for SQL
     * @param string $page  the request page
     * @return string
     */
    public static function preparePageLimits($page) {
        $page = ($page - 1) * Config::PAGE_SIZE;

        if ($page < 0) {
            $page = 0;
        }

        $limit = "" . $page . "," . Config::PAGE_SIZE . "";

        return $limit;
    }

    /**
     * Redirect to the provided uri
     * @param string $uri
     * @return null
     */
    public static function redirectTo($uri) {
        echo "<script>location.href='$uri'</script>";
        return;
    }

    /**
     * Validate an email address
     * @param $email
     * @return bool
     */
    public static function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Check if an object is empty or not
     * @param $object
     * @return bool
     */
    public static function isEmptyObject($object){
      $array = (array) $object;

      if(!isset($arry)){
        return true;
      }

      if(empty($array)){
        return true;
      }
      return false;
    }

    /**
     * Get initials from a user's name
     * @param $name
     * @param bool $separator
     * @return string
     */
    public static function getInitials($name,$separator=false){
        //split name using spaces
        $words=array_filter(explode(" ",$name));
        $inits='';
        //loop through array extracting initial letters
        foreach($words as $word){
            $inits.=strtoupper(substr($word,0,1)).(($separator==false) ? '' : $separator);
        }
        return trim($inits);
    }

    /**
     * Convert from RGB to hexadecimal color representation
     * @param $R
     * @param $G
     * @param $B
     * @return string
     */
    public function fromRGBToHexa($R, $G, $B) {
        $R = dechex($R);
        if (strlen($R)<2)
            $R = '0'.$R;

        $G = dechex($G);
        if (strlen($G)<2)
            $G = '0'.$G;

        $B = dechex($B);
        if (strlen($B)<2)
            $B = '0'.$B;

        return '#' . $R . $G . $B;
    }

    /**
     * Convert from hexadecimal to RGBA color representation
     * @param $hex
     * @param bool $as_array
     * @return array|string
     */
    public function hex2rgba($hex,$as_array=true){
        $hex = str_replace("#", "", $hex);

        switch (strlen($hex)) {
            case 3 :
                $r = hexdec(substr($hex, 0, 1).substr($hex, 0, 1));
                $g = hexdec(substr($hex, 1, 1).substr($hex, 1, 1));
                $b = hexdec(substr($hex, 2, 1).substr($hex, 2, 1));
                $a = 1;
                break;
            case 6 :
                $r = hexdec(substr($hex, 0, 2));
                $g = hexdec(substr($hex, 2, 2));
                $b = hexdec(substr($hex, 4, 2));
                $a = 1;
                break;
            case 8 :
                $a = hexdec(substr($hex, 0, 2)) / 255;
                $r = hexdec(substr($hex, 2, 2));
                $g = hexdec(substr($hex, 4, 2));
                $b = hexdec(substr($hex, 6, 2));
                break;
        }
        $rgba = array($r, $g, $b, $a);

        return ($as_array==true)? $rgba : 'rgba('.implode(', ', $rgba).')';
    }

    /**
     * Convert from RGBA to hexadecimal color representation
     * @param $string
     * @return string
     */
    public static function rgba2hex($string) {
        $rgba  = array();
        $regex = '#\((([^()]+|(?R))*)\)#';
        if (preg_match_all($regex, $string ,$matches)) {
            $rgba = explode(',', implode(' ', $matches[1]));
        } else {
            $rgba = explode(',', $string);
        }

        $rr = dechex($rgba['0']);
        $gg = dechex($rgba['1']);
        $bb = dechex($rgba['2']);
        $aa = '';

        if (array_key_exists('3', $rgba)) {
            $aa = dechex($rgba['3'] * 255);
        }

        return strtoupper("#$aa$rr$gg$bb");
    }

    /**
     * Validate a name
     * @param null $name
     * @return bool
     */
    public static function validateName($name = null) {
        //count number of words to check if they are more than two
        if(str_word_count($name) >1){
            //has more than one words, check if it has a space between
            if(!preg_match('/\s/', $name)){
                //invalid name
                return false;
            }

            //check if it has number in between
            if(strcspn($name, "0123456789@\*") != strlen($name)){
                //invalid name
                return false;
            }
            //valid name
            return true;
        }
        //invalid name
        return false;
    }

    /**
     * Timezone Converter
     * @param $newTimezone
     * @param $date
     * @return mixed
     */

    public static function timezoneConverter($newTimezone, $date){
        $datetime = new \DateTime($date);
        //echo $datetime->format('Y-m-d H:i:s') . "\n";
        $la_time = new \DateTimeZone($newTimezone);
        $datetime->setTimezone($la_time);
        return $datetime->format('Y-m-d H:i:s');
    }

    /**
     * Check if a string is a valid base 64 string
     * @param $data
     * @return bool
     */
    public static function isValidBase64($data) {
        if ( base64_encode(base64_decode($data, true)) === $data){
            return true;
        }
        return false;
    }

    /**
     * Checks to see if the number provided is Mpesa Compatible
     * @param $phoneNumber
     * @return bool
     */
    public static function checkMpesaNumber($phoneNumber){
        $array = [
            "25470",
            "25471",
            "25472",
            "25474",
            "25479"
        ];

        $i = 0;
        foreach ($array as $arr){
            if(preg_match("/^".$arr."/",$phoneNumber)){
                $i++;
            }
        }

        if ($i >= 1){
            return true;
        }else{
            return false;
        }


    }

    public static function arefSafe($array, $index, $default=null)
    {
        if(isset($array[$index])) return $array[$index];
        else return $default;
    }

    public static function startsWith($haystack, $needle, $caseSensitive=true)
    {
        if(!$needle)
        {
            $res = false;
        }
        else if($caseSensitive)
        {
            $res = strpos($haystack, $needle, 0) === 0;
        }
        else
        {
            $res = stripos($haystack, $needle, 0) === 0;
        }
        return $res;
    }

    /**
     * Checks to see if the number provided is Mtn Compatible
     * @param $phoneNumber
     * @return bool
     */
    public static function checkMtnUgandaNumber($phoneNumber){
        $array = [
            "2567"
        ];

        $i = 0;
        foreach ($array as $arr){
            if(preg_match("/^".$arr."/",$phoneNumber)){
                $i++;
            }
        }

        if ($i >= 1){
            return true;
        }else{
            return false;
        }

    }

    /**
     * Generates UUID v4
     * @return string
     */
    public static function genUuidv4() {
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

            // 16 bits for "time_mid"
            mt_rand( 0, 0xffff ),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand( 0, 0x0fff ) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand( 0, 0x3fff ) | 0x8000,

            // 48 bits for "node"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }
}
