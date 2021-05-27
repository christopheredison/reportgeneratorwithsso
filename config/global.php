<?php

if (env('APP_ENV') == 'local') {
	$baseUrl = 'http://localhost/koolreport/';
}
elseif (env('APP_ENV') == 'development') {
	$baseUrl = 'https://reportgenerator.panelo.co/';
}

return [
    'onedrive' => [
        'client_id' => '4ad396bc-e329-4272-88fd-2b6632c88267',
        'sign_in_url' => 'https://login.live.com/oauth20_authorize.srf',
        'redirect_uri' => $baseUrl . 'public/onedrive/redirect',
        'api_url' => 'https://graph.microsoft.com',
        'api_version' => '1.0'
    ]
];
