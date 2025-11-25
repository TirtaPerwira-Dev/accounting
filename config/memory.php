<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Memory Configuration
    |--------------------------------------------------------------------------
    |
    | Configure memory limits for the application
    |
    */

    'memory_limit' => env('MEMORY_LIMIT', '512M'),
    'max_execution_time' => env('MAX_EXECUTION_TIME', 300),
    'max_input_vars' => env('MAX_INPUT_VARS', 3000),
];
