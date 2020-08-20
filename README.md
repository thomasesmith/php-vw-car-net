# A VW Car-Net API Client for PHP

## Installing 

The recommended way to install is through [Composer](https://getcomposer.org/).

```bash
composer require thomasesmith/php-vw-car-net
```

A properly detailed README is coming soon.

For now, check out this example code:

```php
/*
If you want to, use the setSaveCallback() method to set a function that 
will execute whenever the Authentication instance gets new or refreshed 
tokens. It's a good place to put the code that will save your token values 
somewhere persistenly. 

In this example, I will simply serialize the $Auth object and save it to 
a flat file, then reload it to use again, but you can do anything you wish.
*/

$authObjectFilename = __DIR__ . '/AuthenticationObjectStore';

$tokenChangeCallback = function($Auth) use ($authObjectFilename) {
    /*
    Here, you can use the return of $Auth->getAllAuthenticationTokens() to
    save the values however you prefer, but for this example I will simplu 
    serialize the $Auth instance and save it to a flat file to be loaded 
    next time.
    */

    file_put_contents($authObjectFilename, serialize($Auth));
};


/*
To actually connect to the API, first we need to create an instance of the 
Authentication object. If you use a callback function like the one above to
save your tokens, this would be the best place to check to see if you have
any tokens stored. 

Forcing your app to re-authenticate each time it runs is slow, so try to 
avoid that whenever possible by persistenly storing your token values in 
some way.

For this example, I will just see if my flat file exists where I expect it.
*/
if (file_exists($authObjectFilename)) {

    /*
    Here you would load your existing tokens from wherever you kept them. I
    For this example, since I just serialized the Authenticate instance and
    saved it to a flat file, I will load its contents and unserialize them here.
    */

    $Auth = unserialize(file_get_contents($authObjectFilename)); 

    /*
    Alternatively, you could instead create a new Authentication instance, and 
    use the setAuthenticationTokens() method to set the tokens values from whatever 
    persistent storage manner you used to store them last.
    */

    // Then, be sure to make sure this instance's save callback function is set.
    $Auth->setSaveCallback($tokenChangeCallback);    

} else {
    /*
    If you don't have any tokens saved anywhere, create a new instance and then
    call authenticate(), passing in your Car-Net credentials.
    */

    try {
        $Auth = new \thomasesmith\VWCarNet\Authentication();  
        
        /*
        The order here is important! This instance's save callback function 
        must be set before authenticate() is called, otherwise when it comes
        time to save the tokens, there will be no action to perform to do so
        and your new tokens will be lost at the end of this execution. 
        */

        $Auth->setSaveCallback($tokenChangeCallback);

        // Now perform the actual authentication process.
        $Auth->authenticate("CAR NET EMAIL ADDRESS", "CAR NET PASSWORD");

    } catch (Exception $e) {
        // Any issue that arises while logging in will throw an exception here.
        print $e->getMessage(); 
    }
}


// Now that we have an instance of Authentication, we can create an instance of
// the API object. Pass in the Auth instance and your Car-Net PIN.
$CN = new \thomasesmith\VWCarNet\API($Auth, "CAR NET PIN");

// Now you can use the API methods to query and command your car!

/*

TODO: Detail the other available methods:

$CN->getVehiclesAndEnrollmentStatus();

$CN->setCurrentlySelectedVehicle('abcdef0123-456789-0abc-ef0123456789');

$CN->getVehicleStatus();

$CN->getBatteryStatus() // Just a shortcut to getVehicleStatus()['powerStatus'] if it exists

$CN->requestRepollOfVehicleStatus()

$CN->adjustClimateControl(bool on/off, int degrees)

$CN->toggleDefroster(bool on/off)

$CN->toggleCharge(bool on/off)

$CN->toggleLock(bool lock/unlock)

*/
```