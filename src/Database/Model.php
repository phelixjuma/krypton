<?php

namespace Kuza\Krypton\Database;

use Kuza\Krypton\Classes\Data;
use Kuza\Krypton\Classes\Dates;
use Kuza\Krypton\Database\Predicates\Between;
use Kuza\Krypton\Framework\RoutesHelper;

class Model extends DBHandler {


    /**
     * Model constructor.
     * @param \PDO $pdo
     * @param string $table
     */
    public function __construct(\PDO $pdo, $table = null) {

        parent::__construct($pdo, $table);

    }

    /**
     * Prepare insert data
     * @param $data
     */
    public function prepareInsertData(&$data) {
        global $app;

        $createdAt = Dates::getTimestamp();

        if (isset($app->requests->body['created_at']) && !empty($app->requests->body['created_at'])) {
            $createdAt = $app->requests->body['created_at'];
        } elseif (isset($app->requests->filters->created_at) && !empty($app->requests->filters->created_at)) {
            $createdAt = $app->requests->filters->created_at;
        } elseif (isset($app->requests->headers->created_at) && !empty($app->requests->headers->created_at)) {
            $createdAt = $app->requests->headers->created_at;
        }

        if((!isset($data['created_at']) || empty($data['created_at'])) && Data::arrayValueExists("created_at",$this->getColumns())) {
            $data['created_at'] = $createdAt;
        }
        if((!isset($data['created_by']) || empty($data['created_by'])) && Data::arrayValueExists("created_by",$this->getColumns())) {
            $data['created_by'] = isset(RoutesHelper::request()->user) ? RoutesHelper::request()->user->id : 0;
        }
        $data = parent::sanitize($data,false);
    }

    /**
     * Prepare an update data
     * @param $data
     */
    public function prepareUpdateData(&$data) {

        if(Data::arrayValueExists("updated_at",$this->getColumns())) {
            $data['updated_at'] = Dates::getTimestamp();
        }
        if(Data::arrayValueExists("updated_by",$this->getColumns())) {
            $data['updated_by'] = isset(RoutesHelper::request()->user) ? RoutesHelper::request()->user->id : null;
        }
        $data = parent::sanitize($data,false);

    }

    /**
     * Prepare delete data
     * @param $data
     */
    public function prepareDeleteData(&$data) {

        if(Data::arrayValueExists("is_archived",$this->getColumns())) {
            $data['is_archived'] = 1;
        }

        if(Data::arrayValueExists("archived_by",$this->getColumns())) {
            $data['archived_by'] = isset(RoutesHelper::request()->user) ? RoutesHelper::request()->user->id : null;
        }
        if(Data::arrayValueExists("archived_at",$this->getColumns())) {
            $data['archived_at'] = Dates::getTimestamp();
        }

        $data = parent::sanitize($data,false);
    }

    /**
     * Prepare a selection criteria
     *
     * @param null $criteria
     * @param null $alias
     * @return $this
     */
    public function prepareCriteria(&$criteria = null, $alias = null) {

        //check if the key exists in the field
        if(Data::arrayValueExists("is_archived",$this->getColumns()) && !isset($criteria['is_archived'])) {
            $criteria['is_archived'] = 0;
        }

        // handle start date and end date
        $startDate = "";
        $endDate = "";
        if (isset($criteria['start_date']) && isset($criteria['end_date'])) {
            $startDate = $criteria['start_date'];
            $endDate = $criteria['end_date'];
        }

        // eliminate non-existent fields
        foreach ($criteria as $key => $value) {
            // we remove the table name from the key.
            $key = str_replace($this->table_name.".","", $key);
            if(!Data::arrayValueExists($key,$this->getColumns())) {
                unset($criteria[$key]);
            }
        }

        if(!empty($startDate) && !empty($endDate)) {
            $criteria[] = new Between("DATE({$this->table_name}.created_at)", [$startDate, $endDate], "created_at");
        }

        if (!is_null($alias)) {
            $this->prepareCriteriaAlias($criteria, $alias);
        }
        return $this;
    }

    /**
     * Prepare criteria alias
     *
     * @param $criteria
     * @param $alias
     * @return $this
     */
    public function prepareCriteriaAlias(&$criteria,$alias) {
        $newCriteria = [];

        foreach($criteria as $key => $value) {
            $newCriteria[$alias.".".$key] = $value;
            unset($criteria[$key]);
        }
        $criteria = $newCriteria;

        return $this;
    }

    /**
     * Return class instance properties
     * @return mixed
     */
    protected function toArray() {
        return  json_decode(json_encode($this), true);
    }

    /**
     * @param $keyword
     * @param bool $partialWord
     * @param false $fullPhrase
     * @param string $exclude
     */
    public function prepareFullTextSearchKeyWord(&$keyword, $partialWord=true, $fullPhrase=false, $exclude="") {
        // Replace all non word characters with spaces
        $sane = preg_replace('/[^\p{L}\p{N}_]+/u', ' ', $keyword);

        // 'apple*'
        // Find rows that contain words such as “apple”, “apples”, “applesauce”, or “applet”.
        if($partialWord) {
            $split = explode(" ", $sane);
            $words = [];
            foreach($split as $word) {
                if(strlen($word) > 0)
                    $words[] = $word . '*';
            }
            $imploded = implode(" ", $words);
            $keyword =  $imploded;
        } elseif($fullPhrase) {
            $keyword = '"'.$sane.'"';
        } else {
            $keyword = $sane;
        }

        // handle exclusions.
        if (strlen($exclude) > 0) {
            $split = explode(" ", preg_replace('/[^\p{L}\p{N}_]+/u', ' ', $exclude));
            $words = [];
            foreach($split as $word) {
                if(strlen($word) > 0)
                    $words[] = '-'. $word;
            }
            $imploded = implode(" ", $words);
            $keyword .=  " ".$imploded;
        }
    }
}
