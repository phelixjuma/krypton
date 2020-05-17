<?php

/**
 * This is Task Interface
 * @author Phelix Juma <jumaphelix@kuzalab.com>
 * @copyright (c) 2020, Kuza Lab
 * @package Kuza Krypton PHP Framework
 */

namespace Kuza\Krypton\Framework;


interface TaskInterface {

    /**
     * All tasks must implement this method
     * @return mixed
     */
    public function runTask();

}