<?php

declare(strict_types=1);

require_once 'vendor/autoload.php';
use PHPUnit\Framework\TestCase;

require_once '_config.php';

require_once 'src/classes/thomasesmith/VWCarNet/Authentication.php';
use thomasesmith\VWCarNet\Authentication;

class AuthenticationTest extends TestCase
{
    // Warning: The Authentication object instance isn't a mock.
    // Some of these tests will result in real requests to the Car-Net servers. 

    public function setUp(): void
    {
        $this->Authentication = new Authentication();
        $this->AuthenticationFromFile = false;

        // $this->Authentication = unserialize(file_get_contents(__DIR__ . '/../src/AuthenticationObjStore'));
        // $this->AuthenticationFromFile = true;
    }


    public function testGetEmailAddressBeforeSet()
    {
        if (!$this->AuthenticationFromFile) {
            $this->expectException(Exception::class);
            $this->Authentication->getEmailAddress();
        }
    }


    public function testGetAccessTokenBeforeSet()
    {
        if (!$this->AuthenticationFromFile) {
            $this->expectException(Exception::class);
            $this->Authentication->getAccessToken();
        }
    }


    public function testGetIdTokenBeforeSet()
    {
        if (!$this->AuthenticationFromFile) {
            $this->expectException(Exception::class);
            $this->Authentication->getIdToken();
        }
    }


    public function testEmptyStringAuthenticateAttempt()
    {
        $this->expectException(Exception::class);

        $this->Authentication->authenticate('', '');
    }


    public function testInvalidEmailAddressAuthenticateAttempt()
    {
        $this->expectException(Exception::class);

        $this->Authentication->authenticate('invalid111111@emailaddress.com', 'd');
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
        $this->assertIsString($authenticationPathStrings['idToken']);
        $this->assertIsString($authenticationPathStrings['refreshToken']);
        $this->assertIsString($authenticationPathStrings['codeChallenge']);
        $this->assertIsString($authenticationPathStrings['codeVerifier']);
        $this->assertIsString($authenticationPathStrings['code']);

        try {
            $acessToken = $this->Authentication->getAccessToken();
        } catch (\Exception $e) {
            $this->fail('An unexpected exception was thrown during getAccessToken() test: ' . $e->getMessage());
        }
        
        $this->assertIsString($acessToken);

        try {
            $idToken = $this->Authentication->getIdToken();
        } catch (\Exception $e) {
            $this->fail('An unexpected exception was thrown during getIdToken() test: ' . $e->getMessage());
        }
        
        $this->assertIsString($idToken);
    }

}