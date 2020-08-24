<?php 

declare(strict_types=1);

namespace thomasesmith\VWCarNet;

use GuzzleHttp;
use SimpleXMLElement;


class Authentication {

    const AUTH_HOST = 'https://identity.na.vwgroup.io';
    const API_HOST = 'https://b-h-s.spr.us00.p.con-veh.net';
    const AUTH_USER_AGENT_SPOOF = 'Mozilla/5.0 (iPhone; CPU iPhone OS 13_3_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.5 Mobile/15E148 Safari/604.1';
    const APP_USER_AGENT_SPOOF = 'Car-Net/60 CFNetwork/1121.2.2 Darwin/19.3.0';
    const APP_CLIENT_ID_IOS = '2dae49f6-830b-4180-9af9-59dd0d060916@apps_vw-dilab_com';

    private $emailAddress;
    private $password;

    private $client;
    private $clientCookieJar;

    private $accessToken;
    private $accessTokenExpires;
    private $idToken;
    private $idTokenExpires;
    private $refreshToken;
    private $refreshTokenExpires;

    private $state; 
    private $codeChallenge; 
    private $codeVerifier; 
    private $csrf; 
    private $relayState; 
    private $hmac; 
    private $nextFormAction; 
    private $code; 

    private $saveCallback; 


    public function __construct()
    {
        $this->client = new GuzzleHttp\Client();
        $this->clientCookieJar = new GuzzleHttp\Cookie\CookieJar();
    }


    public function __sleep()
    {
        return [
            'accessToken', 
            'accessTokenExpires', 
            'idToken',
            'idTokenExpires',
            'refreshToken',
            'refreshTokenExpires',
            'codeVerifier',
            'emailAddress'
        ]; 
    }


    public function __wakeup()
    {
        $this->client = new GuzzleHttp\Client();
        $this->clientCookieJar = new GuzzleHttp\Cookie\CookieJar();
    }


    public function authenticate(string $emailAddress, string $password): void
    {
        $this->emailAddress = $emailAddress;
        $this->password = $password;

        if (!$this->emailAddress || !$this->password)
            throw new \Exception("No email or password set");

        // Execute each step, in sequence
        $this->fetchLogInForm();
        $this->submitEmailAddressForm();
        $this->submitPasswordForm();
        $this->fetchInitialAccessTokens();
    }


    public function setAuthenticationTokens(array $set): void
    {
        if (!isset($set['accessToken']) || !isset($set['accessTokenExpires']) ||
            !isset($set['idToken']) || !isset($set['idTokenExpires']) || 
            !isset($set['refreshToken']) || !isset($set['refreshTokenExpires']) || 
            !isset($set['codeVerifier']) || !isset($set['emailAddress']))
                throw new \Exception('setAuthenticationTokens() method requires an associative array that includes keys: accessToken, accessTokenExpires, idToken, idTokenExpires, refreshToken, refreshTokenExpires, codeVerifier, emailAddress');

        $this->accessToken = $set['accessToken'];
        $this->accessTokenExpires = $set['accessTokenExpires'];
        $this->idToken = $set['idToken'];
        $this->idTokenExpires = $set['idTokenExpires'];
        $this->refreshToken = $set['refreshToken'];
        $this->refreshTokenExpires = $set['refreshTokenExpires'];
        $this->codeVerifier = $set['codeVerifier'];
        $this->emailAddress = $set['emailAddress'];
    }


    public function getEmailAddress(): string
    {
        if (!$this->emailAddress)
            throw new \Exception("There is no email address set.");

        return $this->emailAddress;
    }


    public function getAccessToken(): string
    {
        if (!$this->accessToken)
            throw new \Exception("There is no accessToken set yet.");

        if (time() >= $this->accessTokenExpires)
            $this->fetchRefreshedAccessTokens();

        return $this->accessToken;
    }


    public function getIdToken(): string
    {
        if (!$this->idToken)
            throw new \Exception("There is no idToken set yet.");

        if (time() >= $this->idTokenExpires)
            $this->fetchRefreshedAccessTokens();

        return $this->idToken;
    }


    public function getAllAuthenticationTokens(): array
    {	
        return [
            'accessToken' => $this->accessToken, 
            'accessTokenExpires' => $this->accessTokenExpires, 
            'idToken' => $this->idToken,
            'idTokenExpires' => $this->idTokenExpires,
            'refreshToken' => $this->refreshToken,
            'refreshTokenExpires' => $this->refreshTokenExpires,
            'codeVerifier' => $this->codeVerifier,
            'emailAddress' => $this->emailAddress,
        ];
    }


