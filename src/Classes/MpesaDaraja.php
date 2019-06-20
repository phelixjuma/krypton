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

    public $commandId;
    public $msisdn;
    public $shortcode;
    public $tillNumber;
    public $partyA;

    public $timeOutUrl = "/safdaraja/timeout";
    public $resultUrl = "/safdaraja/result";
    public $callbackUrl = "/safdaraja/callback";
    public $confirmationUrl = "/safdarara/confirmation";
    public $validationUrl = "/safdaraja/validation";

    public $c2bConsumerKey = "";
    public $c2bConsumerSecret = "";
    public $c2bPaybill = "";
    public $c2bPasskey = "";

    public $initiatorName;

    private $securityCredentialPassword;
    private $mpesa_public_key_cert_file_path;
    private $mpesa_production_key_cert_file_path;

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
     * Set party A
     * @param $partyA
     * @return $this
     */
    public function setPartyA($partyA) {
        $this->partyA = $partyA;

        return $this;
    }

    /**
     * Set the security credential password
     * @param $password
     * @return $this
     */
    public function setSecurityCredentialPassword($password) {
        $this->securityCredentialPassword = $password;

        return $this;
    }

    /**
     * Set the initiator name.
     * @param $name
     * @return $this
     */
    public function setInitiatorName($name) {
        $this->initiatorName = $name;

        return $this;
    }

    /**
     * Set the public cert key.
     * @param $filename
     * @return $this
     */
    public function setPublicCertKeyFile($filename) {
        $this->mpesa_public_key_cert_file_path = $filename;

        return $this;
    }

    /**
     * Set the production cert key
     * @param $filename
     * @return $this
     */
    public function setProductionCertKeyFile($filename) {
        $this->mpesa_production_key_cert_file_path = $filename;

        return $this;
    }

    /**
     * Generating authentication token
     * @param $consumer_key
     * @param $consumer_secret
     * @return mixed
     */
    public function authToken($consumer_key, $consumer_secret){
        //API Keys for lipa na mpesa account
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->baseURL."oauth/v1/generate?grant_type=client_credentials");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");

        curl_setopt($ch, CURLOPT_USERPWD, $consumer_key . ":" . $consumer_secret);

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
     * Reversing Transactions
     * Reversal for an erroneous C2B transaction.
     * @param $token
     * @param $Initiator
     * @param $SecurityCredential
     * @param $TransactionID
     * @param $Amount
     * @param $ReceiverParty
     * @param $Remarks
     * @param null $Occasion
     * @param $timeouturl
     * @param $resulturl
     * @return mixed
     */
    public function TransactionReversal($token, $Initiator, $SecurityCredential, $TransactionID, $Amount, $ReceiverParty, $Remarks, $Occasion = null, $timeouturl, $resulturl){
        $commandId = "TransactionReversal";

        $url = $this->baseURL . 'mpesa/reversal/v1/request';

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type:application/json","Authorization:Bearer $token")); //setting custom header

        $curl_post_data = array(
            //Fill in the request parameters with valid values
            'Initiator' => $Initiator,
            'SecurityCredential' => $SecurityCredential,
            'CommandID' => $commandId,
            'TransactionID' => $TransactionID,
            'Amount' => $Amount,
            'ReceiverParty' => $ReceiverParty,
            'ReceiverIdentifierType' => '4',
            'ResultURL' => $resulturl,
            'QueueTimeOutURL' => $timeouturl,
            'Remarks' => $Remarks,
            'Occasion' => $Occasion
        );

        $data_string = json_encode($curl_post_data);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);

        $curl_response = curl_exec($curl);

        return $curl_response;
    }

    /**
     * Business payment e.g send money from business to customers e.g refunds
     * This API enables Business to Customer (B2C) transactions between a company and customers who are the end-users of its products or services.
     * @param null $InitatorName
     * @param null $token
     * @param null $SecurityCredential
     * @param null $Amount
     * @param null $PartyA
     * @param null $PartyB
     * @param $Remarks
     * @param null $timeouturl
     * @param null $resulturl
     * @return mixed
     */
    public function BusinessPayment($InitatorName = null,$token = null, $SecurityCredential = null, $Amount = null, $PartyA = null, $PartyB = null, $Remarks = null, $timeouturl = null, $resulturl = null){
        $commandId = "BusinessPayment";

        $url = $this->baseURL . 'mpesa/b2c/v1/paymentrequest';

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type:application/json","Authorization:Bearer $token")); //setting custom header

        $curl_post_data = array(
            //Fill in the request parameters with valid values
            "InitiatorName" => $InitatorName,
            "SecurityCredential" => $SecurityCredential,
            "CommandID" => $commandId,
            "Amount" => $Amount,
            "PartyA" => $PartyA,
            "PartyB" => $PartyB,
            "Remarks" => $Remarks,
            'QueueTimeOutURL' => $timeouturl,
            'ResultURL' => $resulturl,
            'Occasion' => ' '
        );

        $data_string = json_encode($curl_post_data);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);

        $curl_response = curl_exec($curl);

        return $curl_response;

    }

    /**
     * Account Balance
     * The Account Balance API requests for the account balance of a shortcode.
     * @param $Initiator
     * @param $SecurityCredential
     * @param $PartyA
     * @param null $Remarks
     * @param $token
     * @return mixed
     */
    public function AccountBalance($Initiator, $SecurityCredential, $PartyA, $token, $Remarks = null){
        $commandId = "AccountBalance";

        $url = $this->baseURL . 'mpesa/accountbalance/v1/query';

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type:application/json","Authorization:Bearer $token")); //setting custom header

        $curl_post_data = array(
            //Fill in the request parameters with valid values
            'Initiator' => $Initiator,
            'SecurityCredential' => $SecurityCredential,
            'CommandID' => $commandId,
            'PartyA' => $PartyA,
            'IdentifierType' => '4',
            'Remarks' => $Remarks,
            'QueueTimeOutURL' => $this->timeOutUrl,
            'ResultURL' => $this->resultUrl
        );

        $data_string = json_encode($curl_post_data);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);

        $curl_response = curl_exec($curl);

        return $curl_response;
    }

    /**
     * Customer Paybill Online
     * Used to simulate a transaction taking place in the case of C2B Simulate Transaction or to initiate a transaction on behalf of the customer (STK Push).
     * @param $token
     * @param $BusinessShortCode
     * @param $Password
     * @param $Timestamp
     * @param $Amount
     * @param $PartyA | The MSISDN sending the funds.
     * @param $PartyB | The organization shortcode receiving the funds
     * @param $PhoneNumber | The MSISDN sending the funds.
     * @param $AccountReference
     * @param $TransactionDesc
     * $param $callbackurl
     * @return mixed
     */
    public function CustomerPayBillOnline($token,$BusinessShortCode, $Password, $Timestamp, $Amount, $PartyA, $PartyB, $PhoneNumber, $AccountReference, $TransactionDesc, $callbackurl){
        $commandId = "CustomerPayBillOnline";

        $url = $this->baseURL . 'mpesa/stkpush/v1/processrequest';

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type:application/json","Authorization:Bearer $token")); //setting custom header


        $curl_post_data = array(
            //Fill in the request parameters with valid values
            'BusinessShortCode' => $BusinessShortCode,
            'Password' => $Password,
            'Timestamp' => $Timestamp,
            'TransactionType' => $commandId,
            'Amount' => $Amount,
            'PartyA' => $PartyA,
            'PartyB' => $PartyB,
            'PhoneNumber' => $PhoneNumber,
            'CallBackURL' => $callbackurl,
            'AccountReference' => $AccountReference,
            'TransactionDesc' => $TransactionDesc
        );

        $data_string = json_encode($curl_post_data);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);

        $curl_response = curl_exec($curl);

        return $curl_response;
    }

    /**
     * Transaction Status Query
     * Used to query the details of a transaction.
     * @param $SecurityCredential
     * @param $token
     * @param $Initiator
     * @param $TransactionID
     * @param $PartyA
     * @param $Remarks
     * @param null $Occasion
     * @return mixed
     */
    public function TransactionStatusQuery($SecurityCredential, $token, $Initiator, $TransactionID, $PartyA, $Remarks, $Occasion = null){
        $commandId = "TransactionStatusQuery";

        $url = $this->baseURL . 'mpesa/transactionstatus/v1/query';

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type:application/json","Authorization:Bearer $token")); //setting custom header


        $curl_post_data = array(
            //Fill in the request parameters with valid values
            'Initiator' => $Initiator,
            'SecurityCredential' => $SecurityCredential,
            'CommandID' => $commandId,
            'TransactionID' => $TransactionID,
            'PartyA' => $PartyA,
            'IdentifierType' => '1',
            'ResultURL' => $this->resultUrl,
            'QueueTimeOutURL' => $this->timeOutUrl,
            'Remarks' => $Remarks,
            'Occasion' => $Occasion
        );

        $data_string = json_encode($curl_post_data);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);

        $curl_response = curl_exec($curl);

        return $curl_response;

    }

    /**
     * STK Push Online Query Request
     * @param $BusinessShortCode
     * @param $Password
     * @param $Timestamp
     * @param $token
     * @param $CheckoutRequestID
     * @return string
     */
    public function lnmOnlineQueryRequest($BusinessShortCode, $Password, $Timestamp, $CheckoutRequestID, $token){

        $url = $this->baseURL . 'mpesa/stkpushquery/v1/query';

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type:application/json","Authorization:Bearer $token")); //setting custom header

        $curl_post_data = array(
            //Fill in the request parameters with valid values
            'BusinessShortCode' => $BusinessShortCode,
            'Password' => $Password,
            'Timestamp' => $Timestamp,
            'CheckoutRequestID' => $CheckoutRequestID
        );

        $data_string = json_encode($curl_post_data);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);

        $curl_response = curl_exec($curl);

        return $curl_response;
    }

    /**
     * Business PayBill
     * Sending funds from one paybill to another paybill
     * This API enables Business to Business (B2B) transactions between a business and another business.
     * @param $Initiator
     * @param null $token
     * @param null $SecurityCredential
     * @param string $SenderIdentifierType
     * @param string $ReceiverIdentifierType
     * @param null $Amount
     * @param null $PartyA
     * @param null $PartyB
     * @param $AccountReference (Mandatory)
     * @param null $Remarks
     * @return mixed
     */
    public function BusinessPayBill($Initiator,$token, $SecurityCredential, $AccountReference, $ReceiverIdentifierType = "Shortcode",$SenderIdentifierType = "Shortcode", $Amount = null, $PartyA = null, $PartyB = null, $Remarks = null){
        $commandId = "BusinessPayBill";

        $url = $this->baseURL . 'mpesa/b2b/v1/paymentrequest';

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type:application/json","Authorization:Bearer $token")); //setting custom header

        $curl_post_data = array(
            //Fill in the request parameters with valid values
            'Initiator' => $Initiator,
            'SecurityCredential' => $SecurityCredential,
            'CommandID' => $commandId,
            'SenderIdentifierType' => $SenderIdentifierType,
            'ReceiverIdentifierType' => $ReceiverIdentifierType,
            'Amount' => $Amount,
            'PartyA' => $PartyA,
            'PartyB' => $PartyB,
            'AccountReference' => $AccountReference,
            'Remarks' => $Remarks,
            'QueueTimeOutURL' => $this->timeOutUrl,
            'ResultURL' => $this->resultUrl,
        );

        $data_string = json_encode($curl_post_data);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);

        $curl_response = curl_exec($curl);

        return $curl_response;

    }

    /**
     * C2B Register URL - Resource URL
     * @param $ValidationURL
     * @param $ConfirmationURL
     * @param $ResponseType
     * @param $ShortCode
     * @return array
     */
    public function RegisterC2BUrl($ValidationURL, $ConfirmationURL, $ResponseType, $ShortCode, $token){

        $url = $this->baseURL . 'mpesa/c2b/v1/registerurl';

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type:application/json","Authorization:Bearer $token")); //setting custom header

        $curl_post_data = array(
            //Fill in the request parameters with valid values
            "ShortCode" => $ShortCode,
            "ResponseType" => $ResponseType,
            "ConfirmationURL" => $ConfirmationURL,
            "ValidationURL" => $ValidationURL
        );

        $data_string = json_encode($curl_post_data);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);

        $curl_response = curl_exec($curl);

        return $curl_response;

    }

    /**
     * Used to generate Security Credential
     * Base64 encoded string of the Security Credential, which is encrypted using M-Pesa public key and validates the transaction on M-Pesa Core system.
     * @return string
     */
    public function SecurityCredential(){

        $fp = fopen($this->mpesa_public_key_cert_file_path,"r");
        $publicKey = fread($fp, filesize($this->mpesa_production_key_cert_file_path));

        fclose($fp);

        openssl_get_publickey($publicKey);
        openssl_public_encrypt($this->securityCredentialPassword, $encrypted, $publicKey, OPENSSL_PKCS1_PADDING);

        return base64_encode($encrypted);
    }

    /**
     * Generates Password for mpesa transactions
     * @param $BusinessShortcode
     * @param $Passkey
     * @param $Timestamp
     * @return string
     */
    public function GeneratePassword($BusinessShortcode, $Passkey, $Timestamp){

        return base64_encode($BusinessShortcode.$Passkey.$Timestamp);

    }


    /**
     * Check Identity
     * Similar to STK push, uses M-Pesa PIN as a service.
     * @param $BusinessShortCode
     * @param $Password
     * @param $Timestamp
     * @param $Amount
     * @param $PartyA
     * @param $PartyB
     * @param $PhoneNumber
     * @param $AccountReference
     * @param $TransactionDesc
     * @return mixed
     */
    public function CheckIdentity($BusinessShortCode, $Password, $Timestamp, $Amount, $PartyA, $PartyB, $PhoneNumber, $AccountReference, $TransactionDesc, $token){
        $commandId = "CheckIdentity";

        $url = $this->baseURL . 'mpesa/stkpush/v1/processrequest';

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type:application/json","Authorization:Bearer $token")); //setting custom header


        $curl_post_data = array(
            //Fill in the request parameters with valid values
            'BusinessShortCode' => $BusinessShortCode,
            'Password' => $Password,
            'Timestamp' => $Timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'CommandID' => $commandId,
            'Amount' => $Amount,
            'PartyA' => $PartyA,
            'PartyB' => $PartyB,
            'PhoneNumber' => $PhoneNumber,
            'CallBackURL' => $this->callbackUrl,
            'AccountReference' => $AccountReference,
            'TransactionDesc' => $TransactionDesc
        );

        $data_string = json_encode($curl_post_data);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);

        $curl_response = curl_exec($curl);

        return $curl_response;
    }


    /**
     * Generate Timestamp
     * @return false|string
     */
    public function GenerateTimestamp(){
        return date("YmdHis");
    }

    /**
     * Formats amount to the mpesa required format
     * @param $amount
     * @return string
     */
    public function GenerateAmount($amount){
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