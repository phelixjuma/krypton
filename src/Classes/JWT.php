<?php
/**
 * This is the JWT Authentication handler
 * @author Phelix Juma <jumaphelix@kuzalab.co.ke>
 * @copyright (c) 2018, Kuza Lab
 * @package Kuzalab
 */

namespace Kuza\Krypton\Classes;

use Kuza\Krypton\Config\Config;

class JWT {

    //RSA Keys
    private $private_key_file = "";
    private $public_key_file = "";

    private $privateKey;
    private $publicKey;

    private $expiry_duration = 3600; // defaults to 1 hour

    private $issuer ;
    private $audience;
    private $issuedAt;
    private $notBefore;
    private $expiry;
    private $secret;

    private $payload;

    public $jwtToken;

    /**
     * JWT constructor.
     * @throws CustomException
     */
    public function __construct() {

        $this->issuer = Config::getSiteURL();
        $this->audience = Config::getSiteURL();
        $this->secret = Config::getJWTSecret();
        $this->issuedAt = Dates::getTimestamp();
        $this->notBefore = Dates::getTimestamp();
        $this->expiry = time()+$this->expiry_duration;
        
        $this->payload = array(
            "iss"   => $this->issuer,
            "aud"   => $this->audience,
            "iat"   => $this->issuedAt,
            "nbf"   => $this->notBefore,
            "exp"   => $this->expiry,
            "jti"   => $this->secret
        );
    }

    /**
     * Set the file path to the public key
     * @param $filePath
     */
    public function setPublicKeyFile($filePath) {
        $this->public_key_file = $filePath;
    }

    /**
     * Set the file path to the private key
     * @param $filePath
     */
    public function setPrivateKeyFile($filePath) {
        $this->private_key_file = $filePath;
    }

    /**
     * Set the expiry duration in seconds
     * @param $duration
     */
    public function setExpiryDuration($duration) {
        $this->expiry_duration = $duration;
    }

    /**
     * Get the RSA public key
     * @return bool|string
     */
    private function getRSAPublicKey() {
        return file_get_contents($this->public_key_file);
    }

    /**
     * Get the RSA private key
     * @return bool|string
     */
    private function getRSAPrivateKey() {
        return file_get_contents($this->private_key_file);
    }

    /**
     * Generate JWT token
     * @param $userId
     * @throws CustomException
     */
    public function generateToken($userId) {

        $this->privateKey = $this->getRSAPrivateKey();

        $this->payload['id'] = $userId;

        try {
            $this->jwtToken = \Firebase\JWT\JWT::encode($this->payload, $this->privateKey, 'RS256');
        } catch(\Exception $e) {
            throw new CustomException($e->getMessage(),Requests::RESPONSE_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Decode a JWT token
     * @param $token
     * @return array
     * @throws JWTTokenException
     */
    public function decodeToken($token) {

        $this->publicKey = $this->getRSAPublicKey();

        try {
            $decoded = \Firebase\JWT\JWT::decode($token, $this->publicKey, array('RS256'));
        } catch(\Exception $e) {
            throw new JWTTokenException($e->getMessage(),Requests::RESPONSE_INTERNAL_SERVER_ERROR);
        }

        return (array) $decoded;
    }
}