<?php
/**
 *
 * @author Phelix Juma <jumaphelix@kuzalab.co.ke>
 * @copyright (c) 2018, Kuza Lab
 * @package Kuzalab
 */

namespace Kuza\Krypton\Exceptions;

use \Exception;


class PaymentRequiredException extends Exception {

    /**
     *
     * @param $message
     * @param $code
     * @param Exception|null $previous
     */
    public function __construct($message, $code = 402, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}

