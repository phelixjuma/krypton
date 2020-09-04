<?php

/**
 * This is script handles benchmarks.
 * @author Phelix Juma <jumaphelix@kuzalab.co.ke>
 * @copyright (c) 2018, Kuza Lab
 * @package Kuzalab
 */

namespace Kuza\Krypton\Classes;

final class Benchmark {

    private $startCpuUsage;
    private $stopCpuUsage;
    private $startMemoryUsage;
    private $stopMemoryUsage;
    private $startTime;
    private $stopTime;

    public $avgCpuUsage;
    public $avgMemoryUsage;
    public $loadTime;

    public function __construct() {
    }

    /**
     * Capture details at the start of the benchmark
     */
    public function start() {
        $this->startCpuUsage = $this->getServerCpuTime();
        $this->startMemoryUsage = $this->getServerMemoryUsage();
        $this->startTime = $this->getScriptLoadTime();
    }

    /**
     * Capture details at the end of the benchmark
     */
    public function stop() {
        $this->stopCpuUsage = $this->getServerCpuTime();
        $this->stopMemoryUsage = $this->getServerMemoryUsage();
        $this->stopTime = $this->getScriptLoadTime();
    }

    /**
     * Get the average performance results
     */
    public function results() {
        $this->avgCpuUsage = $this->stopCpuUsage - $this->startCpuUsage;
        $this->avgMemoryUsage = $this->stopMemoryUsage - $this->startCpuUsage;
        $this->loadTime = $this->stopTime - $this->startTime;

        return $this;
    }

    public function format() {

        $this->avgCpuUsage = "$this->avgCpuUsage s";
        $this->avgMemoryUsage = "$this->avgMemoryUsage MBs";
        $this->loadTime = "$this->loadTime s";

        return $this;
    }

    /**
     * Get the results as an array.
     * @return mixed
     */
    public function toArray() {
        return  json_decode(json_encode($this), true);
    }

    /**
     * Get the server memory usage in MBs
     * @return float|int
     */
    private function getServerMemoryUsage() {
        return memory_get_usage()/1024/1024;
    }

    /**
     * Get the CPU usage of the server
     * @return mixed
     */
    private function getServerCpuTime() {
        $ru = getrusage();

        $currentTime = $ru['ru_stime.tv_sec'] +
            round($ru['ru_stime.tv_usec']/1000000, 4);

        return $currentTime;
    }

    /**
     * Get the script load time.
     * @return mixed
     */
    private function getScriptLoadTime() {
        return microtime(true);
    }

}
