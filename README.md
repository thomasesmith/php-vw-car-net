# A VW Car-Net API Client for PHP

A properly detailed README is coming soon.

For now, check out this example code:

```
require_once __DIR__ . '/vendor/autoload.php';

use thomasesmith\VWCarNet;

/*
 If you want to, use the setSaveCallback() method to set a callback to execute whenever the Authentication instance gets new or refreshed tokens. It's a good place to put the code that will save your token values somewhere persistenly. In this example, I will simply serialize the $Auth object and save it to a flat file, then reload it, but you can do anything you wish.
*/

$authObjectFilename = __DIR__ . '/AuthenticationObjectStore';

$tokenChangeCallback = function($Auth) use ($authObjectFilename) {
    file_put_contents($authObjectFilename, serialize($Auth));
    // Or, you can use $Auth->getAllAuthenticationTokens() and save the values however you prefer 
};


/*
 To set up a connection to the API, first check to see if you have any tokens saved. In this example, I will look for the file thats written during the save callback.
*/
if (file_exists($authObjectFilename)) {

    /*
     Load your existing tokens in whatever manner you want. In this example, I just serialized the Authenticate instance to a flat file, so I will unserialize it here.
    */

    $Auth = unserialize(file_get_contents($authObjectFilename)); 

    /*
     Alternatively, you could create a new Authentication instance, and use its setAuthenticationTokens() method to set the tokens to whatever they were at the end of your last execution, from whatever persistent storage manner you used to store them.
    */

    // Then be sure to make sure this instance's save callback function is set
    $Auth->setSaveCallback($tokenChangeCallback);    

} else {
    // If you don't have any tokens saved anywhere, create a new instance and call authenticate() 
    // using your Car-Net credentials

    try {
        $Auth = new VWCarNet\Authentication();  
        
        /*
         The order here is important. This instance's save callback function must be set before authenticate() is called, otherwise the callback function won't execute and your new tokens will be lost at the end of this execution 
        */

        $Auth->setSaveCallback($tokenChangeCallback);

        // Now authenticate
        $Auth->authenticate("CAR NET EMAIL ADDRESS", "CAR NET PASSWORD");

    } catch (Exception $e) {
        // Any issue logging in will throw an exception here.
        print $e->getMessage(); 
    }
}


// Now you can create an instance of the API object, and pass in the Auth object and your Car-Net PIN 
$CN = new VWCarNet\API($Auth, "CAR NET PIN");


// Now you can use the $CN objects methods to query or command your car
var_dump($CN->getVehicleStatus());

/*

TODO: detail the other available methods:


$CN->getVehiclesAndEnrollmentStatus();

$CN->setCurrentlySelectedVehicle('abcdef0123-456789-0abc-ef0123456789');

$CN->getVehicleStatus()

$CN->getBatteryStatus()

$CN->requestRepollOfVehicleStatus()

$CN->adjustClimateControl(true, 72)

$CN->toggleDefroster(true)

$CN->toggleCharge(true)

$CN->toggleLock(true)

*/
```