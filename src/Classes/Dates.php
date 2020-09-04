<?php

/**
 * This script handles miscelleneous public static functions
 * @author Phelix Juma <jumaphelix@kuzalab.co.ke>
 * @copyright (c) 2018, Kuza Lab
 * @package Kuzalab
 */

namespace Kuza\Krypton\Classes;

use DateTime;

/**
 * Miscellaneous utility methods.
 */
final class Dates {

    private function __construct() {

    }

    /**
     * format a date
     * @param string $datetime
     * @param string $format
     * @return string
     */
    public static function formatDate($datetime, $format) {
        $date = date_create(str_replace("/", "-", $datetime));
        return date_format($date, $format);
    }

    /**
     * Convert date to a timestamp
     * @param  $datetime
     * @return string
     */
    public static function dateToTimestamp($datetime) {
        $date = new DateTime(self::formatDate($datetime, "Y-m-d"));

        return $date->getTimestamp();
    }

    /**
     * Convert seconds to human readable time
     * @param $seconds
     * @return array
     */
    public static function secondsToTime($seconds) {
        $dtF = new \DateTime('@0');
        $dtT = new \DateTime("@$seconds");
        $diff = $dtF->diff($dtT);

        return [
            "years" => $diff->y,
            "months"    => $diff->m,
            "days"      => $diff->d,
            "hours"     => $diff->h,
            "minutes"   => $diff->i,
            "seconds"   => $diff->s,
            "microseconds"  => $diff->f
        ];
    }

    /**
     * get difference between two dates in either years, months, days , hours, minutes or seconds
     * @param string $date1
     * @param string $date2
     * @return array The array has the key as the duration type e.g days and the value is the duration e.g $interval['months']='1';
     */
    public static function getDateDifference($date1, $date2) {
        $age = array();

        $fromDate = new \DateTime($date1);
        $endDate = new \DateTime($date2);

        $interval = $fromDate->diff($endDate);


        if ($interval->y > 0) {
            $age['years'] = $interval->y;
        }
        if ($interval->m > 0) {
            $age['months'] = $interval->m;
        }
        if ($interval->d > 0) {
            $age['days'] = $interval->d;
        }
        if ($interval->h > 0) {
            $age['hours'] = $interval->h;
        }
        if ($interval->i > 0) {
            $age['minutes'] = $interval->i;
        }
        if ($interval->s > 0) {
            $age['seconds'] = $interval->s;
        }
        return $age;
    }

    /**
     * Get date difference in days
     * @param $startDate
     * @param $endDate
     * @return float|int|mixed
     */
    public static function getDateDifferenceInDays($startDate, $endDate) {

        $days = 0;

        $dateDiff = self::getDateDifference($startDate, $endDate);

        if (isset($dateDiff['years'])) {
            $days += $dateDiff['years'] * 565;
        }
        if (isset($dateDiff['months'])) {
            $days += $dateDiff['months'] * 30;
        }
        if (isset($dateDiff['days'])) {
            $days += $dateDiff['days'];
        }
        return $days;
    }

    /**
     * Unlike the public static function getDateDifference, this one gets the date difference and formats its display
     * Example: 2 days, 1 month et al
     * @param string $date1
     * @param string $date2
     * @return string a string formatting the duration e.g "now, 2 seconds, 3 days, 5 months, 2 years etc"
     */
    public static function displayDateDifference($date1, $date2) {
        $diff = self::getDateDifference($date1, $date2);
        $age = '';
        if (isset($diff['years']) && !empty($diff['years'])) {
            $age = $diff['years'] . ' years';
        } elseif (isset($diff['months']) && !empty($diff['months'])) {
            $age = $diff['months'] . ' months';
        } elseif (isset($diff['days']) && !empty($diff['days'])) {
            $age = $diff['days'] . ' days';
        } elseif (isset($diff['hours']) && !empty($diff['hours'])) {
            $age = $diff['hours'] . ' hours';
        } elseif (isset($diff['minutes']) && !empty($diff['minutes'])) {
            $age = $diff['minutes'] . ' min';
        } elseif (isset($diff['seconds']) && !empty($diff['seconds'])) {
            $age = $diff['seconds'] . ' sec';
            if ($diff['seconds'] == 0) {
                $age = 'now';
            }
        }
        return $age;
    }

