<?php
/**
 * This is script handles files
 * @author Phelix Juma <jumaphelix@Kuza\Krypton.co.ke>
 * @copyright (c) 2018, Kuza Lab
 * @package Kuza\Krypton
 */

namespace Kuza\Krypton\Classes;


use Aws\S3\Exception\S3Exception;
use Kuza\Krypton\Config\Config;
use Aws\Credentials\Credentials;
use Aws\S3\S3Client;
use Kuza\Krypton\Exceptions\CustomException;

/**
 * Class for managing uploads to S3
 * @package Kuza\Krypton
 */
class S3 {

    private $credentials;
    public $bucket;

    /**
     * @var $s3 S3Client
     */
    protected $s3;

    /**
     * S3 constructor.
     */
    public function __construct() {
    }

    /**
     *
     * @param string $version
     * @param string $region
     * @param string $accessKey
     * @param string $accessSecret
     * @return $this
     * @throws \Kuza\Krypton\Exceptions\ConfigurationException
     */
    public function init($version="latest", $region="eu-west-1", $accessKey = "", $accessSecret= "") {
        if (empty($accessKey)) {
            $accessKey = Config::getAWSAccessKey();
        }
        if (empty($accessSecret)) {
            $accessSecret = Config::getAWSAccessSecret();
        }

        $this->credentials = new Credentials($accessKey, $accessSecret);

        $this->s3 = new S3Client([
            'version'       => $version,
            'region'        => $region,
            'credentials'   => $this->credentials
        ]);

        return $this;
    }

    /**
     * Set the bucket into which the document is to be uploaded
     *
     * @param $bucketName
     * @return $this
     */
    public function setBucket($bucketName) {
        $this->bucket = $bucketName;

        return $this;
    }

    /**
     * Upload a file to AWS.
     * This uploads files without generating a signed CloudFront URL
     * @param $source_file
     * @param $destination_directory
     * @param $destination_file_name
     * @param $mime_type
     * @return bool
     * @throws \Exception
     */
    public function uploadFile($source_file, $destination_directory,$destination_file_name, $mime_type) {
        $success = false;
        try {

            $options = array(
                'Bucket'        => $this->bucket,
                'ContentType'   => $mime_type,
                'Key'           => $destination_directory."/".$destination_file_name,
                'SourceFile'    => $source_file,
                'CacheControl'  => 'max-age=172800',
                "Expires"       => gmdate("D, d M Y H:i:s T", strtotime("+5 years"))
            );

            $response = $this->s3->putObject($options);


            if (isset($response['ObjectURL']) && strlen($response['ObjectURL']) > 0) {
                $success = true;
            }
        } catch (S3Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }
        return $success;
    }

    /**
     * Upload a file to AWS.
     * This uploads files without generating a signed CloudFront URL
     * @param $source_file
     * @param $destination_directory
     * @param $destination_file_name
     * @param $mime_type
     * @return bool
     * @throws \Exception
     */
    public function uploadFileStream($source_file, $destination_directory,$destination_file_name, $mime_type) {
        $success = false;
        try {

            $options = array(
                'Bucket'        => $this->bucket,
                'ContentType'   => $mime_type,
                'Key'           => $destination_directory."/".$destination_file_name,
                'Body'    => $source_file,
                'CacheControl'  => 'max-age=172800',
                "Expires"       => gmdate("D, d M Y H:i:s T", strtotime("+5 years"))
            );

            $response = $this->s3->putObject($options);


            if (isset($response['ObjectURL']) && strlen($response['ObjectURL']) > 0) {
                $success = true;
            }
        } catch (S3Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }
        return $success;
    }


    /**
     * Get the id of the CloudFront key pair. This is generated from CloudFront
     *
     * @return array|false|string
     * @throws \Kuza\Krypton\Exceptions\ConfigurationException
     */
    private static function getCloudFrontKeyPairId() {
        return Config::getCloudFrontKeyPairId(); // This is the id of the Cloudfront key pair you generated
    }

    /**
     * Get the CloudFront private key
     *
     * @param $privateKeyFile
     * @return bool|string
     */
    private static function getCloudFrontPrivateKey($privateKeyFile) {

        $fp = fopen($privateKeyFile, "r");
        $privateKey = fread($fp,8192);
        fclose($fp);

        return $privateKey;
    }

    /**
     * Used to sign a private resource (file) in CloudFront
     * This is a canned policy
     * @param string $resource full CloudFront url of the resources
     * @param integer $timeout timeout in seconds
     * @param string $privateKeyFile
     * @return string signed url
     * @throws \Exception
     */
    public static function getSignedURL($resource, $timeout, $privateKeyFile) {

        // get the key pair id
        $keyPairId = self::getCloudFrontKeyPairId();

        // get the expiry. // Timeout in seconds
        $expires = time() + $timeout;

        // set the policy statement
        $policyStatement = '{"Statement":[{"Resource":"'.$resource.'","Condition":{"DateLessThan":{"AWS:EpochTime":'.$expires.'}}}]}';

        // Get the CloudFront private key
        $privateKey = self::getCloudFrontPrivateKey($privateKeyFile);

        // Create the private key
        $key = \openssl_get_privatekey($privateKey);

        if (!$key) {
            throw new \Exception('Loading private key failed');
        }
        // Sign the policy with the private key
        if (!\openssl_sign($policyStatement, $signed_policy, $key, OPENSSL_ALGO_SHA1)) {
            throw new \Exception('Signing policy failed, '.openssl_error_string());
        }

        // Create url safe signed policy
        $base64_signed_policy = base64_encode($signed_policy);
        $signature = str_replace(array('+','=','/'), array('-','_','~'), $base64_signed_policy);

        // Construct the URL
        $url = $resource .  (strpos($resource, '?') === false ? '?' : '&') . 'Expires='.$expires.'&Signature=' . $signature . '&Key-Pair-Id=' . $keyPairId;

        return $url;
    }
}