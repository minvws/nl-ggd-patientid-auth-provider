<?php

declare(strict_types=1);

return [
    'cert' => env('CMS_X509_CERT', dirname(__FILE__) . '/../cms/cert.pem'),
    'chain' => env('CMS_X509_CHAIN', dirname(__FILE__) . '/../cms/chain.pem'),
];
