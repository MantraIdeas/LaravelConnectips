<?php

return array(
    'merchantId' => env('CONNECTIPS_MERCHANT_ID'),
    'appId' => env('CONNECTIPS_APP_ID'),
    'password' => env('CONNECTIPS_PASSWORD'),
    'appName' => env('CONNECTIPS_APP_NAME'),
    'successUrl' => env('CONNECTIPS_SUCCESS_URL'),
    'failureUrl' => env('CONNECTIPS_FAILURE_URL'),
    'pemPath' => env('CONNECTIPS_PEM_PATH'),
    'connectIpsUrl' => env('CONNECTIPS_URL', 'https://uat.connectips.com'),
);
