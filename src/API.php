<?php 

declare(strict_types=1);

namespace thomasesmith\VWCarNet;

use GuzzleHttp;
use thomasesmith\VWCarNet\Authentication;


class API {

    const API_HOST = 'https://b-h-s.spr.us00.p.con-veh.net';
    const APP_USER_AGENT_SPOOF = 'Car-Net/60 CFNetwork/1121.2.2 Darwin/19.3.0';

    private $Authentication;

    private $client;

    private $pin;
    private $tspToken;
    private $tspTokenExpires;

    private $userId; 
    private $vwId; 
    private $currentlySelectedVehicle;
    private $vehiclesAndEnrollmentStatus; 
    private $currentlySelectedVehicleStatus;
    private $currentlySelectedVehicleClimateStatus;


    public function __construct(Authentication $Authentication, $pin)
    {
        $this->Authentication = $Authentication;
        $this->pin = $pin;

        // Set up a new Guzzle client with some defaults
        $this->client = new GuzzleHttp\Client(
            [
            'version' => 2, 
            'base_uri' => self::API_HOST,
            'headers' => [
                'content-type' => 'application/json;charset=UTF-8',
                'authorization' => 'Bearer ' . $this->Authentication->getAccessToken(),
                'x-user-id' => $this->userId,
                'user-agent' => self::APP_USER_AGENT_SPOOF,
                'x-user-agent' => self::APP_USER_AGENT_SPOOF,
                'x-app-uuid' => Authentication::generateMockUuid(),
                'accept' => '*/*'
            ]
        ]);
    }


    public function getVehiclesAndEnrollmentStatus(): array
    {
        if ($this->vehiclesAndEnrollmentStatus)
            return $this->vehiclesAndEnrollmentStatus;

        $this->fetchVehiclesAndEnrollmentStatus();
        return $this->vehiclesAndEnrollmentStatus;
    }


    public function getCurrentlySelectedVehicle(): array
    {
        // If there isn't one explicitly set, default to first vehicle in their list.
        if (!$this->currentlySelectedVehicle) 
            $this->currentlySelectedVehicle = $this->getVehiclesAndEnrollmentStatus()['vehicleEnrollmentStatus'][0];

        return $this->currentlySelectedVehicle;
    }


    public function setCurrentlySelectedVehicle(string $vehicleId): void
    {
        $count = 0;

        foreach ($this->getVehiclesAndEnrollmentStatus()['vehicleEnrollmentStatus'] as $vehicle) {
            if ($vehicleId == $vehicle['vehicleId']) {
                // Found it, break out of this loop
                $index = $count;
                break;
            }

            $count++;
        }

        if (isset($index))
            $this->currentlySelectedVehicle = $this->getVehiclesAndEnrollmentStatus()['vehicleEnrollmentStatus'][$index];

        throw new \Exception('That vehicle id was not found in your vehicles list.');
    }


    public function getVehicleStatus($forceRefresh = false): array
    {
        if ($this->currentlySelectedVehicleStatus && !$forceRefresh)
            return $this->currentlySelectedVehicleStatus;

        $this->fetchVehicleStatus(); 
        return $this->currentlySelectedVehicleStatus;
    }


    public function getAllVehicles(): array
    {
        return $this->getVehiclesAndEnrollmentStatus()['vehicleEnrollmentStatus'];
    }


    public function getUserId(): string
    {
        if ($this->userId)
            return $this->userId;

        $this->fetchVehiclesAndEnrollmentStatus();
        return $this->userId;
    }


    public function getVehicleId(): string
    {
        return $this->getCurrentlySelectedVehicle()['vehicleId'];
    }


    public function getAccountNumber(): string
    {
        return $this->getCurrentlySelectedVehicle()['rolesAndRights']['tspAccountNum'];
    }


    public function getBatteryStatus(): array
    {
        if ($this->getVehicleStatus()['powerStatus']['battery'])
            return $this->getVehicleStatus()['powerStatus']['battery'];

        throw new \Exception('No battery status information in the vehicle status.');
    }


