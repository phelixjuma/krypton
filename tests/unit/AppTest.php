<?php

use PHPUnit\Framework\TestCase;

class AppTest extends TestCase {

    /**
     * @var \Kuza\Krypton\App
     */
    protected $app;

    /**
     * Set up the test case.
     */
    public function setUp(): void {

        // require_once "./vendor/autoload.php";

        require_once "./src/App.php";


        $this->app = new \Kuza\Krypton\App();
    }

    /**
     * test an IP address in the US
     *
     * @throws Exception
     */
    public function testAppCreated() {

        // initialize the application
        $this->app->init();

        // set the default system timezone which is GMT/UTC
        date_default_timezone_set('GMT');

        // we authenticate the user
        $this->app->authenticateUser();

        /**
         * We set authorization
         * @var A
         */
        //$app->authorization = $this->app->DIContainer->get("\Kuzalab\Classes\Authorization");

        // run application!
        $this->app->run();

        // print_r($this->app);

        $this->assertTrue(is_object($this->app));
    }
}