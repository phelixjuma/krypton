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
    const JWT_RSA_PRIVATE_KEY = "ibuild-jwt-rsa-private.key";
    const JWT_RSA_PUBLIC_KEY = "ibuild-jwt-rsa-public.key";

    private $privateKey;
    private $publicKey;

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

        $this->publicKey = $this->getRSAPublicKey();
        $this->privateKey = $this->getRSAPrivateKey();

        $this->issuer = Config::getSiteURL();
        $this->audience = Config::getSiteURL();
        $this->secret = Config::getJWTSecret();
        $this->issuedAt = Dates::getTimestamp();
        $this->notBefore = Dates::getTimestamp();
        $this->expiry = time()+(3600*24*30*12); //expires after 12 months
        
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
     * Get the directory where the RSA Keys reside
     * @return string
     */
    private function getKeysDirectory() {
        return dirname(__DIR__).'/Keys/';
    }

    /**
     * Get the RSA public key
     * @return bool|string
     */
    private function getRSAPublicKey() {
        $publicKeyFile = $this->getKeysDirectory().self::JWT_RSA_PUBLIC_KEY;
        return file_get_contents($publicKeyFile);
    }

    /**
     * Get the RSA private key
     * @return bool|string
     */
    private function getRSAPrivateKey() {
        $privateKey = $this->getKeysDirectory().self::JWT_RSA_PRIVATE_KEY;
        return file_get_contents($privateKey);
    }

    /**
     * Generate JWT token
     * @param $userId
     * @throws CustomException
     */
    public function generateToken($userId) {

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
     * @throws CustomException
     */
    public function decodeToken($token) {
        try {
            $decoded = \Firebase\JWT\JWT::decode($token, $this->publicKey, array('RS256'));
        } catch(\Exception $e) {
            throw new CustomException($e->getMessage(),Requests::RESPONSE_INTERNAL_SERVER_ERROR);
        }

        return (array) $decoded;
    }
}