    public function getClimateStatus(): array
    {
        if (!$this->currentlySelectedVehicleClimateStatus)
            $this->fetchCurrentlySelectedVehicleClimateStatus();

        return $this->currentlySelectedVehicleClimateStatus;
    }


    public function getPowerStatus(): array
    {
        if ($this->getVehicleStatus()['powerStatus'])
            return $this->getVehicleStatus()['powerStatus'];

        throw new \Exception('No power status information in the vehicle status.');
    }


    public function requestRepollOfVehicleStatus(): void
    {
        $url = '/mps/v1/vehicles/' . $this->getAccountNumber() . '/status/fresh';

        $res = $this->client->request('PUT', $url, 
            [
                'json' => [
                    'tsp_token' => $this->getTspToken(),
                    'email' => $this->Authentication->getEmailAddress(),
                    'vw_id' => $this->vwId
                ]
            ]
        );
    }


    public function setClimateControl(bool $active, int $targetTemperature = 75): void
    {
        $url = '/mps/v1/vehicles/' . $this->getAccountNumber() . '/status/climate';

        $res = $this->client->request('PUT', $url, 
            [
                'json' => [
                    'active' => $active,
                    'target_temperature' => $targetTemperature,
                    'tsp_token' => $this->getTspToken(),
                    'email' => $this->Authentication->getEmailAddress(),
                    'vw_id' => $this->vwId
                ]
            ]
        );
    }


    public function setDefroster(bool $on): void
    {
        $url = '/mps/v1/vehicles/' . $this->getAccountNumber() . '/status/defrost';

        $res = $this->client->request('PUT', $url, 
            [
                'json' => [
                'active' => $on,
                'tsp_token' => $this->getTspToken(),
                'email' => $this->Authentication->getEmailAddress(),
                'vw_id' => $this->vwId
            ]
        ]);
    }


    public function setCharge(bool $charge): void
    {
        $url = '/mps/v1/vehicles/' . $this->getAccountNumber() . '/status/charging';

        $res = $this->client->request('PATCH', $url, 
            [
                'json' => [
                    'active' => $charge,
                    'tsp_token' => $this->getTspToken(),
                    'email' => $this->Authentication->getEmailAddress(),
                    'vw_id' => $this->vwId
                ]
            ]
        );
    }


    public function setLock(bool $lock): void
    {
        $url = '/mps/v1/vehicles/' . $this->getAccountNumber() . '/status/exterior/doors';

        $res = $this->client->request('PUT', $url, 
            [
                'json' => [
                    'lock' => $lock,
                    'tsp_token' => $this->getTspToken(),
                    'email' => $this->Authentication->getEmailAddress(),
                    'vw_id' => $this->vwId
                ]
            ]
        );
    }


    public function setUnpluggedClimateControl(bool $enabled): void
    {
        $url = '/mps/v1/vehicles/' . $this->getAccountNumber() . '/settings/unplugged_climate_control';

        $res = $this->client->request('PUT', $url, 
            [
                'json' => [
                    'enabled' => $enabled,
                    'tsp_token' => $this->getTspToken(),
                    'email' => $this->Authentication->getEmailAddress(),
                    'vw_id' => $this->vwId
                ]
            ]
        );
    }



    // Static Public Methods ////////////////////////////////

    static public function kilometersToMiles(string $km): int
    {
        return intval(ceil($km / 1.609));
    }


    static public function fahrenheitToCelsius(string $f): int
    {
        return intval(ceil(($f - 32) * 5/9));
    }


    static public function camelCaseToWords(string $string): string
    {
        $arr = preg_split('/(?=[A-Z])/', $string);
        $spaced = trim(implode(' ', $arr));
        return ucfirst(strtolower($spaced));
    }



    // Private Methods ////////////////////

    private function getTspToken(): string
    {
        // If the tsp token is not yet set, or has expired, refetch it before returning...
        if (!$this->tspToken || time() >= $this->tspTokenExpires)
            $this->fetchTspToken();

        return $this->tspToken;
    }


