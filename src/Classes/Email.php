<?php
/**
 * This is script handles cache. Implements memcached
 * @author Phelix Juma <jumaphelix@kuzalab.co.ke>
 * @copyright (c) 2018, Kuza Lab
 * @package Kuzalab
 */

namespace Kuza\Krypton\Classes;

use Kuza\Krypton\Config\Config;

/** Do not allow direct access of this file */
if (str_replace(DIRECTORY_SEPARATOR, "/", __FILE__) == $_SERVER['SCRIPT_FILENAME']) {
    exit;
}

/**
 * This class handles all methods and instances for managing the email sending
 * @package mealtimefranchise
 */
class Email {

    /**
     * The PHPMailer object
     * @var \PHPMailer
     */
    private $PHPMailer;

    /**
     * This is the email address of the email sender
     * @var string
     */
    private $senderEmail;

    /**
     * This is the name of the sender
     * @var string
     */
    private $senderName;

    /**
     * This is an array having the email addresses of the email recipients
     * @var array
     */
    private $recipients = [];

    /**
     * This is the email subject
     * @var string
     */
    private $subject;

    /**
     * this is the body/content of the email
     * @var string
     */
    private $body;

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
     * @param \PHPMailer $PHPMailer
     */
    public function __construct(\PHPMailer $PHPMailer) {
        $this->PHPMailer = $PHPMailer;
    }

    /**
     * Set the sender of the email
     * @param string $senderEmail the email address of the sender
     */
    public function setSenderEmail($senderEmail) {
        $this->senderEmail = $senderEmail;
    }

    public function setSenderName($senderName) {
        $this->senderName = $senderName;
    }

    /**
     * Set the subject of the email
     * @param string $subject the subject of the email
     */
    public function setSubject($subject) {
        $this->subject = $subject;
    }

    /**
     * Set the body/content of the email
     * @param string $body the content of the email
     */
    public function setBody($body) {
        $this->body = $body;
    }

    /**
     * Set the recipients of the email
     * @param array $recipients the recipients of the email
     */
    public function setRecipients($recipients) {
        $this->recipients = $recipients;
    }

    /**
     * Send the email
     */
    public function sendEmail() {

        $mail = $this->PHPMailer;

        try {
            $mail->Timeout = 10;
            $mail->SMTPDebug = 3;
            $mail->IsSMTP(); //telling the class to use SMTP
            $mail->SMTPAuth = true; //enable SMTP authentication
            $mail->Host = Config::MAIL_HOST;
            $mail->Port = Config::MAIL_PORT;
            $mail->Username = Config::MAIL_USERNAME;
            $mail->Password = Config::MAIL_PASSWORD;
            $mail->SMTPSecure = Config::MAIL_SMTPSECURE;

            foreach ($this->recipients as $recipient) {
                $mail->addAddress($recipient);
            }
            $mail->From = $this->senderEmail;
            $mail->FromName = $this->senderName;

            $mail->addReplyTo($this->senderEmail, $this->senderName);
            $mail->Subject = $this->subject;
            $mail->Body = $this->body;
            $mail->AltBody = $this->body;

            $mail->isHTML(true);

            try {
                $this->success = $mail->send();
            } catch (\Exception $e) {
                $this->responseMessage = $e->getMessage();
            }
        } catch (\Exception $e) {
            $this->responseMessage = $e->getMessage();
        }
    }

}
