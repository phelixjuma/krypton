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

class DBConnection extends Model {

    /**
     * DBConnection constructor.
     * @param null $table
     */
    public function __construct($table = null, $database = null) {

        parent::__construct($this->dbConnection($database), $table);
    }

    /**
     * Connect to the database. Sets the PDO connection.
     */
    private function dbConnection($db_name = null) {

        $pdoConnection = null;

        try {

            if ($db_name !== null || !isset($GLOBALS['pdoConnection']) || is_null($GLOBALS['pdoConnection'])) {

                $app_env = Config::getSpecificConfig("APP_ENV");

                $host = Config::getDBHost();
                $engine = Config::getDBEngine();
                $port = Config::getDBPort();
                $name = $app_env == "testing" ? Config::getSpecificConfig("DB_NAME_TESTING") : Config::getDBName();
                if ($db_name !== null) {
                    $name = $db_name;
                }

                $source = $engine . ":host=" . $host . ";port=" . $port . ";dbname=" . $name;
                $user = Config::getDBUser();
                $password = Config::getDBPassword();

                $GLOBALS['pdoConnection'] = new \PDO($source, $user, $password);
            }


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

        return $GLOBALS['pdoConnection'];
    }
}
