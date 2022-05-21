<?php


/**
 * This is main app class file
 * @author Phelix Juma <jumaphelix@kuzalab.co.ke>
 * @copyright (c) 2018, Kuza Lab
 * @package Kuzalab
 */

namespace Kuza\Krypton;

use DI\Container;
use Dotenv\Dotenv;
use Kuza\Krypton\Classes\Response;
use Kuza\Krypton\Config\Config;
use Pecee\SimpleRouter\SimpleRouter;

use Kuza\Krypton\Classes\Benchmark;
use Kuza\Krypton\Classes\Requests;


/**
 * Main application class.
 */
final class App {

    /**
     * Holds all details of a received request.
     * @var Requests $requests
     */
    public $requests;

    /**
     * Holds the response instance
     * @var Response $response
     */
    public $response;

    /**
     * Checks if there is an error or exception
     * @var bool
     */
    public $is_error = false;

    /**
     * Holds the error or exception trace
     * @var array
     */
    public $debug_trace = [];

    /**
     * Dependency Injection Service Container
     * @var Container $DIContainer
     */
    public $DIContainer;

    /**
     * @var Benchmark $benchmark the benchmark handler
     */
    public $benchmark;

    /**
     * @var \PDO $pdoConnection the database connection object
     */
    public $pdoConnection;

    /**
     * The current view to display
     * @var $view
     */
    public $view;

    private $exception_handler = [];

    private $error_handler = [];

    private $controllers_directory = "Controllers";
    private $views_directory = "Views";
    private $layouts_directory = "Layouts";
    private $logs_directory = "Logs";
    private $routes_file = "routes";

    private $timezone;

    private $document_root;

    public $log_access = 0;
    public $access_log_handler = [];

    /**
     * Initialize the system
     *
     * @param null $document_root
     * @param null $memory_limit
     * @param null $upload_max_filesize
     * @param null $post_max_size
     * @param null $timezone
     * @throws \Exception
     */
    public function init($document_root = null, $memory_limit=null, $upload_max_filesize=null, $post_max_size=null, $timezone=null) {

        // we set the default timezone
        $d_timezone = $timezone ?? "Africa/Nairobi";
        date_default_timezone_set($d_timezone);

        $this->document_root = !empty($document_root) ? $document_root : getcwd();

        //set spl autoload
        spl_autoload_register([$this, 'loadClass']);

        //set exception handler
        set_exception_handler([$this, 'handleException']);

        // set error handler
        set_error_handler([$this, "handleErrors"]);


        // we start the benchmark
//        $this->benchmark = new Benchmark();
//        $this->benchmark->start();

        // load the environment file
        try {
            $dotenv = Dotenv::createImmutable($this->document_root);
            $dotenv->load();
        } catch (\Exception $e) {
            //print_r($e->getMessage());
        }

        try {
            $displayErrors = Config::getSpecificConfig("DISPLAY_ERRORS");
        } catch (\Exception $e) {
            $displayErrors = 0;
        }

        // error reporting - all errors for development. Works when display_errors = On in php.ini file
        error_reporting(E_ALL | E_STRICT);
        ini_set("display_errors", $displayErrors);
        ini_set("html_errors", 1);
        ini_set("display_startup_errors", 0);
        ini_set("log_errors", 1);
        ini_set("error_log", $this->logs_directory . "/" .date("Y-m-d", time()). "-errors.log");
        ini_set("ignore_repeated_errors", 1);
        ini_set('memory_limit', $memory_limit ?? '1024M');
        ini_set('upload_max_filesize', $upload_max_filesize ?? '1024M');
        ini_set('post_max_size', $post_max_size ?? '1024M');

        mb_internal_encoding('UTF-8');

        //set the php-di container
        $builder = new \DI\ContainerBuilder();
        //$builder->useAnnotations(true);

        $this->DIContainer = $builder->build();

        $this->requests = new Requests();

        $this->response = new Response($this->requests);

        // we show errors when in backtrace mode.
        if ($this->requests->backtrace == 1) {
            ini_set("display_errors",1);
        }
    }

    /**
     * Set exception handler
     * @param $handler
     * @return $this
     */
    public function setExceptionHandler($handler) {

        $this->exception_handler = $handler;

        return $this;
    }

    /**
     * Set error handler
     * @param $handler
     * @return $this
     */
    public function setErrorHandler($handler) {

        $this->error_handler = $handler;

        return $this;
    }

