<?php

/**
 * This is the Users Controller Class
 * @author Phelix Juma <jumaphelix@kuzalab.com>
 * @copyright (c) 2019, Kuza Lab
 * @package Kuza Krypton PHP Framework
 */

namespace Kuza\Krypton\Framework;


use Kuza\Krypton\Classes\Requests;
use Kuza\Krypton\Classes\Response;
use Kuza\Krypton\Exceptions\CustomException;
use Kuza\Krypton\App;
use Rakit\Validation\Validator;

class Controller {

    /**
     * @var App
     */
    protected $app;

    /**
     * @var Requests
     */
    protected $requests;

    /**
     * @var Response
     */
    protected $response;

    protected $jsonResponse;

    public $validation_errors = false;
    public $errors = [];


    public $currentUser;

    /**
     * Controller constructor.
     * @throws \Kuza\Krypton\Exceptions\ConfigurationException
     */
    public function __construct() {

        global $app;

        $this->app  = $app;

        $this->jsonResponse = new JsonResponse;
        $this->requests = $this->app->requests;
        $this->response = $this->app->response;

        if ($this->requests->backtrace == 1) {
            ini_set("display_errors",1);
        }

        // set current user
        $this->currentUser = RoutesHelper::request()->user;
    }

    /**
     * Handle API response
     * @param int $code
     * @param bool $success
     * @param string $message
     * @param array $data
     * @param array $errors
     * @param int $totalRecords
     * @throws CustomException
     */
    public function apiResponse($code = Requests::RESPONSE_OK, $success = true, $message = "", $data = [], $errors = [], $totalRecords = 0) {

        $this->app->benchmark->stop();

        $this->jsonResponse->code = $code;
        $this->jsonResponse->success = $success;
        $this->jsonResponse->message = $message;
        $this->jsonResponse->data = $data;
        $this->jsonResponse->errors = $errors;
        $this->jsonResponse->meta['total_records'] = $totalRecords;

        if ($this->requests->isBenchmark) {
            $this->jsonResponse->meta['benchmark'] = $this->app->benchmark->results()->format()->toArray();
        }

        // access log
        if ($this->app->log_access == 1) {
            $this->logAccess();
        }

        //Send response
        if ($this->requests->download == true) {
            $fileType = $this->requests->filters->download_file_type ?? 'excel';
            $this->response->status_code($code)->download($data, $fileType);
        } else {
            $this->response->status_code($code)->json($this->jsonResponse->toArray());
        }
    }

    /**
     * Render a view
     * @param $view
     * @param array $data
     * @param array $errors
     */
    public function view($view, $data = [], $errors = []) {
        $this->app->view($view, $data, $errors);
    }

    /**
     * Validation of data
     * @param $data
     * @param $rules
     * @return array|bool
     * @throws CustomException
     */
    public function validate($data, $rules) {

        $validator = new Validator();

        $validation = $validator->validate($data, $rules);

        if ($validation->fails()) {

            $errors = $validation->errors->firstOfAll();

            if ($this->requests->isJsonRequest()) {

                $this->jsonResponse->errors = $errors;

                $this->response->status_code(400)->json($this->jsonResponse->toArray());

            }
            return $errors;
        }
        return true;
    }

    /**
     *
     */
    public function logAccess() {
        call_user_func($this->app->access_log_handler, $this->app, $this->jsonResponse);
    }

}