    public function setSaveCallback(callable $function): void
    {
        $this->saveCallback = $function;
    }



    // Static Public Methods 

    static public function generateMockUuid(): string
    {
        // This is derived from https://www.php.net/manual/en/function.uniqid.php#94959

        // This method doesn't create unique values or cryptographically secure values. 
        // It simply creates mocks to satisfy the Car-Net APIs expectations. 

        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }



    // Private Methods ///////////////////

    private function fetchLogInForm(): void
    {
        $this->state = self::generateMockUuid();

        $PKCEPair = $this->generatePKCEPair();

        $this->codeChallenge = $PKCEPair['codeChallenge'];
        $this->codeVerifier = $PKCEPair['codeVerifier'];

        $url = self::API_HOST . '/oidc/v1/authorize?redirect_uri=car-net%3A%2F%2F%2Foauth-callback&scope=openid&prompt=login&code_challenge='.$this->codeChallenge.'&state=' . $this->state . '&response_type=code&client_id=' . self::APP_CLIENT_ID_IOS;

        $res = $this->client->request('GET', $url, ['cookies' => $this->clientCookieJar]);

        // Scrape some values from the log in page for use in subsequent requests
        $xml = new SimpleXMLElement(strval($res->getBody()));

        $csrfQuery = $xml->xpath("//*[@name='_csrf']/@value");
        $relayStateQuery = $xml->xpath("//*[@name='relayState']/@value");
        $hmacQuery = $xml->xpath("//*[@name='hmac']/@value");
        $formActionQuery = $xml->xpath("//*[@name='emailPasswordForm']/@action");

        if (!$csrfQuery || !$relayStateQuery || !$hmacQuery || !$formActionQuery) 
            throw new \Exception('Could not find the required values in HTML of first step of log-in process.');

        $this->csrf = strval($csrfQuery[0][0][0]);
        $this->relayState = strval($relayStateQuery[0][0][0]);
        $this->hmac = strval($hmacQuery[0][0][0]);
        $this->nextFormAction = strval($formActionQuery[0][0][0]);
    }


    private function submitEmailAddressForm(): void
    {
        $url = self::AUTH_HOST . $this->nextFormAction;

        $res =	$this->client->request('POST', $url, 
            [
                'cookies' => $this->clientCookieJar,
                'headers' => [
                    'user-agent' => self::AUTH_USER_AGENT_SPOOF,
                    'content-type' => 'application/x-www-form-urlencoded',
                    'accept-language' => 'en-us',
                    'accept' => '*/*'
                ],
                'form_params' => [
                    '_csrf' => $this->csrf,
                    'relayState' => $this->relayState,
                    'hmac' => $this->hmac,
                    'email' => $this->emailAddress
                ]
            ]
        );

        $returnedHtml = strval($res->getBody());

        // Before we scrape, we have to remove a bit of problematic html 
        // from this this response, because it was breaking the SimpleXMLElement parser...
        $re = '/onclick="(.*)"/m';
        $repairedHtml = preg_replace($re, '', $returnedHtml);

        // Scrape some more values for the next requests...
        $xml = new SimpleXMLElement($repairedHtml);

        $hmacQuery = $xml->xpath("//*[@name='hmac']/@value");
        $formActionQuery = $xml->xpath("//*[@name='credentialsForm']/@action");

        if (!$hmacQuery || !$formActionQuery) 
            throw new \Exception('Could not scrape required values in the response of the second step of the log in process. This is likely due to an incorrect email address.');

        $this->hmac = strval($hmacQuery[0][0][0]);
        $this->nextFormAction = strval($formActionQuery[0][0][0]);
    }


