<?php

declare(strict_types=1);

require_once 'vendor/autoload.php';
use PHPUnit\Framework\TestCase;

require_once '_config.php';

require_once 'src/classes/thomasesmith/VWCarNet/Authentication.php';
require_once 'src/classes/thomasesmith/VWCarNet/API.php';

use thomasesmith\VWCarNet\Authentication;
use thomasesmith\VWCarNet\API;


class APITest extends TestCase
{
    // Warning: The API and Authentication object instances aren't mocks.
    // In order to test effectively, some ofthese tests will result in 
    // real requests to the VW / Car-Net hosts.

    public function setUp(): void
    {
        if (!KnownValidCarNetEmailAddress || !KnownValidCarNetPassword || !KnownValidCarNetPIN) {
            $this->fail('_config.php must contain valid Car-Net credentials.');
        }

        $this->Authentication = new Authentication();
        $this->Authentication->authenticate(KnownValidCarNetEmailAddress, KnownValidCarNetPassword);
        $this->AuthenticationFromFile = false;

        // $this->Authentication = unserialize(file_get_contents(__DIR__ . '/../src/AuthenticationObjStore'));
        // $this->AuthenticationFromFile = true;

        $this->API = new API($this->Authentication, KnownValidCarNetPIN);
    }


    public function testGetUserId(): void
    {
        $this->assertIsString($this->API->getUserId());
    }


    public function testGetVehicleId(): void
    {
        $this->assertIsString($this->API->getVehicleId());
    }


    public function testGetAccountNumber(): void
    {
        $this->assertIsString($this->API->getAccountNumber());
    }

    // TODO: test for...

    // getCurrentlySelectedVehicle()
    // setCurrentlySelectedVehicle()
}
