<?php

return [
    'onedrive' => [
        'client_id' => env('ONEDRIVE_CLIENTID'),
        'sign_in_url' => 'https://login.live.com/oauth20_authorize.srf',
        'api_url' => 'https://graph.microsoft.com',
        'api_version' => '1.0'
    ]
];
