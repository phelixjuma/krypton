<?php

namespace Kuza\Krypton\Classes;

use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use MaxMind\Db\Reader\InvalidDatabaseException;

/**
 * Location Class
 *
 * This class gets the location of the user based on their IP address.
 *
 * @author Phelix Juma
 *
 * @package Kuza\UserDataCapture
 */
class Location {

    /**
     * @var string $ip_address the ip address of the user
     */
    public $ip_address;

    /**
     * @var string $continent_code  the continent code
     */
    public $continent_code;

    /**
     * @var string$continent_name the name of the continent
     */
    public $continent_name;

    /**
     * @var string $country_code the code of the country
     */
    public $country_code;

    /**
     * @var string $country_name the name of the country
     */
    public $country_name;

    /**
     * @var string the country subdivision code
     */
    public $country_subdivision_code;

    /**
     * @var string the country subdivision name
     */
    public $country_subdivision_name;

    /**
     * @var string $city_name the name of the city
     */
    public $city_name;

    /**
     * @var string $postal_code the postal code
     */
    public $postal_code;

    /**
     * @var string $latitude the latitude of the user
     */
    public $latitude;

    /**
     * @var string $longitude the longitude of the user
     */
    public $longitude;

    /**
     * @var string $timezone the timezone of the user
     */
    public $timezone;

    /**
     * @var string $accuracy_radius the radius accuracy of the user's location
     */
    public $accuracy_radius;

    /**
     * @var string $autonomous_system_number the autonomous system number
     */
    public $autonomous_system_number;

    /**
     * @var string $autonomous_system_organization the autonomous system organization
     */
    public $autonomous_system_organization;

    /**
     * @var string $connection_type the connection type
     */
    public $connection_type;

    /**
     * @var string $domain the domain name associated with the IP
     */
    public $domain;

    /**
     * @var bool $is_anonymous whether this is an anon access or not
     */
    public $is_anonymous;

    /**
     * @var bool$is_anonymous_proxy whether this is an anon proxy access or not
     */
    public $is_anonymous_proxy;

    /**
     * @var  bool $is_anonymous_vpn whether this is an anon vpn or not
     */
    public $is_anonymous_vpn;

    /**
     * @var bool $is_hosting_provider whether this is a hosting provider or not
     */
    public $is_hosting_provider;

    /**
     * @var bool $is_legitimate_proxy whether this is a legitimate proxy or not
     */
    public $is_legitimate_proxy;

    /**
     * @var string $isp the internet service provider
     */
    public $isp;

    /**
     * @var bool $is_public_proxy whether this is a public proxy
     */
    public $is_public_proxy;

    /**
     * @var bool $is_satellite_provider whether this is a satellite provider
     */
    public $is_satellite_provider;

    /**
     * @var bool $is_tor_exit_node whether the user is using TOR
     */
    public $is_tor_exit_node;

    /**
     * @var string $organization the name of the organization
     */
    public $organization;

    /**
     * @var string $user_type the user type
     */
    public $user_type;


    /**
     * @var Reader The maxmind db reader
     */
    protected $reader;

    /**
     * Location constructor.
     * @param $database_path
     * @param string $ipAddress
     * @throws InvalidDatabaseException
     * @throws \Exception
     */
    public function __construct($database_path, $ipAddress = '') {
        $this->reader = new Reader($database_path . DIRECTORY_SEPARATOR . 'GeoLite2-City.mmdb');
        $this->setIpAddress($ipAddress);
    }

    /**
     * Set the user ip address
     * @param string $ipAddress
     * @throws \Exception
     */
    public function setIpAddress($ipAddress = '') {

        $ipAddress = empty($ipAddress)  ? $this->getIpAddress() : $ipAddress;
        $this->ip_address = $ipAddress;

        if (!empty($this->ip_address)) {
            $this->setLocation();
        }
    }

