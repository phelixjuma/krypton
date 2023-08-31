<?php


/**
 * This is main app class file
 * @author Phelix Juma <jumaphelix@kuzalab.co.ke>
 * @copyright (c) 2018, Kuza Lab
 * @package Kuzalab
 */

namespace Kuza\Krypton;

use DI\Container;
use Doctrine\Common\Annotations\AnnotationReader;
use Dotenv\Dotenv;
use Kuza\Krypton\Classes\Response;
use Kuza\Krypton\Config\Config;
use Pecee\Http\Middleware\Exceptions\TokenMismatchException;
use Pecee\SimpleRouter\Exceptions\HttpException;
use Pecee\SimpleRouter\Exceptions\NotFoundHttpException;
use Pecee\SimpleRouter\SimpleRouter;

use Kuza\Krypton\Classes\Benchmark;
use Kuza\Krypton\Classes\Requests;
use Kuza\Krypton\Framework\EventListener;
use Phoole\Event\Dispatcher;
use Phoole\Event\Provider;
use Predis\Client;
use Libcast\JobQueue\Queue\QueueFactory;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Libcast\JobQueue\JobQueue;


/**
 * Main application class.
 */
final class App {

    private static $instance = null;

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
     * @var Dispatcher $eventsDispatcher
     */
    public $eventsDispatcher;

    /**
     * @var JobQueue
     */
    public $jobQueue;

    /**
     * @var Logger
     */
    public $logger;

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
    private $events_directory = "Events/Events";
    private $event_listeners_directory = "Events/Listeners";
    private $layouts_directory = "Layouts";
    private $logs_directory = "Logs";
    private $routes_file = "routes";

    private $timezone;

    private $app_root;
    private $app_public_directory;

    public $log_access = 0;
    public $access_log_handler = [];

    // Ensure only one instance is created
    private function __construct() {}

