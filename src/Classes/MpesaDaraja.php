<?php
/**
 * Created by PhpStorm.
 * User: Allan
 * Date: 29/11/2018
 * Time: 13:03
 */

namespace Kuza\Krypton\Classes;


class MpesaDaraja
{

    public $timeOutUrl = "safdaraja/timeout";
    public $resultUrl = "safdaraja/result";
    public $callbackUrl = "safdaraja/callback";
    public $confirmationUrl = "safdarara/confirmation";
    public $validationUrl = "safdaraja/validation";

    private $c2b_consumer_key;
    private $c2b_consumer_secret;
    private $c2b_paybill;
    private $c2b_pass_key;

    private $b2c_paybill;
    private $b2c_consumer_key;
    private $b2c_consumer_secret;
    private $b2c_initiator_name;
    private $b2c_security_credential_password;
    private $b2c_security_credential;

    private $timestamp;
    private $password;
    private $token;

    private $live_base_url = "https://api.safaricom.co.ke/";
    private $sandbox_base_url = "https://sandbox.safaricom.co.ke/";
    private $baseURL = "";

    /**
     * MpesaDaraja constructor.
     *
     * Default uses sandbox
     */
    public function __construct() {
        $this->baseURL = $this->sandbox_base_url;
    }

    /**
     * Switch to using live
     * @return $this
     */
    public function useLive() {
        $this->baseURL = $this->live_base_url;

        return $this;
    }

    /**
     * Switch to using sandbox
     * @return $this
     */
    public function useSandbox() {
        $this->baseURL = $this->sandbox_base_url;

        return $this;
    }

    /**
     * Initialize C2B transaction.
     * @param $c2bConsumerKey
     * @param $c2bConsumerSecret
     * @param $passKey
     * @param $c2bPayBill
     * @return $this
     */
    public function initC2B($c2bConsumerKey, $c2bConsumerSecret, $passKey, $c2bPayBill) {

        $this->c2b_consumer_key = $c2bConsumerKey;
        $this->c2b_consumer_secret = $c2bConsumerSecret;
        $this->c2b_pass_key = $passKey;
        $this->c2b_paybill = $c2bPayBill;

        $this->timestamp = $this->GenerateTimestamp();
        $this->password = $this->GeneratePassword($this->c2b_paybill, $this->c2b_pass_key, $this->timestamp);
        $this->token = $this->authToken($this->c2b_consumer_key,$this->c2b_consumer_secret)->access_token;

        return $this;
    }

    /**
     * Initialize B2C transaction
     * @param $b2cConsumerKey
     * @param $b2cConsumerSecret
     * @param $securityCredentialPassword
     * @param $initiatorName
     * @param $b2cPayBill
     * @param string $publicKeyFile
     * @param string $productionKeyFile
     * @return $this
     */
    public function initB2C($b2cConsumerKey, $b2cConsumerSecret, $securityCredentialPassword, $initiatorName, $b2cPayBill, $publicKeyFile = "", $productionKeyFile = "") {

        $this->b2c_consumer_key = $b2cConsumerKey;
        $this->b2c_consumer_secret = $b2cConsumerSecret;
        $this->b2c_security_credential_password =  $securityCredentialPassword;
        $this->b2c_initiator_name = $initiatorName;
        $this->b2c_paybill = $b2cPayBill;

        $this->token = $this->authToken($this->b2c_consumer_key,$this->b2c_consumer_secret)->access_token;

        if (!empty($publicKeyFile) && !empty($productionKeyFile)) {
            $this->setSecurityCredential($publicKeyFile, $productionKeyFile);
        }

        return $this;
    }

    /**
     * Generating authentication token
     * @param $consumer_key
     * @param $consumer_secret
     * @return mixed
     */
    private function authToken($consumer_key, $consumer_secret){
        return $this->sendTokenRequest($consumer_key, $consumer_secret);
    }