    /**
     * Get IP Address
     */
    private function getIpAddress() {

        if (isset($_SERVER["HTTP_CF_CONNECTING_IP"]) && filter_var(trim($_SERVER["HTTP_CF_CONNECTING_IP"]), FILTER_VALIDATE_IP,
                FILTER_FLAG_IPV4 |
                FILTER_FLAG_NO_PRIV_RANGE |
                FILTER_FLAG_NO_RES_RANGE)) {
            return trim($_SERVER["HTTP_CF_CONNECTING_IP"]);
        }
        if (isset($_SERVER["HTTP_X_FORWARDED_FOR"]) && filter_var(trim($_SERVER["HTTP_X_FORWARDED_FOR"]), FILTER_VALIDATE_IP,
                FILTER_FLAG_IPV4 |
                FILTER_FLAG_NO_PRIV_RANGE |
                FILTER_FLAG_NO_RES_RANGE)) {
            return trim($_SERVER["HTTP_X_FORWARDED_FOR"]);
        }
        if (isset($_SERVER["HTTP_X_FORWARDED"]) && filter_var(trim($_SERVER["HTTP_X_FORWARDED"]), FILTER_VALIDATE_IP,
                FILTER_FLAG_IPV4 |
                FILTER_FLAG_NO_PRIV_RANGE |
                FILTER_FLAG_NO_RES_RANGE)) {
            return trim($_SERVER["HTTP_X_FORWARDED"]);
        }
        if (isset($_SERVER["HTTP_FORWARDED_FOR"]) && filter_var(trim($_SERVER["HTTP_FORWARDED_FOR"]), FILTER_VALIDATE_IP,
                FILTER_FLAG_IPV4 |
                FILTER_FLAG_NO_PRIV_RANGE |
                FILTER_FLAG_NO_RES_RANGE)) {
            return trim($_SERVER["HTTP_FORWARDED_FOR"]);
        }
        if (isset($_SERVER["REMOTE_ADDR"]) && filter_var(trim($_SERVER["REMOTE_ADDR"]), FILTER_VALIDATE_IP,
                FILTER_FLAG_IPV4 |
                FILTER_FLAG_NO_PRIV_RANGE |
                FILTER_FLAG_NO_RES_RANGE)) {
            return trim($_SERVER["REMOTE_ADDR"]);
        }
        if (isset($_SERVER["HTTP_CLIENT_IP"]) && filter_var(trim($_SERVER["HTTP_CLIENT_IP"]), FILTER_VALIDATE_IP,
                FILTER_FLAG_IPV4 |
                FILTER_FLAG_NO_PRIV_RANGE |
                FILTER_FLAG_NO_RES_RANGE)) {
            return trim($_SERVER["HTTP_CLIENT_IP"]);
        }
        if (isset($_SERVER["HTTP_X_CLUSTER_CLIENT_IP"]) && filter_var(trim($_SERVER["HTTP_X_CLUSTER_CLIENT_IP"]), FILTER_VALIDATE_IP,
                FILTER_FLAG_IPV4 |
                FILTER_FLAG_NO_PRIV_RANGE |
                FILTER_FLAG_NO_RES_RANGE)) {
            return trim($_SERVER["HTTP_X_CLUSTER_CLIENT_IP"]);
        }
        return null;
    }

    /**
     * @throws InvalidDatabaseException
     * @throws \Exception
     */
    public function setLocation() {

        try {

            $record = $this->reader->city($this->ip_address);

            $this->continent_name = $record->continent->name;
            $this->continent_code = $record->continent->code;

            $this->country_code = $record->country->isoCode;
            $this->country_name = $record->country->name;

            $this->country_subdivision_code = $record->mostSpecificSubdivision->isoCode;
            $this->country_subdivision_name = $record->mostSpecificSubdivision->name;

            $this->city_name = $record->city->name;

            $this->postal_code = $record->postal->code;

            $this->latitude = $record->location->latitude;
            $this->longitude = $record->location->longitude;
            $this->timezone = $record->location->timeZone;
            $this->accuracy_radius = $record->location->accuracyRadius;

            $this->autonomous_system_number = $record->traits->autonomousSystemNumber;
            $this->autonomous_system_organization = $record->traits->autonomousSystemOrganization;
            $this->connection_type = $record->traits->connectionType;
            $this->domain = $record->traits->domain;
            $this->is_anonymous = $record->traits->isAnonymous;
            $this->is_anonymous_proxy = $record->traits->isAnonymousProxy;
            $this->is_anonymous_vpn = $record->traits->isAnonymousVpn;
            $this->is_hosting_provider = $record->traits->isHostingProvider;
            $this->is_legitimate_proxy = $record->traits->isLegitimateProxy;
            $this->isp = $record->traits->isp;
            $this->is_public_proxy = $record->traits->isPublicProxy;
            $this->is_satellite_provider = $record->traits->isSatelliteProvider;
            $this->is_tor_exit_node = $record->traits->isTorExitNode;
            $this->organization = $record->traits->organization;
            $this->user_type = $record->traits->userType;

        } catch (AddressNotFoundException $e) {
            throw new \Exception('The address was not found');
        }
    }

    /**
     * Get all the data as an array
     *
     * @return mixed
     */
    public function toArray() {
        return  json_decode(json_encode($this), true);
    }
}
