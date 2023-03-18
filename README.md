
## About Fit App Data

This package is for Read Steps data from diffrent heath APP and smart devices. it currently supports below services.

- [Google Fit](https://www.google.com/fit/)

## Installation

```
composer require mitulmlakhani/fit-app-data
```

## Example
```
use Mitulmlakhani\FitAppData\Factory;

$t = Factory::client('googleFit', [
    'authToken' => 'Google Fit oauth2 Token',
    'refreshToken' => 'Google Fit oauth2 Refresh Token',
    'tokenExpiry' => 'token expiry unix timestamp'
]);


//print_r($t->getStepsCount("From Timestamp", "To Timestamp", "Bucket Time(default is 1 day)"));
print_r($t->getStepsCount(1679123692, 1679123692));
```