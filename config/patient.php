<?php

declare(strict_types=1);

return [

    /**
     * Here you can specify a specific cache store.
     * By default, the 'cache.default' store is used.
     * You can use the cache stores that are registered in the cache.php config file.
     */
    'cache_store' => env('PATIENT_CACHE_STORE'),

    /**
     * Here you can specify a number of code attempts
     * after that the code will be invalidated.
     */
    'invalidate_code_after_attempts' => env('PATIENT_INVALIDATE_CODE_AFTER_ATTEMPTS', 5),
];
