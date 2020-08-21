# An unofficial PHP Wrapper for the VW Car-Net API

## Installing 
It is recommended that you install this with [Composer](https://getcomposer.org/).
```bash
composer require thomasesmith/php-vw-car-net
```
## Quick Start

```php
use thomasesmith\VWCarNet;

$Auth = new VWCarNet\Authentication();  
$Auth->authenticate("YOUR CAR NET EMAIL ADDRESS", "YOUR CAR NET PASSWORD");
$CN = new VWCarNet\API($Auth, "YOUR CAR NET PIN");

var_dump($CN->getVehicleStatus());
```
#### ...will return...

```php
array(10) {
  ["currentMileage"]=>
  int(55788)
  ["timestamp"]=>
  int(1597167320003)
  ["exteriorStatus"]=>
  array(5) {
    ["secure"]=>
    string(6) "SECURE"
    ["doorStatus"]=>
    array(8) {
      ["frontLeft"]=>
      string(6) "CLOSED"
      ["frontRight"]=>
      string(6) "CLOSED"
      ["rearLeft"]=>
      string(6) "CLOSED"
      ["rearRight"]=>
      string(6) "CLOSED"
      ["trunk"]=>
      string(6) "CLOSED"
      ["hood"]=>
      string(6) "CLOSED"
    }
    ["doorLockStatus"]=>
    array(4) {
      ["frontLeft"]=>
      string(6) "LOCKED"
      ["frontRight"]=>
      string(6) "LOCKED"
      ["rearLeft"]=>
      string(6) "LOCKED"
      ["rearRight"]=>
      string(6) "LOCKED"
    }
    ["windowStatus"]=>
    array(7) {
      ["frontLeft"]=>
      string(6) "CLOSED"
      ["frontRight"]=>
      string(6) "CLOSED"
      ["rearLeft"]=>
      string(6) "CLOSED"
      ["rearRight"]=>
      string(6) "CLOSED"
    }
    ["lightStatus"]=>
    array(5) {
      ["left"]=>
      string(3) "OFF"
      ["right"]=>
      string(3) "OFF"
    }
  }
  ["powerStatus"]=>
  array(5) {
    ["cruiseRange"]=>
    int(131)
    ["fuelPercentRemaining"]=>
    int(0)
    ["cruiseRangeUnits"]=>
    string(2) "KM"
    ["cruiseRangeFirst"]=>
    int(131)
    ["battery"]=>
    array(4) {
      ["chargePercentRemaining"]=>
      int(100)
      ["minutesUntilFullCharge"]=>
      int(15)
      ["chargePlug"]=>
      string(9) "PLUGGEDIN"
      ["triggeredByTimer"]=>
      string(5) "false"
    }
  }
  ["location"]=>
  array(2) {
    ["latitude"]=>
    float(35.123456)
    ["longitude"]=>
    float(-120.123456)
  }
  ["lastParkedLocation"]=>
  array(2) {
    ["latitude"]=>
    float(35.123456)
    ["longitude"]=>
    float(-120.123456)
  } 
  ["lockStatus"]=>
  string(6) "LOCKED"
}
```
## Methods Available in the `API` Object

#### `getVehiclesAndEnrollmentStatus()`: `array`
This will return an associative array of information about your Car-Net account, but most importantly will contain a `vehicleEnrollmentStatus` array, containing an array about each of the vehicles associated with your account including their `vehicleId` values.

#### `getAllVehicles()`: `array`
This is just a shortcut to the `vehicleEnrollmentStatus` values of the response above. In case you don't want or need the rest of that response.

#### `setCurrentlySelectedVehicle(string $vehicleId)`: `array`
This method takes a `vehicleId` as its one parameter and sets it as your "current vehicle," that is, the vehicle you will be commanding/querying with the subsequent method calls you make. It will use this vehicle, until of course it is set to a different value.
>If you only have one vehicle associated with your Car-Net account, you don't have to set this at all, because your currently selected vehicle will default to the first one listed in the `getVehiclesAndEnrollmentStatus()` vehicles list.

#### `getVehicleId()`: `string`
This returns a string containing the vehicle id of the currently selected vehicle.

#### `getVehicleStatus()`: `array`
This returns an associative array containing all the current details of the currently selected vehicle and its various statuses, such as door lock status, battery status, window states, cruise range, odometer mileage, etc.
> If the time in `timestamp` is getting old, try running `requestRepollOfVehicleStatus()` first.

#### `requestRepollOfVehicleStatus()`: `void`
While we're talking about vehicle status, sometimes the information returned by the car can get a little stale, so call this method to force the Car-Net API to re-poll the car for an updated status of your currently selected vehicle. 
> The status takes about 25 seconds to actually update after making this method call. So don't call `getVehicleStatus()` immediately after calling this method, without first waiting a bit. 

#### `getBatteryStatus()`: `array`
***EV ONLY*** This is just a shortcut that returns the `powerStatus` array that `getVehicleStatus()` includes as part of its output.

#### `setUnpluggedClimateControl(bool $enabled)`: `void`
***EV ONLY*** Passing in a boolean `false` will disable your currently selected vehicle from turning its climate system on when it is not plugged in. A boolean `true` will set it to allow the car to use the climate system when unplugged. This method returns nothing. 
> This setting stays persistent in the vehicle, you don't have to set it every time you execute your code.

#### `setClimateControl(bool $enabled [, int $temperatureDegrees])`: `void`
Passing in a boolean `false` as the first parameter will turn off the climate system in the currently selected vehicle. A boolean `true` will turn it one. An additional ***EV ONLY*** feature: use the optional second parameter to set the target temperature you would like the vehicle to get to. If no int is passed in, the cars default is used. This method returns nothing.
>If the currently selected vehicle is an EV, it must either be plugged in or you have to make sure `setUnpluggedClimateControl()` is set to `true`. 

#### `setDefroster(bool $enabled)`: `void`
Passing in a boolean `true` will start your currently selected vehicles defroster. A boolean `false` will stop it. This method returns nothing.
>If the currently selected vehicle is an EV, your car must either be plugged in or you have to make sure `setUnpluggedClimateControl()` is set to `true`. 

#### `setCharge(bool $enabled)`: `void`
***EV ONLY*** Passing in a boolean `true` starts your currently selected vehicles battery charger. A boolean `false` will stop it. This method returns nothing.

#### `setLock(bool $enabled)`: `void`
Passing in a boolean `true` will lock the doors of your currently selected vehicle. A boolean `false` will unlock them. This method returns nothing.

#### `getVehicleHealthReport()`: `array`
This returns an associative array containing your currently selected vehicles health report. 

#### `requestRepollOfVehicleHealthReport()`: `void`
Like the vehicle status can, the health report can get a little stale, so call this method to force the Car-Net API to re-poll the car for an updated health report of the currently selected vehicle. This method returns nothing. 
> The health report takes about 25 seconds to actually update after making this method call. So don't call `getVehicleHealthReport()` immediately after calling this method, without first waiting a bit. 

***
## It's Important to Store Your Tokens! 

The Authentication object will do all the work to getting and holding on to the access tokens it will need to use the API–and will refresh them for you automatically if they expire–but it will only hold on to them for the duration of your scripts execution unless you store them somewhere persistently between those executions. Not doing so will force your app to re-authenticate each time it runs, and that will make your app very slow. 

The Authentication class has three methods that can help with this: `setSaveCallback()` `getAllAuthenticationTokens()` and  `setAuthenticationTokens()`

#### Flat File Method
A cheap and easy way to save your access tokens is to just serialize and save to a flat file your Authentication instance. If your application is simple enough and you think that might work for you, that would look something like this: 

```php
$authObjectFilename = __DIR__ . '/AuthenticationObjectStore';

$tokenChangeCallback = function($Auth) use ($authObjectFilename) {
    file_put_contents($authObjectFilename, serialize($Auth));
};


if (file_exists($authObjectFilename)) {

    $Auth = unserialize(file_get_contents($authObjectFilename)); 

    // Be sure to just make sure this instance's save callback function is set.
    $Auth->setSaveCallback($tokenChangeCallback);    

} else {
    
    // No flat file found, so create one.
    try {
        $Auth = new \thomasesmith\VWCarNet\Authentication();  

        $Auth->setSaveCallback($tokenChangeCallback);
        /* 
        The order here is important! This instance's setSaveCallback() method
        must be called before authenticate() is, to make sure it has something
        to perform at the very end of the authenticate() method. 
        */

        // Finally, perform the actual authentication process.
        $Auth->authenticate("YOUR CAR NET EMAIL ADDRESS", "YOUR CAR NET PASSWORD");

    } catch (Exception $e) {
        // Any issue that arises while logging in will throw an exception here.
        print $e->getMessage(); 
    }
}


// Now create an instance of the API object and pass in the Auth instance 
// as the first parameter, and your Car-Net PIN as the second, and you're set
$CN = new \thomasesmith\VWCarNet\API($Auth, "YOUR CAR NET PIN");

// ...
```

#### Or, Save Them However You Wish
If you want to store tokens persistently in some other manner, that might look more like this pseudo-code: 

```php
$tokenChangeCallback = function($Auth) use ($authObjectFilename) {

    $tokensArray = $Auth->getAllAuthenticationTokens();

    // ...
    // Here you'd put code to persistently store the $tokensArray
    // in whatever manner you with
    // ... 
};

// ...
// Code to load the tokens array from your persistent store
$loadedTokensArray = [];
// ... 

if ($loadedTokensArray) {

    // If you have 'em, use 'em.
    $Auth = new \thomasesmith\VWCarNet\Authentication();  
    $Auth->setAuthenticationTokens($loadedTokensArray);
    $Auth->setSaveCallback($tokenChangeCallback); 

} else {

    // If you don't, get new ones and be sure to use setSaveCallback() so store them.
    try {

        $Auth = new \thomasesmith\VWCarNet\Authentication();  
        $Auth->setSaveCallback($tokenChangeCallback); // Must be called BEFORE authenticate() is
        $Auth->authenticate("YOUR CAR NET EMAIL ADDRESS", "YOUR CAR NET PASSWORD");

    } catch (Exception $e) {
        // Any issue that arises while logging in will throw an exception here.
        print $e->getMessage(); 
    }
}


// Now create an instance of the API object and pass in the Auth instance 
// as the first parameter, and your Car-Net PIN as the second, and you're set
$CN = new \thomasesmith\VWCarNet\API($Auth, "YOUR CAR NET PIN");

// ...
```