    /**
     * Reversing Transactions
     * Reversal for an erroneous C2B transaction.
     * @param $TransactionID
     * @param $Amount
     * @param $ReceiverParty
     * @param $Remarks
     * @param null $Occasion
     * @param $timeouturl
     * @param $resulturl
     * @return mixed
     */
    public function TransactionReversal($TransactionID, $Amount, $ReceiverParty, $Remarks, $Occasion = null, $timeouturl, $resulturl){
        $commandId = "TransactionReversal";

        $url = $this->baseURL . 'mpesa/reversal/v1/request';

        $requestBody = array(
            //Fill in the request parameters with valid values
            'Initiator' => $this->b2c_initiator_name,
            'SecurityCredential' => $this->b2c_security_credential,
            'CommandID' => $commandId,
            'TransactionID' => $TransactionID,
            'Amount' => $Amount,
            'ReceiverParty' => $ReceiverParty,
            'ReceiverIdentifierType' => '4',
            'ResultURL' => $this->baseURL . $resulturl,
            'QueueTimeOutURL' => $this->baseURL. $timeouturl,
            'Remarks' => $Remarks,
            'Occasion' => $Occasion
        );

        return $this->sendRequest($url, $requestBody);
    }

    /**
     * Business payment e.g send money from business to customers e.g refunds
     * This API enables Business to Customer (B2C) transactions between a company and customers who are the end-users of its products or services.
     * @param int $Amount
     * @param string $phoneNumber
     * @param null $Remarks
     * @param null $timeouturl
     * @param null $resulturl
     * @return mixed
     */
    public function BusinessPayment($Amount, $phoneNumber, $Remarks = null, $timeouturl = null, $resulturl = null){
        $commandId = "BusinessPayment";

        $url = $this->baseURL . 'mpesa/b2c/v1/paymentrequest';

        $requestBody = array(
            //Fill in the request parameters with valid values
            "InitiatorName" => $this->b2c_initiator_name,
            "SecurityCredential" => $this->b2c_security_credential,
            "CommandID" => $commandId,
            "Amount" => $Amount,
            "PartyA" => $this->b2c_paybill,
            "PartyB" => $phoneNumber,
            "Remarks" => $Remarks,
            'QueueTimeOutURL' => $this->baseURL . $timeouturl,
            'ResultURL' => $this->baseURL . $resulturl,
            'Occasion' => ' '
        );

        return $this->sendRequest($url, $requestBody);

    }

    /**
     * Account Balance
     * The Account Balance API requests for the account balance of a shortcode.
     * @param $shortCode
     * @param null $Remarks
     * @return mixed
     */
    public function AccountBalance($shortCode, $Remarks = null){
        $commandId = "AccountBalance";

        $url = $this->baseURL . 'mpesa/accountbalance/v1/query';

        $requestBody = array(
            //Fill in the request parameters with valid values
            'Initiator' => $this->b2c_initiator_name,
            'SecurityCredential' => $this->b2c_security_credential,
            'CommandID' => $commandId,
            'PartyA' => $shortCode,
            'IdentifierType' => '4',
            'Remarks' => $Remarks,
            'QueueTimeOutURL' => $this->baseURL . $this->timeOutUrl,
            'ResultURL' => $this->baseURL . $this->resultUrl
        );

        return $this->sendRequest($url, $requestBody);
    }

    /**
     * Customer Paybill Online
     * Used to simulate a transaction taking place in the case of C2B Simulate Transaction or to initiate a transaction on behalf of the customer (STK Push).
     * @param $amount
     * @param $phoneNumber
     * @param $accountReference
     * @param $transactionDesc
     * @param $callbackUrl
     * @return mixed
     */
    public function CustomerPayBillOnline($amount, $phoneNumber, $accountReference, $transactionDesc, $callbackUrl) {

        $commandId = "CustomerPayBillOnline";

        $url = $this->baseURL . 'mpesa/stkpush/v1/processrequest';

        $requestBody = array(
            //Fill in the request parameters with valid values
            'BusinessShortCode' => $this->c2b_paybill,
            'Password' => $this->password,
            'Timestamp' => $this->timestamp,
            'TransactionType' => $commandId,
            'Amount' => $amount,
            'PartyA' => $phoneNumber,
            'PartyB' => $this->c2b_paybill,
            'PhoneNumber' => $phoneNumber,
            'CallBackURL' => $callbackUrl,
            'AccountReference' => $accountReference,
            'TransactionDesc' => $transactionDesc
        );

        return $this->sendRequest($url, $requestBody);
    }

