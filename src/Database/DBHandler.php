<?php

namespace Kuza\Krypton\Database;

use Kuza\Krypton\Classes\Data;
use Kuza\Krypton\Config\Config;

use Kuza\Krypton\Database\Predicates\Between;
use Kuza\Krypton\Database\Predicates\DateDiffGreaterThanOrEqualTo;
use Kuza\Krypton\Database\Predicates\In;
use Kuza\Krypton\Database\Predicates\NestedAnd;
use Kuza\Krypton\Database\Predicates\NestedOr;
use Kuza\Krypton\Database\Predicates\PredicateFunction;

abstract class DBHandler {

    private $db;

    protected static $db_adapters = [];

    protected $table_name;

    private $recordsAffected;
    private $lastAffectedId;
    private $recordsSelected;
    private $join;

    protected $prkey;
    protected $is_error;
    protected $message;

    private $success;
    private $columns;
    private $table_meta;

    /**
     * DBHandler constructor.
     * @param string $tableName
     */
    public function __construct($tableName="") {

        if(!empty($tableName)) {
            $this->table($tableName);
        }

        $this->prepareModel();
    }

    /**
     * Adds the database PDO adapter
     * @param \PDO $db
     */
    protected function addDbAdapter(\PDO $db) {
        $this->db = $db;
    }

    /**
     * Get the database adapter
     * @param \PDO|null $db
     * @return \PDO
     */
    public function adapter(\PDO $db=null) {
        $this->db = $db? $db : $this->db;
        return $this->db;
    }

    /**
     * Prepare the model.
     * Instantiates the database connection
     */
    protected function prepareModel() {

        try {

            $source = Config::getSource();
            $user = Config::getDBUser();
            $password = Config::getDBPassword();
            $pdo = new \PDO($source,$user,$password);
            $this->addDbAdapter($pdo);

            $this->setKeys();
            $this->setColumns();
            $this->adapter()->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        }
        catch (\Exception $ex) {
            $title = 'Connection Failed';
            switch ($ex->getCode()){
                case 2002: $message = 'Attempt to Connect to database failed'; break;
                default: $message = $ex->getMessage(); break;
            }
            $response = json_encode(['message'=>$message,'title'=>$title,'status'=>'error']);
            die($response);
        }

    }

    /**
     * Start database transaction
     */
    public function startTransaction() {
        if($this->adapter()->inTransaction()!==true){
            $this->adapter()->beginTransaction();
        }
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
     */
    public function setKeys() {
        $sql="SHOW INDEX FROM $this->table_name WHERE Key_name = 'PRIMARY' ";
        $statement = $this->adapter()->prepare($sql);
        $statement->execute();
        $result=$statement->fetch(\PDO::FETCH_ASSOC);
        $this->prkey=$result['Column_name'];
    }

    /**
     * Set the columns of the table
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
     * @param null $table_name
     * @return null
     */
    public function table($table_name=null) {
        if($table_name!=null)
        {
            $this->table_name=$table_name;
            $this->join=null;
        }
        return $this->table_name;
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
                } elseif($value instanceof PredicateFunction || $value instanceof Between || $value instanceof  In || $value instanceof DateDiffGreaterThanOrEqualTo) {
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
    protected function executeStatement(\PDOStatement $statement) {
        try {
            $this->success=(int)$statement->execute();
            $this->recordsAffected=(int)$statement->rowCount();
            $this->lastAffectedId = $this->adapter()->lastInsertId();
            $this->is_error=false;
            $this->message='success';
        } catch (\Exception $ex) {
            $this->recordsAffected=0;
            $this->lastAffectedId=null;
            $this->is_error=true;
            $this->message= $statement->errorInfo()[2];
        }
        return $this->recordsAffected;
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
     * Handle DELETE SQL statement
     * @param $criteria
     * @return int
     */
    public function delete($criteria) {

        $cpv=self::createColumnsParamsValues($criteria);

        $sql="DELETE FROM `$this->table_name` WHERE ".
            implode("AND",$cpv['columns_equals_params']);

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
     * Delete all records from a table
     */
    public function deleteAll() {
        $this->delete();
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

    /**
     * Handle SELECT SQL statement
     * @param null $criteria
     * @param null $columns
     * @param null $group_by
     * @param null $order_by
     * @param null $limit
     * @param bool $isSearch
     * @return array|null
     */
    public function select($criteria=null,$columns=null,$group_by=null,$order_by=null,$limit=null, $isSearch = false) {
        $columns = (is_array($columns) && count($columns)>0)?  implode(',', $columns) : '*';

        $group_by = (is_array($group_by) && count($group_by)>0)?  implode(',', $group_by) : $group_by;

        $group_by = (strlen(trim($group_by))==0)? null : " GROUP BY ".$group_by;

        $order_by = (is_array($order_by) && count($order_by)>0)?  implode(',', $order_by) : $order_by;

        $order_by = (strlen(trim($order_by))==0)? null : " ORDER BY ".$order_by;

        if(is_array($criteria) && count($criteria)>0){

            if ($isSearch) {
                $cpv = self::createColumnsParamsValues($criteria,":", " LIKE ");
            } else {
                $cpv = self::createColumnsParamsValues($criteria);
            }
            $params=$cpv['params'];
            $values=$cpv['values'];

            if ($isSearch) {
                $criteria=implode("\nOR   ", $cpv['columns_equals_params']);
            } else {
                $criteria=implode("\nAND   ", $cpv['columns_equals_params']);
            }
        }
        else{
            $criteria="1";
            $params=[];
            $values=[];
        }

        $queryLimit = "";
        if($limit != null && !empty($limit)) {
            $queryLimit = "LIMIT $limit";
        }

        $sql="SELECT $columns FROM $this->table_name $this->join WHERE $criteria $group_by $order_by $queryLimit ";

       // print $sql."\n";

        $result = [];
        try {
            $statement=$this->createStatement($sql,$params,$values);
            $statement->execute();
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
            $this->recordsSelected = $statement->rowCount();
        } catch(\Exception $e) {
            $this->is_error = true;
            $this->message = $e->getMessage();
        }

        return ($this->recordsSelected>0)? $result : null;
    }

    /**
     * Join database tables
     * @param $table
     * @param null $on
     * @param null $type
     */
    public function join($table,$on=null,$type=null) {
        $default_on=" $this->table_name.$this->prkey=$table.$this->prkey ";
        $on=" ON ".(($on==null)? $default_on : $on);
        $type=strtoupper($type.' JOIN ');
        $this->join = $this->join." $type $table $on ";
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

        for($i=0 ; $i < count($this->columns) ; $i++) {

            $default_value = ($use_defaults==true)? $default[$i] : null;
            $key = $this->columns[$i];

            if (isset($data[$key]) && $data[$key] == '0') {
                $value = '0';
            } else {
                $value = isset($data[$key]) && !is_array($data[$key]) && !empty($data[$key]) ? trim($data[$key]) : $default_value;
            }

            $result[$key] = trim($value);
        }
        $sanitized_data = array_filter($result, function($r) {
            return empty($r) && $r != '0' ? false: true;
        });
        return $sanitized_data;
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
