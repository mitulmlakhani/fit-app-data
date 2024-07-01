<?php

namespace Mitulmlakhani\FitAppData\Services;

use Mitulmlakhani\FitAppData\Interfaces\ReadStepDataContract;

class GoogleFit extends BaseService implements ReadStepDataContract
{
    private $authToken;
    private $refreshToken;
    private $tokenExpiry;

    public function __construct($authToken, $refreshToken = null, $tokenExpiry = null)
    {
        $this->authToken = $authToken;
        $this->refreshToken = $refreshToken;
        $this->tokenExpiry = $tokenExpiry;
    }

    public function getStepsCount($startTime, $endTime = null, $bucketTime = 86400): array
    {
        $this->validateAuthToken();

        $steps = [
            'rows' => [],
            'total' => 0,
            'total_calories' => 0
        ];

        $response = parent::httpReq(
            'post',
            'https://www.googleapis.com/fitness/v1/users/me/dataset:aggregate',
            [
                "Authorization" => "Bearer " . $this->authToken,
                "content-type" => 'application/json'
            ],
            json_encode([
                "aggregateBy" => [
                    [
                        "dataTypeName" => "com.google.step_count.delta"
                    ],
                    [
                        "dataTypeName" => "com.google.calories.expended"
                    ]
                ],
                "bucketByTime" => [
                    "durationMillis" => (($bucketTime + 0) * 1000)
                ],
                "startTimeMillis" => (($startTime + 0)  * 1000),
                "endTimeMillis" => (($endTime + 0) * 1000)
            ])
        );

        if ($response->getStatusCode() === 200) {
            $responseBody = json_decode($response->getBody(), true);

            $buckets = $responseBody['bucket'];

            foreach ($buckets as $bucket) {
                $totalSteps = 0;
                $totalCalories = 0;
                $datasets = $bucket['dataset'] ?: [];


                foreach ($datasets as $dataset) {
                    $dataSourceId = $dataset['dataSourceId'] ?? '';
                    $rows = $dataset['point'] ?: [];

                    foreach ($rows as $row) {
                        if ($dataSourceId === "derived:com.google.calories.expended:com.google.android.gms:aggregated") {
                            $totalCalories += array_sum(array_map(function ($val) {
                                return $val['fpVal'];
                            }, $row['value']));
                        }

                        if ($dataSourceId === "derived:com.google.step_count.delta:com.google.android.gms:aggregated") {
                            $totalSteps += array_sum(array_map(function ($val) {
                                return $val['intVal'];
                            }, $row['value']));
                        }
                    }
                }

                $steps['rows'][] = [
                    'from' => $bucket['startTimeMillis'],
                    'to' => $bucket['endTimeMillis'],
                    'steps' => $totalSteps,
                    'calories' => $totalCalories
                ];

                $steps['total'] += $totalSteps;
                $steps['total_calories'] += $totalCalories;
            }
        }

        return $steps;
    }

    public function getAuthData()
    {
        return [
            'authToken' => $this->authToken,
            'refreshToken' => $this->refreshToken,
            'tokenExpiry' => $this->tokenExpiry,
        ];
    }

    public function resetToken()
    {
        $this->authToken = null;
        $this->refreshToken = null;
        $this->tokenExpiry = null;
    }

    public function validateAuthToken()
    {
        if ($this->authToken && $this->tokenExpiry) {
            if ($this->isTokenExpired() == false) {
                return $this->authToken;
            }

            if ($this->refreshToken) {
                $this->refreshToken();
            } else {
                throw new \Exception("GoogleFit API oauth2 token has been expired, Please reconnect.");
            }
        }
    }

    public function isTokenExpired()
    {
        if ($this->tokenExpiry) {
            $currentTime = time();

            if ($currentTime >= $this->tokenExpiry) {
                return true;
            }

            return false;
        }

        return true;
    }

    public function refreshToken()
    {
        $response = parent::httpReq('post', 'https://developers.google.com/oauthplayground/refreshAccessToken', [
            "content-type" => 'application/json'
        ], json_encode([
            "refresh_token" => $this->refreshToken,
            "token_uri" => "https://oauth2.googleapis.com/token"
        ]));

        if ($response->getStatusCode() === 200) {
            $responseBody = json_decode($response->getBody(), true);
            $responseStatusCode = ($responseBody['Response']['Status-Code'] ?? 0);

            if ($responseStatusCode == 200) {
                $this->authToken = ($responseBody['access_token'] ?? null);
                $this->refreshToken = ($responseBody['refresh_token'] ?? null);
                $this->tokenExpiry = ($responseBody['expires_in'] ?? null);

                return;
            }
        }

        throw new \Exception("Failed to refresh GoogleFit API oauth2 token. Please reconnect");
    }
}