    /**
     * Transaction Status Query
     * Used to query the details of a transaction.
     * @param $TransactionID
     * @param $PartyA
     * @param $Remarks
     * @param null $Occasion
     * @return mixed
     */
    public function TransactionStatusQuery($TransactionID, $PartyA, $Remarks, $Occasion = null){
        $commandId = "TransactionStatusQuery";

        $url = $this->baseURL . 'mpesa/transactionstatus/v1/query';

        $requestBody = array(
            //Fill in the request parameters with valid values
            'Initiator' => $this->b2c_initiator_name,
            'SecurityCredential' => $this->b2c_security_credential,
            'CommandID' => $commandId,
            'TransactionID' => $TransactionID,
            'PartyA' => $PartyA,
            'IdentifierType' => '1',
            'ResultURL' => $this->resultUrl,
            'QueueTimeOutURL' => $this->baseURL . $this->timeOutUrl,
            'Remarks' => $Remarks,
            'Occasion' => $Occasion
        );

        return $this->sendRequest($url, $requestBody);

    }

    /**
     * STK Push Online Query Request
     * @param $CheckoutRequestID
     * @return string
     */
    public function lnmOnlineQueryRequest($CheckoutRequestID){

        $url = $this->baseURL . 'mpesa/stkpushquery/v1/query';

        $requestBody = array(
            //Fill in the request parameters with valid values
            'BusinessShortCode' => $this->c2b_paybill,
            'Password' => $this->password,
            'Timestamp' => $this->timestamp,
            'CheckoutRequestID' => $CheckoutRequestID
        );

        return $this->sendRequest($url, $requestBody);
    }

    /**
     * Business PayBill
     * Sending funds from one paybill to another paybill
     * This API enables Business to Business (B2B) transactions between a business and another business.
     * @param $Amount
     * @param $receiverPayBill
     * @param $AccountReference
     * @param string $ReceiverIdentifierType
     * @param string $SenderIdentifierType
     * @param null $Remarks
     * @return mixed
     */
    public function BusinessPayBill($Amount, $receiverPayBill, $AccountReference, $ReceiverIdentifierType = "Shortcode",$SenderIdentifierType = "Shortcode", $Remarks = null){
        $commandId = "BusinessPayBill";

        $url = $this->baseURL . 'mpesa/b2b/v1/paymentrequest';

        $requestBody = array(
            //Fill in the request parameters with valid values
            'Initiator' => $this->b2c_initiator_name,
            'SecurityCredential' => $this->b2c_security_credential,
            'CommandID' => $commandId,
            'SenderIdentifierType' => $SenderIdentifierType,
            'ReceiverIdentifierType' => $ReceiverIdentifierType,
            'Amount' => $Amount,
            'PartyA' => $this->b2c_paybill,
            'PartyB' => $receiverPayBill,
            'AccountReference' => $AccountReference,
            'Remarks' => $Remarks,
            'QueueTimeOutURL' => $this->baseURL . $this->timeOutUrl,
            'ResultURL' => $this->baseURL . $this->resultUrl,
        );

        return $this->sendRequest($url, $requestBody);

    }

    /**
     * C2B Register URL - Resource URL
     * @param $ValidationURL
     * @param $ConfirmationURL
     * @param $ResponseType
     * @return array
     */
    public function RegisterC2BUrl($ValidationURL, $ConfirmationURL, $ResponseType, $ShortCode){

        $url = $this->baseURL . 'mpesa/c2b/v1/registerurl';

        $requestBody = array(
            //Fill in the request parameters with valid values
            "ShortCode" => $ShortCode,
            "ResponseType" => $ResponseType,
            "ConfirmationURL" => $ConfirmationURL,
            "ValidationURL" => $ValidationURL
        );

        return $this->sendRequest($url, $requestBody);

    }

