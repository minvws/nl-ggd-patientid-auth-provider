<?php

declare(strict_types=1);

return [
    'verify_service' => env('CMS_VERIFY_SERVICE', 'native'), // native or process_spawn
    'cert' => explode(",", env('CMS_X509_CERT', app_path('cms/cert.pem'))),
    'chain' => env('CMS_X509_CHAIN', app_path('cms/chain.pem')),
];
