<?php

return [
    'redaction' => [
        'keys' => [
            'authorization', 'cookie', 'set-cookie', 'password',
            'password_confirmation', 'token', 'secret', 'api_key',
            'apikey', 'access_token', 'refresh_token', 'php_auth_pw',
        ],
        'max_length' => 2000,
    ],
];
