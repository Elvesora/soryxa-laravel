<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Soryxa API Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL of the Soryxa API instance you are connecting to.
    | No trailing slash.
    |
    */
    'base_url' => 'https://soryxa.elvesora.com',

    /*
    |--------------------------------------------------------------------------
    | API Token
    |--------------------------------------------------------------------------
    |
    | Your Bearer token for authenticating with the Soryxa API.
    | Generate one from your Soryxa dashboard → API Tokens.
    |
    */
    'token' => env('SORYXA_API_TOKEN', ''),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | Maximum number of seconds to wait for a response from the API.
    |
    */
    'timeout' => env('SORYXA_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Retries
    |--------------------------------------------------------------------------
    |
    | Number of times to retry a request on 5xx server errors.
    | Set to 0 to disable retries.
    |
    */
    'retries' => env('SORYXA_RETRIES', 0),

    /*
    |--------------------------------------------------------------------------
    | Retry Delay (ms)
    |--------------------------------------------------------------------------
    |
    | Milliseconds to wait between retries.
    |
    */
    'retry_delay' => env('SORYXA_RETRY_DELAY', 100),

    /*
    |--------------------------------------------------------------------------
    | Silent on Limit
    |--------------------------------------------------------------------------
    |
    | When enabled, exceeding your usage limit will NOT throw a
    | UsageLimitException. Instead, validate() returns a result with
    | decision "allow" and reasonCode "LIMIT_EXCEEDED", so your
    | application flow is never interrupted by quota errors.
    |
    */
    'silent_on_limit' => env('SORYXA_SILENT_ON_LIMIT', false),

];
