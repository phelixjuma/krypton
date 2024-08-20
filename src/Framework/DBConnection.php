<?php
/**
 * This is the DB Connection model
 * @author Allan Dhoye <allan@kuzalab.com>
 * @copyright (c) 2018, Kuza Lab
 * @package Kuzalab
 */

namespace Kuza\Krypton\Framework;


use Kuza\Krypton\Database\Model;

class DBConnection extends Model {

    /**
     * @param $table
     * @param $database
     */
    public function __construct($table = null, $database = null) {
        parent::__construct($database, $table);
    }


}
