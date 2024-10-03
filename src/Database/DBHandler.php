<?php

namespace Kuza\Krypton\Database;

use Kuza\Krypton\Classes\Data;

use Kuza\Krypton\Config\Config;
use Kuza\Krypton\Database\Predicates\Between;
use Kuza\Krypton\Database\Predicates\DateDiffGreaterThanOrEqualTo;
use Kuza\Krypton\Database\Predicates\In;
use Kuza\Krypton\Database\Predicates\JsonContains;
use Kuza\Krypton\Database\Predicates\NestedAnd;
use Kuza\Krypton\Database\Predicates\NestedOr;
use Kuza\Krypton\Database\Predicates\PredicateFunction;
use Kuza\Krypton\Exceptions\CustomException;
use PDO;

class DBHandler {

    const MAX_RETRIES = 3;

    private $db;
    protected $table_name;
    private $join;
    protected $prkey;
    private $columns;
    private $table_meta;
    private $db_name;
    private $source;
    private $user;
    private $password;

    private $success;
    protected $is_error;
    protected $message;
    private $recordsAffected;
    private $lastAffectedId;
    public $recordsSelected;
    public $total_records = 0;

    private $connectionOptions = array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_PERSISTENT => true,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    );

    /**
     * @var PDO $pdo
     */
    private $pdo;

    /**
     * @param $db_name
     * @param $table
     */
    public function __construct($db_name = null, $table = null) {

        $this->db_name = $db_name;
        $this->table_name = $table;

        $this->dbConnection();

        $this
            ->table($table)
            ->prepareModel();
    }

    public function __sleep() {
        // Specify the properties to be serialized
        return ['table_name', 'join', 'prkey', 'columns', 'table_meta', 'db_name', 'source', 'user', 'password'];
    }

    public function __wakeup() {

        // Reinitialize the PDO connection
        $this->dbConnection();

        // Reset the table and prepare model
        $this
            ->table($this->table_name)
            ->prepareModel();
    }

    /**
     * Connect to the database. Sets the PDO connection.
     */
    private function dbConnection() {

        try {

            if ($this->db_name !== null || !isset($GLOBALS['pdoConnection']) || is_null($GLOBALS['pdoConnection'])) {

                $app_env = Config::getSpecificConfig("APP_ENV");

                $host = Config::getDBHost();
                $engine = Config::getDBEngine();
                $port = Config::getDBPort();
                $name = $app_env == "testing" ? Config::getSpecificConfig("DB_NAME_TESTING") : Config::getDBName();
                if ($this->db_name !== null) {
                    $name = $this->db_name;
                }

                $this->setSource($engine . ":host=" . $host . ";port=" . $port . ";dbname=" . $name. ";charset=utf8mb4");
                $this->setUser(Config::getDBUser());
                $this->setPassword(Config::getDBPassword());

                $GLOBALS['pdoConnection'] = new \PDO($this->getSource(), $this->getUser(), $this->getPassword(), $this->getConnectionOptions());
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

        $this->pdo =  $GLOBALS['pdoConnection'];
    }

    /**
     * @return void
     */
    public function closeConnection() {
        $this->disconnect();
    }

    public function __destruct() {
        $this->closeConnection();
    }

    public function setSource($source) {
        $this->source = $source;
    }

    public function setUser($user) {
        $this->user = $user;
    }

    /**
     * @param $password
     * @return void
     */
    public function setPassword($password) {
        $this->password = $password;
    }

    /**
     * @return mixed
     */
    public function getSource() {
        return $this->source;
    }

    /**
     * @return mixed
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * @return mixed
     */
    public function getPassword() {
        return $this->password;
    }

    /**
     * @return array
     */
    public function getConnectionOptions(): array
    {
        return $this->connectionOptions;
    }

    /**
     * @return void
     */
    private function reconnect() {

        try {

            if ($this->db_name !== null || !isset($GLOBALS['pdoConnection']) || is_null($GLOBALS['pdoConnection'])) {

                $app_env = Config::getSpecificConfig("APP_ENV");

                $host = Config::getDBHost();
                $engine = Config::getDBEngine();
                $port = Config::getDBPort();
                $name = $app_env == "testing" ? Config::getSpecificConfig("DB_NAME_TESTING") : Config::getDBName();
                if ($this->db_name !== null) {
                    $name = $this->db_name;
                }

                $this->setSource($engine . ":host=" . $host . ";port=" . $port . ";dbname=" . $name. ";charset=utf8mb4");
                $this->setUser(Config::getDBUser());
                $this->setPassword(Config::getDBPassword());

                $GLOBALS['pdoConnection'] = new PDO($this->getSource(), $this->getUser(), $this->getPassword(), $this->getConnectionOptions());

                $this->pdo = $GLOBALS['pdoConnection'];
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

    }

    /**
     * @return void
     */
    public function disconnect() {
        $GLOBALS['pdoConnection'] = null;
        $this->pdo = null;
    }

    /**
     * Adds the database PDO adapter
     *
     * @param PDO $db
     * @return $this
     */
    protected function addDbAdapter(PDO $db) {
        $this->db = $db;

        return $this;
    }

    /**
     * Get the database adapter
     * @return PDO
     */
    public function adapter() {
        return $this->db;
    }

    /**
     * Prepare the model.
     * Instantiates the database connection
     *
     * @return $this
     */
    protected function prepareModel() {

        $this->db = $this->pdo ?? $this->db;

        $this->setKeys();
        $this->setColumns();

        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $this;
    }

    /**
     * Start database transaction
     *
     * @return $this
     */
    public function startTransaction() {
        if($this->adapter()->inTransaction()!==true){
            $this->adapter()->beginTransaction();
        }

        return $this;
    }

    /**
     * End database transaction
     */
    public function endTransaction() {
        if($this->adapter()->inTransaction()==true){
            $this->adapter()->commit();
        }
    }

    /**
     * Roll back a transaction
     */
    public function RollBack() {
        if($this->adapter()->inTransaction()==true){
            $this->adapter()->rollBack();
        }
        $this->recordsAffected = 0;
        $this->lastAffectedId=null;
        $this->is_error=true;
    }

    /**
     * Get the last affected id
     * @param null $lastAffectedId
     * @return int
     */
    public function lastAffectedId($lastAffectedId=null) {
        $this->lastAffectedId = $lastAffectedId? $lastAffectedId : $this->lastAffectedId;
        return $this->lastAffectedId;
    }

    /**
     * Get the records affected by a transaction
     * @param null $recordsAffected
     * @return null
     */
    public function recordsAffected($recordsAffected=null) {
        $this->recordsAffected = $recordsAffected? $recordsAffected : $this->recordsAffected;
        return $this->recordsAffected;
    }

    /**
     * Get the records selected
     * @return mixed
     */
    public function recordsSelected() {
        return $this->recordsSelected;
    }

    /**
     * Set the table keys
     * Also sets the primary key of the table
     * @return $this
     */
    public function setKeys() {
        $sql="SHOW INDEX FROM $this->table_name WHERE Key_name = 'PRIMARY' ";
        $statement = $this->adapter()->prepare($sql);
        $statement->execute();
        $result=$statement->fetch(PDO::FETCH_ASSOC);
        $this->prkey=$result['Column_name'];

        return $this;
    }

    /**
     * Set the columns of the table
     *
     * @return $this
     */
    public function setColumns() {
        //get table description i.e. metadata
        $statement = $this->adapter()->prepare("DESCRIBE ".$this->table_name);

        //execute statement
        $statement->execute();

        //get table meta information
        $this->table_meta = $statement->fetchAll();

        //fetch table columns
        $this->columns = Data::getArrayMap($this->table_meta, 'Field');

        return $this;
    }

    /**
     * Get table columns
     * @return mixed
     */
    public function getColumns() {
        return $this->columns;
    }

    /**
     * Get meta data of a table
     * @return mixed
     */
    public function getTableMeta() {
        return $this->table_meta;
    }

    /**
     * Get the tables primary key
     * @return mixed
     */
    public function primaryKey() {
        return $this->prkey;
    }

    /**
     * Get the sum of the values of a column
     * @param $column
     * @param null $alias
     * @param bool $remove_alias
     * @return string
     */
    public function sumColumn($column,$alias=null,$remove_alias=false) {
        $alias=($alias)? $alias : $column;
        return 'COALESCE(SUM('.$column.'),0) '. ($remove_alias==true? '' : ' AS '.$alias);
    }

    /**
     * Count the number of records in a column
     * @param $column
     * @param null $alias
     * @param bool $remove_alias
     * @return string
     */
    public function countColumn($column,$alias=null,$remove_alias=false) {
        $alias=($alias)? $alias : $column;
        return 'COALESCE(COUNT('.$this->table_name.'.'.$column.'),0) '. ($remove_alias==true? '' : ' AS '.$alias);
    }

    /**
     * Get the average of the values of a column
     * @param $column
     * @param null $alias
     * @param bool $remove_alias
     * @return string
     */
    public function averageColumn($column,$alias=null,$remove_alias=false) {
        $alias=($alias)? $alias : $column;
        return 'COALESCE(AVG('.$column.'),0) '. ($remove_alias==true? '' : ' AS '.$alias);
    }

    /**
     * Concatenate columns
     * @param $columns
     * @param string $separator
     * @return string
     */
    public function concat($columns,$separator=' ') {
        $columns = is_array($columns)==true? implode(',', $columns) : $columns;
        return " CONCAT_WS('$separator',$columns) ";
    }

    /**
     * Coalesce columns
     * @param $column
     * @param string $default
     * @param null $alias
     * @return string
     */
    public function coalesce($column,$default='',$alias=null) {
        $alias=($alias)? $alias : $column;
        return 'COALESCE('.$column.',\''.$default.'\') AS '.$alias;
    }

    /**
     * Set the table name, if exists, otherwise, get the table name
     *
     * @param null $table_name
     * @return $this
     */
    public function table($table_name=null) {
        if($table_name!=null)
        {
            $this->table_name=$table_name;
            $this->join=null;
        }
        return $this;
    }

    /**
     * @param $table_name
     * @return $this
     */
    public function resetTable($table_name=null) {
        $this->table_name=$table_name;
        return $this;
    }

    /**
     * Creates a data with the columns, parameters and values from the provided data
     * @param array $data
     * @param string $params_prefix
     * @return array
     */
    protected static function createColumnsParamsValues(array $data, $params_prefix=":", $operator = "=") {
        $columns=[];
        $values=[];
        $params=[];
        $columns_equals_params=[];

        foreach ($data as $key=>$value) {
            /* iterator here to loop through criteria objects as we build an expression */
            $expBuilder = function($value,$column) use (&$expBuilder,&$columns,&$values,&$params,&$columns_equals_params,$params_prefix, &$operator){
                /* the recursive section.. we obtain the expression by repeating several predicates*/
                if(($value instanceof NestedOr) || ($value instanceof NestedAnd)) {
                    $expression = [];
                    foreach ($value->getValue() as $k=>$v)
                    {
                        $expression[] = is_object($v)? $expBuilder($v,$column) : $expBuilder($v,$k);
                    }
                    $expression = '('.implode( ($value instanceof NestedOr? ' OR ': ' AND ') , $expression).')';
                } elseif($value instanceof PredicateFunction || $value instanceof Between || $value instanceof  In ||
                    $value instanceof DateDiffGreaterThanOrEqualTo || $value instanceof JsonContains) {
                    $expression = $value->getExpression();
                } else {
                    /*if the argument supplied is an object we obtain values, expression from the object accessor methods */
                    if(is_object($value))
                    {
                        $column = str_replace('.', '',$value->getAlias());
                        $alias = $column;
                        $columns[] = $column;
                        $param =$params_prefix.$alias;
                        $params[] = $param;
                        $bound_val = $value->getValue();
                        $bound_exp = $value->getExpression($params_prefix);

                        $values[] = is_array($bound_val)==true? implode(',', $bound_val) : $bound_val;
                        $not = preg_match('/NOT/i',get_class($value));
                        $formula = $not? "ISNULL($column) OR FIND_IN_SET($column,$param)=0" : "FIND_IN_SET($column,$param)>0";
                        $expression = is_array($bound_val)==true? $formula : $bound_exp;
                    }
                    else
                    {
                        /*if not object, we construct an expression i.e. =(single value) OR FIND_INSET(for many values) */
                        $columns[] = $column;

                        $alias = str_replace('.', '',$column);
                        $alias = str_replace(["(", ")"], '', $alias);

                        //$param=$params_prefix.$column;
                        $param=$params_prefix.$alias;

                        $params[]=$param;

                        $values[] = is_array($value)? implode(',', $value) : ((trim($value)=="")? null : $value);

                        $expression = is_array($value)==true? "FIND_IN_SET($column,$param)>0" : $column.$operator.$param;
                    }
                }
                return $expression;
            };
            $columns_equals_params[] = $expBuilder($value,$key);
        }
        return ['columns'=>$columns,'params'=>$params,'values'=>$values,'columns_equals_params'=>$columns_equals_params];
    }

    /**
     * Create a PDO statement
     * @param $sql
     * @param array $params
     * @param array $values
     * @return bool|\PDOStatement
     */
    public function createStatement($sql,array $params,array $values) {

        $statement=$this->adapter()->prepare($sql);

        $this->bindStatementParams($statement, $params, $values);

        return $statement;
    }

    /**
     * Bind PDO statement parameters
     * @param $statement
     * @param $params
     * @param $values
     */
    private function bindStatementParams($statement,$params,$values) {
        for($i=0;$i<count($params);$i++)
        {
            $statement->bindParam($params[$i], $values[$i]);
        }
    }

    /**
     * Execute a PDO statement
     * @param \PDOStatement $statement
     * @return int
     */
    protected function executeStatement(\PDOStatement $statement, $params=null) {

        for ($try = 0; $try < self::MAX_RETRIES; $try++) {
            try {

                $this->success=(int)$statement->execute($params);
                $this->recordsAffected=(int)$statement->rowCount();
                $this->lastAffectedId = $this->adapter()->lastInsertId();
                $this->is_error=false;
                $this->message='success';

                // If success, break the loop
                break;

            } catch (\PDOException $e) {
                // Check if the error is a connection issue (you might need to adjust error code)
                if (self::hasGoneAway($ex)) {
                    // Try to reconnect
                    $this->reconnect();
                    continue; // Go to the next iteration and retry the operation
                } else {
                    // If it's a different error, throw it again
                    $this->recordsAffected=0;
                    $this->lastAffectedId=null;
                    $this->is_error=true;
                    $this->message = "{$statement->errorInfo()[0]} {$statement->errorInfo()[1]} {$statement->errorInfo()[2]}";

                    break;
                }
            }
        }
        return $this->recordsAffected;
    }

    /**
     * @param $e
     * @return bool
     */
    public static function hasGoneAway($e): bool
    {
        if (is_null($e) || !($e instanceof \PDOException)) {
            return false;
        }
        return ($e->getCode() == 'HY000' && stristr($e->getMessage(), 'server has gone away'));
    }

    /**
     * Handle INSERT SQL statement
     * @param array $data
     * @return int
     */
    public function insert(array $data) {
        $cpv=self::createColumnsParamsValues($data);

        $sql="INSERT INTO `$this->table_name` (".implode(",", $cpv['columns']). ") 
					VALUES(" .implode(',', $cpv['params']). ") ";

        $statement=$this->createStatement($sql,$cpv['params'],$cpv['values']);

        return $this->executeStatement($statement);
    }

    /**
     * @param array $data
     * @return int
     */
    public function insertMulti(array $data) {

        $colNames = array_keys($data[0]);
        $dataToInsert = array();

        foreach($data as $d){
            array_push($dataToInsert, ...array_values($d));
        }

        // setup the placeholders - a fancy way to make the long "(?, ?, ?)..." string
        $rowPlaces = '(' . implode(', ', array_fill(0, count($colNames), '?')) . ')';
        $allPlaces = implode(', ', array_fill(0, count($data), $rowPlaces));

        $sql = "INSERT INTO `$this->table_name` (" . implode(', ', $colNames) .
            ") VALUES " . $allPlaces;

        // and then the PHP PDO boilerplate
        $statement = $this->adapter()->prepare($sql);

        return $this->executeStatement($statement, $dataToInsert);
    }

    /**
     * Handle DELETE SQL statement
     * @param $criteria
     * @param $named_tables
     * @return int
     */
    public function delete($criteria, $named_tables=null) {

        $cpv=self::createColumnsParamsValues($criteria);


        $sql="DELETE $named_tables FROM $this->table_name $this->join WHERE ".
            implode(" AND ",$cpv['columns_equals_params']);

        $statement=$this->createStatement($sql,$cpv['params'],$cpv['values']);

        return $this->executeStatement($statement);
    }

    /**
     * Delete one record from a table
     * @param $id
     * @return int
     */
    public function deleteOne($id) {
        return $this->delete([$this->prkey=>$id]);
    }

    /**
     * Handle UPDATE SQL statement
     * @param $data
     * @param null $criteria
     * @return int
     */
    public function update($data,$criteria=null) {
        $criteria=($criteria)? $criteria : [$this->prkey=>$data[$this->prkey]];

        //assemble parameters,columns and values
        $cpv = self::createColumnsParamsValues($data,':c');
        $upc = self::createColumnsParamsValues($criteria,':p');

        //assemble criteria parameters in where clause
        $where=" WHERE ".((count($upc['columns'])>0)? implode(" AND ", $upc['columns_equals_params']) : "1");
        //assemble update statement
        $sql="UPDATE `$this->table_name` SET ".implode(",", $cpv['columns_equals_params'])." ".$where;

        //bind data columns
        $statement=$this->createStatement($sql,$cpv['params'],$cpv['values']);
        //bind criteria columns
        $this->bindStatementParams($statement, $upc['params'],$upc['values']);

        $response = $this->executeStatement($statement);

        $this->lastAffectedId = isset($data[$this->prkey])?  $data[$this->prkey] :
            (isset($criteria[$this->prkey])? $criteria[$this->prkey] : $this->lastAffectedId);

        return $response;
    }

    /**
     * Select one record from a table
     * @param $id
     * @param null $columns
     * @return null
     */
    public function selectOne($id,$columns=null) {
        $result=$this->select([$this->prkey=>$id],$columns);
        return ($result && count($result)>0)? $result[0] : null;
    }

    /**
     * Get the first record from the selected data
     * @param $criteria
     * @param null $columns
     * @param null $group_by
     * @param null $order_by
     * @param null $limit
     * @return null
     */
    public function filterOne($criteria,$columns=null,$group_by=null,$order_by=null,$limit=null) {
        $result=$this->select($criteria,$columns,$group_by,$order_by,$limit);
        return ($result && count($result)>0)? $result[0] : null;
    }

    /**
     * Check if a record exists
     * @param $criteria
     * @return bool
     */
    public function exists($criteria) {
        $criteria=(is_array($criteria))? $criteria : [$this->prkey=>$criteria];
        $result=$this->select($criteria,[$this->countColumn($this->prkey,$this->prkey)]);
        return ($result && count($result)>0 && (int)$result[0][$this->prkey]>0)? true : false;
    }

    /**
     * Count records
     * @param $criteria
     * @return int
     */
    public function count($criteria) {
        $criteria = (is_numeric($criteria))? [$this->prkey=>$criteria] : $criteria;

        $result = $this->select($criteria,[$this->countColumn($this->prkey,$this->prkey)]);

        return ($result && count($result)>0 && (int)$result[0][$this->prkey]>0)? (int)$result[0][$this->prkey] : 0;
    }

    /**
     * Get the records of a specific column
     * @param $col
     * @param array $criteria
     * @param bool $as_array
     * @param null $alias
     * @param null $unique_only
     * @return array|null
     */
    public function fetchColumn($col,$criteria=[],$as_array=false,$alias=null,$unique_only=null){

        $criteria=(is_numeric($criteria))? [$this->prkey=>$criteria] : $criteria;
        $result=$this->select($criteria,[$col]);
        $col = ($alias)? $alias : $col;

        $values=null;
        if($result && count($result)>0)
        {
            $values = $result[0][$col];
            if(count($result)>1){
                $values=[];
                foreach ($result as $row)
                {
                    $values[]=$row[$col];
                }
            }
        }
        $values = ($as_array==true)? ($values? (is_array($values)==true? $values : [$values]) : []): $values;
        $values = $unique_only==true? array_unique($values) : $values;
        return $values;
    }

    /**
     * Save data.
     * If the data exists, it will be updated, otherwise it will be created.
     * @param $data
     * @return int
     */
    public function save($data){
        if(isset($data[$this->prkey]) && ((int)$data[$this->prkey]>0))
            return $this->update($data);
        else
            return $this->insert($data);
    }

    private function execute() {

    }

    /**
     * @param $criteria
     * @param $columns
     * @param $group_by
     * @param $order_by
     * @param $limit
     * @param $isSearch
     * @param $distinct
     * @param $having
     * @return array
     */
    public function getSelectSQL($criteria=null,$columns=null,$group_by=null,$order_by=null,$limit=null, $isSearch = false, $distinct=false, $having=null)
    {

        $columns = (is_array($columns) && count($columns) > 0) ? implode(',', $columns) : '*';

        $group_by = (is_array($group_by) && count($group_by) > 0) ? implode(',', $group_by) : $group_by;
        $group_by = (strlen(trim($group_by)) == 0) ? null : " GROUP BY " . $group_by;

        $having = (is_array($having) && count($having) > 0) ? implode(',', $having) : $having;
        $having = (strlen(trim($having)) == 0) ? null : " HAVING " . $having;

        $order_by = (is_array($order_by) && count($order_by) > 0) ? implode(',', $order_by) : $order_by;

        if (strlen(trim($order_by)) > 0) {
            $order_by = $order_by != 0 ? " ORDER BY " . $order_by : null;
        } elseif (!empty($this->primaryKey())) {
            $order_by = " ORDER BY " . $this->table_name . "." . $this->primaryKey() . " DESC";
        } else {
            $order_by = null;
        }

        if (is_array($criteria) && count($criteria) > 0) {

            if ($isSearch) {
                $cpv = self::createColumnsParamsValues($criteria, ":", " LIKE ");
            } else {
                $cpv = self::createColumnsParamsValues($criteria);
            }
            $params = $cpv['params'];
            $values = $cpv['values'];

            if ($isSearch) {
                $criteria = implode("\nOR   ", $cpv['columns_equals_params']);
            } else {
                $criteria = implode("\nAND   ", $cpv['columns_equals_params']);
            }
        } else {
            $criteria = "1";
            $params = [];
            $values = [];
        }

        $queryLimit = "";
        if ($limit != null && !empty($limit)) {
            $queryLimit = "LIMIT $limit";
        }

        $distinct_part = $distinct ? "DISTINCT" : "";

        $sql = "SELECT $distinct_part $columns FROM $this->table_name $this->join WHERE $criteria $group_by $having $order_by $queryLimit ";

        print "\nPREPARED SQL: $sql\n";

        $count_sql = "SELECT {$this->countColumn($this->prkey,$this->prkey)} FROM $this->table_name $this->join WHERE $criteria $group_by $having";

        return [
            'select_sql' => $sql,
            'count_sql' => $count_sql,
            'params'    => $params,
            'values'    => $values
        ];

    }

    /**
     * @param $criteria
     * @param $columns
     * @param $group_by
     * @param $order_by
     * @param $limit
     * @param $isSearch
     * @param $distinct
     * @param $having
     * @param $count
     * @return array|false|null
     */
    public function select($criteria=null,$columns=null,$group_by=null,$order_by=null,$limit=null, $isSearch = false, $distinct=false, $having=null, $count = true) {

        $selectSQL = $this->getSelectSQL($criteria,$columns,$group_by,$order_by,$limit, $isSearch, $distinct, $having);

        $sql= $selectSQL['select_sql'];
        $count_sql= $selectSQL['count_sql'];
        $params = $selectSQL['params'];
        $values = $selectSQL['values'];

        $result = [];

        for ($try = 0; $try < self::MAX_RETRIES; $try++) {

            try {


                // get the records
                $statement=$this->createStatement($sql,$params,$values);
                print "\nSQL: {$statement->queryString}\n";
                $statement->execute();
                $result = $statement->fetchAll(PDO::FETCH_ASSOC);
                $this->recordsSelected = $statement->rowCount();

                // count the records
                if ($count) {
                    $count_statement=$this->createStatement($count_sql,$params,$values);
                    $count_statement->execute();
                    $count_result = $count_statement->fetchAll(PDO::FETCH_ASSOC);
                    $this->total_records = ($count_result && count($count_result)>0 && (int)$count_result[0][$this->prkey]>0)? (int)$count_result[0][$this->prkey] : 0;
                }

                // Success, we break out of the loop.
                break;

            } catch(\PDOException $e) {

                if (self::hasGoneAway($e)) {
                    // reconnect and continue to next iteration
                    $this->reconnect();
                    continue;
                } else {
                    // different error, set it and break out of loop.
                    $this->is_error = true;
                    $this->message = $e->getMessage();
                    break;
                }
            }
        }

        return ($this->recordsSelected > 0)? $result : null;
    }

    /**
     * Execute a custom select query
     *
     * @param $sql
     * @param array $params
     * @return array
     * @throws CustomException
     */
    public function selectCustomQuery($sql, $params = []) {

        if (preg_match('/(?i)(INSERT\s+INTO|UPDATE|DELETE\s+FROM|DROP\s+TABLE|DROP\s+DATABASE|DESCRIBE)(?-i)\s+/', $sql))
        {
            throw new CustomException("Unsupported query");
        }

        $response = [];

        for ($try = 0; $try < self::MAX_RETRIES; $try++) {

            try {
                $stmt = $this->adapter()->prepare($sql);

                $stmt->execute($params);

                // set the resulting array to associative
                $stmt->setFetchMode(PDO::FETCH_ASSOC);

                $response =  $stmt->fetchAll();

                break;

            } catch (\PDOException $e) {

                if (self::hasGoneAway($e)) {
                    $this->reconnect();
                    continue;
                } else {

                    $this->is_error = true;
                    $this->message = $e->getMessage();

                    break;
                }
            }
        }

        return $response;
    }

    /**
     * Join database tables
     *
     * @param $table
     * @param null $on
     * @param null $type
     * @return $this
     */
    public function join($table,$on=null,$type=null) {
        $default_on=" $this->table_name.$this->prkey=$table.$this->prkey ";
        $on=" ON ".(($on==null)? $default_on : $on);
        $type=strtoupper($type.' JOIN ');
        $this->join = $this->join." $type $table $on ";

        return $this;
    }

    /**
     * Sanitize data and validate against table fields
     * @param $data
     * @param bool $use_defaults
     * @return array
     */
    public function sanitize($data,$use_defaults=true) {

        $default = Data::getArrayMap($this->getTableMeta(),'Default',false);

        $result=[];

        $dataKeys = array_keys($data);

        for($i=0 ; $i < count($this->columns) ; $i++) {

            $default_value = ($use_defaults==true)? $default[$i] : null;
            $key = $this->columns[$i];

            if (in_array($key, $dataKeys)) {

                if (isset($data[$key]) && $data[$key] == '0') {
                    $value = '0';
                } elseif (isset($data[$key]) && trim($data[$key]) == "") {
                    $value = null;
                } else {
                    $value = isset($data[$key]) && !is_array($data[$key]) ? trim($data[$key]) : $default_value;
                }
                $result[$key] = !empty($value) ? trim($value) : $value;
            }
        }
        return array_filter($result, function($r) {
            return !(empty($r) && $r !== '0' && !is_null($r));
        });
    }

    /**
     * Get the success status
     * @return mixed
     */
    public function success() {
        return $this->success;
    }

    /**
     * Set or get the response message
     * @param null $message
     * @return null
     */
    public function message($message=null) {
        $this->message = $message? $message : $this->message;
        return $this->message;
    }

    /**
     * Check if an error has occured
     * @param null $is_error
     * @return null
     */
    public function isError($is_error=null) {
        $this->is_error = $is_error? $is_error : $this->is_error;
        return $this->is_error;
    }
}
