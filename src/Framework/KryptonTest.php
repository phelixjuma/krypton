<?php

namespace Kuza\Krypton\Framework;

use \PHPUnit\Framework\TestCase;

class KryptonTest extends TestCase {

    /**
     * @var \Kuza\Krypton\App
     */
    protected $app;

    /**
     * Holds the response from an api test.
     * @var $response
     */
    protected $response;

    /**
     * KryptonTest constructor.
     * @throws \Exception
     */
    public function __construct() {

        parent::__construct();

        global $app;

        $this->app = $app;
    }

    /**
     * Calls and endpoint method to get its response
     * @param $instance
     * @param $method
     * @return mixed
     */
    public function getEndpointResponse($instance, $method) {

        ob_start();

        call_user_func(array($instance, $method));

        $this->response = ob_get_contents(); // Store buffer in variable

        ob_end_clean();

        return $this->response();
    }

    /**
     * @return mixed
     */
    protected function response() {

        // Test 1: we test if the response is a valid json field.
        $this->assertJson($this->response);

        // we get the response as an array
        $responseArray = json_decode($this->response, true);

        // Test 2: We check if the response has all the required fields.
        $this->assertArrayHasKey("status_code", $responseArray);
        $this->assertArrayHasKey("success", $responseArray);
        $this->assertArrayHasKey("message", $responseArray);
        $this->assertArrayHasKey("data", $responseArray);
        $this->assertArrayHasKey("meta", $responseArray);

        return $responseArray;
    }

    /**
     * Check if an array has all the specified keys.
     * @param $array
     * @param $keys
     * @return bool
     */
    protected function arrayHasKeys($array, $keys) {

        $arrayKeys = array_keys($array);

        return  array_values(array_intersect($arrayKeys, $keys)) == $keys;

    }

    /**
     * Checks if the data is an object or not.
     * @param array $data
     * @return bool
     */
    protected function isObject(array $data) {
        if (array() === $data) return false;
        return array_keys($data) !== range(0, count($data) - 1);
    }
}