<?php

declare(strict_types=1);

return [
    'hmac_key' => env('CODEGEN_HMAC_KEY', ''),
    'expiry' => (int)env('CODEGEN_EXPIRY', 900),

    'throttle' => [
        /**
         * Here you can specify a specific cache store.
         * By default, the 'cache.default' store is used.
         * You can use the cache stores that are registered in the cache.php config file.
         */
        'cache_store' => env('CODEGEN_THROTTLE_CACHE_STORE'),

        /**
         * Here you can specify a retry after seconds on a specific attempt count.
         * For example:
         *      300,300,300,900,1800,3600
         * For the first 3 attempts the user needs to 300 seconds, after that he can request a new code.
         * After the fourth attempt the user needs to wait 900 seconds, and so on.
         */
        'attempt_retry_after' => explode(',', env('CODEGEN_THROTTLE_ATTEMPT_RETRY_AFTER', '300,300,300,900,1800,3600')),
    ]
];
