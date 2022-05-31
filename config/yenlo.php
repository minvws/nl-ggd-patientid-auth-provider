<?php

declare(strict_types=1);

return [
    'client_id' => env('YENLO_CLIENT_ID', ''),
    'client_secret' => env('YENLO_CLIENT_SECRET', ''),
    'token_url' => env('YENLO_TOKEN_URL', ''),
    'userinfo_url' => env('YENLO_USERINFO_URL', ''),
];
