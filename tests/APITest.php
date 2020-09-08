<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;

if (file_exists(__DIR__ . '/_config.php')) {
    require_once __DIR__ . '/_config.php';
} else {
    die('Please enter the Car-Net account details that you\'d like to test with in to the tests/_config.example.php file and rename it to tests/_config.php' . PHP_EOL);
}

require_once __DIR__ . '/../src/Authentication.php';
require_once __DIR__ . '/../src/API.php';

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

        $this->API = new API($this->Authentication, KnownValidCarNetPIN);
    }


    public function testGetVehicleId(): void
    {
        $vehicleId = $this->API->getVehicleId();

        $regexPattern = '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/m';
        
        $this->assertMatchesRegularExpression($regexPattern, $vehicleId);
    }


    // TODO Write more test coverage for API object. 

    public function testHelperMethods(): void 
    {
        $kilometersToTest = 122;
        $milesResult = 76;
        $kilometersToMiles = $this->API::kilometersToMiles($kilometersToTest); 
        $this->assertEquals($kilometersToMiles, $milesResult);

        $fahrenheitToTest = 78;
        $celsiusResult = 26;
        $fahrenheitToCelsius = $this->API::fahrenheitToCelsius($fahrenheitToTest); 
        $this->assertEquals($fahrenheitToCelsius, $celsiusResult);

        $camelCaseStringToTest = "thisIsACamelCaseString";
        $ccConvertResult = $this->API::codeCaseToWords($camelCaseStringToTest); 
        $this->assertEquals($ccConvertResult, "This is a camel case string");

        $snakeCaseStringToTest = "this_is_a_snake_case_string";
        $scConvertResult = $this->API::codeCaseToWords($snakeCaseStringToTest); 
        $this->assertEquals($scConvertResult, "This is a snake case string");
    }

}
