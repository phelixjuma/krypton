<?php

/**
 * This is script handles requests.
 * @author Phelix Juma <jumaphelix@kuzalab.co.ke>
 * @copyright (c) 2018, Kuza Lab
 * @package Kuzalab
 */

namespace Kuza\Krypton\Classes;

use Kuza\Krypton\Config\Config;
use Kuza\Krypton\Exceptions\ConfigurationException;
use Kuza\Krypton\Exceptions\HttpException;

/**
 * Requests class methods.
 * Handles URLs and request bodies
 * Uses AltoRouter library for pattern matching
 */
final class Requests {

    /**
     * Response OK
     */
    const RESPONSE_OK = 200;

    /**
     * This status code should be returned whenever the new instance is created.
     * E.g on creating a new instance, using POST method, should always return 201 status code.
     */
    const RESPONSE_CREATED = 201;

    /**
     * Represents the request is successfully processed, but has not returned any content eg when data is deleted
     */
    const RESPONSE_NO_CONTENT = 204;

    /**
     * Indicates that the client is not allowed to access resources, and should re-request with the required credentials.
     */
    const RESPONSE_UNAUTHORIZED = 401;

    /**
     * Indicates that the request is valid and the client is authenticated, but the client is not allowed access the page or
     * resource for any reason.
     * E.g sometimes the authorized client is not allowed to access the directory on the server.
     */
    const RESPONSE_FORBIDDEN = 403;

    /**
     * Indicates that the requested resource is not available now.
     */
    const RESPONSE_NOT_FOUND = 404;

    /**
     * Indicates that the request by the client was not processed, as the server could not understand what the client
     * is asking for.
     */
    const RESPONSE_BAD_REQUEST = 400;

    /**
     * The request could not be completed due to a conflict with the current state of the resource.
     * This code is only allowed in situations where it is expected that the user might be able to resolve the
     * conflict and resubmit the request.
     */
    const RESPONSE_EXISTING_RECORD = 409;

    /**
     * Generic internal server error occurring
     */
    const RESPONSE_INTERNAL_SERVER_ERROR = 500;


    public $urlParts = [];
    public $uri;
    public $module = "";
    public $identifier = "";
    public $option = "";
    public $filters = "";
    public $page = 1;
    public $offset = 0; //default offset is 0
    public $limit = 50; //default limit is 50
    public $search = "";
    public $sort = "desc";
    public $backtrace = 0;
    public $body = [];
    public $method = "";
    public $isGet = false;
    public $isPost = false;
    public $isPatch = false;
    public $isPut = false;
    public $isDelete = false;
    public $isHead = false;
    public $isOptions = false;
    public $isTrace = false;
    public $isConnect = false;
    public $headers;

    public $isBenchmark = false;

    public $ip_address;
    public $user_agent;

    public $apiData = [
        "success"       => false,
        "message"       => "",
        "data"          => null,
        "errors"        => [],
        "total_records" => 0,
        "code"          =>  200,
        "benchmark"     => []
    ];

    /**
     * Requests constructor.
     * @throws ConfigurationException
     */
    public function __construct() {

        $this->enableCors();

        $this->uri = $this->getRequestUri();

        $this->setURIParts();

        $this->setRequestHeaders();

        $this->setModule();
        $this->setIdentifier();
        $this->setOption();

        $this->setQueryParameters();
        $this->setMethod();
        $this->setBody();

        $this->setUserAgent();
        $this->setIpAddress();
    }

