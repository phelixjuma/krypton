<?php
/**
 * This is the DB Connection model
 * @author Allan Dhoye <allan@kuzalab.com>
 * @copyright (c) 2018, Kuza Lab
 * @package Kuzalab
 */

namespace Kuza\Krypton\Framework;


use Kuza\Krypton\Config\Config;
use Kuza\Krypton\Database\Model;

class Database {

    private static $instance = null;
    private $connection;

    private function __construct() {
        $this->setConnection();
    }

    public static function getInstance(): ?Database
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    /**
     * Connect to the database. Sets the PDO connection.
     */
    private function setConnection() {

        try {

            $app_env = Config::getSpecificConfig("APP_ENV");

            $host = Config::getDBHost();
            $engine = Config::getDBEngine();
            $port = Config::getDBPort();
            $name = $app_env == "testing" ? Config::getSpecificConfig("DB_NAME_TESTING") : Config::getDBName();

            $source = $engine . ":host=" . $host . ";port=" . $port . ";dbname=" . $name. ";charset=utf8mb4";
            $user = Config::getDBUser();
            $password = Config::getDBPassword();

            $this->connection = new \PDO($source, $user, $password, array(
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_PERSISTENT => false,
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ));

        } catch (\Exception $ex) {
            $title = 'Connection Failed';
            switch ($ex->getCode()) {
                case 2002:
                    $message = 'Attempt to Connect to database failed';
                    break;
                default:
                    $message = $ex->getMessage();
                    break;
            }
            $response = json_encode(['message' => $message, 'title' => $title, 'status' => 'error']);
            die($response);
        }
    }

    public function getConnection() {
        return $this->connection;
    }

    public function closeConnection() {
        $this->connection = null;
    }

    public function __destruct() {
        $this->closeConnection();
    }
}
