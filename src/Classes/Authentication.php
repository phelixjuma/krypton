<?php
/**
 * This is the Authentication handler
 * @author Phelix Juma <jumaphelix@Krypton.co.ke>
 * @copyright (c) 2018, Kuza Lab
 * @package Krypton
 */

namespace Kuza\Krypton\Classes;

class Authentication {

    protected $JWT;

    private $userId;

    public function __construct(JWT $JWT) {
        $this->JWT = $JWT;
    }

    /**
     * Get the id of the authenticated user
     * @return int
     */
    public function getAuthenticatedUser() {
        return $this->userId;
    }

    /**
     * Authenticate the user via JWT
     * @param $token
     * @throws JWTTokenException
     */
    public function JWTAuthentication($token)
    {
        try {

            $decodedToken = $this->JWT->decodeToken($token);

            //check that the uuid is provided in the token
            if (!isset($decodedToken['id']) || empty($decodedToken['id'])) {
                throw new InvalidJWTTokenException("Invalid Token", Requests::RESPONSE_UNAUTHORIZED);
            }

            //we set the user
            $this->userId = $decodedToken['id'];

        } catch (\Exception $e) {
            throw new JWTTokenException($e->getMessage(), Requests::RESPONSE_UNAUTHORIZED);
        }
    }
}