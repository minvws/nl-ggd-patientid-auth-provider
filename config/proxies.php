<?php

declare(strict_types=1);

return [
    'trusted' => env('TRUSTED_PROXIES', '') == '*'
        ? '*'
        : explode(",", env('TRUSTED_PROXIES', ''))
];
