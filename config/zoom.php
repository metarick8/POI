<?php

return [
    'client_id' => env('ZOOM_CLIENT_ID'),
    'client_secret' => env('ZOOM_CLIENT_SECRET'),
    'account_id' => env('ZOOM_ACCOUNT_ID'),
    'server_to_server_oauth'=>true,
    'base_url' => 'https://api.zoom.us/v2/',
];
