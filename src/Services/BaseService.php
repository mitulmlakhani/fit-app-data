<?php

namespace Mitulmlakhani\FitAppData\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class BaseService
{
    public function httpReq($type, $url, $headers = [], $body = [])
    {
        $client = new Client();
        $request = new Request($type, $url, $headers, $body);
        
        $res = $client->send($request);

        return $res;
    }
}
