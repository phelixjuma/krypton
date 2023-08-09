<?php

/**
 * This is the Event Listener class
 * @author Phelix Juma <jumaphelix@kuzalab.com>
 * @copyright (c) 2019, Kuza Lab
 * @package Kuza Krypton PHP Framework
 */

namespace Kuza\Krypton\Framework;

use Phoole\Event\Events\StoppableEvent;

class Event extends StoppableEvent {

    protected $data;

    public function __construct(array $data) {
        $this->data = $data;
    }

    public function getData(): array {
        return $this->data;
    }
}

