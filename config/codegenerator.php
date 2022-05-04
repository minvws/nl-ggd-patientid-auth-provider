<?php

declare(strict_types=1);

return [
    'hmac_key' => env('CODEGEN_HMAC_KEY', ''),
    'expiry' => (int)env('CODEGEN_EXPIRY', 900),
];
