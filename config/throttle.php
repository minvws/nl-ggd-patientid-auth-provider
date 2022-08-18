<?php

declare(strict_types=1);

return [
    'requests' => env('THROTTLE_NUM_REQUESTS', '3'),
    'period' => env('THROTTLE_PERIOD_MINUTES', '10'),

    'resend' => [
        /**
         * Here you can specify a specific cache driver.
         * By default, the 'cache.default' driver is used.
         * You can use cache options that are registered in the cache.php config file.
         */
        'cache_driver' => env('THROTTLE_RESEND_CACHE_DRIVER')
    ]
];
