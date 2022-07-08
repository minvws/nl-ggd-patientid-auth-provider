<?php

declare(strict_types=1);

return [
    'private_key_path' => '/secrets/jwt.key',
    'certificate_path' => '/secrets/cert.pem',
    'iss' => env('CORONACHECK_JWT_ISS', ''),
    'aud' => env('CORONACHECK_JWT_AUD', ''),
    'exp' => env('CORONACHECK_JWT_EXP', 3600),
];
