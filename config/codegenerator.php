<?php

declare(strict_types=1);

return [
    'hmac_key' => env('CODEGEN_HMAC_KEY', ''),
    'expiry' => (int)env('CODEGEN_EXPIRY', 900),

    'throttle' => [
        /**
         * Here you can specify a specific cache driver.
         * By default, the 'cache.default' driver is used.
         * You can use cache options that are registered in the cache.php config file.
         */
        'cache_driver' => env('CODEGEN_THROTTLE_CACHE_DRIVER'),

        /**
         * Here you can specify a retry after seconds on a specific attempt count.
         */
        'attempt_retry_after' => explode(',', env('CODEGEN_THROTTLE_ATTEMPT_RETRY_AFTER', '5,5,5,15,30,60')),
    ]
];