    /**
     * Set views directory
     * @param $dir
     * @return $this
     */
    public function setViewsDirectory($dir) {

        $this->views_directory = $dir;

        return $this;
    }

    /**
     * Set layouts directory
     * @param $dir
     * @return $this
     */
    public function setLayoutsDirectory($dir) {

        $this->layouts_directory = $dir;

        return $this;
    }

    /**
     * Set controllers directory
     * @param $dir
     * @return $this
     */
    public function setControllersDirectory($dir) {

        $this->controllers_directory = $dir;

        return $this;
    }

    /**
     * Set logs directory
     * @param $dir
     * @return $this
     */
    public function setLogsDirectory($dir) {

        $this->logs_directory = $dir;

        return $this;
    }

    /**
     * Set routes file
     * @param $file
     * @return $this
     */
    public function setRoutesFile($file) {
        $this->routes_file = $file;

        return $this;
    }

    /**
     * Handle exception
     * @param $ex
     */
    public function handleException($ex) {
        call_user_func([new $this->exception_handler[0](), $this->exception_handler[1]], $this, $ex);
    }

    /**
     * Handle error
     * @param $errorNumber
     * @param $errorString
     * @param $errorFile
     * @param $errorLine
     */
    public function handleErrors($errorNumber, $errorString, $errorFile, $errorLine) {
        call_user_func([new $this->error_handler[0](), $this->error_handler[1]], $this, $errorNumber, $errorString, $errorFile, $errorLine);
    }

    /**
     * Run the application!
     * @param null $cors
     * @throws \Pecee\Http\Middleware\Exceptions\TokenMismatchException
     * @throws \Pecee\SimpleRouter\Exceptions\HttpException
     * @throws \Pecee\SimpleRouter\Exceptions\NotFoundHttpException
     */
    public function run($cors = null) {

        if (!is_null($cors)) {
            $this->enableCors($cors);
        }

        #call_user_func( array( $obj, 'method' ), "");

        include $this->getRouteDefinitions();

        /**
         * The default namespace for route-callbacks, so we don't have to specify it each time.
         * Can be overwritten by using the namespace config option on your routes.
         */

        SimpleRouter::setDefaultNamespace("\Kuza\Krypton\Framework\\".$this->controllers_directory);

        // Add our container to simple-router and enable dependency injection
        SimpleRouter::enableDependencyInjection($this->DIContainer);

        // Start the routing
        SimpleRouter::start();
    }

    /**
     * Class loader.
     */
    public function loadClass($name) {

        //we eliminate the root namespace. What remains is of the form: Models\Users
        $namespace = str_ireplace("Kuza\Krypton\\Framework\\","", $name);

        //we define the directory seperator
        $directorySeperator = "\\"; // Windows-based systems
        if(DIRECTORY_SEPARATOR == "/"){
            $directorySeperator = "/";//Unix systems
        }

        //we replace the backslash in the namespace with the directory seperator and add the class extension
        $classFile = $this->document_root .$directorySeperator. str_ireplace("\\",$directorySeperator, $namespace).".php";

        if(is_file($classFile)){
            require_once  $classFile;
        } else {
            echo "file does not exist: ". $classFile;
        }
    }

    /**
     * Enable CORS
     */
    public function enableCors($cors) {

        foreach ($cors as $header) {
            header($header);
        }

        header('Vary', 'Origin');
        // set credentials to true
        header('Access-Control-Allow-Credentials', true);

    }

    /**
     * Get the routes definition file
     * @return string
     */
    private function getRouteDefinitions() {
        return $this->routes_file . ".php";
    }

    /**
     * Gets the layout file
     * @return string
     */
    public function getLayout() {
        return $this->layouts_directory . "/layout.php";
    }

    /**
     * Get the phtml template page for the page
     * @param $page
     * @return string
     */
    private function getViewTemplate($page) {
        return $this->views_directory ."/". $page . '.phtml';
    }

    /**
     * Render a view
     * @param $view
     * @param array $data
     * @param array $errors
     */
    public function view($view, $data = [], $errors = []) {

        // we set the view details.
        $this->view = $this->getViewTemplate($view);

        # we extract the supplied variables to make them available to the controller
        extract($data);
        extract($errors);

//        $this->benchmark->stop();

        // we require the layout file
        require $this->getLayout();
    }
}
