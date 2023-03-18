<?php

namespace Mitulmlakhani\FitAppData;

use Mitulmlakhani\FitAppData\Services\GoogleFit;

class Factory
{
    public static function client($serviceName, $config = [
        'authToken' => null, 'refreshToken' => null, 'tokenExpiry' => null
    ])
    {
        switch ($serviceName) {
            case 'googleFit':
                return new GoogleFit($config['authToken'], $config['refreshToken'], $config['tokenExpiry']);

            default:
                throw new \Exception("Invalid Service Name");
                break;
        }
    }
}
