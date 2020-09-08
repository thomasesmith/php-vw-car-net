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

use thomasesmith\VWCarNet\Authentication;


class AuthenticationTest extends TestCase
{
    // Warning: The Authentication object instance isn't a mock.
    // Some of these tests will result in real requests to the Car-Net servers. 

    public function setUp(): void
    {
        if (!KnownValidCarNetEmailAddress || !KnownValidCarNetPassword || !KnownValidCarNetPIN) {
            $this->fail('tests/_config.php must contain valid Car-Net credentials.');
        }
        
        $this->Authentication = new Authentication();
        $this->AuthenticationFromFile = false;

    }


    public function testGetEmailAddressBeforeSet()
    {
        $this->expectException(Exception::class);
        $this->Authentication->getEmailAddress();
    }


    public function testGetAccessTokenBeforeSet()
    {
        $this->expectException(Exception::class);
        $this->Authentication->getAccessToken();
    }


    public function testGetIdTokenBeforeSet()
    {
        $this->expectException(Exception::class);
        $this->Authentication->getIdToken();
    }


    public function testEmptyStringAuthenticateAttempt()
    {
        $this->expectException(Exception::class);

        $this->Authentication->authenticate('', '');
    }


    public function testInvalidEmailAddressAuthenticateAttempt()
    {
        $this->expectException(Exception::class);

        $this->Authentication->authenticate('invalid111111@emailaddress.com', 'invalidpassword');
    }


    public function testKnownInvalidPasswordAddressAuthenticateAttempt()
    {
        $this->expectException(Exception::class);

        $this->Authentication->authenticate(KnownValidCarNetEmailAddress, 'invalidpassword');
    }


    public function testKnownValidAuthenticateAttempt()
    {
        try {
            $this->Authentication->authenticate(KnownValidCarNetEmailAddress, KnownValidCarNetPassword);
        } catch (\Exception $e) {
            $this->fail('An unexpected exception was thrown during known valid authentication attempt test: ' . $e->getMessage());
        }

        $authenticationPathStrings = $this->Authentication->getAllAuthenticationTokens();

        $this->assertIsArray($authenticationPathStrings);
        $this->assertIsString($authenticationPathStrings['accessToken']);
        $this->assertIsInt($authenticationPathStrings['accessTokenExpires']);
        $this->assertIsString($authenticationPathStrings['idToken']);
        $this->assertIsInt($authenticationPathStrings['idTokenExpires']);
        $this->assertIsString($authenticationPathStrings['refreshToken']);
        $this->assertIsInt($authenticationPathStrings['refreshTokenExpires']);
        $this->assertIsString($authenticationPathStrings['codeVerifier']);
        $this->assertIsString($authenticationPathStrings['emailAddress']);

        try {
            $accessToken = $this->Authentication->getAccessToken();
        } catch (\Exception $e) {
            $this->fail('An unexpected exception was thrown during test of getAccessToken(): ' . $e->getMessage());
        }
        
        $this->assertIsString($accessToken);

        try {
            $idToken = $this->Authentication->getIdToken();
        } catch (\Exception $e) {
            $this->fail('An unexpected exception was thrown during test of getIdToken(): ' . $e->getMessage());
        }
        
        $this->assertIsString($idToken);

        $this->assertEquals($authenticationPathStrings['accessToken'], $accessToken);
        $this->assertEquals($authenticationPathStrings['idToken'], $idToken);
    }  


    public function testGenerateMockUuid()
    {
        $regexPattern = '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/m';
        
        $this->assertMatchesRegularExpression($regexPattern, $this->Authentication::generateMockUuid());
    }
}