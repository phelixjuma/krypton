<?php
/**
 * Created by PhpStorm.
 * User: Allan
 * Date: 08/04/2019
 * Time: 15:29
 */

namespace Kuza\Krypton\Classes;

use Kuza\Krypton\Config\Config;
use AfricasTalking\SDK\AfricasTalking;

class SendSMS
{

    public $username = "";
    public $apiKey   = "";
    public $from   = "";
    public $status = "";
    public $at = "";

    public function __construct()
    {
        $username = Config::getSpecificConfig("AFRICAS_TALKING_USERNAME");
        $apiKey   = Config::getSpecificConfig("AFRICAS_TALKING_API_KEY");
        $from   = Config::getSpecificConfig("AFRICAS_TALKING_SENDER_ID");
        $this->username = $username;
        $this->apiKey = $apiKey;
        $this->from = $from;
        $AT = new AfricasTalking($this->username, $this->apiKey);
        $this->at = $AT;
    }

    /**
     * Send SMS to one phone number
     * @param $phone_number
     * @param $message
     * @return bool
     */
    public function sendSms($phone_number, $message){
        $sms      = $this->at->sms();

        $result   = $sms->send([
            "to"      => $phone_number,
            "message" => $message,
            "from" => $this->from
        ]);

        $status = $result['status'];
        if ($status == "success"){
            return true;
        }else{
            return false;
        }
    }

    /**
     * Get application data such as airtime balance
     * @return array
     */
    public function getApplicationData(){
        $application = $this->at->application();

        return $application->fetchApplicationData();
    }


}