    /**
     * Check Identity
     * Similar to STK push, uses M-Pesa PIN as a service.
     * @param $Amount
     * @param $phoneNumber
     * @param $AccountReference
     * @param $TransactionDesc
     * @return mixed
     */
    public function CheckIdentity($Amount, $phoneNumber, $AccountReference, $TransactionDesc){
        $commandId = "CheckIdentity";

        $url = $this->baseURL . 'mpesa/stkpush/v1/processrequest';

        $requestBody = array(
            //Fill in the request parameters with valid values
            'BusinessShortCode' => $this->c2b_paybill,
            'Password' => $this->password,
            'Timestamp' => $this->timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'CommandID' => $commandId,
            'Amount' => $Amount,
            'PartyA' => $phoneNumber,
            'PartyB' => $this->c2b_paybill,
            'PhoneNumber' => $phoneNumber,
            'CallBackURL' => $this->callbackUrl,
            'AccountReference' => $AccountReference,
            'TransactionDesc' => $TransactionDesc
        );

        return $this->sendRequest($url, $requestBody);
    }

    /**
     * Used to generate Security Credential
     * Base64 encoded string of the Security Credential, which is encrypted using M-Pesa public key and validates the transaction on M-Pesa Core system.
     * @param $publicKeyFile
     * @param $productionKeyFile
     */
    private function setSecurityCredential($publicKeyFile, $productionKeyFile) {

        $fp = fopen($publicKeyFile,"r");
        $publicKey = fread($fp, filesize($productionKeyFile));

        fclose($fp);

        openssl_get_publickey($publicKey);
        openssl_public_encrypt($this->b2c_security_credential_password, $encrypted, $publicKey, OPENSSL_PKCS1_PADDING);

        $this->b2c_security_credential =  base64_encode($encrypted);
    }

    /**
     * Generates Password for mpesa transactions
     * @param $BusinessShortCode
     * @param $Passkey
     * @param $Timestamp
     * @return string
     */
    private function GeneratePassword($BusinessShortCode, $Passkey, $Timestamp){

        return base64_encode($BusinessShortCode.$Passkey.$Timestamp);

    }

    /**
     * Send the request
     * @param $url
     * @param $requestBody
     * @return mixed
     */
    private function sendRequest($url, $requestBody) {

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type:application/json","Authorization:Bearer $this->token")); //setting custom header

        $data_string = json_encode($requestBody);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);

        $curl_response = curl_exec($curl);

        return $curl_response;
    }

    /**
     * Send token query request
     * @param $consumerKey
     * @param $consumerSecret
     * @return mixed
     */
    private function sendTokenRequest($consumerKey, $consumerSecret) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->baseURL."oauth/v1/generate?grant_type=client_credentials");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");

        curl_setopt($ch, CURLOPT_USERPWD, $consumerKey . ":" . $consumerSecret);

        $headers = array();
        $headers[] = "Accept: application/json";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = json_decode(curl_exec($ch));
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close ($ch);

        return $result;
    }


    /**
     * Generate Timestamp
     * @return false|string
     */
    private function GenerateTimestamp(){
        return date("YmdHis");
    }

    /**
     * Formats amount to the mpesa required format
     * @param $amount
     * @return string
     */
    private function GenerateAmount($amount){
        return number_format($amount,0, '.', '');
    }

    /**
     * Returns the value for mpesa transaction result parameters
     * @param $data
     * @param $parameter
     * @return string | null
     */
    public function getMpesaResultParameters($data, $parameter){

        $x = Data::searchMultiArrayByKeyReturnKeys($data['Result']['ResultParameters']['ResultParameter'],"Key",$parameter);

        if ($x){
            return $x['Value'];
        }

        return null;

    }
}