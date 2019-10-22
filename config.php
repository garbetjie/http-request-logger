<?php

return [
    'level' => env('REQUEST_LOGGING_LEVEL', 100),

    'requests' => [
        'enabled' => env('REQUEST_LOGGING_ENABLED_REQUESTS', true),
        'message' => [
            'in' => env('REQUEST_LOGGING_MESSAGE_REQUEST_IN', 'http request received'),
            'out' => env('REQUEST_LOGGING_MESSAGE_REQUEST_OUT', 'http request sent'),
        ]
    ],

    'responses' => [
        'enabled' => env('REQUEST_LOGGING_ENABLED_RESPONSES', true),
        'message' => [
            'in' => env('REQUEST_LOGGING_MESSAGE_RESPONSE_IN', 'http response received'),
            'out' => env('REQUEST_LOGGING_MESSAGE_RESPONSE_OUT', 'http response sent'),
        ]
    ]
];
