<?php

/**
 * This is the Event Listener class
 * @author Phelix Juma <jumaphelix@kuzalab.com>
 * @copyright (c) 2019, Kuza Lab
 * @package Kuza Krypton PHP Framework
 */

namespace Kuza\Krypton\Framework;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class EventListener {
    public $event;
    public $priority = 50; // Default priority
}
