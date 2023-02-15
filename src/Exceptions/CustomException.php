<?php
/**
 * This is script handles not found exceptions 
 * @author Phelix Juma <jumaphelix@kuzalab.co.ke>
 * @copyright (c) 2018, Kuza Lab
 * @package Kuzalab
 */

namespace Kuza\Krypton\Exceptions;

use \Exception;


class CustomException extends Exception {

    /**
     * Redefine the exception so message isn't optional
     * CustomException constructor.
     * @param $message
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct($message, $code = 500, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}

