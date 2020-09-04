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

    const CLOUDFRONT_PRIVATE_KEY = "cloudfront-2018-11-28-private-pk-APKAIYR5D6P6UHWCD4RA.pem";
    private $credentials;
    private $s3;
    public $bucket;

    /**
     * S3 constructor.
     */
    public function __construct() {
        $this->credentials = new Credentials(Config::getAWSAccessKey(), Config::getAWSAccessSecret());

        $this->s3 = new S3Client([
            'version'       => 'latest',
            'region'        => 'eu-west-1',
            'credentials'   => $this->credentials
        ]);
    }

    /**
     * Set the bucket into which the document is to be uploaded
     */
    public function setBucket() {
        $this->bucket = "gmoney-public-uploads";
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
     * @return string
     * @throws CustomException
     */
    private static function getCloudFrontKeyPairId() {
        return Config::getCloudFrontKeyPairId(); // This is the id of the Cloudfront key pair you generated
    }

    /**
     * Get the directory where the Keys reside
     * @return string
     */
    private static function getKeysDirectory() {
        return dirname(__DIR__).'/Keys/';
    }

    /**
     * Get the CloudFront private key
     * @return bool|string
     */
    private static function getCloudFrontPrivateKey() {

        $privateKey = self::getKeysDirectory().self::CLOUDFRONT_PRIVATE_KEY;

        $fp = fopen($privateKey, "r");
        $privateKey = fread($fp,8192);
        fclose($fp);

        return $privateKey;
    }

    /**
     * Used to sign a private resource (file) in CloudFront
     * This is a canned policy
     * @param string $resource full CloudFront url of the resources
     * @param integer $timeout timeout in seconds
     * @return string signed url
     * @throws \Exception
     */
    public static function getSignedURL($resource, $timeout) {

        // get the key pair id
        $keyPairId = self::getCloudFrontKeyPairId();

        // get the expiry. // Timeout in seconds
        $expires = time() + $timeout;

        // set the policy statement
        $policyStatement = '{"Statement":[{"Resource":"'.$resource.'","Condition":{"DateLessThan":{"AWS:EpochTime":'.$expires.'}}}]}';

        // Get the CloudFront private key
        $privateKey = self::getCloudFrontPrivateKey();

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