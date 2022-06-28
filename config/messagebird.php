<?php

declare(strict_types=1);

return [
    'api_key' => env('SMS_GATEWAY_MESSAGEBIRD_API_KEY', ''),
    'sender' => env('SMS_GATEWAY_SENDER', env('APP_NAME', '')),
    'ratelimit' => (int)env('SMS_GATEWAY_RATELIMIT', 5),
];
