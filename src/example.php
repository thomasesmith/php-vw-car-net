<?php 

require_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/classes/thomasesmith/VWCarNet/Authentication.php';
require_once __DIR__ . '/classes/thomasesmith/VWCarNet/API.php';

use thomasesmith\VWCarNet;

$Auth = new VWCarNet\Authentication();	

$authFilename = __DIR__ . '/AuthenticationObjectStore';

// Check to see if you have any tokens saved (in whatever manner you saved them)... 
if (file_exists($authFilename)) {

    // Load your existing tokens in whatever manner you want. In this example, I just serialized
    // the $Auth object to a flat file, then I re-load it here...
    $Auth = unserialize(file_get_contents($authFilename));

    // or you can use $Auth->setAuthenticationTokens() and pass in an array of values that you have saved.

} else {
    // If you don't have any tokens saved, perform an authenticate() with your Car-Net credentials

    // It's optional, but if you want to use the setSaveCallback() method to set a callback 
    // to execute whenever the $Auth object gets new tokens or refreshed tokens, do so here before the 
    // authenticate() method. It's a good place to put the code that will save your token values 
    // somewhere persistenly. In this example, I just serialize the $Auth object and save it to a flat
    // file.

    $Auth->setSaveCallback(
        function($Auth) use ($authFilename) {
            file_put_contents($authFilename, serialize($Auth));
            // or you can use $Auth->getAllAuthenticationTokens() and save the values however you prefer. 
        }
    );    

    // Now, actually do the authentication...
    try {
        $Auth->authenticate("CAR NET EMAIL ADDRESS", "CAR NET PASSWORD");
    } catch (Exception $e) {
        // Any issue logging in will throw an exception here.
        print $e->getMessage(); 
    }
}


// Now you can create an instance of the API object, and pass in the Auth object and your Car-Net PIN 
$API = new VWCarNet\API($Auth, "CAR NET PIN");


// Now you can use the $API objects methods to query or command your car
var_dump($API->getVehicleStatus());

/*

TODO: detail the other available methods here.


$API->getVehiclesAndEnrollmentStatus();

$API->setCurrentlySelectedVehicle('abcdef0123-456789-0abc-ef0123456789');

$API->getVehicleStatus()

$API->getBatteryStatus()

$API->requestRepollOfVehicleStatus()

$API->adjustClimateControl(true, 72)

$API->toggleDefroster(true)

$API->toggleCharge(true)

$API->toggleLock(true)

*/
