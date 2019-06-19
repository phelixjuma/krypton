<?php
/**
 * This is script handles unauthenticated exceptions
 * @author Phelix Juma <jumaphelix@kuzalab.com>
 * @copyright (c) 2018, Kuza Lab
 * @package Kuzalab
 */

namespace Kuza\Krypton\Exceptions;

use \Exception;


class UnauthenticatedException extends Exception {

    /**
     * Redefine the exception so message isn't optional
     * CustomException constructor.
     * @param $message
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct($message, $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}