    /**
     * Set the IP Address
     */
    public function setIpAddress() {
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip_address = $_SERVER['HTTP_CLIENT_IP'];
        } elseif(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else if(isset($_SERVER['HTTP_X_FORWARDED'])) {
            $ip_address = $_SERVER['HTTP_X_FORWARDED'];
        } else if(isset($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ip_address = $_SERVER['HTTP_FORWARDED_FOR'];
        } else if(isset($_SERVER['HTTP_FORWARDED'])) {
            $ip_address = $_SERVER['HTTP_FORWARDED'];
        } else if(isset($_SERVER['REMOTE_ADDR'])) {
            $ip_address = $_SERVER['REMOTE_ADDR'];
        } else {
            $ip_address = 'UNKNOWN';
        }

        $this->ip_address = Utils::escape($ip_address);
    }

    /**
     * Set the user agent
     */
    public function setUserAgent() {
        $this->user_agent = Utils::escape($_SERVER['HTTP_USER_AGENT']);
    }

    /**
     * Get request URI
     * @return bool|mixed|string
     * @throws ConfigurationException
     */
    public function getRequestUri() {

        $requestUri = Utils::escape(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

        $sitePathUri = Utils::escape(parse_url(Config::getSiteURL(), PHP_URL_PATH));

        $actualUri = str_replace($sitePathUri,"",$requestUri);

        $pathUriLength = strlen($sitePathUri);

        if (substr($requestUri, 0, $pathUriLength) == $sitePathUri) {
            $actualUri = substr($requestUri, $pathUriLength);
        }

        return $actualUri;
    }

    /**
     * Set URI parts
     */
    public function setURIParts() {
        $this->urlParts = Data::resetArray(explode("/",$this->uri));
    }

    /**
     * Set the request module
     */
    public function setModule() {
        $this->module = isset($this->urlParts[0]) ? $this->urlParts[0] : "";
    }

    /**
     * Set the request identifier
     */
    public function setIdentifier() {
        $this->identifier = isset($this->urlParts[1]) ? $this->urlParts[1] : "";
    }

    /**
     * Set the request option
     */
    public function setOption() {
        $this->option = isset($this->urlParts[2]) ? $this->urlParts[2] : "";
    }

    /**
     * Set the query parameters
     * @param string $queryParams
     */
    public function setQueryParameters($queryParams = "") {
        //initialize the query
        $filters = [];
        //we get the url query parameters

        $params = !empty($queryParams) ? $queryParams : parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);

        $urlQueryParams = explode("&", $params);

        if (sizeof($urlQueryParams) > 0) {
            foreach ($urlQueryParams as $param) {
                if (!empty($param)) {
                    $paramKeyValuePair = explode("=", $param);
                    if (isset($paramKeyValuePair[0]) && isset($paramKeyValuePair[1])) {
                        $key = $paramKeyValuePair[0];
                        // Don't wrongly interpret PHP debug parameters as filters!
                        if($key !== "XDEBUG_SESSION_START" && $key !== "XDEBUG_PROFILE") {
                            $filters[Utils::escape($key)] = Utils::escape($paramKeyValuePair[1]);
                        }
                    }
                }
            }
        }

        $this->offset = isset($filters['offset']) ? $filters['offset'] : 0;
        $this->limit = isset($filters['limit']) ? $filters['limit'] : Config::PAGE_SIZE;
        $this->search = isset($filters['search']) ? $filters['search'] : "";
        $this->sort = isset($filters['sort']) ? $filters['sort'] : "desc";
        $this->isBenchmark = isset($filters['benchmark']) && $filters['benchmark'] == 1 ? true : false;
        $this->backtrace = isset($filters['backtrace']) ? $filters['backtrace'] : 0;

        //unset the offset and limit from the filters
        unset($filters['offset']);
        unset($filters['limit']);
        unset($filters['search']);
        unset($filters['sort']);
        unset($filters['benchmark']);
        unset($filters['backtrace']);

        // formatting of filters ensures that when comma-seperated, we format them to an array
        $formattedFilters = [];

        if (sizeof($filters) > 0) {
            foreach ($filters as $key => $value) {
                $valueParts = explode(",",$value);

                if (sizeof($valueParts) > 1) {
                    $formattedFilters[$key] = $valueParts;
                } else {
                    $formattedFilters[$key] = $valueParts[0];
                }
            }
        }
        $this->filters = (object) $formattedFilters;
    }

    /**
     * Set the request method
     */
    public function setMethod() {

        $this->method = $this->headers->request_method;

        switch($this->headers->request_method){
            case 'GET':
                $this->isGet = true;
                break;
            case 'POST':
                $this->isPost = true;
                break;
            case 'PATCH':
                $this->isPatch = true;
                break;
            case 'PUT':
                $this->isPut = true;
                break;
            case 'DELETE':
                $this->isDelete = true;
                break;
            case 'HEAD':
                $this->isHead = true;
                break;
            case 'OPTIONS':
                $this->isOptions = true;
                break;
            case 'TRACE':
                $this->isTrace  = true;
                break;
            CASE 'CONNECT':
                $this->isConnect = true;
                break;
        }
    }

    /**
     * set the request body
     * @param string $body
     */
    public function setBody($body = "" ) {

        $this->body = !empty($body) ? json_decode($body, JSON_FORCE_OBJECT) : json_decode(file_get_contents("php://input"), JSON_FORCE_OBJECT);

        if (is_array($this->body) && sizeof($this->body) > 0) {
            array_walk_recursive($this->body, function(&$value, $key) {
                $value = Utils::escape($value);
            });
        }
    }

    /**
     * set the request headers
     */
    public function setRequestHeaders(){

        unset($_SERVER['USER']);
        unset($_SERVER['HOME']);
        unset($_SERVER['SERVER_SOFTWARE']);
        unset($_SERVER['GATEWAY_INTERFACE']);
        unset($_SERVER['FCGI_ROLE']);

        $headers = [];
        foreach($_SERVER as $key=>$value){
            $headers[Utils::escape(strtolower(str_ireplace("HTTP_","", $key)))] = Utils::escape($value);
        }
        $this->headers = (object) $headers;
    }

    /**
     * Enable CORS
     */
    public function enableCors() {
        // Specify domains from which requests are allowed
        header('Access-Control-Allow-Origin: *');

        header('Vary', 'Origin');

        // Specify which request methods are allowed
        header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');

        // Additional headers which may be sent along with the CORS request
        header('Access-Control-Allow-Headers: X-Requested-With , Content-Type , Authorization, Business, Role');

        // Set the age to 1 day to improve speed/caching.
        header('Access-Control-Max-Age: 5');

        // set credentials to true
        //header('Access-Control-Allow-Credentials', true);
    }

    /**
     * Send response
     */
    public function sendResponse() {

        header("Content-Type: application/json;charset=utf-8");//only send json data

        if (!isset($this->apiData['code'])) {
            $this->apiData['code'] = 200;
        }

        http_response_code($this->apiData['code']);

        if($this->apiData['code'] != 204) {

            $this->apiData = [
                "status_code"      => $this->apiData['code'],
                "success"   => isset($this->apiData['success']) ? $this->apiData['success'] : false,
                "message"   => isset($this->apiData['message']) ? $this->apiData['message'] : "",
                "data"      => isset($this->apiData['data']) ? $this->apiData['data'] : null,
                "errors"    => isset($this->apiData['errors']) ? $this->apiData['errors'] : [],
                "benchmark" => isset($this->apiData['benchmark']) ? $this->apiData['benchmark'] : [],
                "meta"      =>  [
                    "no_of_records" => 0,
                    "total_records" => isset($this->apiData['total_records']) && !is_null($this->apiData['total_records']) ? $this->apiData['total_records'] : 0
                ]
            ];

            if (substr($this->apiData['code'],0,1) == '2') {
                // $this->apiData['success'] = true;

                if (!is_null($this->apiData['data'])) {
                    $this->apiData['meta']['no_of_records'] = is_array($this->apiData['data']) ? sizeof($this->apiData['data']) : 1;
                }
            }

            echo json_encode($this->apiData);
        }
        // we terminate the code
        //exit;
    }

    /**
     * Send a curl request
     * @param $endpoint
     * @param $headers
     * @param $type
     * @param array $body
     * @return array
     * @throws HttpException
     */
    public static function sendCurlRequest($endpoint, $headers, $type, $body = array()) {

        try {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_POST, false);
            if ($type == 'post') {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            }
            curl_setopt($ch, CURLOPT_URL, $endpoint);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if (FALSE == $response || null == $response) {
                throw new HttpException("Curl failed for the request: $type $endpoint");
            }

            return [
                "status"    => $status,
                "body"      => $response
            ];

        } catch (\Exception $e) {
            throw new HttpException($e->getMessage(), $e->getCode());
        }
    }
}