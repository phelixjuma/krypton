<?php

/**
 * This script handles miscelleneous public static public static functions
 * @author Phelix Juma <jumaphelix@kuzalab.co.ke>
 * @copyright (c) 2018, Kuza Lab
 * @package Kuzalab
 */

namespace Kuza\Krypton\Classes;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Shuchkin\SimpleXLSXGen;

/**
 * Miscellaneous utility methods.
 */
final class Data {

    public function __construct() {

    }

    /**
     * Get the last key of an array
     * @param  $array
     * @return
     */
    public static function getLastArrayKey($array) {
        return count($array) - 1;
    }

    /**
     * sort an array by a specific key
     * Defaults to ascending order
     * @param string $key : the array key to use for sorting
     * @param array $array : the array to sort
     * @param string $direction : can be asc or desc
     * @return boolean
     */
    public static function sortBy($key, &$array, $direction = 'asc') {
        usort($array, create_function('$a, $b', '
        $a = $a["' . $key . '"];
        $b = $b["' . $key . '"];

        if ($a == $b)
        {
            return 0;
        }
        return ($a ' . ($direction == 'desc' ? '>' : '<') . ' $b) ? -1 : 1;
    '));

        return true;
    }

    /**
     * Function to search array data for a specific value by the provided key
     * Returns the found data
     * @param array $arrayData
     * @param string $searchKey
     * @param string $searchValue
     * @return mixed
     */
    public static function searchMultiArrayByKey($arrayData, $searchKey, $searchValue) {
        $foundData = array();
        $size = sizeof($arrayData);
        for ($i = 0; $i < $size; $i++) {
            if ($arrayData[$i][$searchKey] == $searchValue) {
                $foundData[] = $arrayData[$i];
            }
        }
        return $foundData;
    }

    /**
     * Function to search array data for a specific value by the provided key
     * Returns the found array keys
     * @param array $arrayData
     * @param string $searchKey
     * @param string $searchValue
     * @return array|boolean
     */
    public static function searchMultiArrayByKeyReturnKeys
    ($arrayData, $searchKey, $searchValue) {
        $size = is_array($arrayData) ? sizeof($arrayData) : 0;
        for ($i = 0; $i < $size; $i++) {
            if (strtolower($arrayData[$i][$searchKey]) == strtolower($searchValue)) {
                return $arrayData[$i];
            }
        }
        return false;
    }

    /**
     * Implements PHP's uasort in a stable way
     * @param $array
     * @param $cmp_function
     */
    public static function stableuasort(&$array, $cmp_function) {
        if (count($array) < 2) {
            return;
        }

        $halfway = count($array) / 2;
        $array1 = array_slice($array, 0, $halfway, TRUE);
        $array2 = array_slice($array, $halfway, NULL, TRUE);

        self::stableuasort($array1, $cmp_function);
        self::stableuasort($array2, $cmp_function);
        if (call_user_func($cmp_function, end($array1), reset($array2)) < 1) {
            $array = $array1 + $array2;
            return;
        }
        $array = array();
        reset($array1);
        reset($array2);
        while (current($array1) && current($array2)) {
            if (call_user_func($cmp_function, current($array1), current($array2)) < 1) {
                $array[key($array1)] = current($array1);
                next($array1);
            } else {
                $array[key($array2)] = current($array2);
                next($array2);
            }
        }
        while (current($array1)) {
            $array[key($array1)] = current($array1);
            next($array1);
        }
        while (current($array2)) {
            $array[key($array2)] = current($array2);
            next($array2);
        }
        return;
    }

    /**
     * Implement stable php usort public static function
     * @param $array
     * @param $cmp_function
     */
    public static function stableusort(&$array, $cmp_function) {
        // Arrays of size < 2 require no action.
        if (count($array) < 2)
            return;
        // Split the array in half
        $halfway = count($array) / 2;
        $array1 = array_slice($array, 0, $halfway);
        $array2 = array_slice($array, $halfway);
        // Recurse to sort the two halves
        self::stableusort($array1, $cmp_function);
        self::stableusort($array2, $cmp_function);
        // If all of $array1 is <= all of $array2, just append them.
        if (call_user_func($cmp_function, end($array1), $array2[0]) < 1) {
            $array = array_merge($array1, $array2);
            return;
        }
        // Merge the two sorted arrays into a single sorted array
        $array = array();
        $ptr1 = $ptr2 = 0;
        while ($ptr1 < count($array1) && $ptr2 < count($array2)) {
            if (call_user_func($cmp_function, $array1[$ptr1], $array2[$ptr2]) < 1) {
                $array[] = $array1[$ptr1++];
            } else {
                $array[] = $array2[$ptr2++];
            }
        }
        // Merge the remainder
        while ($ptr1 < count($array1))
            $array[] = $array1[$ptr1++];
        while ($ptr2 < count($array2))
            $array[] = $array2[$ptr2++];
        return;
    }

    /**
     * Eliminate repetition of data from an array
     * @param array $data
     * @return array
     */
    public static function makeUnique(array $data) {
        $final = array();
        foreach ($data as $array) {
            if (!in_array($array, $final)) {
                $final[] = $array;
            }
        }
        return $final;
    }

    /**
     * Get unique values from an associative array
     * @param $array
     * @return array
     */
    public static function makeAssociativeArrayUnique($array) {
        return array_map("unserialize", array_unique(array_map("serialize", $array)));

    }

    /**
     * Format mobile phone numbers. All phone numbers must start with the country code
     * @param String $phoneNumber
     * @param String $countryCode
     * @return String
     */
    public static function formatPhoneNumber($phoneNumber, $countryCode = "254") {

        // Check if starts with '+'
        return preg_replace('/\s+/', '', str_replace("+", "", $phoneNumber));

//        //check if the first 3 characters are the same as the country code.
//        if (substr($phoneNumber, 0, 3) == $countryCode) {
//            return $phoneNumber;
//        }
//
//        //check if the phone number starts with a 0.
//        if (substr($phoneNumber, 0, 1) == "0") {
//            //replace the zero with the country code
//            return substr_replace($phoneNumber, $countryCode, 0, 1);
//        }
//        // prepend 254
//        return $countryCode . $phoneNumber;
    }

    public static function formatPhoneNumberOld($phoneNumber, $countryCode = "254") {

        // Check if starts with '+'
        $phoneNumber =  preg_replace('/\s+/', '', str_replace("+", "", $phoneNumber));

        //check if the first 3 characters are the same as the country code.
        if (substr($phoneNumber, 0, 3) == $countryCode) {
            return $phoneNumber;
        }

        //check if the phone number starts with a 0.
        if (substr($phoneNumber, 0, 1) == "0") {
            //replace the zero with the country code
            return substr_replace($phoneNumber, $countryCode, 0, 1);
        }
        // prepend 254
        return $countryCode . $phoneNumber;
    }

    /**
     * Format username
     * @param string $username
     * @return string
     */
    public static function formatUsername($username) {
        //eliminate the leading '+' in the username
        $usernameNumericCheck = str_ireplace("+", "", $username);
        //check if the username is numerical ie a phone number
        if (is_numeric($usernameNumericCheck)) {
            return self::formatPhoneNumber($username);
        }
        //the username is not a phone number
        return $username;
    }

    /**
     * public static function to get float value from a string
     * @param String $string
     * @return String
     */
    public static function getFloatValue($string) {
        //option 1: We use regex to eliminate all string except numerals and the decimal demarcator (a full stop in this case)
        $regex = "/[^0-9\.]/"; //regex eliminates all characters except numerals and the full stop
        $floatValue = preg_replace($regex, "", $string);


        return floatval($floatValue);
    }

    /**
     *
     * @param array $input the input array to be reset by eliminating empty keys
     * @return array
     */
    public static function resetArray(array $input) {
        $output = [];
        foreach ($input as $value) {
            if (!empty($value)) {
                $output[] = $value;
            }
        }
        return $output;
    }

    /**
     * Format text
     * @param string $content
     * @return string
     */
    public static function formatText($content) {

        $text = htmlspecialchars($content);

        $text = html_entity_decode($text);
        $text = htmlspecialchars_decode($text);
        $text = htmlspecialchars_decode($text, ENT_QUOTES); //converts quotes

        return $text;
    }

    /**
     * Serialize data. A better implementation of PHP's serialize function
     * @param array|object $data
     * @return string A base64_encoded serialized data.
     */
    public static function serializeData($data) {
        return base64_encode(serialize($data));
    }

    /**
     * Unserialize data. A better implementation of PHP's unserialize function
     * @param array|object $data
     * @return string
     */
    public static function unserializeData($data) {
        return unserialize(base64_decode($data));
    }

    /**
     * Encode data as json. A better implementation of PHP's json_encode function
     * @param array|string|object|int $data
     * @return string
     */
    public static function jsonEncode($data) {
        //do not encode json strings
        if (!is_array($data) && self::jsonDecode($data) !== null) {
            return $data;
        }

        if (is_array($data)) {
            array_walk_recursive($data, function(&$value, $key) {
                $value = iconv(mb_detect_encoding($value, mb_detect_order(), true), "UTF-8", $value);
            });
        }
        //return json_encode($data,JSON_FORCE_OBJECT);
        return json_encode($data);
    }

    /**
     * Decode json data. A better implementation of PHP's json_decode function
     * @param String . A json string to decode
     * @return array all data types depending on what was encode
     */
    public static function jsonDecode($data) {
        return json_decode($data, true);
    }

    /**
     * function to trim long content
     * @param string $string        The string to be shortened
     * @param int $length           The length to which the text should be truncated
     * @param string $etc           the characters add at the end of the shortened text. Default is '...'
     * @param boolean $break_words  Whether to break at a word or not. Defaults to false
     * @param boolean $middle       Whether to shorten at the middle of the text
     * @return string               The shortened text
     */
    public static function truncate($string, $length = 80, $etc = '...', $break_words = false, $middle = false) {
        if ($length == 0) {
            return '';
        }
        // no MBString fallback
        if (isset($string[$length])) {
            $length -= min($length, strlen($etc));
            if (!$break_words && !$middle) {
                $string = preg_replace('/\s+?(\S+)?$/', '', substr($string, 0, $length + 1));
            }
            if (!$middle) {
                return substr($string, 0, $length) . $etc;
            }

            return substr($string, 0, $length / 2) . $etc . substr($string, - $length / 2);
        }

        return $string;
    }

    /**
     * Capitalize the first letter of the given string
     * @param string $string string to be capitalized
     * @return string capitalized string
     */
    public static function capitalize($string) {
        return ucfirst(mb_strtolower($string));
    }

    /**
     * Create a random string from alphanumeric characters
     * @param int $length
     * @param bool $num_only
     * @param bool $use_symbols
     * @return bool|string
     */
    public static function randomize($length=8,$num_only=false,$use_symbols=false) {
        if($num_only==true)
            $chars = "0123456789";
        else
            $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";

        if($use_symbols==true)
            $chars = $chars."!@#$%^&*()_-=+;:,.?";

        $result = substr( str_shuffle( $chars ), 0, $length );
        return $result;
    }

    /**
     * Join any two 2-d arrays
     * @param $arrayA
     * @param $arrayB
     * @param $key
     * @return array
     */
    public static function join2DArrays($arrayA,$arrayB,$key) {
        $results = [];
        foreach ($arrayA as $a)
        {
            foreach ($arrayB as $b)
            {
                if($a[$key] == $b[$key])
                {
                    $results[] = array_merge($a,$b);
                }
            }
        }
        return $results;
    }

    /**
     * Filter an array.
     * Searches for the array components that have a key with the specified value
     * @param  array $source the source array
     * @param string $key the key to check
     * @param string $val the value to search for
     * @return array
     */
    public static function getFiltered($source,$key,$val) {
        $source = (array)$source;
        $result = [];

        foreach ($source as $row){
            if($row[$key]==$val){
                $result[] = $row;
            }
        }

        return $result;
    }

    /**
     * Get a specific array data from an array
     * @param array $data the array to search
     * @param string $key the key to search for
     * @param bool $as_number
     * @param null $default
     * @return null
     */
    public static function getFromArray($data,$key,$as_number=false,$default=null) {
        $value =  isset($data[$key])? $data[$key] : null;
        return ($as_number==true && intval($value)==0)? $default : $value;
    }

    /**
     * Get array map
     * Can be used to get unique values from an array
     * @param $data
     * @param $key
     * @param bool $unique
     * @return array
     */
    public static function getArrayMap($data,$key,$unique=true) {
        $results = [];
        $data = is_array($data)==true? $data : [];
        foreach ($data as $row)
        {
            if(array_key_exists($key,$row)==true)
            {
                $results[] = $row[$key];
            }
        }
        $results = ($unique==true)? array_unique($results) : $results;
        return $results;
    }

    /**
     * Get Array association
     * @param $data
     * @param $key_col
     * @param $val_col
     * @param bool $unique
     * @return array|null
     */
    public static function getArrayAssoc($data,$key_col,$val_col,$unique=true) {
        $results = [];
        $data = is_array($data)==true? $data : [];
        foreach ($data as $row)
        {
            if(array_key_exists($key_col,$row)==true)
            {
                $results[$row[$key_col]] = $row[$val_col];
            }
        }
        $results = ($unique==true)? array_unique($results) : $results;
        $results = count($results)>0? $results : null;
        return $results;
    }

    /**
     * Get a subset of an array
     * @param $source
     * @param $keys
     * @return array
     */
    public static function arraySubset($source,$keys) {
        $source = (array)$source;
        $keys = (array)$keys;
        $result = [];

        foreach ($keys as $key){
            if(array_key_exists($key, $source)==true){
                $result[$key] = $source[$key];
            }
        }
        return $result;
    }

    /**
     * Get a copy of an array
     * @return array
     */
    public static function getArrayCopy() {
        //return get_object_vars($this);
    }

    /**
     * Get class variables
     * @param null $class
     * @return array
     */
    public static function getClassVars($class=null) {
        return get_class_vars($class);
    }

    /**
     * Get array keys
     * @return array
     */
    public static function arrayKeys() {
        //return array_keys(get_class_vars(get_class($this)));
    }

    /**
     * List array keys
     * @return string
     */
    public static function listKeys($arrayKeys) {
        return implode(',',$arrayKeys);
    }

    /**
     * Check if value exists in an array
     * @param $value
     * @param $array
     * @return bool
     */
    public static function arrayValueExists($value,$array) {
        foreach($array as $r) {
            if($r == $value) {
                return true;
            }
        }
        return false;
    }

    /**
     * Removes all the empty values from an array
     * @param $array
     * @return array
     */
    public static function eliminateEmptyKeysFromArray($array) {
        return  array_filter($array);
    }

    /**
     * Convert all null values in an array to empty string values
     * @param $array
     */
    public static function nullToEmptyString(&$array) {

        if (is_array($array) && sizeof($array) > 0) {
            array_walk_recursive($array, function (&$value) {
                $value = !is_null($value) ? $value : "";
            });
        }
    }

    /**
     * Convert all null values in an array to empty string values
     * @param $array
     */
    public static function nullToZero(&$array) {

        if (is_array($array) && sizeof($array) > 0) {
            array_walk_recursive($array, function (&$value) {
                $value = !is_null($value) ? $value : 0;
            });
        }
    }


    /**
     * Round a value up
     * @param $value
     * @param $precision
     */
    public static function roundUp(&$value, $precision) {
            //        $power = 10 ** $precision;
            //
            //        $prec_value = (float)($value * (float)$power);
            //
            //        $rounded_up = ceil($prec_value);
            //
            //        $value = $rounded_up/$power;
            $value = $value;
    }

    /**
     * Calculate the compound interest amount.
     * @param $principal
     * @param $rate
     * @param $time
     * @return array
     */
    public static function calculateCompoundInterest($principal, $rate, $time) {
        $amount = floatval($principal) * ((1 + floatval($rate)) ** floatval($time));

        return [
            "amount"    => $amount,
            "interest"  => $amount - $principal
        ];
    }

    /**
     * Checks if the data is between the
     * @param $data
     * @param $startValue
     * @param $endValue
     * @return bool
     */
    public static function numericValueBetweenClosed($data, $startValue, $endValue) {
        return $data >= $startValue && $data <= $endValue;
    }

    /**
     * @param object $object
     * @param array $array
     */
    public static function mapArrayToObject(&$object, array $array) {

        // We set values
        $reflectionClass = new \ReflectionClass(get_class($object));

        foreach ($array as $key => $value) {
            if ($reflectionClass->hasProperty($key)) {
                $property = $reflectionClass->getProperty($key);
                $property->setAccessible(true);
                $property->setValue($object, $value);
            }
        }
    }

    /**
     * @param $object
     * @return void
     */
    public static function resetObjectPropertiesToNull(&$object) {

        $reflectionClass = new \ReflectionClass(get_class($object));

        foreach ($reflectionClass->getProperties() as $property) {

            $property->setAccessible(true);
            $currentValue = $property->getValue($object);

            if (!is_object($currentValue)) {
                // Set non-object properties to null
                $property->setValue($object, null);
            }
        }
    }

    /**
     * Convert an objec to an array
     * @param $object
     * @return mixed
     */
    public static function mapObjectToArray($object) {
        return  json_decode(json_encode($object), true);
    }

    /**
     * Convert array to csv
     * @param array $data
     * @return false|null|string
     */
    private static function array2csv(array &$data) {

        if (count($data) == 0) {
            return null;
        }
        ob_start();

        $df = fopen("php://output", 'w');

        fputcsv($df, array_keys(reset($data)));

        foreach ($data as $row) {
            fputcsv($df, $row);
        }
        fclose($df);
        return ob_get_clean();
    }

    /**
     * Send headers for file download
     * @param $filename
     */
    private static function download_send_headers($filename) {

        $now = gmdate("D, d M Y H:i:s");

        header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
        header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
        header("Last-Modified: {$now} GMT");

        // force download
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");

        // disposition / encoding on response body
        header("Content-Disposition: attachment;filename={$filename}");
        header("Content-Transfer-Encoding: binary");
    }

    /**
     * Download a csv file
     * @param $data
     * @param $filename
     */
    public static function download_csv_file($data, $filename) {
        self::download_send_headers($filename);
        echo self::array2csv($data);
        die();
    }

    /**
     * @param $data
     * @return SimpleXLSXGen
     */
    public static function getExcel($data): SimpleXLSXGen
    {

        $excelData = [];

        // Check if it's a single associative array
        if (array_keys($data) === range(0, count($data) - 1) && is_array($data[0])) {
            $excelData = $data;
        } else {
            $excelData[] = $data;
        }

        array_unshift($excelData,array_keys($excelData[0]));

        return SimpleXLSXGen::fromArray( $excelData );
    }
}