    /**
     * Get all the dates between any two provided dates
     * @param Date $fromDate
     * @param Date $toDate
     * @return array
     */
    public static function getDates($fromDate, $toDate) {
        $years = 0;
        $months = 0;
        $days = 0;
        //get the duration length in days
        $duration = self::getDateDifference($fromDate, $toDate);
        //convert the duration to days if not
        if ($duration['years'] != "") {
            $years = $duration['years'];
        }
        if ($duration['months'] != "") {
            $months = $duration['months'];
        }
        if ($duration['days'] != "") {
            $days = $duration['days'];
        }
        $length = ($years * 365) + ($months * 30) + ($days);
        //loop through the length and generate all dates in between.
        $dates = array();

        for ($i = 0; $i <= $length; $i++) {
            $date = strtotime($fromDate);
            $dates[] = date('Y-m-d', $date + ($i * 86400));
        }
        return $dates;
    }

    /**
     * Add days to a date
     * @param $date
     * @param $days
     * @return string
     * @throws \Exception
     */
    public static function addDaysToDate($date, $days) {

        $referenceDate = new DateTime($date);

        $newDate = $referenceDate
            ->add(new \DateInterval("P{$days}D"))
            ->format("Y-m-d");

        return $newDate;
    }

    /**
     * Get all the months of the year by name
     * @return array
     */
    public static function getMonths() {
        return array("january", "february", "march", "april", "may", "june", "july", "august", "september", "october", "november", "december");
    }

    /**
     * Gets the range of dates within each of the months of the provided year.
     * Ranges are given in the format date month,year e.g 1 February,2016
     * @param string $year
     * @return array . Array keys are: month,startDate,endDate
     */
    public static function getMonthDateRanges($year) {
        $dateRanges = [];

        $isLeapYear = false;
        if ($year % 4 == 0) {
            $isLeapYear = true;
        }
        $months = getMonths();

        for ($i = 0; $i < 12; $i++) {
            $month = $months[$i];
            $startDate = "1 " . ucfirst($month) . ",{$year}";
            //get the ranges for jan to July. Odd numbered indices have end dates of 31st
            if ($i <= 6) {
                //format date for February
                if ($i == 1) {
                    $endDate = "28 February,{$year}";
                    if ($isLeapYear) {
                        $endDate = "29 February,{$year}";
                    }
                }
                //format the dates for the other months.
                else {
                    if ($i % 2 == 0) {
                        $endDate = "31 " . ucfirst($month) . ",{$year}";
                    } else {
                        $endDate = "30 " . ucfirst($month) . ",{$year}";
                    }
                }
            }
            //get the date ranges from August to December
            else {
                if ($i % 2 == 0) {
                    $endDate = "30 " . ucfirst($month) . ",{$year}";
                } else {
                    $endDate = "31 " . ucfirst($month) . ",{$year}";
                }
            }
            $dateRanges[$i]['month'] = $month;
            $dateRanges[$i]['startDate'] = self::formatDate($startDate, "Y-m-d");
            $dateRanges[$i]['endDate'] = self::formatDate($endDate, "Y-m-d");
        }
        return $dateRanges;
    }

    /**
     * Get timezones
     * @return array
     */
    public static function getTimezones() {
        $time_zones = array();
        $t = timezone_identifiers_list();

        foreach ($t as $a) {
            $t = '';
            //Get the time difference
            $zone = new \DateTimeZone($a);
            $seconds = $zone->getOffset(new \DateTime("now", $zone));
            $hours = sprintf("%+02d", intval($seconds / 3600));
            $minutes = sprintf("%02d", ($seconds % 3600) / 60);

            $t = $a . "  [UTC $hours:$minutes ]";
            $time_zones[$a] = $t;
        }
        ksort($time_zones);
        return $time_zones;
    }

    /**
     * Get the current timestamp
     * @return false|string
     */
    public static function getTimestamp() {
        return Date("Y-m-d H:i:s",time());
    }

    /**
     * Get the current year
     * @return string
     */
    public static function getCurrentYear() {
        return Date("Y", time());
    }

    /**
     * get the current month
     */
    public static function getCurrentMonth() {
        return Date("m", time());
    }

    /** Function that detects whether a given date falls in
     * a leap year or not.
     *
     * @param null $date Optional
     * @return bool TRUE if it's a leap year. FALSE if it is not a leap year.
     */
    public function isLeapYear($date = null){
        //Use the current timestamp by default.
        $ts = time();
        //A specific year or date was given.
        if(!is_null($date)){
            //A year was provided by itself... probably.
            if(strlen($date) == 4){
                //Create a full date string.
                $date = $date . '-01-01';
            }
            $ts = strtotime("$date");
        }
        //If date "L" returns a 1 string, it was a leap year.
        if(date('L', $ts) == 1){
            return true;
        }
        //Otherwise, return false.
        return false;
    }
}
