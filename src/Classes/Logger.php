<?php

/**
 * This is script handles logging
 * @author Phelix Juma <jumaphelix@Kuza\Krypton.co.ke>
 * @copyright (c) 2018, Kuza Lab
 * @package Kuza\Krypton
 */

namespace Kuza\Krypton\Classes;

use Kuza\Krypton\Exceptions\CustomException;
use Kuza\UserDataCapture\Location;
use Kuza\UserDataCapture\Request;
use Kuza\UserDataCapture\UserAgent;
use Kuza\Krypton\App;

final class Logger {

    private function __construct() {

    }

    /**
     * Prepare log user access.
     * @param $statusCode
     * @param App $app
     * @return array
     */
    public static function prepareAccessLog($statusCode, App $app) {

        // we set the success of the endpoint
        $success = $statusCode == 200 ? true : false;

        // we add health check details. Contains the API Response time and the API memory usage
        $app->benchmark->stop();
        $benchmarkData = $app->benchmark
            ->results()
            ->toArray();

        $logData = array_merge(
            [
                "id"    => $app->currentUser->id . "-" . time() ."-". rand(0,100),
                "date" => date("Y-m-d H:i:s", time()),
                "timestamp" => time()
            ],
            [
                "success" => $success
            ],
            [
                "user_id" => $app->currentUser->id,
                "email_address" => $app->currentUser->email_address,
                "phone_number" => $app->currentUser->phone_number,
                "user_name" => "{$app->currentUser->given_name} {$app->currentUser->surname} {$app->currentUser->other_names}"
            ],
            $benchmarkData
        );

        // we get location details
        try {

            $location = new Location();

            $logData = array_merge($logData, $location->toArray());

        } catch (\Exception $ex) {
            //print ($ex->getMessage());
        }

        // we get the user agent data
        try {

            $userAgent = new UserAgent();

            $logData = array_merge($logData, $userAgent->toArray());

        } catch (\Exception $ex) {
           // print ($ex->getMessage());
        }

        // we get the Geo metrics, user agent details and the request details.
        try {

            $request = new Request();

            // we get the session
            $session = isset($request->headers['session']) ? $request->headers['session'] : "";

            // we unset request headers
            unset($request->headers);

            $logData = array_merge($logData, $request->toArray(), ["session" => $session]);

        } catch (\Exception $ex) {
           // print ($ex->getMessage());
        }

        $logData = Data::eliminateEmptyKeysFromArray($logData);
        $logData['headers'] = Data::eliminateEmptyKeysFromArray($logData['headers']);

        // we serialize the arrays within the
        array_walk($logData, function (&$value, $key) {
            if (is_array($value)) {
                $value = Data::serializeData($value);
            }
        });

        return $logData;
    }

    /**
     * We log all server errors to dynamo db.
     * @param DynamoDB $dynamoDB
     * @param Randomstring $randomiser
     * @param $debugTrace
     * @return bool
     * @throws CustomException
     */
    public static function logErrors(DynamoDB $dynamoDB, Randomstring $randomiser, $debugTrace) {
        global $app;
        // we upload the file to Dynamo DB

        $dynamoDB->setTable("app_server_error_logs");

        $subject = "Backend Server Error";
        $description = "Logs from the backend to help trace what errors and exceptions are thrown.";

        // we add the current user details to the log data
        $debugTrace['current_user_id'] = isset($app->currentUser) && $app->currentUser !== null ? $app->currentUser->id : null;
        $debugTrace['current_role_id'] = $app->currentRole;
        $debugTrace['currentUser'] = $app->currentUser;

        self::prepareLogMetaData($randomiser->uniqueString,$subject,$description,$debugTrace);


        $logData = [];

        // format the request
        foreach ($debugTrace['received_request'] as $key => $value) {
            if (is_object($value)) {
                $value =  (array) $value;

                if (sizeof($value) > 0) {
                    $logData[$key] = $value;
                }
            } elseif(is_array($value) && sizeof($value) > 0) {
                $logData[$key] = $value;
            } elseif (!empty($value)) {
                $logData[$key] = $value;
            }
        }
        unset($debugTrace['received_request']);

        // format the error trace
        foreach($debugTrace['error_details'] as $key => $value) {
            if (!empty($value)) {
                $logData[$key] = $value;
            }
        }
        unset($debugTrace['error_details']);


        foreach($debugTrace as $key => $value) {
            if (!empty($value)) {
                $logData[$key] = $value;
            }
        }

        $logData['headers'] = Data::eliminateEmptyKeysFromArray($logData['headers']);
        $logData['apiData'] = Data::eliminateEmptyKeysFromArray($logData['apiData']);
        $logData['body'] = Data::eliminateEmptyKeysFromArray($logData['body']);
        $logData['currentUser'] = Data::eliminateEmptyKeysFromArray($logData['currentUser']);


        $result = $dynamoDB->addJsonItem(json_encode($logData));

        return $result;
    }

    /**
     * This is a test point handler.
     * This logs responses at various sections where it's called.
     * The logging is useful for debugging various sections of the codebase by checking the responses at the various sections.
     * @param string $section
     * @param string|array $data
     * @return boolean
     */
    public static function testPoint($section, $data) {
        $tpMessage = "
		" . DATE('Y-m-d H:i:s', time()) . "<br>
                " . $section . "<br>
		" . jsonDecode($data) . "<br><br>
	";
        if (file_put_contents(TEST_POINT_LOGS, $tpMessage, FILE_APPEND | LOCK_EX)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $id
     * @param $logLevel
     * @param $subject
     * @param $description
     * @param $logData
     * @throws CustomException
     */
    public static function prepareLogMetaData($id, $subject, $description, &$logData) {
        $module = isset($_SERVER['module']) ? $_SERVER['module'] : "MobileApp";;
        $time = time();
        if(is_array($logData))
        {
            $logData['Environment'] = $_SERVER['HTTP_HOST'];
            $logData['Module'] = $module;
            $logData['Event_Time'] = "$time";
            $logData['Subject'] = $subject;
            $logData['Description'] = $description;
            $logData['Deployment'] = \Kuza\Krypton\Config\Config::getSpecificConfig("DEPLOYMENT");
            $logId = $module.'-'. $time . '-'.$id;
            $logData['id'] = $logId;
        }
    }
}