    /**
     * Static method to retrieve the instance
     *
     * @return App|null
     */
    public static function getInstance(): ?App
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize the system
     *
     * @param null $app_root
     * @param null $memory_limit
     * @param null $upload_max_filesize
     * @param null $post_max_size
     * @param null $timezone
     * @throws \Exception
     */
    public function init($app_root = null, $memory_limit=null, $upload_max_filesize=null, $post_max_size=null, $timezone=null) {

        // we set the default timezone
        $d_timezone = $timezone ?? "Africa/Nairobi";
        date_default_timezone_set($d_timezone);

        $this->app_root = !empty($app_root) ? $app_root : getcwd();

        //set spl autoload
        spl_autoload_register([$this, 'loadClass']);

        //set exception handler
        set_exception_handler([$this, 'handleException']);

        // set error handler
        set_error_handler([$this, "handleErrors"]);

        // we start the benchmark
        $this->benchmark = new Benchmark();
        $this->benchmark->start();

        // load the environment file
        try {
            $dotenv = Dotenv::createImmutable($this->app_root);
            $dotenv->load();
        } catch (\Exception $e) {
            //print_r($e->getMessage());
        }

        //set the php-di container
        $builder = new \DI\ContainerBuilder();
        //$builder->useAnnotations(true);

        $this->DIContainer = $builder->build();

        // Load listeners (or other services) via annotations and register them
        $this->registerListenersFromAnnotations();

        // Set job queue
        $this->instantiateJobQueue();

        // Set logger
        $this->setLogger();

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
        ini_set("error_log", $this->getLogsDirectory() . "/" .date("Y-m-d", time()). "-errors.log");
        ini_set("ignore_repeated_errors", 1);
        ini_set('memory_limit', $memory_limit ?? '1024M');
        ini_set('upload_max_filesize', $upload_max_filesize ?? '1024M');
        ini_set('post_max_size', $post_max_size ?? '1024M');

        mb_internal_encoding('UTF-8');

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
     * @return string
     */
    private function getLogsDirectory() {
        return $this->app_root . DIRECTORY_SEPARATOR . $this->logs_directory;
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
     * @param $cors
     * @return void
     * @throws TokenMismatchException
     * @throws HttpException
     * @throws NotFoundHttpException
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
        $classFile = $this->app_root .$directorySeperator. str_ireplace("\\",$directorySeperator, $namespace).".php";

        if(is_file($classFile)){
            require_once  $classFile;
        } else {
            echo "file does not exist: ". $classFile;
        }
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    private function registerListenersFromAnnotations() {

        $annotationReader = new AnnotationReader();
        $provider = new Provider();

        $this->eventsDispatcher = new Dispatcher($provider);

        $listenerDir = $this->app_root . DIRECTORY_SEPARATOR . $this->event_listeners_directory .DIRECTORY_SEPARATOR;


        foreach (new \DirectoryIterator($listenerDir) as $file) {
            if ($file->isDot() || $file->getExtension() !== 'php') continue;

            $listenerClass = 'Kuza\Krypton\\Framework\\Events\\Listeners\\' . $file->getBasename('.php');

            $reflectionClass = new \ReflectionClass($listenerClass);

            foreach ($reflectionClass->getMethods() as $method) {

                try {
                    $annotation = $annotationReader->getMethodAnnotation($method, EventListener::class);

                    if ($annotation) {

                        // Use the DI container to resolve the instance and its dependencies
                        $listenerInstance = $this->DIContainer->get($listenerClass);

                        $callable = [$listenerInstance, $method->getName()];

                        $priority = $annotation->priority ?? 50; // Default to 50 if not set in annotation
                        $provider->attach($callable, $priority);

                    }

                } catch (\Exception $e) {
                    //print $e->getMessage();
                }
            }
        }
    }

    /**
     * @return void
     */
    private function instantiateJobQueue() {

        try {

            $redisDSN = Config::getSpecificConfig("JOBQUEUE_REDIS_DSN");

            if (!empty($redisDSN)) {

                $logger = new Logger('JobQueue');
                $logger->pushHandler(new StreamHandler($this->app_root . "/Logs/JobQueue.log", Logger::DEBUG));

                $redis = new Client($redisDSN);

                $this->jobQueue = new JobQueue([
                    'queue'  => QueueFactory::build($redis),
                    'logger' => $logger,
                ]);
            }

        } catch (\Exception $e) {
        }
    }

    /**
     * @return void
     */
    private function setLogger() {
        try {
            $this->logger = new Logger('App');
            $this->logger->pushHandler(new StreamHandler($this->app_root . "/Logs/App.log", Logger::DEBUG));
        } catch (\Exception $e) {
        }
    }

    /**
     * @return Dispatcher
     */
    public static function events(): Dispatcher
    {
        return self::getInstance()->eventsDispatcher;
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
        return $this->app_root . DIRECTORY_SEPARATOR . $this->routes_file . ".php";
    }

    /**
     * Gets the layout file
     * @return string
     */
    public function getLayout() {
        return $this->app_root . DIRECTORY_SEPARATOR . $this->layouts_directory .DIRECTORY_SEPARATOR . "layout.php";
    }

    /**
     * Get the phtml template page for the page
     * @param $page
     * @return string
     */
    private function getViewTemplate($page) {
        return $this->app_root . DIRECTORY_SEPARATOR . $this->views_directory . DIRECTORY_SEPARATOR . $page . '.phtml';
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

        $this->benchmark->stop();

        // we require the layout file
        require $this->getLayout();
    }

    /**
     * @return mixed
     */
    public static function appRoot() {
        return self::getInstance()->app_root;
    }

    /**
     * @return string
     */
    public static function publicDirectory(): string
    {
        return self::getInstance()->app_root . DIRECTORY_SEPARATOR . 'Public';
    }

    /**
     * @return Requests
     */
    public static function requests(): Requests
    {
        return self::getInstance()->requests;
    }

    /**
     * @return Response
     */
    public static function response(): Response
    {
        return self::getInstance()->response;
    }
}
