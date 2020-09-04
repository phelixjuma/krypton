<?php
/**
 * This is script handles Dynamo DB document storage engine at AWS
 * @author Phelix Juma <jumaphelix@kuzalab.co.ke>
 * @copyright (c) 2018, Kuza Lab
 * @package Kuzalab
 */

namespace Kuza\Krypton\Classes;


use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use Kuza\Krypton\Config\Config;
use Aws\Credentials\Credentials;
use Aws\DynamoDb\Marshaler;

/**
 * Class for managing uploads to Dynamo DB
 * @package Kuzalab
 */
class DynamoDB {

    private $credentials;
    private $dynamoClient;
    private $marshaller;
    private $table;

    /**
     * DynamoDB constructor.
     */
    public function __construct() {
        $this->credentials = new Credentials(Config::getAWSAccessKey(), Config::getAWSAccessSecret());

        $this->marshaller = new Marshaler();

        $this->dynamoClient = new DynamoDbClient([
            'version'       => 'latest',
            'region'        => 'us-east-1',
            'credentials'   => $this->credentials
        ]);
    }

    /**
     *
     * @param $table
     */
    public function setTable($table) {
        $this->table = $table;
    }

    /**
     * Add an item into Dynamo DB
     * @param $item
     * @return bool
     */
    public function addItem($item) {
        $success = false;
        try {

            $result = $this->dynamoClient->putItem(array(
                'TableName' => $this->table,
                'Item' => $item
            ));

            if ($result) {
                $success = true;
            }
        } catch (DynamoDbException $e) {
            echo $e->getMessage() . PHP_EOL;
        }
        return $success;
    }

    /**
     * Add JSON data to Dynamo Db
     * @param $item
     * @return bool
     */
    public function addJsonItem($item) {

        $formattedItem = [];

        try {
            $formattedItem = $this->marshaller->marshalJson($item);
        } catch (\Exception $ex) {
           // print $ex->getMessage();
        }

        $success = false;
        try {

            $result = $this->dynamoClient->putItem(array(
                'TableName' => $this->table,
                'Item' => $formattedItem
            ));

            if ($result) {
                $success = true;
            }
        } catch (DynamoDbException $e) {
           // echo $e->getMessage() . PHP_EOL;
        }
        return $success;
    }

}