    private function submitPasswordForm(): void
    {
        $url = self::AUTH_HOST . $this->nextFormAction;

        try {
            $res =	$this->client->request('POST', $url, 
                [
                    'cookies' => $this->clientCookieJar,
                    'headers' => [
                        'user-agent' => self::AUTH_USER_AGENT_SPOOF,
                        'content-type' => 'application/x-www-form-urlencoded',
                        'accept-language' => 'en-us',
                        'accept' => '*/*'
                        ],
                    'form_params' => [
                        '_csrf' => $this->csrf,
                        'relayState' => $this->relayState,
                        'hmac' => $this->hmac,
                        'email' => $this->emailAddress,
                        'password' => $this->password
                    ]
                ]
            );
        } catch (\InvalidArgumentException $e) {
            // We are expecting this to throw an exception even when succesful, 
            // because guzzle seems to have no way of gracefully handling redirects that 
            // attempt to redirect to a custom uri scheme (i.e. car-net:/// )

            // Luckily guzzle returns the failed uri in the exception message, so we 
            // can just grab the value we need from that and move on...

            $code = trim(explode("&code=", $e->getMessage())[1]);
        }

        if (!isset($code) || preg_match('/^[a-f0-9]{96}$/', $code) === false) 
            throw new \Exception('No "code" value was returned from the API after the final step of the log in process. This is most likely due to an incorrect email address or password.');

        $this->code = $code;
    }


    private function fetchInitialAccessTokens()
    {
        if (!$this->code || !$this->codeVerifier)
            throw new \Exception("Can not request access tokens without valid 'code' and 'codeVerifier' values.");

        $url = self::API_HOST . '/oidc/v1/token';

        $res =	$this->client->request('POST', $url,
            [
                'headers' => [
                    'user-agent' => self::APP_USER_AGENT_SPOOF,
                    'content-type' => 'application/x-www-form-urlencoded',
                    'accept-language' => 'en-us',
                    'accept' => '*/*',
                    'accept-encoding' => 'gzip, deflate, br'
                ],
                'form_params' => [
                    'grant_type' => 'authorization_code',
                    'code' => $this->code,
                    'client_id' => self::APP_CLIENT_ID_IOS,
                    'redirect_uri' => 'car-net:///oauth-callback',
                    'code_verifier' => $this->codeVerifier
                ]
            ]
        );

        $responseJson = json_decode(strval($res->getBody()), true); 

        if (!$responseJson['access_token'] || !$responseJson['id_token'] || !$responseJson['refresh_token'])
            throw new \Exception('Invalid response from initial token request');

        $this->accessToken = $responseJson['access_token'];
        $this->accessTokenExpires = time() + $responseJson['expires_in'];
        $this->idToken = $responseJson['id_token'];
        $this->idTokenExpires = time() + $responseJson['id_expires_in'];
        $this->refreshToken = $responseJson['refresh_token'];
        $this->refreshTokenExpires = time() + $responseJson['refresh_expires_in'];

        if (is_callable($this->saveCallback))
            call_user_func($this->saveCallback, $this); 
    }


    private function fetchRefreshedAccessTokens(): void
    {
        if (!$this->refreshToken)
            throw new \Exception("Can not refresh access tokens without a valid 'refresh token' value.");

        if (time() >= $this->refreshTokenExpires)
            throw new \Exception("Your saved refresh token has expired. Please re-authenticate.");

        $url = self::API_HOST . '/oidc/v1/token';

        $res =	$this->client->request('POST', $url, 
            [	
                'headers' => [
                    'user-agent' => self::APP_USER_AGENT_SPOOF,
                    'content-type' => 'application/x-www-form-urlencoded',
                    'accept-language' => 'en-us',
                    'accept' => '*/*',
                    'accept-encoding' => 'gzip, deflate, br'
                ],
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $this->refreshToken,
                    'client_id' => self::APP_CLIENT_ID_IOS,
                    'code_verifier' => $this->codeVerifier
                ]
            ]
        );

        $responseJson = json_decode(strval($res->getBody()), true); 

        if (!$responseJson['access_token'] || !$responseJson['id_token'] || !$responseJson['refresh_token'])
            throw new \Exception('Invalid response from refresh token request');

        $this->accessToken = $responseJson['access_token'];
        $this->accessTokenExpires = time() + $responseJson['expires_in'];
        $this->idToken = $responseJson['id_token'];
        $this->idTokenExpires = time() + $responseJson['id_expires_in'];
        $this->refreshToken = $responseJson['refresh_token'];
        $this->refreshTokenExpires = time() + $responseJson['refresh_expires_in'];

        if (is_callable($this->saveCallback))
            call_user_func($this->saveCallback, $this); 
    }


    private function generatePKCEPair(): array
    {
        $bytes = random_bytes(64 / 2);
        $codeVerifier = bin2hex($bytes);

        $hashOfVerifier = hash('sha256', $codeVerifier, true);
        $codeChallenge = strtr(base64_encode($hashOfVerifier), '+/', '-_'); 

        return [
            'codeVerifier' => $codeVerifier, 
            'codeChallenge' => $codeChallenge
        ];
    }
}