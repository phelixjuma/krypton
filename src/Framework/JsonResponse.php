<?php

/**
 * This is Task Interface
 * @author Phelix Juma <jumaphelix@kuzalab.com>
 * @copyright (c) 2020, Kuza Lab
 * @package Kuza Krypton PHP Framework
 */

namespace Kuza\Krypton\Framework;


class JsonResponse {

    public $code = null;
    public $success = false;
    public $message = "";
    public $data = [];
    public $errors = [];
    public $meta = [
        "total_records" => 0,
        "benchmark" => null,
        "backtrace" => null
    ];

   public function toArray() {
       return  json_decode(json_encode($this), true);
   }

}
