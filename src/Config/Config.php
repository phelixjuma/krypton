<?php
/**
 * This is script handles system configurations
 * @author Phelix Juma <jumaphelix@Kuza\Krypton.co.ke>
 * @copyright (c) 2018, Kuza Lab
 * @package Kuza\Krypton
 */

namespace Kuza\Krypton\Config;

use Kuza\Krypton\Exceptions\ConfigurationException;
use Kuza\Krypton\Classes\Requests;
use Kuza\Krypton\Classes\Utils;

/**
 * Application configuration.
 */
final class Config
{

    const TITLE = "G-Money";
    const TAGLINE = "";

    const PAGE_SIZE = 20;

    const DEFAULT_AVATAR = "";
    const LOGO = "";

    const MAX_FILE_SIZE = 200000;

    const DEFAULT_PAGE = 'home';
    const CONTROLLERS_DIR = 'Controllers/';
    const VIEWS_DIR = 'Views/';
    const LAYOUT_DIR = 'Layout/';
    const CLASSES_DIR = 'Classes/';
    const MODELS_DIR = 'Models/';
    const CONFIG_DIR = 'Config/';
    const VENDOR_DIR = 'vendor/';
    const LOGS_DIR = 'Logs/';

    const RUNTIME_ERROR_LOGS = "../Logs/php-runtimeerror.log";
    const TEST_POINT_LOGS = "../Logs/testpoints.log";

    const FIREBASE_SERVICE_ACCOUNT = "firebase-service-account.json";

    public function __construct() {
    }

    /**
     * Get the database source
     * This depends on whether we are in AWS land or not
     * @return string
     * @throws ConfigurationException
     */
    public static function getSource() {
        return self::getDBEngine() . ":host=" . self::getDBHost() . ";port=" . self::getDBPort() . ";dbname=" . self::getDBName();
    }

    /**
     * Get all the configurations from the env file
     * @return mixed
     */
    public static function getConfigs() {
        return $_ENV;
    }

    /**
     * Get the environment value for a specified configuration variable
     * @param $config_param
     * @return array|false|string
     * @throws ConfigurationException
     */
    public static function getSpecificConfig($config_param) {

        if (isset($_SERVER[$config_param]) && !empty($_SERVER[$config_param])) {

            $res = $_SERVER[$config_param];

        } else {

            $res = isset($_ENV[$config_param]) ? $_ENV[$config_param] : "";

            if (empty($res)) {
                $res = getenv($config_param);
            }
        }
        if ($res === false) {
            throw new ConfigurationException("Missing SpecificConfig for " . $config_param, Requests::RESPONSE_INTERNAL_SERVER_ERROR);
        }

        $res = trim($res);
        return $res;
    }

    /**
     * Get the deployment type
     * @return array|false|string
     * @throws ConfigurationException
     */
    public static function getDeployment() {
        return self::getSpecificConfig("DEPLOYMENT");
    }

    /**
     * @return array|false|string
     * @throws ConfigurationException
     */
    public static function getURIBasePath() {
        return self::getSpecificConfig("URI_BASE_PATH");
    }

    /**
     * Get the site url
     * @return string
     * @throws ConfigurationException
     */
    public static function getSiteURL() {
        // return self::getSpecificConfig("SITE_URL");
        $scheme = isset($_SERVER['REQUEST_SCHEME']) ? Utils::escape($_SERVER['REQUEST_SCHEME']) : "http";
        $host = isset($_SERVER['HTTP_HOST']) ?  Utils::escape($_SERVER['HTTP_HOST']) : "";
        $path = self::getURIBasePath();


        return "$scheme://$host/$path/";
    }

    /**
     * Get the JWT secret
     * @return array|false|string
     * @throws ConfigurationException
     */
    public static function getJWTSecret() {
        return self::getSpecificConfig("JWT_SECRET");
    }

    /**
     * Get the database host
     * @return array|false|string
     * @throws ConfigurationException
     */
    public static function getDBHost() {
        return self::getSpecificConfig("DB_HOST");
    }