    private function fetchTspToken(): void
    {
        $url = '/ss/v1/user/' . $this->getUserId() . '/vehicle/' . $this->getCurrentlySelectedVehicle()['vehicleId'] . '/session';

        try {
            $res =	$this->client->request('POST', $url, 
                [
                    'json' => [
                        'accountNumber' => $this->getCurrentlySelectedVehicle()['rolesAndRights']['tspAccountNum'],
                        'idToken' => $this->Authentication->getIdToken(),
                        'tspPin' => $this->pin,
                        'tsp' => $this->getCurrentlySelectedVehicle()['vehicle']['tspProvider']
                    ]
                ]
            );
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            throw new \Exception('Invalid response from tsp token request. This is likely due to an incorrect PIN. ' . $e->getMessage());
        }

        $responseJson = json_decode(strval($res->getBody()), true); 

        if (!$responseJson['data']['tspToken'])
            throw new \Exception('Invalid response from tsp token request.');

        $this->tspToken = $responseJson['data']['tspToken'];

        // The API doesn't give expiration information for this token, 
        // so we'll set an expiry ourselves of 30 minutes from now.
        $this->tspTokenExpires = time() + 3600;
    }


    private function fetchVehiclesAndEnrollmentStatus(): void
    {
        $url = '/account/v1/enrollment/status?idToken=' . $this->Authentication->getIdToken();

        $res = $this->client->request('GET', $url);

        $responseJson = json_decode(strval($res->getBody()), true);

        $this->vehiclesAndEnrollmentStatus = $responseJson['data'];
        $this->userId = $this->vehiclesAndEnrollmentStatus['customer']['userId'];
        $this->vwId = $this->vehiclesAndEnrollmentStatus['customer']['vwId'];
    }


    private function fetchVehicleStatus(): void
    {
        $url = '/rvs/v1/vehicle/' . $this->getVehicleId();

        $res = $this->client->request('GET', $url);

        $responseJson = json_decode(strval($res->getBody()), true);

        // unset any 'UNAVAILABLE' or 'UNSUPPORTED' values from the exterior statuses summary
        foreach ($responseJson['data']['exteriorStatus']['doorStatus'] as $k => $v) {
            if ($v == "NOTAVAILABLE" || $v == "UNSUPPORTED")
                unset($responseJson['data']['exteriorStatus']['doorStatus'][$k]);
        }

        foreach ($responseJson['data']['exteriorStatus']['doorLockStatus'] as $k => $v) {
            if ($v == "NOTAVAILABLE" || $v == "UNSUPPORTED")
                unset($responseJson['data']['exteriorStatus']['doorLockStatus'][$k]);
        }

        foreach ($responseJson['data']['exteriorStatus']['windowStatus'] as $k => $v) {
            if ($v == "NOTAVAILABLE" || $v == "UNSUPPORTED")
                unset($responseJson['data']['exteriorStatus']['windowStatus'][$k]);
        }

        foreach ($responseJson['data']['exteriorStatus']['lightStatus'] as $k => $v) {
            if ($v == "NOTAVAILABLE" || $v == "UNSUPPORTED")
                unset($responseJson['data']['exteriorStatus']['lightStatus'][$k]);
        }

        $this->currentlySelectedVehicleStatus = $responseJson['data'];
    }


    private function fetchCurrentlySelectedVehicleClimateStatus(): void 
    {
        $url = '/mps/v1/vehicles/' . $this->getAccountNumber() . '/status/climate/details';

        $res = $this->client->request('PUT', $url, 
            [
                'json' => [
                    'tsp_token' => $this->getTspToken(),
                    'email' => $this->Authentication->getEmailAddress(),
                    'vw_id' => $this->vwId
                ]
            ]
        );

        $responseJson = json_decode(strval($res->getBody()), true);

        $this->currentlySelectedVehicleClimateStatus = $responseJson['data'];
    }
}