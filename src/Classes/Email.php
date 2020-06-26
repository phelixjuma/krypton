<?php
/**
 * This is script handles emails.
 * @author Phelix Juma <jumaphelix@kuzalab.com>
 * @copyright (c) 2018, Kuza Lab
 * @package Kuzalab
 */

namespace Kuza\Krypton\Classes;

use Kuza\Krypton\Config\Config;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * This class handles all methods and instances for managing the email sending
 * @package mealtimefranchise
 */
class Email {

    /**
     * The PHPMailer object
     * @var PHPMailer
     */
    private $PHPMailer;

    /**
     * The success status
     * @var string
     */
    public $success = false;

    /**
     * The response message
     * @var string
     */
    public $responseMessage = '';

    /**
     * Email constructor.
     * @param PHPMailer $PHPMailer
     * @throws \Kuza\Krypton\Exceptions\ConfigurationException
     */
    public function __construct(PHPMailer $PHPMailer) {

        $this->PHPMailer = $PHPMailer;

        $host = Config::getSpecificConfig("MAIL_HOST");
        $port = Config::getSpecificConfig("MAIL_PORT");
        $username = Config::getSpecificConfig("MAIL_USERNAME");
        $password = Config::getSpecificConfig("MAIL_PASSWORD");
        $auth = Config::getSpecificConfig("MAIL_SMTPAUTH");
        $secure = Config::getSpecificConfig("MAIL_SMTPSECURE");
        $timeout = Config::getSpecificConfig("MAIL_TIMEOUT");
        $debug = Config::getSpecificConfig("MAIL_DEBUG");

        $this->setConfigurations($host, $port, $username, $password, $auth, $secure, $timeout, $debug);

    }

    /**
     * Set configuration details
     * @param $host
     * @param $port
     * @param $username
     * @param $password
     * @param bool $auth
     * @param bool $secure
     * @param int $timeout
     * @param int $debug
     */
    private function setConfigurations($host, $port, $username, $password, $auth = true, $secure = true, $timeout=3, $debug=3) {

        $this->PHPMailer->Timeout = $timeout;
        $this->PHPMailer->SMTPDebug = $debug;
        $this->PHPMailer->isHTML(true);

        $this->PHPMailer->IsSMTP(); //telling the class to use SMTP
        $this->PHPMailer->SMTPAuth = $auth; //enable SMTP authentication

        $this->PHPMailer->Host = $host;
        $this->PHPMailer->Port = $port;
        $this->PHPMailer->Username = $username;
        $this->PHPMailer->Password = $password;
        $this->PHPMailer->SMTPSecure = $secure;
    }

    /**
     * Set the sender of the email
     * @param string $senderEmail the email address of the sender
     * @return $this
     */
    public function setSenderEmail($senderEmail) {

        $this->PHPMailer->From = $senderEmail;

        return $this;
    }

    /**
     * Set the sender name
     * @param $senderName
     * @return $this
     */
    public function setSenderName($senderName) {
        $this->PHPMailer->FromName = $senderName;

        return $this;
    }

    /**
     * Set reply to
     * @param $email
     * @param $name
     * @return $this
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function setReplyTo($email, $name) {
        $this->PHPMailer->addReplyTo($email, $name);

        return $this;
    }

    /**
     * Set the subject of the email
     * @param string $subject the subject of the email
     * @return $this
     */
    public function setSubject($subject) {

        $this->PHPMailer->Subject = $subject;

        return $this;
    }

    /**
     * Set the body/content of the email
     * @param string $body the content of the email
     *
     * @return $this
     */
    public function setBody($body) {

        $this->PHPMailer->Body = $body;

        $this->PHPMailer->AltBody = $body;

        return $this;
    }

    /**
     * Set the recipients of the email
     * @param $recipients
     * @return $this
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function setRecipients($recipients) {

        foreach ($recipients as $recipient) {
            $this->PHPMailer->addAddress($recipient);
        }

        return $this;
    }

    /**
     * Send the email
     */
    public function sendEmail() {

        try {

            try {
                $this->success = $this->PHPMailer->send();
            } catch (\Exception $e) {
                $this->responseMessage = $e->getMessage();
            }
        } catch (\Exception $e) {
            $this->responseMessage = $e->getMessage();
        }
    }

}