    /**
     * Get the database name
     * Database name varies depending on the environment being used and the current host
     * @return array|false|string
     * @throws ConfigurationException
     */
    public static function getDBName() {
        return self::getSpecificConfig("DB_NAME");
    }

    /**
     * Get the database engine
     * @return array|false|string
     * @throws ConfigurationException
     */
    public static function getDBEngine() {
        return self::getSpecificConfig("DB_ENGINE");
    }

    /**
     * Get the database type
     * @return array|false|string
     * @throws ConfigurationException
     */
    public static function geDBType() {
        return self::getSpecificConfig("DB_TYPE");
    }

    /**
     * Get the database port
     * @return array|false|string
     * @throws ConfigurationException
     */
    public static function getDBPort() {
        return self::getSpecificConfig("DB_PORT");
    }

    /**
     * Get the database user
     * @return array|false|string
     * @throws ConfigurationException
     */
    public static function getDBUser() {
        return self::getSpecificConfig("DB_USER");
    }

    /**
     * Get the database password
     * @return array|false|string
     * @throws ConfigurationException
     */
    public static function getDBPassword() {
        return self::getSpecificConfig("DB_PASSWORD");
    }

    /**
     * Get the mail server host
     * @return array|false|string
     * @throws ConfigurationException
     */
    public static function getMailHost() {
        return self::getSpecificConfig("MAIL_HOST");
    }

    /**
     * Get the SMTP auth status
     * @return array|false|string
     * @throws ConfigurationException
     */
    public static function getMailSMTPAuth() {
        return self::getSpecificConfig("MAIL_SMTPAUTH");
    }

    /**
     * Get the mail server user
     * @return array|false|string
     * @throws ConfigurationException
     */
    public static function getMailUsername() {
        return self::getSpecificConfig("MAIL_USERNAME");
    }

    /**
     * Get the mail server user password
     * @return array|false|string
     * @throws ConfigurationException
     */
    public static function getMailPassword() {
        return self::getSpecificConfig("MAIL_PASSWORD");
    }

    /**
     * Get the SMTP security protocol
     * @return array|false|string
     * @throws ConfigurationException
     */
    public static function getMailSMTPSecurity() {
        return self::getSpecificConfig("MAIL_SMTPSECURE");
    }

    /**
     * Get the mail server port
     * @return array|false|string
     * @throws ConfigurationException
     */
    public static function getMailPort() {
        return self::getSpecificConfig("MAIL_PORT");
    }

    /**
     * Get the access key for AWS
     * @return array|false|string
     * @throws ConfigurationException
     */
    public static function getAWSAccessKey() {
        return self::getSpecificConfig("AWS_ACCESS_KEY");
    }

    /**
     * Get the access secret for AWS
     * @return array|false|string
     * @throws ConfigurationException
     */
    public static function getAWSAccessSecret() {
        return self::getSpecificConfig("AWS_SECRET_KEY");
    }

    /**
     * Get the S3 bucket name
     * Bucket name varies depending on the environment being used and the current host
     * @return array|false|string
     * @throws ConfigurationException
     */
    public static function getS3Bucket() {
        return self::getSpecificConfig("S3_BUCKET_NAME");
    }

    /**
     * Get the AWS CloudFront Key Pair id
     * @return array|false|string
     * @throws ConfigurationException
     */
    public static function getCloudFrontKeyPairId() {
        return self::getSpecificConfig("CLOUDFRONT_KEY_PAIR_ID");
    }

    /**
     * Get the URL for accessing files in S3
     * The URL depends on the environment and use-case.
     * Public files all go to the public URL
     * @return array|false|string
     * @throws ConfigurationException
     */
    public static function getAWSDocumentURL() {
        return self::getSpecificConfig("PUBLIC_UPLOADS");
    }

    /**
     * Get the CloudFront URL for accessing files in S3
     * The URL depends on the environment and use-case.
     * Public files all go to the public URL
     * @return array|false|string
     * @throws ConfigurationException
     */
    public static function getAWSCloudFrontDocumentURL() {
        return self::getSpecificConfig("CLOUDFRONT_URL");
    }
}