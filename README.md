# An unofficial PHP Wrapper for the VW Car-Net API

[![Latest Stable Version](https://poser.pugx.org/thomasesmith/php-vw-car-net/v)](//packagist.org/packages/thomasesmith/php-vw-car-net) [![Total Downloads](https://poser.pugx.org/thomasesmith/php-vw-car-net/downloads)](//packagist.org/packages/thomasesmith/php-vw-car-net) [![Latest Unstable Version](https://poser.pugx.org/thomasesmith/php-vw-car-net/v/unstable)](//packagist.org/packages/thomasesmith/php-vw-car-net) [![License](https://poser.pugx.org/thomasesmith/php-vw-car-net/license)](//packagist.org/packages/thomasesmith/php-vw-car-net)

This package attempts to follow the advice and guidelines outlined [in this document](https://github.com/thomasesmith/vw-car-net-api) detailing the workings of the VW Car-Net API. This package and its code will try to immediately reflect in its functionality any changes made to that document. 

## Installing 
It is recommended that you install this with [Composer](https://getcomposer.org/).
```bash
composer require thomasesmith/php-vw-car-net
```
## Regional Support
So far, this package has only been officially tested with a ***U.S.A. VW Car-Net account***. It is unknown how this will work with Car-Net accounts outside of the U.S., and it is unknown how it will work with VW WeConnect accounts. If you want to help test this, [please do](https://github.com/thomasesmith/php-vw-car-net/issues).

## Quick Start
All you need now is a VW Car-Net account in good standing...
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
## All Methods Available in the `API` Object

#### `getVehiclesAndEnrollmentStatus()`: `array`
This will return an associative array of information about your Car-Net account, but most importantly will contain a `vehicleEnrollmentStatus` array, where in each of the vehicles associated with your account will have their own array of details, including their `vehicleId` values.

#### `getAllVehicles()`: `array`
This is just a shortcut to the `vehicleEnrollmentStatus` array thats inside of the response above. In case you don't want or need the rest of that response.

#### `setCurrentlySelectedVehicle(string $vehicleId)`: `array`
This method takes a `vehicleId` as its one parameter and sets it as your "current vehicle," that is, the vehicle you will be commanding/querying with the subsequent method calls you make. It will use this vehicle, until of course it is set to a different value.

> If you only have one vehicle associated with your Car-Net account, you don't have to set this at all, because your currently selected vehicle will default to the first one listed in the `getVehiclesAndEnrollmentStatus()` vehicles list.

#### `getVehicleStatus([boolean $refetch])`: `array`
This returns an associative array containing all the current details of the currently selected vehicle and its various statuses, such as door lock status, battery status, window states, cruise range, odometer mileage, etc. 

This method will actually only fetch the vehicle status the first time you call it. After that, it will return back the saved results of that first fetch. If you want the status to be re-fetched, you will have to pass in this methods one parameter, an optional boolean. Passing in a value of `true` there will force it to re-fetch the status. It will wait until that fetch is complete, then return the response just the same as without the parameter.  

> "Re-fetching" the status should not be confused with "re-polling" the status. These are two different functions. Forcing a re-fetch is a "cheaper" thing to execute than a re-poll, and is sometimes all you need. 

#### `requestRepollOfVehicleStatus()`: `void`
Sometimes the information returned by the car can get a little stale, call this method to force the Car-Net API to re-poll the car for an updated status of your currently selected vehicle. ***Subject to request rate limit***

> Calling this method will automatically force the next call to `getVehicleStatus()` to perform a re-fetch of the status. 

> The status takes about 25 seconds to actually update after making this method call. So wait about that long before calling `getVehicleStatus()` to give the vehicle time to comply with your command. 

#### `getVehicleId()`: `string`
This returns a string containing the vehicle id value of the currently selected vehicle.

#### `getBatteryStatus()`: `array`
***EV ONLY*** This is just a shortcut that returns the `powerStatus` array that `getVehicleStatus()` includes as part of its output.

#### `getClimateStatus()`: `array`
This returns an associative array containing all the details of the currently selected vehicle's climate system: the outdoor temperature, whether or not the climate control is running, where or not your defroster is running, and whether or not these conditions were triggered by a departure timer. ***Subject to request rate limit***

#### `setUnpluggedClimateControl(bool $enabled)`: `void`
***EV ONLY*** Passing in a boolean `false` will disable your currently selected vehicle from turning its climate system on when it is not plugged in. A boolean `true` will set it to allow the car to use the climate system when unplugged. This method returns nothing. ***Subject to request rate limit***
> This setting stays persistent in the vehicle, you don't have to set it every time you execute your code.

#### `setClimateControl(bool $enabled [, int $temperatureDegrees])`: `void`
Passing in a boolean `false` as the first parameter will turn off the climate system in the currently selected vehicle. A boolean `true` will turn it on. An additional ***EV ONLY*** feature: use the optional second parameter to set the target temperature you would like the vehicle to try to get to. If no int is passed in, the cars default is used. This method returns nothing.***Subject to request rate limit***

> If the currently selected vehicle is an EV, it must either be plugged in, or you have to make sure `setUnpluggedClimateControl()` is set to `true`. 

#### `setDefroster(bool $enabled)`: `void`
Passing in a boolean `true` will start your currently selected vehicles defroster. A boolean `false` will stop it. This method returns nothing. ***Subject to request rate limit***

> If the currently selected vehicle is an EV, your car must either be plugged in or you have to make sure `setUnpluggedClimateControl()` is set to `true`. 

#### `setCharge(bool $enabled)`: `void`
***EV ONLY*** Passing in a boolean `true` starts your currently selected vehicles battery charger. A boolean `false` will stop it. This method returns nothing. ***Subject to request rate limit***

#### `setLock(bool $enabled)`: `void`
Passing in a boolean `true` will lock the doors of your currently selected vehicle. A boolean `false` will unlock them. This method returns nothing. ***Subject to request rate limit***

#### `getVehicleHealthReport([boolean $refetch])`: `array`
This returns an associative array containing your currently selected vehicles health report. ***Subject to request rate limit***

This method will actually only fetch the vehicles health report the first time you call it. After that, it will return back the saved results of that first fetch. If you want the status to be actually re-fetched, you will have to pass in this methods one parameter, an optional boolean. Passing in a value of `true` there will force it to re-fetch the health report. It will wait until that fetch is complete, then return the response just the same as without the parameter.  

> "Re-fetching" the health report should not be confused with "re-polling" the health report. These are two different functions. 

#### `requestRepollOfVehicleHealthReport()`: `void`
Like the vehicle status can, the health report can get a little stale, so call this method to force the Car-Net API to re-poll the car for an updated health report of the currently selected vehicle. This method returns nothing. ***Subject to request rate limit***

> Calling this method will automatically force the next call of `getVehicleHealthReport()` to perform a re-fetch of the status. 

> The health report takes about 25 seconds to actually update after making this method call. So don't call `getVehicleHealthReport()` immediately after calling this method, without first waiting a bit. 

***

## What is the "request rate limit?"

Many of the `API` object methods make requests on which the Car-Net API imposes a strict rate limit. These methods are denoted in this documentation with the phrase "subject to request rate limit." These methods will begin throwing exceptions with a message like, "429 Too Many Requests" if your account makes too many requests over a certain length of time.

How many requests are allowed, over how much time, before your requests begin to be 429'd is unknown. How long you must wait before you can expect your requests to be fulfilled again is also unknown. 

Be careful with these for now. 

***

## It's Important to Store Your Tokens! 

The Authentication object will do all the work to getting and holding on to the access tokens it will need to use the API–and will refresh them for you automatically if they expire–but it will only hold on to them for the duration of your scripts execution unless you store them somewhere persistently between those executions. Not doing so will force your app to re-authenticate each time it runs, and that will make your app very slow. 

The Authentication class has three methods that can help with this: `setSaveCallback()` `getAllAuthenticationTokens()` and  `setAuthenticationTokens()`

#### Flat File Method
A cheap and easy way to save your access tokens is to just serialize and save to a flat file your Authentication instance. If your application is simple enough and you think that might work for you, that would look something like this: 

```php
use thomasesmith\VWCarNet;

// ...

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
        $Auth = new VWCarNet\Authentication();  

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
$CN = new VWCarNet\API($Auth, "YOUR CAR NET PIN");

// ...
```

#### Or, Save Them However You Wish
If you want to store tokens persistently in some other manner, that might look more like this pseudo-code: 

```php
use thomasesmith\VWCarNet;

// ...

$tokenChangeCallback = function($Auth) {

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
    $Auth = new VWCarNet\Authentication();  
    $Auth->setAuthenticationTokens($loadedTokensArray);
    $Auth->setSaveCallback($tokenChangeCallback); 

} else {

    // If you don't, get new ones and be sure to use setSaveCallback() so store them.
    try {

        $Auth = new VWCarNet\Authentication();  
        $Auth->setSaveCallback($tokenChangeCallback); // Must be called BEFORE authenticate() is
        $Auth->authenticate("YOUR CAR NET EMAIL ADDRESS", "YOUR CAR NET PASSWORD");

    } catch (Exception $e) {
        // Any issue that arises while logging in will throw an exception here.
        print $e->getMessage(); 
    }
}


// Now create an instance of the API object and pass in the Auth instance 
// as the first parameter, and your Car-Net PIN as the second, and you're set
$CN = new VWCarNet\API($Auth, "YOUR CAR NET PIN");

// ...
```

***

## Helper Methods
Included in the `API` object are some static helper methods that might come in handy while using this package.

#### `API::kilometersToMiles(int $kilometers)`: `int` 
The Car-Net API seems to default to using kilometers as distance units, so if you prefer imperial units you'll have to convert them. This method takes an int representing kilometers, and returns an int representing its approximate equivalent miles.
```php
$kilometersCruiseRange = $CN->getVehicleStatus()['powerStatus']['cruiseRange']; // 122
echo VWCarNet\API::kilometersToMiles($kilometersCruiseRange) . " miles"; // echoes: 76 miles
```

#### `API::fahrenheitToCelsius(int $celsius)`: `int` 
The Car-Net API seems to default to using fahrenheit as temperature units, so if you prefer metric units you'll have to convert them. This method takes an int representing fahrenheit degrees, and returns an int representing its approximate equivalent celsius degrees.
```php
$outdoorTempF = $CN->getClimateStatus()['outdoor_temperature']; // 78 
echo  VWCarNet\API::fahrenheitToCelsius($outdoorTempF) . " celsius"; // echoes: 26 celsius;
```

#### `API::codeCaseToWords(string $weirdCaseString)`: `string` 
The API is inconsistent in that it names some of its attribute keys with camel case (i.e. "frontLeft", "sunRoof") and others in snake case (i.e. "outdoor_temperature"). If you want to use those names in your app, but you want to turn them in to plain english, this method takes either a camel case or snake case string and tries its best to return a string containing the plain english words, separated by space characters.
```php
foreach ($CN->getVehicleStatus()['exteriorStatus']['doorLockStatus'] as $position => $status) {
  // Here, $position can equal "frontLeft", "frontRight", "rearLeft", etc.
  echo VWCarNet\API::codeCaseToWords($position) . " door is " . strtolower($status) . "." . PHP_EOL;
}

/*

The above will print:

Front left door is unlocked.
Front right door is locked.
Rear left door is locked.
Rear right door is locked.

*/
```

***
## Disclaimer
No affiliation or sponsorship is to be implied between the developers of this package and Volkswagen AG. Any trademarks, service marks, brand names or logos are the properties of their respective owners and are used on this site for educational purposes only.