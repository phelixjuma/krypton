<?php

/**
 * This is script handles Firebase details
 * @author Phelix Juma <jumaphelix@Kuza\Krypton.co.ke>
 * @copyright (c) 2018, Kuza Lab
 * @package Kuza\Krypton
 */

namespace Kuza\Krypton\Classes;

use Kuza\Krypton\Config\Config;

use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use Kuza\Krypton\Exceptions\CustomException;

/**
 * Handle Firebase functions
 */
final class Firebase {

    private $auth;

    public function __construct() {

        $serviceAccount = ServiceAccount::fromJsonFile(dirname(__DIR__).'/'.Config::FIREBASE_SERVICE_ACCOUNT);

        $firebase = (new Factory)
            ->withServiceAccount($serviceAccount)
            ->create();

        $this->auth = $firebase->getAuth();
    }

    /**
     * Verify a user's authorization token
     * JWT registered claims include:
     * iss (issuer), exp (expiration time), sub (subject), aud (audience)
     * @param $token
     * @return array an array containing the user details.
     * @throws CustomException
     */
    public function verifyIdToken($token) {
        try {
            $verifiedIdToken = $this->auth->verifyIdToken($token,true);
        } catch (\Exception $e) {
            throw new CustomException($e->getMessage(), Requests::RESPONSE_UNAUTHORIZED);
        }

        $uid = $verifiedIdToken->getClaim('sub');

        $user = $this->getUser($uid);

        return $user;
    }

    /**
     * Get a user from Firebase
     * @param $uid
     * @return mixed
     */
    public function getUser($uid) {
        $user = [];
        $userDetails =  $this->auth->getUser($uid);

        if(isset($userDetails->uid) && !empty($userDetails->uid)) {
            $user['firebase_id'] = $userDetails->uid;
            $user['email_address'] = $userDetails->email;
            $user['phone_no'] = $userDetails->phoneNumber;
            $user['email_verified'] = $userDetails->emailVerified;
            $user['avatar_url'] = $userDetails->photoUrl;
            $user['disabled'] = $userDetails->disabled;
            $user['created_at'] = $userDetails->metadata->createdAt->format("Y-m-d H:i:s");
            $user['last_login_at'] = $userDetails->metadata->lastLoginAt->format("Y-m-d H:i:s");
        }
        return $user;
    }

    public function changeUserPassword($uid,$newPassword) {
        return $this->auth->changeUserPassword($uid,$newPassword);
    }

    /**
     * Disable a user
     * @param $uid
     * @return mixed
     */
    public function disableUser($uid) {
        return $this->auth->disableUser($uid);
    }

    /**
     * Enable a disabled user
     * @param $uid
     * @return mixed
     */
    public function enableUser($uid) {
        return $this->auth->enableUser($uid);
    }

    /**
     * Delete a user from firebase
     * @param $uid
     */
    public function deleteUser($uid) {
        $this->auth->deleteUser($uid);
    }

    /**
     * Send email verification email
     * @param $uid
     */
    public function sendEmailVerificationEmail($uid) {
        $redirectTo = "";

        $this->auth->sendEmailVerification($uid,$redirectTo);
    }

    /**
     * Send password reset email to the user
     * @param $email
     */
    public function sendPasswordResetEmail($email) {
        $redirectTo = "";

        $this->auth->sendPasswordResetEmail($email,$redirectTo);
    }
}
